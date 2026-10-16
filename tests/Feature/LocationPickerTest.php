<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{User,Property};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
class LocationPickerTest extends TestCase {
    use RefreshDatabase;
    private function admin():User{$u=User::factory()->create();$u->is_admin=true;$u->save();return $u;}
    private function data():array{return ['title'=>'Pinned property','slug'=>'pinned-property','area'=>'Baner','type'=>'rent','kind'=>'Apartment','rent'=>35000,'beds'=>2,'baths'=>2,'size'=>1000,'furnished'=>'Furnished','description'=>'Test property','map'=>'Baner, Pune','amenities'=>'Parking','photo_urls'=>'https://example.com/home.jpg','published'=>1];}
    public function test_search_is_protected_and_validates_query():void{$this->get('/admin/locations/search?q=Baner')->assertRedirect('/admin/login');$this->actingAs($this->admin())->getJson('/admin/locations/search?q=a')->assertUnprocessable();}
    public function test_search_returns_normalized_results_and_caches_provider_calls():void{
        Http::fake(['photon.komoot.io/*'=>Http::response(['features'=>[['geometry'=>['type'=>'Point','coordinates'=>[73.7868,18.559]],'properties'=>['name'=>'Baner','city'=>'Pune','state'=>'Maharashtra']]]])]);
        $this->actingAs($this->admin());for($i=0;$i<2;$i++)$this->getJson('/admin/locations/search?q=Baner')->assertOk()->assertJsonPath('results.0.latitude',18.559)->assertJsonPath('results.0.longitude',73.7868)->assertJsonPath('results.0.label','Baner, Pune, Maharashtra');
        Http::assertSentCount(1);
    }
    public function test_provider_failure_is_reported_without_breaking_property_editor():void{
        Http::fake(['photon.komoot.io/*'=>Http::response([],503)]);$this->actingAs($this->admin())->getJson('/admin/locations/search?q=Baner')->assertStatus(503)->assertJsonStructure(['message']);
        $this->get('/admin/properties/create')->assertOk()->assertSee('data-location-picker',false);
    }
    public function test_coordinates_are_saved_and_exposed_to_public_map_and_can_be_cleared():void{
        $this->actingAs($this->admin())->post('/admin/properties',[...$this->data(),'latitude'=>18.5590001,'longitude'=>73.7868002])->assertRedirect('/admin/properties');
        $p=Property::firstOrFail();$this->assertEqualsWithDelta(18.5590001,$p->latitude,.00000001);$this->assertEqualsWithDelta(73.7868002,$p->publicData()['longitude'],.00000001);
        $this->get('/admin/properties/'.$p->id.'/edit')->assertOk()->assertSee('18.5590001');
        $this->put('/admin/properties/'.$p->id,[...$this->data(),'latitude'=>'','longitude'=>''])->assertRedirect('/admin/properties');$this->assertNull($p->fresh()->latitude);$this->assertNull($p->fresh()->longitude);
    }
    public function test_half_pairs_and_out_of_range_coordinates_are_rejected():void{
        $this->actingAs($this->admin())->post('/admin/properties',[...$this->data(),'latitude'=>18.55])->assertSessionHasErrors('longitude');
        $this->post('/admin/properties',[...$this->data(),'latitude'=>91,'longitude'=>181])->assertSessionHasErrors(['latitude','longitude']);$this->assertDatabaseCount('properties',0);
    }
}
