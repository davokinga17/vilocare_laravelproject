<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    // Specify the table name if different from the default plural form
    protected $table = 'patients';

    // Set the primary key if it's not 'id'
    protected $primaryKey = 'patient_id'; // Important if your primary key is 'patient_id'

    // Disable timestamps if your table doesn't have created_at and updated_at columns
    public $timestamps = false;

    // Fillable fields for mass assignment
    protected $fillable = [
        'art_number',
        'first_name',
        'last_name',
        'sex',
        'address',
        'phone',
        'art_start_date',
        'current_regimen',
        'age',
        'is_pregnant',
        'is_breastfeeding',
        'arv_adherence',
        'facility_id',
        'county_id',
        'state_id'
    ];

    protected $casts = [
        'art_start_date' => 'date',
        'age' => 'integer',
        'is_pregnant' => 'boolean',
        'is_breastfeeding' => 'boolean',
    ];

    public function sampleCollections()
    {
        return $this->hasMany(SampleCollection::class, 'patient_id', 'patient_id');
    }

    public function viralLoads(): HasMany
    {
        return $this->hasMany(ViralLoad::class, 'patient_id', 'patient_id');
    }

    public function eacSessions(): HasMany
    {
        return $this->hasMany(EACSession::class, 'patient_id', 'patient_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'patient_id', 'patient_id');
    }
}
