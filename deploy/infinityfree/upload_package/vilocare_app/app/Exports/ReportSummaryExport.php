<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithDrawings;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;

class ReportSummaryExport implements FromArray, WithDrawings
{
    public function __construct(
        private readonly array $summary,
        private readonly array $report
    ) {
    }

    public function array(): array
    {
        $rows = [
            [],
            [],
            [],
            ['ViLoCare Summary Report'],
            ['Report Reference', $this->report['reference']],
            ['Generated At', $this->report['generated_at_human']],
            ['Verification URL', $this->report['verification_url']],
            [],
            ['Metric', 'Value'],
            ['Total Patients', $this->summary['totalPatients']],
            ['Total Viral Loads', $this->summary['totalViralLoads']],
            ['Suppressed Results', $this->summary['suppressed']],
            ['Unsuppressed Results', $this->summary['unsuppressed']],
            ['Suppression Rate', $this->summary['suppressionRate']],
            ['Latest Result Date', $this->summary['latestResultDate']],
            ['Covered Counties', $this->summary['coveredCounties']],
            ['Covered Facilities', $this->summary['coveredFacilities']],
        ];

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
