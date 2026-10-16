<?php
namespace Database\Seeders;
use App\Models\{Property,Post};
use Illuminate\Database\Seeder;
class DemoContentSeeder extends Seeder {
    public function run():void{
        $data=json_decode(file_get_contents(__DIR__.'/content.json'),true,512,JSON_THROW_ON_ERROR);
        foreach($data['properties'] as $p){$slug=$p['id'];unset($p['id']);$p['available']=$p['available']?:null;Property::firstOrCreate(['slug'=>$slug],[...$p,'published'=>true,'illustrative'=>true]);}
        foreach($data['posts'] as $p){$slug=$p['id'];unset($p['id']);$p['body']=implode("\n\n",$p['body']);Post::firstOrCreate(['slug'=>$slug],[...$p,'published'=>true,'illustrative'=>true]);}
    }
}
