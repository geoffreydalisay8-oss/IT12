<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'supplier_name',
        'contact_info',
        'location',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
