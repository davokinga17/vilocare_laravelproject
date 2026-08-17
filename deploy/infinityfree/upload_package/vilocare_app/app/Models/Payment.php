<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

class Payment extends Model
{
    protected $table = 'payments';

    protected $primaryKey = 'payment_id';

    protected $fillable = [
        'patient_id',
        'eac_id',
        'vl_id',
        'created_by',
        'accepted_by',
        'payment_type',
        'service_label',
        'amount',
        'currency',
        'payment_method',
        'status',
        'receipt_number',
        'external_reference',
        'paid_at',
        'accepted_at',
        'notes',
        'meta',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'accepted_at' => 'datetime',
        'meta' => 'array',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id', 'patient_id');
    }

    public function eacSession(): BelongsTo
    {
        return $this->belongsTo(EACSession::class, 'eac_id', 'eac_id');
    }

    public function viralLoad(): BelongsTo
    {
        return $this->belongsTo(ViralLoad::class, 'vl_id', 'vl_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function acceptedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function scopePaid(Builder $query): Builder
    {
        return $query->where('status', 'paid');
    }
}
