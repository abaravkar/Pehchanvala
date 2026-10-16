<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Property extends Model {
    use SoftDeletes;
    protected $guarded = ['id'];
    protected function casts(): array {return ['latitude'=>'float','longitude'=>'float','amenities'=>'array','images'=>'array','available'=>'date','published'=>'boolean','featured'=>'boolean','illustrative'=>'boolean'];}
    public function publicData(): array {
        return array_merge($this->only(['title','area','type','kind','rent','sale','beds','baths','size','furnished','description','amenities','images','map','latitude','longitude','sharing','deposit','meals','featured','illustrative']), ['id'=>$this->slug,'available'=>$this->available?->format('Y-m-d') ?? '']);
    }
}
