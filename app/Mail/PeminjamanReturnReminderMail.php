<?php

namespace App\Mail;

use App\Models\Peminjaman;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class PeminjamanReturnReminderMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Pengingat H-1 sebelum tanggal kembali.
     */
    public const JENIS_SEBELUM_TENGGAT = 'sebelum_tenggat';

    /**
     * Pengingat tepat pada tanggal kembali.
     */
    public const JENIS_HARI_TENGGAT = 'hari_tenggat';

    /**
     * Pengingat berkala setelah tanggal kembali terlewat.
     */
    public const JENIS_TERLAMBAT = 'terlambat';

    /**
     * Number of times the queued job may be attempted before it is
     * moved to the failed_jobs table.
     */
    public int $tries = 3;

    /**
     * Wait this many seconds between retry attempts (SMTP hiccups,
     * transient DNS issues on the internal relay, etc.).
     */
    public int $backoff = 30;

    public Peminjaman $peminjaman;

    public string $jenis;

    /**
     * Selisih hari terhadap tanggal kembali. Bernilai 0 pada hari tenggat dan
     * positif ketika sudah terlambat.
     */
    public int $hariTerlambat;

    /**
     * Alat yang belum kembali beserta sisa jumlahnya.
     *
     * @var array<int, array{name: string, code: string, qty: int}>
     */
    public array $items;

    public string $returnUrl;

    public function __construct(Peminjaman $peminjaman, string $jenis, int $hariTerlambat = 0)
    {
        $this->peminjaman = $peminjaman;
        $this->jenis = $jenis;
        $this->hariTerlambat = max($hariTerlambat, 0);

        // Hanya alat yang benar-benar masih di tangan peminjam yang ditampilkan,
        // supaya pengembalian sebagian tidak ikut ditagih ulang.
        $this->items = $peminjaman->items
            ->map(fn ($item) => [
                'name' => $item->alat?->nama ?? '-',
                'code' => $item->alat?->kode ?? '-',
                'qty' => max((int) $item->approved_qty - (int) $item->returned_qty, 0),
            ])
            ->filter(fn (array $item) => $item['qty'] > 0)
            ->values()
            ->all();

        $route = $peminjaman->is_inter_area ? 'pengiriman-antar-area' : 'mutasi-alat';
        $this->returnUrl = rtrim((string) config('app.url'), '/') . '/' . $route;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine(),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.peminjaman.return-reminder',
            with: [
                'peminjaman' => $this->peminjaman,
                'jenis' => $this->jenis,
                'hariTerlambat' => $this->hariTerlambat,
                'items' => $this->items,
                'returnUrl' => $this->returnUrl,
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    private function subjectLine(): string
    {
        $id = $this->peminjaman->id;

        return match ($this->jenis) {
            self::JENIS_SEBELUM_TENGGAT => "Pengingat: Peminjaman Alat #{$id} Jatuh Tempo Besok",
            self::JENIS_HARI_TENGGAT => "Pengingat: Peminjaman Alat #{$id} Jatuh Tempo Hari Ini",
            default => "Terlambat {$this->hariTerlambat} Hari: Peminjaman Alat #{$id} Belum Dikembalikan",
        };
    }

    /**
     * Called by the queue worker when the job has exhausted all of its
     * retry attempts. Logged here so a persistently-down SMTP server
     * doesn't fail silently into the failed_jobs table.
     */
    public function failed(Throwable $exception): void
    {
        Log::error('Gagal mengirim email pengingat pengembalian peminjaman setelah beberapa percobaan.', [
            'peminjaman_id' => $this->peminjaman->id,
            'jenis' => $this->jenis,
            'error' => $exception->getMessage(),
        ]);
    }
}
