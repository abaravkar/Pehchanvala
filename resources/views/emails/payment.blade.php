<h1>Payment confirmed · Pehchanvala</h1>
<p>Reference: {{ $payment->paymentRequest->reference }}<br>Purpose: {{ $payment->paymentRequest->purpose }}<br>Amount: ₹{{ number_format($payment->amount / 100,2) }}<br>Razorpay payment: {{ $payment->payment_id }}</p>
<p>This confirms receipt of a captured payment. Property availability and booking terms are governed by the separate agreement with the agency.</p>
