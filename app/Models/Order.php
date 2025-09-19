<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_id',
        'supplier_id',
        'status',
        'order_date',
        'expected_date',
        'total',
    ];

    protected $casts = [
        'order_date' => 'datetime',
        'expected_date' => 'datetime',
        'total' => 'float',
    ];

    public function items() {
        return $this->hasMany(\App\Models\OrderItem::class);
    }

    public function supplier() {
        return $this->belongsTo(\App\Models\Supplier::class);
    }

    public function buyer() {
        // assumes your users table is App\Models\User; column is buyer_id
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }
}
