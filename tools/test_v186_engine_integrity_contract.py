#!/usr/bin/env python3
"""Static release contract for the Warqnaa V186 18-engine audit."""
from __future__ import annotations

import json
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
EXPECTED = [
    "tarneeb", "trix", "hand", "banakil", "baloot", "basra",
    "tarneeb_400", "syrian_tarneeb", "trix_complex", "saudi_hand",
    "hand_partner", "trix_partner", "tarneeb_41", "tarneeb_61",
    "pinochle", "solitaire_multiplayer", "domino", "backgammon",
]


def fail(message: str) -> None:
    raise SystemExit("[FAIL] " + message)


def text(rel: str) -> str:
    path = ROOT / rel
    if not path.is_file():
        fail("missing " + rel)
    return path.read_text(encoding="utf-8")


def quoted_keys(block: str) -> list[str]:
    return re.findall(r"'([a-z0-9_]+)'", block)


def main() -> None:
    release = json.loads(text("RELEASE_VERSION.json"))
    if release.get("full") != "0.3.5+186":
        fail("release metadata must be 0.3.5+186")

    flutter = text("flutter_app/lib/main.dart")
    match = re.search(r"const gamesCatalog = \[(.*?)\n\];", flutter, re.S)
    if not match:
        fail("Flutter gamesCatalog is missing")
    flutter_keys = re.findall(r"GameInfo\('([^']+)'", match.group(1))
    if flutter_keys != EXPECTED:
        fail(f"Flutter catalog drift: {flutter_keys}")

    registry = text("backend-laravel/app/Services/GameEngine/EngineRegistry.php")
    product = re.search(r"PRODUCT_KEYS\s*=\s*\[(.*?)\];", registry, re.S)
    if not product or quoted_keys(product.group(1)) != EXPECTED:
        fail("EngineRegistry::PRODUCT_KEYS must exactly mirror Flutter")
    registry_keys = re.findall(r"^\s{12}'([a-z0-9_]+)'\s*=>\s*self::entry", registry, re.M)
    if set(registry_keys) != set(EXPECTED) or len(registry_keys) != 18:
        fail(f"registry entries drift: {registry_keys}")

    catalog = text("backend-laravel/app/Services/Games/GameCatalog.php")
    catalog_keys = re.findall(r"^\s{12}'([a-z0-9_]+)'\s*=>\s*\[", catalog, re.M)
    if set(catalog_keys) != set(EXPECTED) or len(catalog_keys) != 18:
        fail(f"Laravel catalog drift: {catalog_keys}")
    if "'basra'=>[" not in catalog or "'targets'=>[121]" not in catalog:
        fail("Basra must use the audited 121 target")

    factory = text("backend-laravel/app/Services/GameEngine/GameFactory.php")
    for key in EXPECTED:
        if f"'{key}'" not in factory:
            fail(f"GameFactory route missing: {key}")
    if "default => throw new \\InvalidArgumentException" not in factory:
        fail("GameFactory must fail closed for unknown game ids")

    tarneeb = text("backend-laravel/app/Services/GameEngine/TarneebRules.php")
    for needle in ("public function availableActions", "deal_commitment", "deal_reveal"):
        if needle not in tarneeb:
            fail("Tarneeb action/fairness contract missing: " + needle)

    core = text("backend-laravel/app/Services/GameEngine/GlobalEngines/GlobalCardEngineCore.php")
    for needle in (
        "bin2hex(random_bytes(32))", "'7_H'", "turnDrawSource",
        "بعد سحب الورقة المكشوفة يجب تنزيل", "independent 52-card Klondike deal",
        "validSolitaireRun", "handPenalty", "buyRound", "balootRoundPoints",
        "doubleTrixCard", "finishTrixDoubling", "doubledCards", "pendingHokmBuyer", "confirm_hokm",
    ):
        if needle not in core:
            fail(f"engine rule guard missing: {needle}")

    adapter = text("backend-laravel/app/Services/GameEngine/GlobalCardEngineRules.php")
    for needle in ("$this->engine->applyAction($g,$playerId,$a);", "stock_counts", "down_count", "move_to_tableau"):
        if needle not in adapter:
            fail(f"global adapter guard missing: {needle}")

    controller = text("backend-laravel/app/Http/Controllers/MobileGameController.php")
    if controller.count("lockForUpdate()") < 3 or "stale_game_state" not in controller:
        fail("action and timeout paths must use row locks and revision rejection")
    for private_key in ("'_tarneeb_v2'", "'_global_engine'"):
        if private_key not in controller:
            fail("public state must remove " + private_key)
    web_controller = text("backend-laravel/app/Http/Controllers/RoomController.php")
    if web_controller.count("lockForUpdate()") < 2 or "DB::transaction(function() use($room" not in web_controller:
        fail("web action and timeout paths must share the database room lock")

    audit = text("backend-laravel/tools/test-v186-engine-integrity.php")
    for needle in ("EngineRegistry::PRODUCT_KEYS", "forged_game_key", "Solitaire", "Basra", "Domino", "Backgammon"):
        if needle not in audit:
            fail("standalone audit scenario missing: " + needle)

    print("[PASS] V186 exact 18-game catalog, rule guards, fail-closed routing, privacy and concurrency contract")


if __name__ == "__main__":
    main()
