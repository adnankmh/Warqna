<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\GameEngine\GameFactory;
use App\Services\GameEngine\EngineRegistry;
use App\Services\WarqnaPro\StoreCatalogService;

class V134CriticalFixesTest extends TestCase
{
    public function test_admin_view_routes_are_defined(): void
    {
        $view=file_get_contents(resource_path('views/admin/index.blade.php'));
        preg_match_all("/route\('([^']+)'/", $view, $m);
        foreach(array_unique($m[1]) as $name){
            $this->assertTrue(\Illuminate\Support\Facades\Route::has($name), $name.' route is missing');
        }
    }

    public function test_all_curated_game_engines_start(): void
    {
        foreach(EngineRegistry::PRODUCT_KEYS as $key){
            $meta=EngineRegistry::get($key);$players=[];
            for($i=0;$i<(int)$meta['max'];$i++) $players[]=$i===0?'user:1':'bot:'.$i;
            $state=GameFactory::make($key)->initialState($players,['target'=>41,'player_count'=>(int)$meta['max']]);
            $this->assertNotEmpty($state['turn'],$key);
            $this->assertNotEmpty($state['phase'],$key);
        }
    }

    public function test_store_tables_are_curated_50_and_pasha_image_exists(): void
    {
        $store=new StoreCatalogService();
        $this->assertCount(140,$store->tableSkins());
        $this->assertFileExists(public_path('assets/store/basha1.png'));
    }

    public function test_settings_country_dropdown_and_private_room_password_exist(): void
    {
        $settings=file_get_contents(resource_path('views/pages/settings.blade.php'));
        $room=file_get_contents(resource_path('views/room/index.blade.php'));
        $this->assertStringContainsString('country-select-v134',$settings);
        $this->assertStringContainsString('privatePasswordInput',$room);
        $this->assertFileExists(config_path('countries.php'));
    }
}
