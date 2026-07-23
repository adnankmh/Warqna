<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Games\GameCatalog;
use App\Services\GameEngine\{EngineRegistry,GameFactory};

class V122CatalogAndEnginesTest extends TestCase
{
    public function test_current_curated_catalog_matches_product_contract(): void
    {
        $games=GameCatalog::all();
        $expected=EngineRegistry::PRODUCT_KEYS;
        $actual=array_keys($games);
        sort($expected); sort($actual);
        $this->assertSame($expected,$actual);
        $this->assertCount(18,$games);
        foreach(['ludo','jackaroo','chess'] as $future) $this->assertArrayNotHasKey($future,$games);
    }

    public function test_every_curated_game_has_a_real_engine_object(): void
    {
        foreach(array_keys(GameCatalog::all()) as $key){
            $engine=GameFactory::make($key);
            $this->assertTrue(method_exists($engine,'initialState'),$key.' engine has no initialState');
            $this->assertTrue(method_exists($engine,'validate'),$key.' engine has no validate');
            $this->assertTrue(method_exists($engine,'apply'),$key.' engine has no apply');
        }
    }
}
