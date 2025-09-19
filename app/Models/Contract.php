<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    protected $table = 'contracts';
    protected $fillable = ['buyer_id','supplier_id','title','terms','start_date','end_date','value'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
