<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ShipmentItem extends Model
{
    protected $fillable = ['shipment_id','product_id','product_name','product_sku','quantity','unit_cost'];

    public function shipment() {
        return $this->belongsTo(Shipment::class);
    }
}
