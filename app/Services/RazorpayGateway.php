<?php
namespace App\Services;
use Illuminate\Support\Facades\Http;
class RazorpayGateway {
    private function client(){return Http::withBasicAuth(config('agency.razorpay_key'),config('agency.razorpay_secret'))->acceptJson()->timeout(20);}
    public function createOrder(array $data):array{return $this->client()->post('https://api.razorpay.com/v1/orders',$data)->throw()->json();}
    public function payment(string $id):array{return $this->client()->get('https://api.razorpay.com/v1/payments/'.rawurlencode($id))->throw()->json();}
}
