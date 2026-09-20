<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function assignedRepairTickets()
    {
        return $this->hasMany(RepairTicket::class, 'assigned_to');
    }

    public function repairStatusHistories()
    {
        return $this->hasMany(RepairStatusHistory::class, 'changed_by');
    }

    public function devicePhotos()
    {
        return $this->hasMany(DevicePhoto::class, 'uploaded_by');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}