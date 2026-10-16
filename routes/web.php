<?php

use Illuminate\Support\Facades\{Route,Storage};
use App\Http\Controllers\{WebsiteController,AuthController,AdminController,EnquiryController,PaymentController};
Route::get('/',WebsiteController::class)->name('home');
Route::get('/media/{folder}/{file}',function(string $folder,string $file){abort_unless(in_array($folder,['properties','journal'])&&preg_match('/^[a-zA-Z0-9_-]+\.(jpg|jpeg|png|webp)$/i',$file),404);abort_unless(Storage::disk('public')->exists($folder.'/'.$file),404);return Storage::disk('public')->response($folder.'/'.$file,null,['X-Content-Type-Options'=>'nosniff']);})->name('media');
Route::post('/enquiries',[EnquiryController::class,'store'])->middleware('throttle:5,1');
Route::post('/payments/orders',[PaymentController::class,'order'])->middleware('throttle:5,1');
Route::post('/payments/verify',[PaymentController::class,'verify'])->middleware('throttle:20,1');
Route::post('/payments/webhook',[PaymentController::class,'webhook'])->middleware('throttle:120,1');
Route::get('/admin/login',[AuthController::class,'form'])->name('login');
Route::post('/admin/login',[AuthController::class,'login'])->middleware('throttle:5,1');
Route::prefix('admin')->middleware('admin')->group(function(){
    Route::get('/locations/search',\App\Http\Controllers\LocationSearchController::class)->middleware('throttle:20,1');
    Route::get('/',[AdminController::class,'dashboard'])->name('admin');
    Route::post('/logout',[AuthController::class,'logout']);
    Route::put('/password',[AuthController::class,'password']);
    Route::get('/properties',[AdminController::class,'properties']);
    Route::get('/properties/create',[AdminController::class,'propertyForm']);
    Route::get('/properties/{property}/edit',[AdminController::class,'propertyForm']);
    Route::post('/properties',[AdminController::class,'saveProperty']);
    Route::put('/properties/{property}',[AdminController::class,'saveProperty']);
    Route::delete('/properties/{property}',[AdminController::class,'deleteProperty']);
    Route::get('/posts',[AdminController::class,'posts']);
    Route::get('/posts/create',[AdminController::class,'postForm']);
    Route::get('/posts/{post}/edit',[AdminController::class,'postForm']);
    Route::post('/posts',[AdminController::class,'savePost']);
    Route::put('/posts/{post}',[AdminController::class,'savePost']);
    Route::delete('/posts/{post}',[AdminController::class,'deletePost']);
    Route::get('/enquiries',[AdminController::class,'enquiries']);
    Route::get('/enquiries/{enquiry}',[AdminController::class,'enquiry']);
    Route::put('/enquiries/{enquiry}',[AdminController::class,'updateEnquiry']);
    Route::post('/enquiries/{enquiry}/retry',[AdminController::class,'retryEnquiry'])->middleware('throttle:5,1');
    Route::get('/settings',[AdminController::class,'settings']);
    Route::put('/settings',[AdminController::class,'saveSettings']);
    Route::get('/payments',[AdminController::class,'payments']);
    Route::post('/payment-requests',[AdminController::class,'createPaymentRequest']);
    Route::put('/payment-requests/{paymentRequest}/disable',[AdminController::class,'disablePaymentRequest']);
});
