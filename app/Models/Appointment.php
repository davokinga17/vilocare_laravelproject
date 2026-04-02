<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $table = 'appointments';

    protected $primaryKey = 'appointment_id';

    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'appointment_date',
        'reason',
        'status'
    ];

    public function patient()
    {
        return $this->belongsTo(\App\Models\Patient::class, 'patient_id');
    }
}