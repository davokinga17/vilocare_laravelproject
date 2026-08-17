<?php

namespace App\Exports;

use App\Exports\Concerns\FormatsViLoCareWorkbook;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class ViralLoadExport implements FromArray, WithDrawings, WithEvents, WithTitle
{
    use FormatsViLoCareWorkbook;

    public function __construct(
        private readonly Collection $results,
        private readonly array $report
    ) {
    }

    public function array(): array
    {
        $rows = [
            [], [], [],
            ['ViLoCare Viral Load Report'],
            ['Clinical virologic results, suppression status and facility coverage'],
            [],
            ['Report Reference', $this->report['reference'], 'Generated', $this->report['generated_at_human'], 'Records', $this->report['record_count'], 'Verification URL', $this->report['verification_url']],
            ['Scope', $this->scopeLabel()],
            [],
            ['ART Number', 'Patient Name', 'VL Status', 'Result (cp/mL)', 'Result (log)', 'Sample Date', 'Result Date', 'Indication', 'State', 'County', 'Facility'],
        ];

        foreach ($this->results as $result) {
            $rows[] = [
                $result->art_number,
                $result->full_name,
                $result->viral_load_status,
                is_numeric($result->result_cpml) ? (float) $result->result_cpml : $result->result_cpml,
                is_numeric($result->result_log) ? (float) $result->result_log : $result->result_log,
                $result->sample_date,
                $result->result_date,
                $result->vl_testing_indication ?: 'N/A',
                $result->state_name ?? 'Unassigned',
                $result->county_name ?? 'Unassigned',
                $result->facility_name ?? 'Unassigned',
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Viral Load Report';
    }

    public function drawings(): array
    {
        return $this->brandedDrawings('H1');
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastRow = max(10, 10 + $this->results->count());
                $this->styleDataSheet($sheet, 'K', 10, $lastRow, [
                    'A' => 14, 'B' => 23, 'C' => 15, 'D' => 15, 'E' => 12,
                    'F' => 15, 'G' => 15, 'H' => 24, 'I' => 15, 'J' => 16, 'K' => 25,
                ], 'C');
                if ($this->results->isNotEmpty()) {
                    $sheet->getStyle("D11:D{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
                    $sheet->getStyle("E11:E{$lastRow}")->getNumberFormat()->setFormatCode('0.00');
                    $sheet->getStyle("F11:G{$lastRow}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                }
            },
        ];
    }
}
