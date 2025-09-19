<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'name',
        'description',
        'price',
        'sku',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function shipments()
    {
        return $this->belongsToMany(Shipment::class)
                    ->withPivot('quantity')
                    ->withTimestamps();
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items')
                    ->withPivot(['quantity', 'price'])
                    ->withTimestamps();
    }
    // app/Models/Product.php
// ... existing imports & class header

public function inventories()
{
    return $this->hasMany(\App\Models\Inventory::class, 'product_id', 'id');
}

}