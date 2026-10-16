<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Setting extends Model {
    protected $primaryKey='key'; public $incrementing=false; protected $keyType='string'; public $timestamps=false;
    protected $guarded=[];
    public static function read(string $key, string $fallback=''): string {return (string)(static::find($key)?->value ?? $fallback);}
}
