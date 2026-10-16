<?php
namespace App\Mail;
use App\Models\Payment;
use Illuminate\Mail\Mailable;
class PaymentMail extends Mailable {
    public function __construct(public Payment $payment){}
    public function build(){return $this->subject('Payment confirmed · '.$this->payment->paymentRequest->reference)->view('emails.payment');}
}
