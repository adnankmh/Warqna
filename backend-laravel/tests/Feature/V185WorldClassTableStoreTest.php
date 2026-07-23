<?php

namespace Tests\Feature;

use App\Models\{InventoryItem,Profile,StoreItem,User,Wallet};
use App\Services\WarqnaPro\StoreCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class V185WorldClassTableStoreTest extends TestCase
{
    use RefreshDatabase;

    private function player(string $username): User
    {
        $user=User::create([
            'username'=>$username,
            'email'=>$username.'@example.test',
            'password'=>Hash::make('password123'),
        ]);
        Profile::create([
            'user_id'=>$user->id,
            'display_name'=>$username,
            'country_code'=>'PS',
            'country_name'=>'Palestine',
        ]);
        Wallet::create(['user_id'=>$user->id,'tokens'=>100000,'gems'=>0]);
        return $user->fresh(['profile','wallet']);
    }

    public function test_reference_tables_are_public_after_catalog_sync(): void
    {
        app(StoreCatalogService::class)->sync();

        $reference=StoreItem::query()
            ->where('category','table')
            ->where('key','like','table_reference_%')
            ->orderBy('key')
            ->get();

        $this->assertCount(40,$reference);
        $this->assertTrue($reference->every(fn(StoreItem $item)=>(bool)$item->active));
        $this->assertSame('table_reference_01',$reference->first()->key);
        $this->assertSame('table_reference_40',$reference->last()->key);
    }

    public function test_owned_table_activation_is_server_authoritative(): void
    {
        $user=$this->player('v185tables');
        app(StoreCatalogService::class)->sync();
        $first=StoreItem::where('key','table_reference_01')->firstOrFail();
        $second=StoreItem::where('key','table_reference_02')->firstOrFail();
        $firstInventory=InventoryItem::create([
            'user_id'=>$user->id,
            'store_item_id'=>$first->id,
            'active'=>false,
        ]);
        $secondInventory=InventoryItem::create([
            'user_id'=>$user->id,
            'store_item_id'=>$second->id,
            'active'=>true,
            'activated_at'=>now(),
        ]);

        Sanctum::actingAs($user);
        $response=$this->postJson('/api/mobile/v1/store/activate',['key'=>'table_reference_01']);

        $response->assertOk()->assertJson([
            'ok'=>true,
            'category'=>'table',
        ]);
        $this->assertTrue((bool)$firstInventory->fresh()->active);
        $this->assertFalse((bool)$secondInventory->fresh()->active);
        $this->assertSame(
            data_get($first->payload,'table','table_reference_01'),
            $user->profile()->firstOrFail()->active_table_skin,
        );
    }

    public function test_unowned_table_cannot_be_activated_by_a_forged_client_request(): void
    {
        $user=$this->player('v185forged');
        app(StoreCatalogService::class)->sync();

        Sanctum::actingAs($user);
        $this->postJson('/api/mobile/v1/store/activate',['key'=>'table_reference_40'])
            ->assertNotFound()
            ->assertJson(['ok'=>false]);

        $this->assertNull($user->profile()->firstOrFail()->active_table_skin);
        $this->assertDatabaseMissing('inventory_items',[
            'user_id'=>$user->id,
            'store_item_id'=>StoreItem::where('key','table_reference_40')->firstOrFail()->id,
        ]);
    }
}
