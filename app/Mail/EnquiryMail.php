<?php
namespace App\Mail;
use App\Models\{Enquiry,Setting};
use Illuminate\Mail\Mailable;
class EnquiryMail extends Mailable {
    public function __construct(public Enquiry $enquiry,public bool $customer){}
    public function build(){return $this->subject($this->customer?'We have received your enquiry · Pehchanvala':'New Pehchanvala enquiry · '.$this->enquiry->reference)->replyTo($this->customer?Setting::read('agency_email',config('agency.email')):$this->enquiry->email)->view('emails.enquiry');}
}
