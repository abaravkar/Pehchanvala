<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Post extends Model {
    protected $guarded=['id'];
    protected function casts(): array {return ['published'=>'boolean','illustrative'=>'boolean'];}
    public function publicData(): array {return array_merge($this->only(['title','category','image','summary','illustrative']),['id'=>$this->slug,'body'=>preg_split('/\R\s*\R/u',$this->body)]);}
}
