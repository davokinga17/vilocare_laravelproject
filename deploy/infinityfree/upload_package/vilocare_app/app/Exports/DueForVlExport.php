<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class DueForVlExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private readonly Collection $rows,
        private readonly array $filterConfig
    ) {
    }

    public function collection()
    {
        return $this->rows->map(function ($row) {
            $exportRow = [
                'ART Number' => $row->art_number,
                'Patient Name' => $row->full_name,
                'Sex' => $row->sex,
                'Phone' => $row->phone,
                'Last EAC Session 3 Date' => $row->last_session_date,
                'VL Due Date' => $row->due_date,
            ];

            foreach (['state_id', 'county_id', 'facility_id'] as $key) {
                if (($this->filterConfig[$key]['available'] ?? false) === true) {
                    $exportRow[$this->filterConfig[$key]['label']] = $row->{$key} ?? '';
                }
            }

            return $exportRow;
        });
    }

    public function headings(): array
    {
        $headings = [
            'ART Number',
            'Patient Name',
            'Sex',
            'Phone',
            'Last EAC Session 3 Date',
            'VL Due Date',
        ];

        foreach (['state_id', 'county_id', 'facility_id'] as $key) {
            if (($this->filterConfig[$key]['available'] ?? false) === true) {
                $headings[] = $this->filterConfig[$key]['label'];
            }
        }

        return $headings;
    }
}
