<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    protected $fillable = ['shipment_id','title','payload','created_by','shared_with'];
    protected $casts = ['payload' => 'array', 'shared_with' => 'array'];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
