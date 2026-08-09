<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'profile_photo_path',
        'username',
        'password',
        'role',
        'contact',
        'must_change_password',
        'created_by',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function isAdministrator(): bool
    {
        return $this->role === 'Administrator';
    }

    public function isClinician(): bool
    {
        return $this->role === 'Clinician';
    }

    public function canManageUsers(): bool
    {
        return $this->isAdministrator() || $this->isClinician();
    }

    public function manageableRoles(): array
    {
        if ($this->isAdministrator()) {
            return ['Administrator', 'Clinician', 'Lab Technician', 'Data Clerk'];
        }

        if ($this->isClinician()) {
            return ['Lab Technician', 'Data Clerk'];
        }

        return [];
    }

    public function profilePhotoUrl(): ?string
    {
        return $this->profile_photo_path ? asset($this->profile_photo_path) : null;
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
