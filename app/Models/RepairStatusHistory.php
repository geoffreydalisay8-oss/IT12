<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RepairStatusHistory extends Model
{
    protected $fillable = [
        'repair_ticket_id',
        'status',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function repairTicket()
    {
        return $this->belongsTo(RepairTicket::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

}
