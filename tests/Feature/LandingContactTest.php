<?php

namespace Tests\Feature;

use App\Models\LandingContactItem;
use App\Models\User;
use App\Services\LandingContacts;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LandingContactTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'session.driver' => 'array', 'cache.default' => 'array']);
        DB::purge('sqlite');
        // Only the new migration's schema, on an isolated in-memory database.
        (require database_path('migrations/2026_09_21_000002_create_landing_contact_items_table.php'))->up();
        (require database_path('migrations/2025_06_27_220837_create_permission_tables.php'))->up();
        Schema::create('users', function (Blueprint $table) { $table->id(); $table->string('name'); $table->timestamps(); });
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        foreach ([
            'landing_sliders' => ['eyebrow','title','description','secondary_text','button_text','button_url','image_path','mobile_image_path','image_alt','is_active','sort_order'],
            'projects' => ['company_id','name','code','district','province','department','status'],
            'project_web_profiles' => ['project_id','show_on_web','featured_on_home','sort_order'],
            'lots' => ['project_id','block_id','status','cash_price','area'],
            'blocks' => ['project_id','status'],
        ] as $name => $columns) {
            Schema::create($name, function (Blueprint $table) use ($columns) {
                $table->id(); foreach ($columns as $column) $table->string($column)->nullable(); $table->timestamps();
            });
        }
    }

    private function authorize(array $actions = ['index','store','show','update','toggle']): void
    {
        $user = User::create(['name' => 'Editor']);
        foreach ($actions as $action) {
            $permission = \Spatie\Permission\Models\Permission::firstOrCreate(['name'=>'admin.landing-contacts.'.$action,'guard_name'=>'web']);
            $user->givePermissionTo($permission);
        }
        $this->actingAs($user);
    }

    private function payload(array $override = []): array
    {
        return array_replace(['type'=>'whatsapp','label'=>'Ventas','value'=>'+51 972 873 511','url'=>null,'network'=>null,'is_primary'=>false,'use_for_cta'=>false,'show_in_footer'=>true,'sort_order'=>1,'is_active'=>true], $override);
    }

    private function item(array $override = []): LandingContactItem
    {
        return LandingContactItem::create($this->payload($override));
    }

    public function test_guest_cannot_enter_admin(): void
    {
        $this->get('/admin/landing-contacts')->assertRedirect('/login');
    }

    public function test_each_action_requires_permission(): void
    {
        $this->authorize([]); $item = $this->item();
        $this->getJson('/admin/landing-contacts')->assertForbidden();
        $this->postJson('/admin/landing-contacts', $this->payload())->assertForbidden();
        $this->getJson('/admin/landing-contacts/'.$item->id)->assertForbidden();
        $this->putJson('/admin/landing-contacts/'.$item->id, $this->payload())->assertForbidden();
        $this->patchJson('/admin/landing-contacts/'.$item->id.'/toggle')->assertForbidden();
    }

    public function test_creates_all_five_types_and_normalizes_links(): void
    {
        $this->authorize();
        foreach ([
            ['whatsapp','+51 (972) 873-511',null,null,'https://wa.me/51972873511'],
            ['phone','+51 (964) 796-708',null,null,'tel:+51964796708'],
            ['email','ventas@example.com',null,null,'mailto:ventas@example.com'],
            ['address',"Dirección\nTarapoto",null,null,null],
            ['social',null,'https://example.com/krea','linkedin','https://example.com/krea'],
        ] as [$type,$value,$url,$network,$href]) {
            $response = $this->postJson('/admin/landing-contacts',$this->payload(compact('type','value','url','network')))->assertCreated();
            $item = LandingContactItem::findOrFail($response->json('data.id'));
            $this->assertSame($href, $item->href());
            if ($value !== null) $this->assertSame($value, $item->value);
        }
    }

    public function test_admin_page_and_detail_render_without_interpreting_contact_html(): void
    {
        $this->authorize();
        $item = $this->item(['label'=>'</script><script>alert(1)</script>']);
        $this->get('/admin/landing-contacts')->assertOk()->assertSee('contact-module')->assertDontSee('</script><script>alert(1)</script>',false);
        $this->getJson('/admin/landing-contacts/'.$item->id)->assertOk()->assertJsonPath('data.label',$item->label);
        $this->putJson('/admin/landing-contacts/'.$item->id,$this->payload(['sort_order'=>4]))->assertOk()->assertJsonPath('data.sort_order',4);
    }

    public function test_invalid_email_social_urls_network_and_phone_rejected(): void
    {
        $this->authorize();
        $this->postJson('/admin/landing-contacts',$this->payload(['type'=>'email','value'=>'javascript:alert(1)']))->assertUnprocessable()->assertJsonValidationErrors('value');
        foreach ([null,'javascript:alert(1)','data:text/html,test','http://example.com','not-url'] as $url) {
            $this->postJson('/admin/landing-contacts',$this->payload(['type'=>'social','network'=>'facebook','url'=>$url]))->assertUnprocessable()->assertJsonValidationErrors('url');
        }
        $this->postJson('/admin/landing-contacts',$this->payload(['type'=>'social','url'=>'https://example.com','network'=>null]))->assertUnprocessable()->assertJsonValidationErrors('network');
        $this->postJson('/admin/landing-contacts',$this->payload(['value'=>'<script>123456789</script>']))->assertUnprocessable()->assertJsonValidationErrors('value');
    }

    public function test_only_whatsapp_can_be_cta(): void
    {
        $this->authorize();
        foreach (['phone','email','address','social'] as $type) {
            $this->postJson('/admin/landing-contacts',$this->payload(['type'=>$type,'use_for_cta'=>true]))->assertUnprocessable()->assertJsonValidationErrors('use_for_cta');
        }
    }

    public function test_replacing_and_editing_cta_preserves_single_selection(): void
    {
        $this->authorize();
        $first = $this->item(['use_for_cta'=>true]);
        $created = $this->postJson('/admin/landing-contacts',$this->payload(['use_for_cta'=>true,'value'=>'+51 999 888 777']))->assertCreated();
        $id = $created->json('data.id');
        $this->assertFalse($first->fresh()->use_for_cta);
        $this->putJson('/admin/landing-contacts/'.$id,$this->payload(['use_for_cta'=>true,'label'=>'Editado']))->assertOk()->assertJsonPath('data.use_for_cta',true);
        $this->assertSame(1,LandingContactItem::where('use_for_cta',true)->count());
        $this->patchJson('/admin/landing-contacts/'.$id.'/toggle')->assertOk()->assertJsonPath('data.is_active',false);
        $this->assertSame(preg_replace('/\D/','',config('landing.whatsapp')), (new LandingContacts)->whatsapp());
        $this->patchJson('/admin/landing-contacts/'.$id.'/toggle')->assertOk()->assertJsonPath('data.is_active',true);
    }

    public function test_database_constraint_prevents_duplicate_cta(): void
    {
        $this->item(['use_for_cta'=>true]);
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->item(['use_for_cta'=>true]);
    }

    public function test_active_footer_contacts_and_socials_are_filtered_and_ordered(): void
    {
        $this->item(['type'=>'address','value'=>'TERCERO','sort_order'=>3]);
        $this->item(['type'=>'address','value'=>'PRIMERO','sort_order'=>1]);
        $this->item(['type'=>'address','value'=>'SEGUNDO','sort_order'=>1]);
        $this->item(['type'=>'address','value'=>'INACTIVO-OCULTO','is_active'=>false]);
        $this->item(['type'=>'address','value'=>'FOOTER-OCULTO','show_in_footer'=>false]);
        $this->item(['type'=>'social','value'=>'Red visible','network'=>'facebook','url'=>'https://example.com/visible']);
        $this->item(['type'=>'social','value'=>'Red oculta','network'=>'instagram','url'=>'https://example.com/oculta','is_active'=>false]);
        $this->get('/')->assertOk()->assertSeeInOrder(['PRIMERO','SEGUNDO','TERCERO'])->assertDontSee('INACTIVO-OCULTO')->assertDontSee('FOOTER-OCULTO')->assertSee('https://example.com/visible')->assertDontSee('https://example.com/oculta');
    }

    public function test_landing_uses_database_cta_even_when_hidden_in_footer(): void
    {
        $this->item(['value'=>'+51 999 123 456','use_for_cta'=>true,'show_in_footer'=>false]);
        $this->get('/')
            ->assertOk()
            ->assertSee('https://wa.me/51999123456')
            ->assertSee('href="tel:+51999123456"', false)
            ->assertSee('+51 999 123 456')
            ->assertDontSee('Síguenos')
            ->assertDontSee(config('landing.phone'));
    }

    public function test_empty_and_inactive_table_use_config_fallback(): void
    {
        $fallbackWhatsapp = preg_replace('/\D/', '', (string) config('landing.whatsapp'));
        $fallbackTel = str_starts_with($fallbackWhatsapp, '51') ? '+'.$fallbackWhatsapp : $fallbackWhatsapp;

        $this->get('/')
            ->assertOk()
            ->assertSee('href="tel:'.$fallbackTel.'"', false)
            ->assertSee(config('landing.email'))
            ->assertSee('https://wa.me/'.$fallbackWhatsapp);

        $this->item(['is_active'=>false,'use_for_cta'=>true]);
        $this->get('/')->assertOk()->assertSee('href="tel:'.$fallbackTel.'"', false);
    }

    public function test_missing_table_uses_config_fallback(): void
    {
        Schema::drop('landing_contact_items');
        $fallbackWhatsapp = preg_replace('/\D/', '', (string) config('landing.whatsapp'));
        $fallbackTel = str_starts_with($fallbackWhatsapp, '51') ? '+'.$fallbackWhatsapp : $fallbackWhatsapp;
        $this->get('/')->assertOk()->assertSee('href="tel:'.$fallbackTel.'"', false);
    }

    public function test_active_contact_without_cta_keeps_config_destination(): void
    {
        $this->item(['type'=>'email','value'=>'new@example.com']);
        $this->get('/')->assertOk()->assertSee('mailto:new@example.com')->assertSee('https://wa.me/'.config('landing.whatsapp'))->assertDontSee('Síguenos');
    }

    public function test_content_is_escaped_and_unsafe_saved_urls_not_rendered(): void
    {
        $this->item(['type'=>'address','value'=>'<script>alert(1)</script>','url'=>'javascript:alert(1)']);
        $this->item(['type'=>'social','value'=>'Unsafe','network'=>'other','url'=>'data:text/html,test']);
        $this->get('/')->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false)->assertDontSee('href="javascript:',false)->assertDontSee('data:text/html,test');
    }

    public function test_resolver_queries_contacts_only_once(): void
    {
        $this->item(); DB::enableQueryLog();
        $resolver = new LandingContacts; $resolver->footer(); $resolver->whatsapp(); $resolver->whatsapp();
        $queries = collect(DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'select * from "landing_contact_items"'));
        $this->assertCount(1,$queries); DB::disableQueryLog();
    }
}
