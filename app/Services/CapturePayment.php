<?php
namespace App\Services;
use App\Models\{Payment,PaymentRequest};
use App\Jobs\SendPaymentEmails;
use Illuminate\Support\Facades\DB;
class CapturePayment {
    public function handle(array $entity):Payment {
        return DB::transaction(function()use($entity){
            $payment=Payment::where('order_id',$entity['order_id']??'')->lockForUpdate()->firstOrFail();
            abort_unless(($entity['status']??'')==='captured' && ($entity['currency']??'')==='INR' && (int)($entity['amount']??0)===(int)$payment->amount && preg_match('/^pay_[a-zA-Z0-9]+$/',$entity['id']??''),422,'Payment details do not match the stored order.');
            if($payment->status==='captured'){abort_unless($payment->payment_id===$entity['id'],422);return $payment;}
            $request=PaymentRequest::whereKey($payment->payment_request_id)->lockForUpdate()->firstOrFail();
            $payment->update(['payment_id'=>$entity['id'],'status'=>'captured','captured_at'=>now()]);
            // A duplicate captured payment remains visible for manual reconciliation.
            $request->update(['paid_at'=>$request->paid_at??now()]);
            SendPaymentEmails::dispatch($payment->id)->afterCommit();return $payment;
        });
    }
}
