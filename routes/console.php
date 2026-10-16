<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('agency:admin {email?} {--local}', function () {
    if($this->option('local')&&!app()->environment('local')){$this->error('Local credentials can only be generated in a local environment.');return 1;}
    $email=$this->argument('email') ?: 'admin@pehchanvala.local';
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) {$this->error('Enter a valid email.');return 1;}
    if (\App\Models\User::where('email',$email)->exists()) {$this->info('User already exists; password unchanged.');return 0;}
    $password=$this->option('local') ? \Illuminate\Support\Str::password(24) : $this->secret('Password (12+ characters, mixed case and a number)');
    if(strlen($password)<12||!preg_match('/[A-Z]/',$password)||!preg_match('/[a-z]/',$password)||!preg_match('/[0-9]/',$password)){$this->error('Choose a stronger password.');return 1;}
    $user=new \App\Models\User(['name'=>'Agency administrator','email'=>$email,'password'=>$password]);$user->is_admin=true;$user->save();
    if($this->option('local')) {if(!app()->environment('local'))throw new \RuntimeException('Local credential export is for local environments only.');file_put_contents(base_path('.local-admin.txt'),"Pehchanvala local CMS\nURL: http://127.0.0.1:8000/admin\nEmail: $email\nPassword: $password\n\nChange the password in Settings after signing in. Do not share this file.\n");$this->info('Local admin created. Credentials saved to the ignored .local-admin.txt file.');}
    else $this->info('Administrator created.');
})->purpose('Create an agency administrator; local mode saves a generated password to an ignored file.');

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');
