<?php

namespace App\Services;

use App\Models\Alat;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Pemilihan alat secara massal lewat Excel: pengguna mengunduh daftar alat yang
 * sudah terisi, mengisi kolom Jumlah pada alat yang dibutuhkan, lalu
 * mengunggahnya kembali. Dipakai template peminjaman maupun laporan alat supaya
 * aturan pencocokannya sama.
 */
class DaftarAlatExcelService
{
    public function __construct(private AlatImportService $parser)
    {
    }

    /**
     * Daftar alat sebagai Excel dengan kolom Jumlah dikosongkan dan disorot.
     *
     * @param  Collection<int, Alat>  $alats
     */
    public function unduh(Collection $alats, string $namaBerkas): BinaryFileResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Alat');

        $headers = ['Kode', 'Nama Alat', 'Jenis Alat', 'Klasifikasi Alat', 'Total Aset', 'Jumlah'];
        foreach ($headers as $index => $header) {
            $sheet->setCellValue([$index + 1, 1], $header);
        }

        $baris = 2;
        foreach ($alats as $alat) {
            // Kode ditulis sebagai teks supaya tidak diubah Excel menjadi angka/tanggal.
            $sheet->setCellValueExplicit([1, $baris], (string) $alat->kode, DataType::TYPE_STRING);
            $sheet->setCellValue([2, $baris], $alat->nama);
            $sheet->setCellValue([3, $baris], $alat->jenis_alat);
            $sheet->setCellValue([4, $baris], $alat->klasifikasi_alat);
            $sheet->setCellValue([5, $baris], (int) $alat->total_aset);
            $baris++;
        }

