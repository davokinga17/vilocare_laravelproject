<?php

namespace App\Exports\Concerns;

use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait FormatsViLoCareWorkbook
{
    protected function brandedDrawings(string $vilocareCoordinate): array
    {
        $drawings = [];

        if (is_file($this->report['ministry_logo_path'] ?? '')) {
            $ministry = new Drawing();
            $ministry->setName('Ministry of Health Logo');
            $ministry->setDescription('Ministry of Health Logo');
            $ministry->setPath($this->report['ministry_logo_path']);
            $ministry->setHeight(68);
            $ministry->setCoordinates('A1');
            $ministry->setOffsetX(8);
            $drawings[] = $ministry;
        }

        if (is_file($this->report['logo_path'] ?? '')) {
            $vilocare = new Drawing();
            $vilocare->setName('ViLoCare Logo');
            $vilocare->setDescription('ViLoCare Logo');
            $vilocare->setPath($this->report['logo_path']);
            $vilocare->setHeight(42);
            $vilocare->setCoordinates($vilocareCoordinate);
            $vilocare->setOffsetX(8);
            $vilocare->setOffsetY(8);
            $drawings[] = $vilocare;
        }

        return $drawings;
    }

    protected function scopeLabel(): string
    {
        return collect($this->report['filters'] ?? [])
            ->map(fn ($value, $label) => $label . ': ' . $value)
            ->implode(' | ') ?: 'All time';
    }

    protected function styleDataSheet(
        Worksheet $sheet,
        string $lastColumn,
        int $headerRow,
        int $lastRow,
        array $columnWidths,
        ?string $statusColumn = null
    ): void {
        $teal = '087F72';
        $sheet->setShowGridlines(false);
        $sheet->mergeCells("A4:{$lastColumn}4");
        $sheet->mergeCells("A5:{$lastColumn}5");
        $sheet->mergeCells("B8:{$lastColumn}8");
        $sheet->getRowDimension(1)->setRowHeight(24);
        $sheet->getRowDimension(2)->setRowHeight(24);
        $sheet->getRowDimension(3)->setRowHeight(24);
        $sheet->getRowDimension(4)->setRowHeight(30);
        $sheet->getRowDimension(5)->setRowHeight(22);
        $sheet->getStyle("A4:{$lastColumn}4")->applyFromArray([
            'font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => $teal]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A5:{$lastColumn}5")->applyFromArray([
            'font' => ['size' => 10, 'color' => ['rgb' => '607481']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getStyle("A7:{$lastColumn}8")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3FAF8']],
            'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFDCD7']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        foreach (['A7', 'C7', 'E7', 'G7', 'A8'] as $cell) {
            if ($sheet->getCell($cell)->getValue() !== null) {
                $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB($teal);
            }
        }
        $sheet->getRowDimension(7)->setRowHeight(32);
        $sheet->getRowDimension(8)->setRowHeight(28);
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 9],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $teal]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(29);

        if ($lastRow > $headerRow) {
            $sheet->getStyle("A" . ($headerRow + 1) . ":{$lastColumn}{$lastRow}")->applyFromArray([
                'font' => ['size' => 8, 'color' => ['rgb' => '29434F']],
                'borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'DCE6E8']]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
                $sheet->getRowDimension($row)->setRowHeight(24);
                if (($row - $headerRow) % 2 === 0) {
                    $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F7FBFA');
                }
                if ($statusColumn) {
                    $status = (string) $sheet->getCell("{$statusColumn}{$row}")->getValue();
                    $color = $status === 'Suppressed' ? '087F72' : ($status === 'Unsuppressed' ? 'D33F38' : '5F7380');
                    $sheet->getStyle("{$statusColumn}{$row}")->getFont()->setBold(true)->getColor()->setRGB($color);
                }
            }
            $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$lastRow}");
        }

        foreach ($columnWidths as $column => $width) {
            $sheet->getColumnDimension($column)->setWidth($width);
        }
        $sheet->freezePane('A' . ($headerRow + 1));
        $sheet->getPageSetup()
            ->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.35)->setBottom(0.55)->setLeft(0.3)->setRight(0.3);
        $sheet->getHeaderFooter()->setOddFooter('&LConfidential - For Official Use Only&CGenerated by ViLoCare&RPage &P of &N');
        $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}{$lastRow}");
        $sheet->getSheetView()->setZoomScale(85);
    }
}
