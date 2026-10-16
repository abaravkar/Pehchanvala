<h1>{{ $customer ? 'Thank you for getting in touch.' : 'A new enquiry has arrived.' }}</h1>
<p>{{ $customer ? 'Your enquiry is safely recorded. The Pehchanvala team will follow up with you.' : 'View and manage this enquiry in your agency CMS.' }}</p>
<p>Reference: {{ $enquiry->reference }}</p>
<p>Name: {{ $enquiry->name }}<br>Email: {{ $enquiry->email }}<br>Phone: {{ $enquiry->phone }}<br>Interest: {{ $enquiry->interest }}<br>Property: {{ $enquiry->property_title }}<br>Neighbourhood: {{ $enquiry->locality }}</p>
<p style="white-space:pre-wrap">{{ $enquiry->message }}</p>
<p>An enquiry does not confirm property availability or a booking.</p>
