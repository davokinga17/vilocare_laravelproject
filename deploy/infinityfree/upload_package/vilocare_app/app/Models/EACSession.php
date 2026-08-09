<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EACSession extends Model
{
    protected $table = 'eac_sessions';

    protected $primaryKey = 'eac_id'; // Important if your primary key is 'eac_id'

    public $timestamps = false; // Disable timestamps if not used

    protected $fillable = [
        'patient_id',
        'session_number',
        'session_date',
        'counselor',
        'barriers',
        'action_plan',
        'notes',
        'completion_status',
        'next_session_date'
    ];

    // Define relationship to Patient
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'eac_id', 'eac_id');
    }

    public function hasPaidConsultationFee(): bool
    {
        return $this->payments
            ->where('payment_type', 'eac_consultation')
            ->where('status', 'paid')
            ->isNotEmpty();
    }
}
