<?php

namespace App\Services;

use App\Models\LaporanAlat;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Drawing as DrawingUnits;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LaporanAlatExcelService
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    private const PHOTO_WIDTH = 184;

    private const PHOTO_HEIGHT = 138;

    private const PHOTO_GAP = 16;

    private const PHOTO_ROW_HEIGHT = 126;

    /** @param Collection<int, LaporanAlat> $reports */
    public function download(Collection $reports, string $category, array $options = [], bool $template = false): BinaryFileResponse
    {
        $workbook = IOFactory::load(storage_path('templates/Format-Laporan-Alat.xlsx'));
        $sheet = $workbook->getActiveSheet();
        $font = $workbook->getDefaultStyle()->getFont();
        $photoColumnWidths = [
            'D' => DrawingUnits::cellDimensionToPixels($sheet->getColumnDimension('D')->getWidth(), $font),
            'E' => DrawingUnits::cellDimensionToPixels($sheet->getColumnDimension('E')->getWidth(), $font),
        ];
        $photoCache = [];
        $output = tempnam(sys_get_temp_dir(), 'laporan-xlsx-');

        try {
            $rows = [];
            foreach ($reports as $report) {
                $paths = $report->photos->isNotEmpty()
                    ? $report->photos->pluck('path')->all()
                    : ($report->path ? [$report->path] : []);
                $description = (string) ($report->deskripsi ?? '');
                // Dua foto per baris fisik. Teks panjang mendapat ruang tambahan.
                $height = max($this->textHeight($description, 65), $this->textHeight($this->toolName($report), 27));
                $rows[] = [
                    'report' => $report,
                    'paths' => $paths,
                    'description' => $description,
                    'count' => max(1, (int) ceil(count($paths) / 2), (int) ceil($height / self::PHOTO_ROW_HEIGHT)),
                ];
            }

            $rowCount = $template ? 12 : max(1, array_sum(array_column($rows, 'count')));
            for ($row = 10; $row <= 21; $row++) {
                $sheet->unmergeCells("D{$row}:E{$row}");
            }
            if ($rowCount > 12) {
                $sheet->insertNewRowBefore(22, $rowCount - 12);
            } elseif ($rowCount < 12) {
                $sheet->removeRow(10 + $rowCount, 12 - $rowCount);
            }

            $lastDataRow = 9 + $rowCount;
            for ($row = 10; $row <= $lastDataRow; $row++) {
                $sheet->duplicateStyle($sheet->getStyle('A10:E10'), "A{$row}:E{$row}");
                $sheet->mergeCells("D{$row}:E{$row}");
                $sheet->getRowDimension($row)->setRowHeight($template ? 48 : self::PHOTO_ROW_HEIGHT);
            }
            $sheet->getStyle("A10:E{$lastDataRow}")->getAlignment()
                ->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
            $sheet->getStyle("A10:E{$lastDataRow}")->getFont()->setSize(11);
            $sheet->getStyle("A10:A{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("B10:E{$lastDataRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $row = 10;
            foreach ($rows as $index => $item) {
                $end = $row + $item['count'] - 1;
                foreach (['A', 'B', 'C'] as $column) {
                    if ($end > $row) {
                        $sheet->mergeCells("{$column}{$row}:{$column}{$end}");
                    }
                }
                $sheet->setCellValue("A{$row}", $index + 1);
                $this->text($sheet, "B{$row}", $this->toolName($item['report']));
                $this->text($sheet, "C{$row}", $item['description']);

                if (! $item['paths']) {
                    $this->text($sheet, "D{$row}", 'Tidak ada foto lampiran.');
                }
                $missing = [];
                foreach ($item['paths'] as $photoIndex => $path) {
                    $photoRow = $row + intdiv($photoIndex, 2);
                    if (! array_key_exists($path, $photoCache)) {
                        $photoCache[$path] = $this->preparePhoto($path);
                    }
                    if (! $photoCache[$path]) {
                        $missing[$photoRow][] = 'Foto '.($photoIndex + 1).' tidak tersedia';

                        continue;
                    }
                    $drawing = new Drawing;
                    $drawing->setName('Laporan '.$item['report']->id.' - Foto '.($photoIndex + 1));
                    $drawing->setDescription($item['report']->alat?->nama.' - lampiran '.($photoIndex + 1));
                    $drawing->setPath($photoCache[$path], true, null, false);
                    $photosInRow = min(2, count($item['paths']) - intdiv($photoIndex, 2) * 2);
                    $gridWidth = $photosInRow * self::PHOTO_WIDTH + ($photosInRow - 1) * self::PHOTO_GAP;
                    $offsetX = (int) floor((array_sum($photoColumnWidths) - $gridWidth) / 2)
                        + ($photoIndex % 2) * (self::PHOTO_WIDTH + self::PHOTO_GAP);
                    // Offset harus berada di kolom jangkar sebenarnya, termasuk
                    // foto kanan yang berada di E walaupun D:E digabung.
                    $column = $offsetX < $photoColumnWidths['D'] ? 'D' : 'E';
                    if ($column === 'E') {
                        $offsetX -= $photoColumnWidths['D'];
                    }
                    $drawing->setCoordinates($column.$photoRow);
                    $drawing->setWidthAndHeight(self::PHOTO_WIDTH, self::PHOTO_HEIGHT);
                    $drawing->setOffsetX($offsetX);
                    $drawing->setOffsetY(8);
                    // One-cell anchor: mengikuti posisi sel, ukuran foto tetap
                    // ketika tinggi baris/lebar kolom diubah di Excel.
                    $drawing->setEditAs(Drawing::EDIT_AS_ONECELL);
                    $drawing->setWorksheet($sheet);
                }
                foreach ($missing as $missingRow => $messages) {
                    $this->text($sheet, "D{$missingRow}", implode('; ', $messages));
                    $sheet->getStyle("D{$missingRow}")->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
                    $sheet->getStyle("D{$missingRow}")->getFont()->setSize(9);
                }
                $sheet->getStyle("A{$end}:E{$end}")->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);
                $row = $end + 1;
            }
            if ($reports->isEmpty() && ! $template) {
                $this->text($sheet, 'C10', 'Tidak ada laporan sesuai filter yang dipilih.');
                $sheet->getRowDimension(10)->setRowHeight(48);
            }

            $label = strtoupper($category);
            $this->text($sheet, 'C2', "FORM LAPORAN {$label} TOOLS");
            if ($category === LaporanAlat::CATEGORY_KEHILANGAN) {
                $this->text($sheet, 'C3', "PELAPORAN KEHILANGAN TOOLS (GENERAL TOOLS,\nLIFTING TOOLS, DAN MEASUREMENT TOOLS)");
            }
            $this->text($sheet, 'E4', 'Lihat footer cetak');
            $this->text($sheet, 'A6', 'No : '.($options['nomor_laporan'] ?? '................................'));
            $sheet->mergeCells('A7:E7');
            $this->text($sheet, 'A7', $options['context'] ?? '');
            $sheet->getStyle('A7')->getAlignment()->setWrapText(true)->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getRowDimension(7)->setRowHeight(max(24, $this->textHeight($options['context'] ?? '', 150)));
            $this->text($sheet, 'A8', "A. DATA {$label} PERALATAN");

            $offset = $rowCount - 12;
            $this->text($sheet, 'A'.(22 + $offset), $template
                ? 'Baris dapat ditambah sesuai jumlah alat yang dilaporkan.'
                : $reports->count().' laporan; jumlah alat: '.$reports->sum('jumlah').'. Foto ditampilkan sesuai laporan masing-masing.');
            foreach (['hasil_pemeriksaan' => 25, 'usulan' => 29] as $key => $baseRow) {
                $target = $baseRow + $offset;
                $value = $options[$key] ?? '';
                $height = max(56, $this->textHeight($value, 155));
                $blockRows = max(2, (int) ceil($height / 126));
                if ($blockRows > 2) {
                    $sheet->unmergeCells('A'.$target.':E'.($target + 1));
                    $sheet->insertNewRowBefore($target + 2, $blockRows - 2);
                    $sheet->duplicateStyle($sheet->getStyle('A'.$target.':E'.$target), 'A'.($target + 1).':E'.($target + $blockRows - 1));
                    $sheet->mergeCells('A'.$target.':E'.($target + $blockRows - 1));
                    $offset += $blockRows - 2;
                }
                $this->text($sheet, 'A'.$target, $value);
                $sheet->getStyle('A'.$target)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
                for ($line = $target; $line < $target + $blockRows; $line++) {
                    $sheet->getRowDimension($line)->setRowHeight($height / $blockRows);
                }
            }
            $date = CarbonImmutable::parse($options['tanggal_cetak'] ?? now()->toDateString())->locale('id')->translatedFormat('d F Y');
            $place = trim($options['tempat'] ?? '');
            $this->text($sheet, 'A'.(32 + $offset), ($place !== '' ? $place.', ' : '').$date."\nDisetujui,");
            $this->text($sheet, 'A'.(36 + $offset), $options['penandatangan'] ?? '................................');
            $this->text($sheet, 'A'.(37 + $offset), $options['jabatan'] ?? '................................');
            $sheet->getStyle('A'.(36 + $offset).':E'.(38 + $offset))->getAlignment()->setWrapText(true);
            $sheet->getRowDimension(36 + $offset)->setRowHeight(max(22, $this->textHeight($options['penandatangan'] ?? '', 150)));
            $sheet->setShowGridlines(false);
            $sheet->getPageSetup()->setPrintArea('A1:E'.(38 + $offset))
                ->setRowsToRepeatAtTopByStartAndEnd(1, 9)->setFitToWidth(1)->setFitToHeight(0);
            $sheet->getHeaderFooter()->setOddFooter('&CHalaman &P dari &N')->setEvenFooter('&CHalaman &P dari &N');
            IOFactory::createWriter($workbook, 'Xlsx')->save($output);
        } catch (\Throwable $exception) {
            if ($output && is_file($output)) {
                unlink($output);
            }
            throw $exception;
        } finally {
            $workbook->disconnectWorksheets();
            foreach (array_filter($photoCache) as $temporaryPhoto) {
                if (is_file($temporaryPhoto)) {
                    unlink($temporaryPhoto);
                }
            }
        }

        $name = ($template ? 'template' : 'laporan').'-'.$category.'-'.now()->format('Ymd-His').'.xlsx';

        return response()->download($output, $name, ['Content-Type' => self::MIME])->deleteFileAfterSend(true);
    }

    private function text(Worksheet $sheet, string $cell, string $value): void
    {
        // Kode/nama/uraian pengguna tidak pernah dieksekusi sebagai formula Excel.
        $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
    }

    private function toolName(LaporanAlat $report): string
    {
        return ($report->alat?->nama ?? '-')."\nKode: ".($report->alat?->kode ?? '-');
    }

    private function textHeight(string $text, int $characters): int
    {
        $lines = array_sum(array_map(fn ($line) => max(1, (int) ceil(mb_strlen($line) / $characters)), explode("\n", $text)));

        return $lines * 15 + 12;
    }

    private function preparePhoto(string $path): ?string
    {
        $disk = Storage::disk('public');
        $root = realpath($disk->path(''));
        $file = realpath($disk->path($path));
        if (! $root || ! $file || ! str_starts_with($file, $root.DIRECTORY_SEPARATOR) || ! is_file($file)) {
            return null;
        }

        // PNG/JPEG/WebP dari unggahan dinormalisasi supaya dapat dibuka di Excel.
        $temporary = null;
        try {
            // Kanvas 4:3 seragam dengan foto utuh di tengah (tanpa dipotong
            // atau diregangkan). Resolusi 4x menjaga detail pada hasil cetak.
            $image = (new ImageManager(new Driver))->read($file)
                ->contain(self::PHOTO_WIDTH * 4, self::PHOTO_HEIGHT * 4, 'f8fafc')
                ->toPng();
            $temporary = tempnam(sys_get_temp_dir(), 'laporan-photo-');
            $image->save($temporary);

            return $temporary;
        } catch (\Exception $exception) {
            if ($temporary && is_file($temporary)) {
                unlink($temporary);
            }
            report($exception);

            return null;
        }
    }
}
