<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DevicePhoto extends Model
{
     protected $fillable = [
        'repair_ticket_id',
        'photo_path',
        'photo_type',
        'uploaded_by',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function repairTicket()
    {
        return $this->belongsTo(RepairTicket::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
