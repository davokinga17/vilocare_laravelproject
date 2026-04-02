<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ViralLoad extends Model
{
    protected $table = 'viral_load_results';

    protected $primaryKey = 'vl_id'; // Important if your primary key is 'vl_id'

    public $timestamps = false; // Disable timestamps if not used

    protected $fillable = [
        'patient_id',
        'sample_date',
        'result',
        'lab',
        'notes',
        'status',
        'result_date',
        'sample_type',
        'result_cpml',
        'result_log',
        'comments',
        'requesting_clinician',
        'clinician_cellphone',
        'request_date',
        'vl_testing_indication'
    ];

    // Define relationship to Patient
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }
}