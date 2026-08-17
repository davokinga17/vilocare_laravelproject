<?php

namespace App\Exports;

use App\Exports\Concerns\FormatsViLoCareWorkbook;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

class ReportSummaryExport implements FromArray, WithDrawings, WithEvents, WithTitle
{
    use FormatsViLoCareWorkbook;

    public function __construct(
        private readonly array $summary,
        private readonly array $analytics,
        private readonly array $report
    ) {
    }

    public function array(): array
    {
        $sex = implode(', ', array_slice($this->analytics['patientSexMix']['labels'] ?? [], 0, 4)) ?: 'No data';
        $indications = implode(', ', array_slice($this->analytics['testingIndications']['labels'] ?? [], 0, 4)) ?: 'No data';
        $facilities = implode(', ', array_slice($this->analytics['facilityCoverage']['labels'] ?? [], 0, 4)) ?: 'No data';
        $rate = ((float) rtrim($this->summary['suppressionRate'], '%')) / 100;

        return [
            [], [], [],
            ['ViLoCare Summary Report'],
            ['HIV Viral Load Monitoring Summary'],
            [],
            ['Report Reference', $this->report['reference'], '', '', 'Generated', $this->report['generated_at_human']],
            ['Scope', $this->scopeLabel(), '', '', 'Verification URL', $this->report['verification_url']],
            [],
            ['Total Patients', '', 'Total Viral Loads', '', 'Suppression Rate', '', 'Facilities Covered'],
            [$this->summary['totalPatientsRaw'], '', $this->summary['totalViralLoadsRaw'], '', $rate, '', $this->summary['coveredFacilitiesRaw']],
            [],
            ['Summary Metrics', '', '', '', '', 'Top Breakdown Snapshot'],
            ['Metric', '', '', 'Value', '', 'Category', '', 'Top Value'],
            ['Total Patients', '', '', $this->summary['totalPatientsRaw'], '', 'Patient Sex Mix', '', $sex],
            ['Total Viral Load Results', '', '', $this->summary['totalViralLoadsRaw'], '', 'Testing Indications', '', $indications],
            ['Suppressed Results', '', '', $this->summary['suppressed_raw'], '', 'Facility Coverage', '', $facilities],
            ['Unsuppressed Results', '', '', $this->summary['unsuppressed_raw']],
            ['Suppression Rate', '', '', $rate],
            ['Latest Result Date', '', '', $this->summary['latestResultDate']],
            ['Covered Counties', '', '', $this->summary['coveredCountiesRaw']],
            ['Covered Facilities', '', '', $this->summary['coveredFacilitiesRaw']],
            [],
            ['System Note', '', '', '', '', '', '', $this->report['footer_text']],
        ];
    }

    public function title(): string
    {
        return 'Summary Report';
    }

    public function drawings(): array
    {
        return $this->brandedDrawings('F1');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $teal = '087F72';
                $sheet->setShowGridlines(false);
                foreach (['A4:H4', 'A5:H5', 'B7:D7', 'F7:H7', 'B8:D8', 'F8:H8', 'A13:D13', 'F13:H13', 'A24:G24'] as $range) {
                    $sheet->mergeCells($range);
                }
                foreach (['A10:B10', 'C10:D10', 'E10:F10', 'G10:H10', 'A11:B11', 'C11:D11', 'E11:F11', 'G11:H11'] as $range) {
                    $sheet->mergeCells($range);
                }
                for ($row = 14; $row <= 22; $row++) {
                    $sheet->mergeCells("A{$row}:C{$row}");
                    $sheet->mergeCells("F{$row}:G{$row}");
                }
                $sheet->getStyle('A4:H4')->applyFromArray(['font' => ['bold' => true, 'size' => 18, 'color' => ['rgb' => $teal]], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
                $sheet->getStyle('A5:H5')->applyFromArray(['font' => ['size' => 10, 'color' => ['rgb' => '607481']], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]]);
                $sheet->getRowDimension(4)->setRowHeight(30);
                $sheet->getRowDimension(5)->setRowHeight(22);
                $sheet->getStyle('A7:H8')->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3FAF8']], 'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFDCD7']]], 'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]]);
                foreach (['A7', 'E7', 'A8', 'E8'] as $cell) {
                    $sheet->getStyle($cell)->getFont()->setBold(true)->getColor()->setRGB($teal);
                }
                foreach (['A10:B11', 'C10:D11', 'E10:F11', 'G10:H11'] as $range) {
                    $sheet->getStyle($range)->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F7FBFA']], 'borders' => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D3E1E2']]], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER]]);
                }
                $sheet->getStyle('A10:H10')->getFont()->setBold(true)->setSize(9)->getColor()->setRGB($teal);
                $sheet->getStyle('A11:H11')->getFont()->setBold(true)->setSize(16)->getColor()->setRGB('17313D');
                $sheet->getStyle('E11:F11')->getNumberFormat()->setFormatCode('0.0%');
                $sheet->getRowDimension(10)->setRowHeight(24);
                $sheet->getRowDimension(11)->setRowHeight(30);
                $sheet->getStyle('A13:D13')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB($teal);
                $sheet->getStyle('F13:H13')->getFont()->setBold(true)->setSize(11)->getColor()->setRGB($teal);
                $sheet->getStyle('A14:D14')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $teal]]]);
                $sheet->getStyle('F14:H14')->applyFromArray(['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $teal]]]);
                $sheet->getStyle('A15:D22')->applyFromArray(['borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'DCE6E8']]], 'alignment' => ['vertical' => Alignment::VERTICAL_CENTER]]);
                $sheet->getStyle('F15:H17')->applyFromArray(['borders' => ['bottom' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'DCE6E8']]], 'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true]]);
                $sheet->getStyle('D19')->getNumberFormat()->setFormatCode('0.0%');
                $sheet->getStyle('A24:H24')->applyFromArray(['fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3FAF8']], 'font' => ['color' => ['rgb' => '4F6875']], 'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_CENTER]]);
                $sheet->getStyle('A24')->getFont()->setBold(true)->getColor()->setRGB($teal);
                foreach (range('A', 'H') as $column) {
                    $sheet->getColumnDimension($column)->setWidth(in_array($column, ['B', 'D', 'F', 'H']) ? 16 : 19);
                }
                $sheet->getColumnDimension('H')->setWidth(28);
                $sheet->getRowDimension(24)->setRowHeight(36);
                $sheet->freezePane('A14');
                $sheet->getPageSetup()->setPaperSize(PageSetup::PAPERSIZE_A4)->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setFitToWidth(1)->setFitToHeight(1);
                $sheet->getPageMargins()->setTop(0.35)->setBottom(0.55)->setLeft(0.35)->setRight(0.35);
                $sheet->getHeaderFooter()->setOddFooter('&LConfidential - For Official Use Only&CGenerated by ViLoCare&RPage &P of &N');
                $sheet->getPageSetup()->setPrintArea('A1:H24');
                $sheet->getSheetView()->setZoomScale(90);
            },
        ];
    }
}
