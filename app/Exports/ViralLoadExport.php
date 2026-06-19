<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ViralLoadExport implements FromArray, Responsable, WithDrawings
{
    public function __construct(
        private readonly Collection $results,
        private readonly array $report
    ) {
    }

    public function array(): array
    {
        $rows = [
            [],
            [],
            [],
            ['ViLoCare Viral Load Report'],
            ['Report Reference', $this->report['reference']],
            ['Generated At', $this->report['generated_at_human']],
            ['Verification URL', $this->report['verification_url']],
            [],
            ['ART Number', 'Full Name', 'VL Status', 'Result (cp/ml)', 'Result (log)', 'Sample Date', 'Result Date', 'Indication', 'State', 'County', 'Facility'],
        ];

        foreach ($this->results as $result) {
            $rows[] = [
                $result->art_number,
                $result->full_name,
                $result->viral_load_status,
                $result->result_cpml,
                $result->result_log,
                $result->sample_date,
                $result->result_date,
                $result->vl_testing_indication,
                $result->state_name ?? 'Unassigned',
                $result->county_name ?? 'Unassigned',
                $result->facility_name ?? 'Unassigned',
            ];
        }

        return $rows;
    }

    public function drawings(): array
    {
        $path = $this->report['logo_path'] ?? null;

        if (! $path || ! is_file($path)) {
            return [];
        }

        $drawing = new Drawing();
        $drawing->setName('ViLoCare Logo');
        $drawing->setDescription('ViLoCare Logo');
        $drawing->setPath($path);
        $drawing->setHeight(55);
        $drawing->setCoordinates('A1');

        return [$drawing];
    }
}
