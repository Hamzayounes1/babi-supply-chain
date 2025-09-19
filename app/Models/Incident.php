<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
    protected $fillable = ['shipment_id','type','message','severity','reported_by','metadata'];
    protected $casts = ['metadata' => 'array'];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
