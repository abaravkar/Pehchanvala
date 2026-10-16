<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache,Http};
class LocationSearchController extends Controller {
    public function __invoke(Request $request){
        $data=$request->validate(['q'=>'required|string|min:3|max:180']);
        try{
            $results=Cache::remember('location-search:'.hash('sha256',mb_strtolower($data['q'])),now()->addHours(12),function()use($data){
                $json=Http::acceptJson()->withHeaders(['User-Agent'=>'PehchanvalaLocalCMS/1.0'])->timeout(12)
                    ->get('https://photon.komoot.io/api/',['q'=>$data['q'],'lat'=>18.5204,'lon'=>73.8567,'bbox'=>'73.3,18.0,74.5,19.2','limit'=>6,'lang'=>'en'])->throw()->json();
                return collect($json['features']??[])->filter(fn($f)=>($f['geometry']['type']??'')==='Point'&&is_numeric($f['geometry']['coordinates'][0]??null)&&is_numeric($f['geometry']['coordinates'][1]??null))
                    ->map(function($f){$p=$f['properties']??[];$label=collect([$p['name']??null,trim(($p['housenumber']??'').' '.($p['street']??'')),$p['district']??null,$p['city']??null,$p['state']??null,$p['postcode']??null])->filter()->unique()->implode(', ');return ['label'=>mb_substr($label?:'Selected location',0,500),'latitude'=>(float)$f['geometry']['coordinates'][1],'longitude'=>(float)$f['geometry']['coordinates'][0]];})->values()->all();
            });
            return response()->json(['results'=>$results]);
        }catch(\Illuminate\Http\Client\ConnectionException|\Illuminate\Http\Client\RequestException $e){return response()->json(['message'=>'Location search is temporarily unavailable. You can still click the map to place the pin.'],503);}
    }
}
