<?php

namespace App\Exports;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class PatientsExport implements FromArray, Responsable, WithDrawings
{
    public function __construct(
        private readonly Collection $patients,
        private readonly array $report
    ) {
    }

    public function array(): array
    {
        $rows = [
            [],
            [],
            [],
            ['ViLoCare Patients Report'],
            ['Report Reference', $this->report['reference']],
            ['Generated At', $this->report['generated_at_human']],
            ['Verification URL', $this->report['verification_url']],
            [],
            ['ART Number', 'Full Name', 'Sex', 'Phone', 'State', 'County', 'Facility', 'ART Start Date'],
        ];

        foreach ($this->patients as $patient) {
            $rows[] = [
                $patient->art_number,
                $patient->full_name,
                $patient->sex,
                $patient->phone,
                $patient->state_name ?? 'Unassigned',
                $patient->county_name ?? 'Unassigned',
                $patient->facility_name ?? 'Unassigned',
                $patient->art_start_date,
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
