<?php
namespace App\Jobs;
use App\Models\{Payment,Setting};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
class SendPaymentEmails implements ShouldQueue {
    use Queueable;
    public $tries=5;
    public function __construct(public int $paymentId){}
    public function backoff():array{return [30,120,600,1800];}
    public function middleware():array{return [(new \Illuminate\Queue\Middleware\WithoutOverlapping('payment-mail-'.$this->paymentId))->releaseAfter(15)->expireAfter(180)];}
    public function handle():void{
        $p=Payment::with('paymentRequest')->findOrFail($this->paymentId);if($p->status!=='captured')return;
        $agency=Setting::read('agency_email',config('agency.email'));
        foreach(['agency'=>$agency,'customer'=>$p->email] as $recipient=>$email){$column=$recipient.'_emailed_at';if(!$p->$column){Mail::to($email)->send(new \App\Mail\PaymentMail($p));$p->update([$column=>now()]);}}
    }
}
