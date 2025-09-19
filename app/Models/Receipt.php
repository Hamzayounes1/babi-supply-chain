<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model {
    protected $fillable = [
        'warehouse_id','received_by','reference',
    ];

    public function items() {
        return $this->hasMany(ReceiptItem::class);
    }
    public function warehouse() {
        return $this->belongsTo(Warehouse::class);
    }
    public function supplier() {
        return $this->belongsTo(Supplier::class);
    }
}