        $barisTerakhir = max($baris - 1, 1);
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:E1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE2E8F0');
        // Kolom Jumlah disorot supaya jelas bagian mana yang perlu diisi pengguna.
        $sheet->getStyle('F1:F'.$barisTerakhir)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF3C7');
        foreach (['A' => 16, 'B' => 40, 'C' => 26, 'D' => 20, 'E' => 12, 'F' => 12] as $kolom => $lebar) {
            $sheet->getColumnDimension($kolom)->setWidth($lebar);
        }
        $sheet->freezePane('A2');
        $sheet->setAutoFilter('A1:F'.$barisTerakhir);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'daftar-alat-').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($temporaryPath);
        $spreadsheet->disconnectWorksheets();

        return response()->download($temporaryPath, $namaBerkas, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Cocokkan berkas yang diunggah dengan daftar alat yang boleh dipakai, tanpa
     * menyimpan apa pun. Hasilnya dipakai frontend untuk mencentang alat secara
     * massal, lalu pengguna tetap meninjau sebelum menyimpan.
     *
     * Kolom dibaca berdasarkan judul (bukan posisi) supaya berkas buatan sendiri
     * dengan urutan kolom berbeda tetap bisa dipakai. Pencocokan memakai kode bila
     * ada; bila tidak, memakai nama alat, dipersempit dengan jenis alat bila diisi.
     *
     * @param  Collection<int, Alat>  $alats
     * @param  string  $konteks  Disebut pada pesan kesalahan, misalnya "template" atau "laporan".
     * @return array{items: array<int, array<string, mixed>>, masalah: array<int, array{baris: int, pesan: string}>, ringkasan: array<string, int>}
     */
    public function cocokkan(UploadedFile $file, Collection $alats, string $konteks): array
    {
        $rows = $this->parser->parseImportFile($file);

        [$kolom, $indeksHeader] = $this->deteksiKolom($rows);
        if ($kolom === null) {
            throw ValidationException::withMessages([
                'file' => ['Baris judul tidak ditemukan. Berkas minimal harus punya kolom "Nama Alat" dan "Jumlah".'],
            ]);
        }

        $perKode = $alats
            ->filter(fn (Alat $alat) => trim((string) $alat->kode) !== '')
            ->keyBy(fn (Alat $alat) => $this->normalkan($alat->kode));
        $perNamaJenis = $alats->groupBy(fn (Alat $alat) => $this->normalkan($alat->nama).'|'.$this->normalkan($alat->jenis_alat));
        $perNama = $alats->groupBy(fn (Alat $alat) => $this->normalkan($alat->nama));

        $terpilih = [];
        $masalah = [];
        $barisDibaca = 0;

        foreach (array_slice($rows, $indeksHeader + 1) as $row) {
            $cells = $row['cells'];
            $nomorBaris = $row['row_number'];
            $jumlahMentah = trim((string) ($cells[$kolom['jumlah']] ?? ''));

            // Baris tanpa jumlah berarti alat itu memang tidak dipilih.
            if ($jumlahMentah === '') {
                continue;
            }

            $barisDibaca++;
            $nama = trim((string) ($cells[$kolom['nama']] ?? ''));
            $label = $nama !== '' ? $nama : "baris {$nomorBaris}";

            if (! preg_match('/^\d+$/', $jumlahMentah) || (int) $jumlahMentah < 1) {
                $masalah[] = ['baris' => $nomorBaris, 'pesan' => "{$label}: jumlah \"{$jumlahMentah}\" harus angka bulat minimal 1."];

                continue;
            }

            [$alat, $pesanGagal] = $this->cocokkanBaris($cells, $kolom, $nama, $perKode, $perNamaJenis, $perNama);
            if (! $alat) {
                $masalah[] = ['baris' => $nomorBaris, 'pesan' => $pesanGagal];

                continue;
            }

            $jumlah = (int) $jumlahMentah + (int) ($terpilih[$alat->id]['qty'] ?? 0);
            $totalAset = (int) $alat->total_aset;

            if ($totalAset < 1) {
                $masalah[] = ['baris' => $nomorBaris, 'pesan' => "{$alat->nama}: total aset 0, tidak bisa dimasukkan ke {$konteks}."];

                continue;
            }

            if ($jumlah > $totalAset) {
                $masalah[] = ['baris' => $nomorBaris, 'pesan' => "{$alat->nama}: jumlah {$jumlah} melebihi total aset, disesuaikan menjadi {$totalAset}."];
                $jumlah = $totalAset;
            }

            $terpilih[$alat->id] = [
                'id' => $alat->id,
                'kode' => $alat->kode,
                'nama' => $alat->nama,
                'jenis_alat' => $alat->jenis_alat,
                'qty' => $jumlah,
                'total_aset' => $totalAset,
            ];
        }

        return [
            'items' => array_values($terpilih),
            'masalah' => $masalah,
            'ringkasan' => [
                'baris_dibaca' => $barisDibaca,
                'alat_cocok' => count($terpilih),
                'baris_bermasalah' => count($masalah),
            ],
        ];
    }

    /**
     * @param  array<int, array{row_number: int, cells: array<int, string>}>  $rows
     * @return array{0: array{kode: ?int, nama: int, jenis: ?int, jumlah: int}|null, 1: int}
     */
    private function deteksiKolom(array $rows): array
    {
        $alias = [
            'kode' => ['kode', 'kodealat', 'id', 'idalat'],
            'nama' => ['nama', 'namaalat'],
            'jenis' => ['jenis', 'jenisalat'],
            'jumlah' => ['jumlah', 'qty', 'jumlahdipinjam', 'jumlahrusak', 'jumlahhilang', 'kuantitas'],
        ];

        // Judul biasanya di baris pertama, tetapi beberapa baris awal diperiksa
        // untuk berjaga-jaga bila pengguna menambahkan keterangan di atas tabel.
        foreach (array_slice($rows, 0, 10, true) as $indeks => $row) {
            $kolom = ['kode' => null, 'nama' => null, 'jenis' => null, 'jumlah' => null];

            foreach ($row['cells'] as $posisi => $nilai) {
                $judul = Str::of((string) $nilai)->lower()->ascii()->replaceMatches('/[^a-z]/', '')->value();
                foreach ($alias as $kunci => $daftar) {
                    if ($kolom[$kunci] === null && in_array($judul, $daftar, true)) {
                        $kolom[$kunci] = $posisi;
                    }
                }
            }

            if ($kolom['nama'] !== null && $kolom['jumlah'] !== null) {
                return [$kolom, $indeks];
            }
        }

        return [null, -1];
    }

    /**
     * @return array{0: ?Alat, 1: string}
     */
    private function cocokkanBaris(
        array $cells,
        array $kolom,
        string $nama,
        Collection $perKode,
        Collection $perNamaJenis,
        Collection $perNama
    ): array {
        $kode = $kolom['kode'] !== null ? trim((string) ($cells[$kolom['kode']] ?? '')) : '';
        if ($kode !== '' && $perKode->has($this->normalkan($kode))) {
            return [$perKode->get($this->normalkan($kode)), ''];
        }

        if ($nama === '') {
            return [null, $kode !== ''
                ? "Kode {$kode} tidak ditemukan dan nama alat kosong."
                : 'Nama alat kosong.'];
        }

        $jenis = $kolom['jenis'] !== null ? trim((string) ($cells[$kolom['jenis']] ?? '')) : '';
        $kandidat = $jenis !== ''
            ? $perNamaJenis->get($this->normalkan($nama).'|'.$this->normalkan($jenis), collect())
            : $perNama->get($this->normalkan($nama), collect());

        if ($kandidat->count() === 1) {
            return [$kandidat->first(), ''];
        }

        if ($kandidat->count() > 1) {
            return [null, "{$nama}: ada {$kandidat->count()} alat dengan nama ini, isi kolom Jenis Alat atau Kode agar tidak tertukar."];
        }

        return [null, $jenis !== ''
            ? "{$nama} ({$jenis}) tidak ditemukan di area ini."
            : "{$nama} tidak ditemukan di area ini."];
    }

    private function normalkan(?string $value): string
    {
        return Str::of((string) $value)->lower()->squish()->value();
    }
}
