<?php
namespace App\Http\Controllers;
use App\Models\{Payment,PaymentRequest,Setting};
use App\Services\{RazorpayGateway,CapturePayment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
class PaymentController extends Controller {
    public const PURPOSES=['Service fee','Brokerage fee','Property booking amount','PG booking amount','Other agreed payment'];
    private function ready():void {abort_unless(config('agency.payments_enabled')&&config('agency.razorpay_key')&&config('agency.razorpay_secret')&&Setting::read('payments_policy_approved')==='1',503,'Payments are not enabled.');}
    public function order(Request $r,RazorpayGateway $gateway){
        $this->ready();$data=$r->validate(['name'=>'required|string|max:100','email'=>'required|email:rfc|max:150','phone'=>['required','regex:/^[+0-9 ()-]{10,20}$/'],'purpose'=>['required',Rule::in(self::PURPOSES)],'amount'=>'required|integer|min:100|max:50000000','reference'=>'required|string|max:40']);
        $sessionHash=hash('sha256',$r->session()->getId());
        $payment=DB::transaction(function()use($data,$sessionHash,$gateway){
            $invoice=PaymentRequest::where('reference',$data['reference'])->lockForUpdate()->first();
            abort_unless($invoice&&$invoice->enabled&&!$invoice->paid_at&&$invoice->expires_at->isFuture(),422,'The payment reference is invalid, expired or already paid.');
            abort_unless((int)$invoice->amount===$data['amount']&&$invoice->purpose===$data['purpose'],422,'Amount or purpose does not match the agreed payment.');
            abort_if($invoice->customer_email&&strcasecmp($invoice->customer_email,$data['email'])!==0,422,'Use the email agreed for this payment.');
            $existing=$invoice->payments()->where('status','created')->first();
            if($existing){abort_unless(hash_equals($existing->session_hash,$sessionHash),409,'A payment is already pending. Contact the agency before retrying.');return $existing;}
            $order=$gateway->createOrder(['amount'=>$data['amount'],'currency'=>'INR','receipt'=>$invoice->reference,'notes'=>['purpose'=>$invoice->purpose]]);
            abort_unless(preg_match('/^order_[a-zA-Z0-9]+$/',$order['id']??'')&&(int)($order['amount']??0)===$data['amount']&&($order['currency']??'')==='INR',502,'Invalid response from payment provider.');
            return Payment::create(['payment_request_id'=>$invoice->id,'order_id'=>$order['id'],'session_hash'=>$sessionHash,'name'=>$data['name'],'email'=>$data['email'],'phone'=>$data['phone'],'amount'=>$invoice->amount,'currency'=>'INR']);
        });return response()->json(['id'=>$payment->order_id,'amount'=>(int)$payment->amount,'currency'=>'INR']);
    }
    public function verify(Request $r,RazorpayGateway $gateway,CapturePayment $capture){
        $data=$r->validate(['razorpay_order_id'=>['required','regex:/^order_[a-zA-Z0-9]+$/'],'razorpay_payment_id'=>['required','regex:/^pay_[a-zA-Z0-9]+$/'],'razorpay_signature'=>['required','regex:/^[a-f0-9]{64}$/']]);
        $payment=Payment::where('order_id',$data['razorpay_order_id'])->firstOrFail();
        abort_unless(hash_equals($payment->session_hash,hash('sha256',$r->session()->getId())),403);
        abort_unless(config('agency.razorpay_secret'),503);
        $signature=hash_hmac('sha256',$payment->order_id.'|'.$data['razorpay_payment_id'],config('agency.razorpay_secret'));
        abort_unless(hash_equals($signature,$data['razorpay_signature']),422,'Payment signature is invalid.');
        $entity=$gateway->payment($data['razorpay_payment_id']);abort_unless(($entity['order_id']??'')===$payment->order_id&&($entity['id']??'')===$data['razorpay_payment_id'],422);
        if(($entity['status']??'')!=='captured')return response()->json(['status'=>'pending'],202);
        $payment=$capture->handle($entity);return response()->json(['status'=>'captured','reference'=>$payment->paymentRequest->reference]);
    }
    public function webhook(Request $r,CapturePayment $capture){
        $secret=config('agency.razorpay_webhook_secret');abort_unless($secret,503);
        $signature=$r->header('X-Razorpay-Signature','');abort_unless(hash_equals(hash_hmac('sha256',$r->getContent(),$secret),$signature),403);
        $event=$r->json()->all();$id=$r->header('X-Razorpay-Event-Id')?:hash('sha256',$r->getContent());abort_if(strlen($id)>255,400);
        DB::transaction(function()use($event,$id,$capture){
            if(!DB::table('webhook_events')->insertOrIgnore(['id'=>$id,'processed_at'=>now()]))return;
            if(in_array($event['event']??'', ['payment.captured','order.paid'])){
                $entity=data_get($event,'payload.payment.entity',[]);
                if(Payment::where('order_id',$entity['order_id']??'')->exists())$capture->handle($entity);
            }
        });return response()->json(['received'=>true]);
    }
}
