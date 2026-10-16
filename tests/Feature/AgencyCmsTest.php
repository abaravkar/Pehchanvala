<?php
namespace Tests\Feature;
use App\Models\{User,Property,Post,Enquiry,PaymentRequest,Payment,Setting};
use App\Jobs\{SendEnquiryEmails,SendPaymentEmails};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\{Storage,Queue,Mail,Http};
use Tests\TestCase;
class AgencyCmsTest extends TestCase {
    use RefreshDatabase;
    private function admin():User{$u=User::factory()->create();$u->is_admin=true;$u->save();return $u;}
    private function property(array $extra=[]):Property{return Property::create(array_merge(['slug'=>'a-test-home','title'=>'A test home','area'=>'Baner','type'=>'rent','kind'=>'Apartment','rent'=>30000,'beds'=>2,'baths'=>2,'size'=>1000,'furnished'=>'Semi furnished','description'=>'A home for testing.','amenities'=>['Parking'],'images'=>['https://images.unsplash.com/test.jpg'],'map'=>'Baner, Pune','published'=>true],$extra));}
    private function invoice():PaymentRequest{return PaymentRequest::create(['reference'=>'PV-TESTREFERENCE','purpose'=>'Service fee','amount'=>250000,'customer_email'=>'customer@example.com','expires_at'=>now()->addDay()]);}
    private function payment(PaymentRequest $invoice):Payment{return Payment::create(['payment_request_id'=>$invoice->id,'order_id'=>'order_test123','session_hash'=>hash('sha256','test-session'),'name'=>'Customer','email'=>'customer@example.com','phone'=>'9876543210','amount'=>$invoice->amount,'currency'=>'INR']);}
    public function test_admin_is_protected_and_nonadmins_are_forbidden():void{$this->get('/admin')->assertRedirect('/admin/login');$this->actingAs(User::factory()->create())->get('/admin/properties')->assertForbidden();$this->post('/admin/login',['email'=>'nobody@example.com','password'=>'wrong'])->assertSessionHasErrors('email');}
    public function test_admin_login_and_logout_work():void{$u=$this->admin();$this->post('/admin/login',['email'=>$u->email,'password'=>'password'])->assertRedirect('/admin');$this->assertAuthenticatedAs($u);$this->post('/admin/logout')->assertRedirect('/admin/login');$this->assertGuest();}
    public function test_all_admin_pages_render():void{$this->actingAs($this->admin());foreach(['/admin','/admin/properties','/admin/properties/create','/admin/posts','/admin/posts/create','/admin/enquiries','/admin/payments','/admin/settings'] as $url)$this->get($url)->assertOk();}
    public function test_uploaded_property_is_persisted_published_and_archive_removes_it():void{
        Storage::fake('public');$this->actingAs($this->admin());
        $response=$this->post('/admin/properties',['slug'=>'uploaded-home','title'=>'Uploaded home','area'=>'Baner','type'=>'pg','kind'=>'PG','rent'=>10000,'beds'=>2,'baths'=>1,'size'=>250,'furnished'=>'Furnished','description'=>'Shared room','map'=>'Baner Pune','sharing'=>'Twin sharing','deposit'=>20000,'meals'=>'Included','amenities'=>'Wi-Fi, Meals','published'=>1,'available'=>'2026-12-01','photos'=>[UploadedFile::fake()->image('room.jpg')]]);
        $response->assertRedirect('/admin/properties');$p=Property::where('slug','uploaded-home')->firstOrFail();$this->assertCount(1,$p->images);Storage::disk('public')->assertExists('properties/'.basename($p->images[0]));
        $this->get('/')->assertOk()->assertSee('uploaded-home');$this->get('/admin/properties/'.$p->id.'/edit')->assertOk();
        $this->delete('/admin/properties/'.$p->id)->assertRedirect();$this->assertSoftDeleted($p);$this->get('/')->assertDontSee('uploaded-home');
    }
    public function test_pg_and_sale_prices_are_validated_and_drafts_are_private():void{
        $this->actingAs($this->admin())->post('/admin/properties',['type'=>'pg'])->assertSessionHasErrors(['rent','sharing']);
        $this->property(['published'=>false,'title'=>'Private draft secret']);$this->get('/')->assertOk()->assertDontSee('Private draft secret');
    }
    public function test_blog_upload_and_edit_are_persisted():void{
        Storage::fake('public');$this->actingAs($this->admin());$this->post('/admin/posts',['title'=>'A Pune story','slug'=>'a-pune-story','category'=>'Pune','summary'=>'A short intro','body'=>"Paragraph one.\n\nParagraph two.",'published'=>1,'cover'=>UploadedFile::fake()->image('cover.png')])->assertRedirect('/admin/posts');
        $p=Post::firstOrFail();$this->get('/admin/posts/'.$p->id.'/edit')->assertOk();
        $this->put('/admin/posts/'.$p->id,['title'=>$p->title,'slug'=>$p->slug,'category'=>$p->category,'summary'=>$p->summary,'body'=>'Updated story','image'=>$p->image,'published'=>1])->assertRedirect('/admin/posts');
        $this->assertSame('Updated story',$p->fresh()->body);$this->delete('/admin/posts/'.$p->id);$this->assertFalse($p->fresh()->published);
    }
    public function test_enquiry_persists_and_queues_two_confirmations():void{
        Queue::fake();$p=$this->property();$data=['name'=>'Test customer','phone'=>'9876543210','email'=>'customer@example.com','interest'=>'A home to rent','propertyReference'=>$p->slug,'message'=>'Please arrange a viewing.','consent'=>'on'];
        $this->postJson('/enquiries',$data)->assertCreated()->assertJsonStructure(['id']);$e=Enquiry::firstOrFail();$this->assertEquals($p->id,$e->property_id);Queue::assertPushed(SendEnquiryEmails::class);
        Mail::fake();(new SendEnquiryEmails($e->id))->handle();$this->assertNotNull($e->fresh()->agency_emailed_at);$this->assertNotNull($e->fresh()->customer_emailed_at);Mail::assertSentCount(2);
        (new SendEnquiryEmails($e->id))->handle();Mail::assertSentCount(2);
        $this->postJson('/enquiries',array_merge($data,['consent'=>false]))->assertUnprocessable();
    }
    public function test_enquiry_followup_notes_are_admin_only():void{$e=Enquiry::create(['reference'=>(string)\Illuminate\Support\Str::uuid(),'name'=>'Customer','phone'=>'9876543210','email'=>'customer@example.com','interest'=>'Rent','consented_at'=>now()]);$this->actingAs($this->admin());$this->get('/admin/enquiries/'.$e->id)->assertOk();$this->put('/admin/enquiries/'.$e->id,['status'=>'contacted','notes'=>'Called and discussed a visit.'])->assertRedirect();$this->assertSame('contacted',$e->fresh()->status);}
    public function test_disabled_payments_never_call_razorpay():void{Http::fake();$this->postJson('/payments/orders',[])->assertStatus(503);Http::assertNothingSent();}
    public function test_amount_tampering_is_rejected_before_provider_request():void{
        config(['agency.payments_enabled'=>true,'agency.razorpay_key'=>'rzp_test_example','agency.razorpay_secret'=>'test-secret']);Setting::create(['key'=>'payments_policy_approved','value'=>'1']);$i=$this->invoice();Http::fake();
        $this->postJson('/payments/orders',['name'=>'Customer','email'=>'customer@example.com','phone'=>'9876543210','purpose'=>'Service fee','amount'=>100,'reference'=>$i->reference])->assertUnprocessable();Http::assertNothingSent();
    }
    public function test_valid_order_uses_server_invoice_amount():void{
        config(['agency.payments_enabled'=>true,'agency.razorpay_key'=>'rzp_test_example','agency.razorpay_secret'=>'test-secret']);Setting::create(['key'=>'payments_policy_approved','value'=>'1']);$i=$this->invoice();Http::fake(['api.razorpay.com/v1/orders'=>Http::response(['id'=>'order_created123','amount'=>250000,'currency'=>'INR'])]);
        $data=['name'=>'Customer','email'=>'customer@example.com','phone'=>'9876543210','purpose'=>'Service fee','amount'=>250000,'reference'=>$i->reference];
        $this->postJson('/payments/orders',$data)->assertOk()->assertJson(['id'=>'order_created123']);$this->assertDatabaseHas('payments',['amount'=>250000]);Http::assertSent(fn($r)=>$r['amount']===250000&&$r['currency']==='INR');
    }
    public function test_signed_webhook_captures_once_and_rejects_wrong_signature():void{
        Queue::fake();config(['agency.razorpay_webhook_secret'=>'webhook-secret']);$i=$this->invoice();$p=$this->payment($i);
        $payload=['event'=>'payment.captured','payload'=>['payment'=>['entity'=>['id'=>'pay_test123','order_id'=>$p->order_id,'amount'=>250000,'currency'=>'INR','status'=>'captured']]]];$body=json_encode($payload);$signature=hash_hmac('sha256',$body,'webhook-secret');
        $this->call('POST','/payments/webhook',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_RAZORPAY_SIGNATURE'=>'wrong'],$body)->assertForbidden();
        for($n=0;$n<2;$n++)$this->call('POST','/payments/webhook',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_RAZORPAY_SIGNATURE'=>$signature,'HTTP_X_RAZORPAY_EVENT_ID'=>'evt-test'],$body)->assertOk();
        $this->assertSame('captured',$p->fresh()->status);$this->assertNotNull($i->fresh()->paid_at);Queue::assertPushed(SendPaymentEmails::class,1);$this->assertDatabaseCount('webhook_events',1);
    }
    public function test_mismatched_capture_never_marks_payment_paid():void{
        Queue::fake();config(['agency.razorpay_webhook_secret'=>'secret']);$p=$this->payment($this->invoice());$body=json_encode(['event'=>'payment.captured','payload'=>['payment'=>['entity'=>['id'=>'pay_test123','order_id'=>$p->order_id,'amount'=>1,'currency'=>'INR','status'=>'captured']]]]);
        $this->call('POST','/payments/webhook',[],[],[],['CONTENT_TYPE'=>'application/json','HTTP_X_RAZORPAY_SIGNATURE'=>hash_hmac('sha256',$body,'secret')],$body)->assertUnprocessable();$this->assertSame('created',$p->fresh()->status);Queue::assertNothingPushed();
    }
    public function test_verification_cannot_be_used_by_another_session():void{$p=$this->payment($this->invoice());$this->postJson('/payments/verify',['razorpay_order_id'=>$p->order_id,'razorpay_payment_id'=>'pay_test123','razorpay_signature'=>str_repeat('a',64)])->assertForbidden();}
    public function test_invalid_policy_approval_does_not_partially_save_settings():void{
        Setting::create(['key'=>'agency_email','value'=>'original@example.com']);
        $this->actingAs($this->admin())->put('/admin/settings',['agency_email'=>'changed@example.com','payments_policy_approved'=>1,'policy_privacy'=>'Privacy only'])->assertSessionHasErrors('payments_policy_approved');
        $this->assertSame('original@example.com',Setting::read('agency_email'));$this->assertSame('',Setting::read('policy_privacy'));
    }
}
