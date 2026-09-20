<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
   protected $fillable = [
        'receipt_code',
        'sale_id',
        'repair_ticket_id',
        'issued_at',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function repairTicket()
    {
        return $this->belongsTo(RepairTicket::class);
    }
}
