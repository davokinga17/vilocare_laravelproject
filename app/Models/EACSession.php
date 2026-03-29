<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EACSession extends Model
{
protected $table = 'eac_sessions';

protected $fillable = [
    'patient_id',
    'session_number',
    'session_date',
    'completion_status'
];

public $timestamps = false;
public function patient()
    {
        return $this->belongsTo(\App\Models\Patient::class);
    }
}

