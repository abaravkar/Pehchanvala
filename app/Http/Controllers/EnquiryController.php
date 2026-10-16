<?php
namespace App\Http\Controllers;
use App\Jobs\SendEnquiryEmails;
use App\Models\{Enquiry,Property};
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class EnquiryController extends Controller {
    public function store(Request $request){
        $data=$request->validate(['name'=>'required|string|max:100','email'=>'required|email:rfc|max:150','phone'=>['required','regex:/^[+0-9 ()-]{10,20}$/'],'interest'=>'required|string|max:100','locality'=>'nullable|string|max:150','message'=>'nullable|string|max:2500','propertyReference'=>'nullable|string|max:200','propertyTitle'=>'nullable|string|max:150','expectedPrice'=>'nullable|integer|min:1|max:999999999999','consent'=>'required|accepted','website'=>'nullable|max:0']);
        $property=null;
        if(!empty($data['propertyReference'])){$property=Property::where('slug',$data['propertyReference'])->where('published',true)->first();if(!$property)throw ValidationException::withMessages(['propertyReference'=>'This property is no longer available.']);}
        $enquiry=Enquiry::create(['reference'=>(string)Str::uuid(),'property_id'=>$property?->id,'name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone'],'interest'=>$data['interest'],'locality'=>$data['locality']??null,'message'=>$data['message']??null,'property_title'=>$property?->title??($data['propertyTitle']??null),'expected_price'=>$data['expectedPrice']??null,'consented_at'=>now()]);
        SendEnquiryEmails::dispatch($enquiry->id)->afterCommit();
        return response()->json(['id'=>$enquiry->reference],201);
    }
}
