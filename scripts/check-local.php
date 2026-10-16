<?php
require __DIR__.'/../vendor/autoload.php';
$app=require __DIR__.'/../bootstrap/app.php';$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$e=App\Models\Enquiry::where('email','local-qa@example.test')->latest()->first();
if(!$e){fwrite(STDERR,"Local QA enquiry not found.\n");exit(1);}
echo 'Local enquiry saved: yes'.PHP_EOL;
echo 'Agency email processed by '.config('mail.default').' transport: '.($e->agency_emailed_at?'yes':'pending').PHP_EOL;
echo 'Customer email processed by '.config('mail.default').' transport: '.($e->customer_emailed_at?'yes':'pending').PHP_EOL;
echo 'Properties in MySQL: '.App\Models\Property::count().PHP_EOL;
echo 'Journal stories in MySQL: '.App\Models\Post::count().PHP_EOL;
$e->update(['status'=>'closed','notes'=>'Local end-to-end verification. No external mail was sent; local log transport only.']);
