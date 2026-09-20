<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    
    protected $fillable = [
        'customer_id',
        'brand',
        'model',
        'serial_or_imei',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function repairTickets()
    {
        return $this->hasMany(RepairTicket::class);
    }
}
