<?php
namespace App\Http\Controllers;
use App\Jobs\SendEnquiryEmails;
use App\Models\{Property,Post,Enquiry,Payment,PaymentRequest,Setting};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminController extends Controller {
    public function dashboard(){return view('admin.dashboard',['counts'=>['Properties'=>Property::count(),'Published'=>Property::where('published',true)->count(),'New enquiries'=>Enquiry::where('status','new')->count(),'Journal stories'=>Post::count()],'recent'=>Enquiry::latest()->limit(6)->get()]);}
    public function properties(Request $r){$q=Property::query();if($r->filled('q'))$q->where(fn($s)=>$s->where('title','like','%'.$r->string('q').'%')->orWhere('area','like','%'.$r->string('q').'%'));return view('admin.properties',['items'=>$q->latest()->paginate(15)->withQueryString()]);}
    public function propertyForm(?Property $property=null){return view('admin.property-form',['item'=>$property??new Property(['type'=>'rent','kind'=>'Apartment','furnished'=>'Semi furnished','images'=>[],'amenities'=>[]])]);}
    public function saveProperty(Request $r,?Property $property=null){
        $item=$property??new Property();$data=$r->validate([
            'title'=>'required|string|max:180','slug'=>['required','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/','max:200',Rule::unique('properties')->ignore($item->id)],
            'area'=>'required|string|max:150','type'=>['required',Rule::in(['rent','sale','both','pg'])],'kind'=>'required|string|max:100',
            'rent'=>['nullable','required_if:type,rent,both,pg','integer','min:1','max:999999999999'],
            'sale'=>['nullable','required_if:type,sale,both','integer','min:1','max:999999999999'],
            'beds'=>'required|integer|min:0|max:100','baths'=>'required|integer|min:0|max:100','size'=>'required|integer|min:1|max:10000000',
            'furnished'=>'required|string|max:100','available'=>'nullable|date_format:Y-m-d','description'=>'required|string|max:10000',
            'amenities'=>'nullable|string|max:1000','map'=>'required|string|max:500','sharing'=>'nullable|required_if:type,pg|string|max:100',
            'latitude'=>'nullable|required_with:longitude|numeric|between:-90,90',
            'longitude'=>'nullable|required_with:latitude|numeric|between:-180,180',
            'deposit'=>'nullable|integer|min:0|max:999999999999','meals'=>'nullable|string|max:150',
            'photo_urls'=>'nullable|string|max:10000','photos'=>'nullable|array|max:20','photos.*'=>'file|image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_images'=>'nullable|array','remove_images.*'=>'string|max:1000','published'=>'sometimes|boolean','featured'=>'sometimes|boolean','illustrative'=>'sometimes|boolean',
        ]);
        $urls=array_values(array_filter(array_map('trim',preg_split('/\R/',$data['photo_urls']??''))));
        foreach($urls as $url)if(!filter_var($url,FILTER_VALIDATE_URL)||parse_url($url,PHP_URL_SCHEME)!=='https')return back()->withInput()->withErrors(['photo_urls'=>'Photo links must be valid HTTPS URLs.']);
        $existing=$item->images??[];$removed=array_intersect($existing,$r->input('remove_images',[]));
        $images=array_values(array_diff($existing,$removed));$newPaths=[];
        try{
            foreach($r->file('photos',[]) as $photo){$path=$photo->store('properties','public');$newPaths[]=$path;$images[]=url('/media/'.$path);}
            $images=array_values(array_unique(array_merge($images,$urls)));
            if(!$images){foreach($newPaths as $path)Storage::disk('public')->delete($path);return back()->withInput()->withErrors(['photos'=>'Add at least one property photograph.']);}
            unset($data['photos'],$data['photo_urls'],$data['remove_images']);
            $data['amenities']=array_values(array_filter(array_map('trim',explode(',',$data['amenities']??''))));$data['images']=$images;
            foreach(['published','featured','illustrative'] as $key)$data[$key]=$r->boolean($key);
            if($data['type']==='sale')$data['rent']=null;if(in_array($data['type'],['rent','pg']))$data['sale']=null;
            if($data['type']!=='pg'){foreach(['sharing','deposit','meals'] as $key)$data[$key]=null;}
            $item->fill($data)->save();
        }catch(\Throwable $e){foreach($newPaths as $path)Storage::disk('public')->delete($path);throw $e;}
        foreach($removed as $url)$this->removeUpload($url,'properties');
        return redirect('/admin/properties')->with('success','Property saved to the database.');
    }
    public function deleteProperty(Property $property){$property->delete();return back()->with('success','Property archived. It no longer appears on the website.');}
    public function posts(){return view('admin.posts',['items'=>Post::latest()->paginate(15)]);}
    public function postForm(?Post $post=null){return view('admin.post-form',['item'=>$post??new Post()]);}
    public function savePost(Request $r,?Post $post=null){$item=$post??new Post();$data=$r->validate(['title'=>'required|string|max:180','slug'=>['required','regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/','max:200',Rule::unique('posts')->ignore($item->id)],'category'=>'required|string|max:100','summary'=>'required|string|max:1000','body'=>'required|string|max:100000','image'=>'nullable|url:http,https|max:1000','cover'=>'nullable|file|image|mimes:jpg,jpeg,png,webp|max:5120']);$old=$item->image;if(!empty($data['image'])&&$data['image']!==$old&&!str_starts_with($data['image'],'https://'))return back()->withInput()->withErrors(['image'=>'Use an HTTPS image URL.']);$newPath=null;if($r->hasFile('cover')){$newPath=$r->file('cover')->store('journal','public');$data['image']=url('/media/'.$newPath);}elseif(empty($data['image']))$data['image']=$old;if(empty($data['image']))return back()->withInput()->withErrors(['cover'=>'Provide a cover photo or HTTPS image URL.']);unset($data['cover']);$data['published']=$r->boolean('published');$data['illustrative']=$r->boolean('illustrative');try{$item->fill($data)->save();}catch(\Throwable $e){if($newPath)Storage::disk('public')->delete($newPath);throw $e;}if($old&&$old!==$item->image)$this->removeUpload($old,'journal');return redirect('/admin/posts')->with('success','Story saved.');}
    public function deletePost(Post $post){$post->update(['published'=>false]);return back()->with('success','Story unpublished.');}
    public function enquiries(Request $r){$q=Enquiry::query();if(in_array($r->input('status'),['new','contacted','closed']))$q->where('status',$r->input('status'));return view('admin.enquiries',['items'=>$q->latest()->paginate(20)->withQueryString()]);}
    public function enquiry(Enquiry $enquiry){return view('admin.enquiry',['item'=>$enquiry]);}
    public function updateEnquiry(Request $r,Enquiry $enquiry){$data=$r->validate(['status'=>['required',Rule::in(['new','contacted','closed'])],'notes'=>'nullable|string|max:10000']);$enquiry->update($data);return back()->with('success','Enquiry updated.');}
    public function retryEnquiry(Enquiry $enquiry){SendEnquiryEmails::dispatch($enquiry->id);return back()->with('success','Unsent confirmations queued for delivery.');}
    public function settings(){return view('admin.settings',['settings'=>Setting::pluck('value','key')]);}
    public function saveSettings(Request $r){
        $data=$r->validate(['agency_email'=>'required|email|max:150','analytics_id'=>['nullable','regex:/^G-[A-Z0-9]+$/'],'policy_privacy'=>'nullable|string|max:50000','policy_terms'=>'nullable|string|max:50000','policy_refund'=>'nullable|string|max:50000']);
        if($r->boolean('payments_policy_approved'))foreach(['policy_privacy','policy_terms','policy_refund'] as $key)if(empty($data[$key]))return back()->withInput()->withErrors(['payments_policy_approved'=>'Provide all three approved policy texts first.']);
        \Illuminate\Support\Facades\DB::transaction(function()use($data,$r){foreach($data as $key=>$value)Setting::updateOrCreate(['key'=>$key],['value'=>$value??'']);Setting::updateOrCreate(['key'=>'payments_policy_approved'],['value'=>$r->boolean('payments_policy_approved')?'1':'0']);});
        return back()->with('success','Website settings saved.');
    }
    public function payments(){return view('admin.payments',['requests'=>PaymentRequest::latest()->paginate(15),'payments'=>Payment::latest()->limit(30)->get()]);}
    public function createPaymentRequest(Request $r){$data=$r->validate(['purpose'=>['required',Rule::in(PaymentController::PURPOSES)],'amount'=>'required|numeric|min:1|max:500000','customer_email'=>'nullable|email|max:150','expires_at'=>'required|date|after:now','property_id'=>'nullable|exists:properties,id']);$data['amount']=(int)round((float)$data['amount']*100);$data['reference']='PV-'.Str::upper(Str::random(14));PaymentRequest::create($data);return back()->with('success','Payment reference created. Share it with the customer alongside the agreed terms.');}
    public function disablePaymentRequest(PaymentRequest $paymentRequest){$paymentRequest->update(['enabled'=>false]);return back()->with('success','Payment reference disabled. Existing payments can still be reconciled.');}
    private function removeUpload(string $url,string $folder):void{$prefix=url('/media/'.$folder.'/');if(str_starts_with($url,$prefix)){$name=substr($url,strlen($prefix));if(preg_match('/^[a-zA-Z0-9._-]+$/',$name))Storage::disk('public')->delete($folder.'/'.$name);}}
}
