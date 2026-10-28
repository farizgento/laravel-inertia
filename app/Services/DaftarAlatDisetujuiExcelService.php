<?php

namespace App\Services;

use App\Models\Peminjaman;
use App\Models\PeminjamanItem;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Daftar alat yang disetujui pada sebuah peminjaman, sebagai Excel siap cetak.
 *
 * Bentuknya meniru surat jalan (kepala dokumen, blok keterangan, lalu tabel
 * berbingkai), tetapi sengaja hanya memuat daftar alat. Dipakai petugas untuk
 * menyiapkan alat sebelum pengiriman, jadi tidak perlu kolom tanda tangan
 * maupun lampiran foto seperti surat jalan sungguhan.
 */
class DaftarAlatDisetujuiExcelService
{
    private const KOLOM_TERAKHIR = 'G';

    private const LEBAR_KOLOM = [
        'A' => 6,
        'B' => 18,
        'C' => 42,
        'D' => 24,
        'E' => 10,
        'F' => 10,
        'G' => 26,
    ];

    private const JUDUL_TABEL = ['No', 'Kode', 'Nama Alat', 'Jenis Alat', 'Jumlah', 'Satuan', 'Keterangan'];

    public function unduh(Peminjaman $peminjaman): BinaryFileResponse
    {
        $peminjaman->loadMissing(['user', 'area', 'requesterArea', 'bidangPekerjaan', 'items.alat']);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar Alat');

        $baris = $this->tulisKepala($sheet);
        $baris = $this->tulisKeterangan($sheet, $peminjaman, $baris);
        $this->tulisTabel($sheet, $peminjaman, $baris);
        $this->aturHalaman($sheet);

        return $this->kirim($spreadsheet, $this->namaBerkas($peminjaman));
    }

    private function tulisKepala(Worksheet $sheet): int
    {
        $akhir = self::KOLOM_TERAKHIR;

        $sheet->mergeCells("A1:{$akhir}1");
        $sheet->setCellValue('A1', 'DAFTAR ALAT DISETUJUI');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells("A2:{$akhir}2");
        $sheet->setCellValue('A2', 'Peminjaman Alat');
        $sheet->getStyle('A2')->getFont()->setSize(10)->getColor()->setARGB('FF64748B');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Garis tipis sebagai pemisah kepala dokumen, seperti kop pada surat jalan.
        $sheet->getStyle("A3:{$akhir}3")
            ->getBorders()
            ->getBottom()
            ->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getRowDimension(3)->setRowHeight(6);

        return 5;
    }

