<?php

namespace App\Exports;

use App\Exports\Concerns\FormatsViLoCareWorkbook;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class PatientsExport implements FromArray, WithDrawings, WithEvents, WithTitle
{
    use FormatsViLoCareWorkbook;

    public function __construct(
        private readonly Collection $patients,
        private readonly array $report
    ) {
    }

    public function array(): array
    {
        $rows = [
            [], [], [],
            ['ViLoCare Patients Report'],
            ['Filtered patient register and facility coverage export'],
            [],
            ['Report Reference', $this->report['reference'], 'Generated', $this->report['generated_at_human'], 'Records', $this->report['record_count'], 'Verification URL', $this->report['verification_url']],
            ['Scope', $this->scopeLabel()],
            [],
            ['ART Number', 'Patient Name', 'Sex', 'Phone', 'State', 'County', 'Facility', 'ART Start Date'],
        ];

        foreach ($this->patients as $patient) {
            $rows[] = [
                $patient->art_number,
                $patient->full_name,
                $patient->sex ?: 'N/A',
                $patient->phone ?: 'N/A',
                $patient->state_name ?? 'Unassigned',
                $patient->county_name ?? 'Unassigned',
                $patient->facility_name ?? 'Unassigned',
                $patient->art_start_date,
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Patients Report';
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
                $lastRow = max(10, 10 + $this->patients->count());
                $this->styleDataSheet($sheet, 'H', 10, $lastRow, [
                    'A' => 15, 'B' => 24, 'C' => 10, 'D' => 17,
                    'E' => 16, 'F' => 17, 'G' => 27, 'H' => 16,
                ]);
                if ($this->patients->isNotEmpty()) {
                    $sheet->getStyle("H11:H{$lastRow}")->getNumberFormat()->setFormatCode('yyyy-mm-dd');
                }
            },
        ];
    }
}
