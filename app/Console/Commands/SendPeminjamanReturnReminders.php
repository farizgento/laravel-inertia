<?php

namespace App\Console\Commands;

use App\Mail\PeminjamanReturnReminderMail;
use App\Models\Peminjaman;
use App\Services\PeminjamanNotifier;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendPeminjamanReturnReminders extends Command
{
    /**
     * Jarak hari antar pengingat setelah tanggal kembali terlewat.
     */
    private const INTERVAL_TERLAMBAT_HARI = 6;

    protected $signature = 'peminjaman:kirim-pengingat-pengembalian
        {--date= : Anggap hari ini adalah tanggal tersebut (format Y-m-d), berguna untuk pengujian}
        {--dry-run : Tampilkan peminjaman yang akan dikirimi email tanpa benar-benar mengirim}';

    protected $description = 'Kirim email pengingat pengembalian alat: H-1, hari tenggat, lalu tiap 6 hari setelah terlambat';

    public function handle(): int
    {
        $today = $this->resolveToday();
        if (! $today) {
            $this->error('Opsi --date tidak valid. Gunakan format Y-m-d, contoh: --date=2026-09-07');

            return self::FAILURE;
        }

        $isDryRun = (bool) $this->option('dry-run');

        // Hanya peminjaman yang memang bisa dikembalikan saat ini yang ditagih,
        // yaitu yang alatnya sudah diterima peminjam (termasuk yang baru kembali
        // sebagian). Status lain belum/tidak lagi memegang alat.
        $peminjamans = Peminjaman::query()
            ->with(['items.alat', 'user', 'area'])
            ->whereIn('status', Peminjaman::returnableStatuses())
            ->whereNotNull('tanggal_kembali')
            ->whereDate('tanggal_kembali', '<=', $today->addDay()->toDateString())
            ->orderBy('tanggal_kembali')
            ->get();

        $terkirim = 0;
        $dilewati = 0;

        foreach ($peminjamans as $peminjaman) {
            $tanggalKembali = CarbonImmutable::parse($peminjaman->tanggal_kembali)->startOfDay();
            $selisihHari = $tanggalKembali->diffInDays($today, false);

            $jenis = $this->resolveJenis($selisihHari);
            if (! $jenis) {
                continue;
            }

            // Pengaman agar perintah yang berjalan dua kali dalam sehari tidak
            // mengirim email ganda ke orang yang sama.
            $reminderTerakhir = $peminjaman->reminder_pengembalian_terakhir;
            if ($reminderTerakhir && CarbonImmutable::parse($reminderTerakhir)->isSameDay($today)) {
                $dilewati++;
                $this->line("  - #{$peminjaman->id} dilewati, pengingat hari ini sudah dikirim.");

                continue;
            }

            $hariTerlambat = max($selisihHari, 0);
            $label = $this->labelJenis($jenis, $hariTerlambat);

            if ($isDryRun) {
                $this->line("  - #{$peminjaman->id} ({$peminjaman->pekerjaan}) -> {$label}");
                $terkirim++;

                continue;
            }

            try {
                $jumlahPenerima = PeminjamanNotifier::notifyReturnReminder($peminjaman, $jenis, $hariTerlambat);

                // Tanggal tetap dicatat walau tidak ada penerima, supaya peminjaman
                // tanpa email tidak diproses ulang berkali-kali pada hari yang sama.
                $peminjaman->forceFill([
                    'reminder_pengembalian_terakhir' => $today->toDateString(),
                ])->save();

                $terkirim++;
                $this->line("  - #{$peminjaman->id} -> {$label} ({$jumlahPenerima} penerima)");
            } catch (Throwable $exception) {
                // Satu peminjaman bermasalah tidak boleh menghentikan sisanya.
                Log::error('Gagal memproses pengingat pengembalian peminjaman.', [
                    'peminjaman_id' => $peminjaman->id,
                    'error' => $exception->getMessage(),
                ]);
                $this->error("  - #{$peminjaman->id} gagal diproses: {$exception->getMessage()}");
            }
        }

        $prefix = $isDryRun ? '[dry-run] ' : '';
        $this->info("{$prefix}Pengingat pengembalian {$today->toDateString()}: {$terkirim} diproses, {$dilewati} dilewati.");

        return self::SUCCESS;
    }

    /**
     * Menentukan jenis pengingat dari selisih hari terhadap tanggal kembali.
     * Bernilai negatif berarti belum jatuh tempo, 0 tepat pada hari tenggat.
     */
    private function resolveJenis(int $selisihHari): ?string
    {
        if ($selisihHari === -1) {
            return PeminjamanReturnReminderMail::JENIS_SEBELUM_TENGGAT;
        }

        if ($selisihHari === 0) {
            return PeminjamanReturnReminderMail::JENIS_HARI_TENGGAT;
        }

        // Setelah tenggat: hanya pada kelipatan 6 hari (H+6, H+12, H+18, ...).
        if ($selisihHari > 0 && $selisihHari % self::INTERVAL_TERLAMBAT_HARI === 0) {
            return PeminjamanReturnReminderMail::JENIS_TERLAMBAT;
        }

        return null;
    }

    private function labelJenis(string $jenis, int $hariTerlambat): string
    {
        return match ($jenis) {
            PeminjamanReturnReminderMail::JENIS_SEBELUM_TENGGAT => 'H-1 sebelum tenggat',
            PeminjamanReturnReminderMail::JENIS_HARI_TENGGAT => 'hari tenggat',
            default => "terlambat {$hariTerlambat} hari",
        };
    }

    private function resolveToday(): ?CarbonImmutable
    {
        $date = $this->option('date');

        if (! $date) {
            return CarbonImmutable::today();
        }

        try {
            return CarbonImmutable::createFromFormat('Y-m-d', $date)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }
}
