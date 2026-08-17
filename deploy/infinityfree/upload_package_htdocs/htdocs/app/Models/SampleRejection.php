<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SampleRejection extends Model
{
    protected $table = 'sample_rejections';

    protected $primaryKey = 'rejection_id';

    public $timestamps = false;

    protected $fillable = [
        'sample_id',
        'rejection_date',
        'reason',
        'action_taken',
        'corrective_action',
    ];

    protected $casts = [
        'rejection_date' => 'date',
    ];

    public function sample()
    {
        return $this->belongsTo(SampleCollection::class, 'sample_id', 'sample_id');
    }
}
