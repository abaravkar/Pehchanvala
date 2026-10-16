<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PaymentRequest extends Model {
    protected $guarded=['id'];
    protected function casts(): array {return ['expires_at'=>'datetime','paid_at'=>'datetime','enabled'=>'boolean'];}
    public function payments(){return $this->hasMany(Payment::class);}
}
