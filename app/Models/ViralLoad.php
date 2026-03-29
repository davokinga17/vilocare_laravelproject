<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViralLoad extends Model
{
protected $table = 'viral_load_results';

protected $fillable = [
    'patient_id',
    'sample_date',
    'result_cpml',
    'lab_number'
];
}
