<?php

namespace App\Exports;

use App\Models\ViralLoad;
use Maatwebsite\Excel\Concerns\FromCollection;

class ViralLoadExport implements FromCollection
{
    public function collection()
    {
        return ViralLoad::with('patient')->get();
    }
}