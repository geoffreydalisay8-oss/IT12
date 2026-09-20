<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable =[
        'name',
        'phone',
        'address',
    ];
    public function devices()
    {
        return $this->hasMany(Devices::class);
    }
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
