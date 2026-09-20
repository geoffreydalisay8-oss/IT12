<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairTicket extends Model
{

    protected $fillable = [
        'device_id',
        'assigned_to',
        'problem_description',
        'quotation_price',
        'final_price',
        'status',
        'date_received',
        'date_completed',
    ];

    protected $casts = [
        'quotation_price' => 'decimal:2',
        'final_price' => 'decimal:2',
        'date_received' => 'datetime',
        'date_completed' => 'datetime',
    ];

    public function device()
    {
        return $this->belongsTo(Device::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function statusHistory()
    {
        return $this->hasMany(RepairStatusHistory::class);
    }

    public function photos()
    {
        return $this->hasMany(DevicePhoto::class);
    }

    public function receipt()
    {
        return $this->hasOne(Receipt::class);
    }
}

