#!/usr/bin/env python3
"""Warqnaa V187 global-offline, rummy UX and engine-admin contract."""
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
    "jackaroo", "leekha",
]


def fail(message: str) -> None:
    raise SystemExit("[FAIL] " + message)


def read(relative: str) -> str:
    path = ROOT / relative
    if not path.is_file():
        fail("missing " + relative)
    return path.read_text(encoding="utf-8")


def main() -> None:
    release = json.loads(read("RELEASE_VERSION.json"))
    if release.get("full") != "0.3.6+187":
        fail("release metadata must be 0.3.6+187")

    main_source = read("flutter_app/lib/main.dart")
    block = re.search(r"const gamesCatalog = \[(.*?)\n\];", main_source, re.S)
    keys = re.findall(r"GameInfo\('([^']+)'", block.group(1) if block else "")
    if keys != EXPECTED:
        fail(f"Flutter catalog drift: {keys}")
    if "serverOnly" in main_source:
        fail("player catalog still contains a server-only gate")
    for needle in (
        "OFFLINE + ONLINE",
        "ReorderableListView.builder",
        "manualHandOrder",
        "_rummyMeldBoard",
        "تحديد مجموعة يدوياً",
        "تنزيل عدة مجموعات",
        "تركيب على مجموعة",
        "TabController(length: 7",
        "سلامة المحركات",
        "جميع الألعاب المحلية والبوتات تعمل دون خادم",
        "targetScore: widget.game.id == 'tarneeb_61' ? 61 : 41",
    ):
        if needle not in main_source:
            fail("missing Flutter V187 contract: " + needle)

    offline = read("flutter_app/lib/engines/offline_special_engines.dart")
    for needle in (
        "'domino' => _OfflineDomino",
        "'backgammon' => _OfflineBackgammon",
        "'solitaire_multiplayer' => _OfflineSolitaire",
        "'jackaroo' => _OfflineJackaroo",
        "'leekha' => _OfflineLeekha",
        "backgammon_max_dice_v187",
        "independent_klondike_v187",
        "jackaroo_partnership_v187",
        "leekha_partnership_101_v187",
        "dealerSeat",
        "score.reduce(max) >= 101",
    ):
        if needle not in offline:
            fail("missing offline engine contract: " + needle)

    local = read("flutter_app/lib/engines/local_game_engine.dart")
    for needle in (
        "createOfflineSpecialEngine(",
        "gameId == 'pinochle'",
        "_isBanakilFamily",
        "_special!.action",
        "_special!.timeout",
        "_discardDrawRequiresMeld",
        "_handPenalty",
        "_bestResolvedRummyRun",
    ):
        if needle not in local:
            fail("missing local routing contract: " + needle)

    player_counts = read("flutter_app/lib/v170_global.dart")
    if "return const [2, 3, 4, 5];" not in player_counts:
        fail("Hand room creation must expose all supported 2-5 player counts")

    registry = read("backend-laravel/app/Services/GameEngine/EngineRegistry.php")
    product = re.search(r"PRODUCT_KEYS\s*=\s*\[(.*?)\];", registry, re.S)
    registry_keys = re.findall(r"'([a-z0-9_]+)'", product.group(1) if product else "")
    if registry_keys != EXPECTED:
        fail(f"backend registry drift: {registry_keys}")
    for needle in ("dedicated_jackaroo_v187", "dedicated_leekha_v187", "warqnaa-2026.07-v187"):
        if needle not in registry:
            fail("missing backend V187 registry contract: " + needle)

    leekha = read("backend-laravel/app/Services/GameEngine/LeekhaRules.php")
    for needle in (
        "'Q_spades', '10_diamonds'",
        "'pass_cards'",
        "'team_score'",
        ">= 101",
        "Lower score wins",
        "individualThresholdReached",
        "dealer_index",
    ):
        if needle not in leekha:
            fail("missing Leekha rule guard: " + needle)

    controller = read("backend-laravel/app/Http/Controllers/MobileGameController.php")
    if "pass_status" not in controller or "unset($copy['pending_passes'])" not in controller:
        fail("Leekha pass selections are not redacted from opponents")

    regression = read("backend-laravel/tools/test-v183-engine-overhaul.php")
    if "$state['players'][$state['currentIndex']]['id']" not in regression:
        fail("Trix regression test still hard-codes p0 as the current player")

    clean_root = read("tools/validate_release.py")
    for legacy in (
        "FEATURES_IMPLEMENTED_AR.md",
        "README_AR.md",
        "START_WEB_DEMO.sh",
        "WARQNA_V142_REAL_ENGINES_FULLSTACK_AR.md",
        "web_demo",
    ):
        if legacy not in clean_root:
            fail("legacy clean-root compatibility missing: " + legacy)

    tests = read("flutter_app/test/offline_special_engines_test.dart")
    for game in ("Domino", "Backgammon", "Solitaire", "Jackaroo", "Leekha", "Classic Banakil"):
        if game not in tests:
            fail("offline Flutter regression missing: " + game)

    global_core = read(
        "backend-laravel/app/Services/GameEngine/GlobalEngines/GlobalCardEngineCore.php"
    )
    for needle in ("handRankPoints", "bestResolvedRummyRun", "handPenalty"):
        if needle not in global_core:
            fail("correct Hand point calculation missing: " + needle)

    for asset in ("jackaroo.png", "leekha.png"):
        if not (ROOT / "flutter_app/assets/images/games" / asset).is_file():
            fail("missing V187 game artwork: " + asset)

    print("[PASS] V187 20-game global/offline engines, rummy organization, secure Leekha and admin-health contract")


if __name__ == "__main__":
    main()
