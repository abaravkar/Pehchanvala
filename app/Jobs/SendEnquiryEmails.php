<?php
namespace App\Jobs;
use App\Models\{Enquiry,Setting};
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
class SendEnquiryEmails implements ShouldQueue {
    use Queueable;
    public $tries=5;
    public function __construct(public int $enquiryId){}
    public function backoff():array{return [30,120,600,1800];}
    public function middleware():array{return [(new \Illuminate\Queue\Middleware\WithoutOverlapping('enquiry-mail-'.$this->enquiryId))->releaseAfter(15)->expireAfter(180)];}
    public function handle():void {
        $e=Enquiry::findOrFail($this->enquiryId);$agency=Setting::read('agency_email',config('agency.email'));
        if(!$e->agency_emailed_at){Mail::to($agency)->send(new \App\Mail\EnquiryMail($e,false));$e->update(['agency_emailed_at'=>now()]);}
        if(!$e->customer_emailed_at){Mail::to($e->email,$e->name)->send(new \App\Mail\EnquiryMail($e,true));$e->update(['customer_emailed_at'=>now()]);}
    }
}
