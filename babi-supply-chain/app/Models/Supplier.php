<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'contact_email',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }
}

