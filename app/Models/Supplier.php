<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'suppliers';

    // fields you mentioned and a few extras used by dashboard/performance
    protected $fillable = [
        'name',
        'contact_email',
        'phone',
        'address',
        'rating',
        'performance_score',
        'on_time_percentage',
        'last_delivery_date',
        'notes',
    ];

    protected $casts = [
        'rating' => 'float',
        'performance_score' => 'integer',
        'on_time_percentage' => 'integer',
        'last_delivery_date' => 'date',
    ];

    /**
     * Orders supplied by this supplier.
     * Assumes orders table has supplier_id foreign key.
     */
    public function orders()
    {
        return $this->hasMany(\App\Models\Order::class, 'supplier_id');
    }

    /**
     * Convenience: recent orders (latest by order_date).
     */
    public function recentOrders()
    {
        return $this->orders()->latest('order_date')->limit(10);
    }
}
