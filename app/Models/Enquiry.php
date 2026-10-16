<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Enquiry extends Model {
    protected $guarded=['id'];
    public function property(){return $this->belongsTo(Property::class)->withTrashed();}
}