    private function tulisKeterangan(Worksheet $sheet, Peminjaman $peminjaman, int $baris): int
    {
        $akhir = self::KOLOM_TERAKHIR;

        foreach ($this->keterangan($peminjaman) as $label => $nilai) {
            $sheet->setCellValue('A'.$baris, $label);
            $sheet->setCellValue('C'.$baris, ': '.$nilai);

            $sheet->mergeCells("A{$baris}:B{$baris}");
            $sheet->mergeCells("C{$baris}:{$akhir}{$baris}");
            $sheet->getStyle('A'.$baris)->getFont()->setBold(true);
            $sheet->getStyle("A{$baris}:{$akhir}{$baris}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER);

            $baris++;
        }

        return $baris + 1;
    }

    /**
     * @return array<string, string>
     */
    private function keterangan(Peminjaman $peminjaman): array
    {
        $area = $peminjaman->area?->name ?? '-';
        if ($peminjaman->requester_area_id && $peminjaman->requesterArea) {
            $area = $peminjaman->requesterArea->name.' (meminjam dari '.$area.')';
        }

        return [
            'No. Peminjaman' => '#'.$peminjaman->id,
            'Peminjam' => $peminjaman->user?->name ?? '-',
            'Area' => $area,
            'Kategori' => $peminjaman->kategori ?? '-',
            'Pekerjaan' => $peminjaman->pekerjaan ?? '-',
            'Bidang Pekerjaan' => $peminjaman->bidangPekerjaan?->nama ?? '-',
            'Periode Pinjam' => $this->periode($peminjaman),
            'Status' => $peminjaman->status ?? '-',
            'Dicetak' => now()->format('d M Y H:i'),
        ];
    }

    private function periode(Peminjaman $peminjaman): string
    {
        $mulai = $peminjaman->tanggal_pinjam?->format('d M Y');
        $selesai = $peminjaman->tanggal_kembali?->format('d M Y');

        if ($mulai && $selesai) {
            return $mulai.' s/d '.$selesai;
        }

        return $mulai ?? $selesai ?? '-';
    }

    private function tulisTabel(Worksheet $sheet, Peminjaman $peminjaman, int $baris): void
    {
        $akhir = self::KOLOM_TERAKHIR;
        $barisJudul = $baris;

        foreach (self::JUDUL_TABEL as $index => $judul) {
            $sheet->setCellValue([$index + 1, $barisJudul], $judul);
        }

        $sheet->getStyle("A{$barisJudul}:{$akhir}{$barisJudul}")->getFont()->setBold(true);
        $sheet->getStyle("A{$barisJudul}:{$akhir}{$barisJudul}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFE2E8F0');
        $sheet->getStyle("A{$barisJudul}:{$akhir}{$barisJudul}")
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($barisJudul)->setRowHeight(22);

        $items = $this->itemDisetujui($peminjaman);
        $baris = $barisJudul + 1;
        $total = 0;
        $nomor = 1;

        foreach ($items as $item) {
            $qty = (int) $item->approved_qty;
            $total += $qty;
            $nama = trim((string) ($item->alat?->nama ?? '-'));

            $sheet->setCellValue('A'.$baris, $nomor);
            // Kode ditulis sebagai teks supaya Excel tidak mengubahnya jadi angka.
            $sheet->setCellValueExplicit('B'.$baris, (string) ($item->alat?->kode ?? '-'), DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('C'.$baris, $nama !== '' ? $nama : '-', DataType::TYPE_STRING);
            $sheet->setCellValue('D'.$baris, $item->alat?->jenis_alat ?? '-');
            $sheet->setCellValue('E'.$baris, $qty);
            $sheet->setCellValue('F'.$baris, 'Unit');
            // Kolom keterangan sengaja kosong, untuk catatan tangan saat penyiapan alat.
            $sheet->setCellValue('G'.$baris, '');

            $sheet->getStyle('C'.$baris)->getAlignment()->setWrapText(true);
            $sheet->getStyle('G'.$baris)->getAlignment()->setWrapText(true);
            $sheet->getRowDimension($baris)->setRowHeight(
                18 + ((max(1, (int) ceil(mb_strlen($nama) / 40)) - 1) * 14)
            );

            $baris++;
            $nomor++;
        }

        if ($items->isEmpty()) {
            $sheet->mergeCells("A{$baris}:{$akhir}{$baris}");
            $sheet->setCellValue('A'.$baris, 'Tidak ada alat yang disetujui pada peminjaman ini.');
            $sheet->getStyle('A'.$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $baris++;
        }

        $barisTerakhirIsi = $baris - 1;

        // Baris total
        $sheet->mergeCells("A{$baris}:D{$baris}");
        $sheet->setCellValue('A'.$baris, 'Total');
        $sheet->setCellValue('E'.$baris, $total);
        $sheet->setCellValue('F'.$baris, 'Unit');
        $sheet->getStyle("A{$baris}:{$akhir}{$baris}")->getFont()->setBold(true);
        $sheet->getStyle("A{$baris}:{$akhir}{$baris}")
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setARGB('FFF1F5F9');
        $sheet->getStyle('A'.$baris)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        $this->rapikanTabel($sheet, $barisJudul, $barisTerakhirIsi, $baris);
    }

    /**
     * Hanya alat dengan jumlah disetujui lebih dari nol yang masuk daftar, sebab
     * alat yang ditolak reviewer tidak perlu disiapkan.
     *
     * @return \Illuminate\Support\Collection<int, PeminjamanItem>
     */
    private function itemDisetujui(Peminjaman $peminjaman)
    {
        return $peminjaman->items
            ->filter(fn (PeminjamanItem $item) => (int) $item->approved_qty > 0)
            ->sortBy(fn (PeminjamanItem $item) => mb_strtolower((string) ($item->alat?->nama ?? '')))
            ->values();
    }

    private function rapikanTabel(Worksheet $sheet, int $barisJudul, int $barisTerakhirIsi, int $barisTotal): void
    {
        $akhir = self::KOLOM_TERAKHIR;

        $sheet->getStyle("A{$barisJudul}:{$akhir}{$barisTotal}")
            ->getBorders()
            ->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN);

        if ($barisTerakhirIsi >= $barisJudul + 1) {
            foreach (['A', 'E', 'F'] as $kolom) {
                $sheet->getStyle("{$kolom}".($barisJudul + 1).":{$kolom}{$barisTerakhirIsi}")
                    ->getAlignment()
                    ->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }
            $sheet->getStyle('A'.($barisJudul + 1).":{$akhir}{$barisTerakhirIsi}")
                ->getAlignment()
                ->setVertical(Alignment::VERTICAL_CENTER);
        }

        foreach (self::LEBAR_KOLOM as $kolom => $lebar) {
            $sheet->getColumnDimension($kolom)->setWidth($lebar);
        }

        // Judul tabel ikut tercetak di tiap halaman bila daftarnya panjang.
        $sheet->getPageSetup()->setRowsToRepeatAtTopByStartAndEnd($barisJudul, $barisJudul);
    }

    private function aturHalaman(Worksheet $sheet): void
    {
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.5)->setBottom(0.5)->setLeft(0.4)->setRight(0.4);
    }

    private function namaBerkas(Peminjaman $peminjaman): string
    {
        return 'DAFTAR-ALAT-DISETUJUI-'.$peminjaman->id.'.xlsx';
    }

    private function kirim(Spreadsheet $spreadsheet, string $namaBerkas): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'daftar-alat-disetujui-').'.xlsx';
        IOFactory::createWriter($spreadsheet, 'Xlsx')->save($path);
        $spreadsheet->disconnectWorksheets();

        return response()->download($path, $namaBerkas, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
