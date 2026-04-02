<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'age_category',
        'is_pregnant',
        'is_breastfeeding',
        'arv_adherence',
        'facility_id',
        'county_id',
        'state_id'
    ];
}