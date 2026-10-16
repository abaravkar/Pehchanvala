<?php
namespace App\Http\Controllers;
use App\Models\{Property,Post,Setting};
class WebsiteController extends Controller {
    public function __invoke(){
        $email=Setting::read('agency_email',config('agency.email'));
        $ready=config('agency.payments_enabled') && config('agency.razorpay_key') && config('agency.razorpay_secret') && Setting::read('payments_policy_approved')==='1';
        return view('website',[
            'properties'=>Property::where('published',true)->orderByDesc('featured')->latest()->get()->map->publicData(),
            'posts'=>Post::where('published',true)->latest()->get()->map->publicData(),
            'publicConfig'=>['enquiryEndpoint'=>url('/enquiries'),'razorpayOrderEndpoint'=>$ready?url('/payments/orders'):'','razorpayVerifyEndpoint'=>$ready?url('/payments/verify'):'','razorpayKeyId'=>$ready?config('agency.razorpay_key'):'','analyticsId'=>Setting::read('analytics_id'),'agencyEmail'=>$email,'isLocalCms'=>false],
            'policies'=>collect(['privacy','terms','refund'])->mapWithKeys(fn($key)=>[$key=>Setting::read('policy_'.$key)]),
        ]);
    }
}
