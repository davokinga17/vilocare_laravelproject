<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SampleCollection extends Model
{
    protected $table = 'sample_collection';

    protected $primaryKey = 'sample_id';

    public $timestamps = false;

    protected $fillable = [
        'patient_id',
        'collection_date',
        'sample_type',
        'collector',
        'status',
        'sample_reception_date',
        'health_facility_code',
    ];

    protected $casts = [
        'collection_date' => 'date',
        'sample_reception_date' => 'date',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function rejections()
    {
        return $this->hasMany(SampleRejection::class, 'sample_id', 'sample_id');
    }
}
