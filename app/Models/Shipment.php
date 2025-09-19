<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    protected $fillable = ['warehouse_id','prepared_by','destination','status','total_quantity','total_value'];

    public function items() {
        return $this->hasMany(ShipmentItem::class);
    }
    public function warehouse() {
        return $this->belongsTo(Warehouse::class);
    }
}
