#!/usr/bin/env python3
"""Warqnaa V185 world-class table marketplace and secure activation contract."""
from __future__ import annotations

import json
import hashlib
import struct
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def fail(message: str) -> None:
    raise SystemExit("[FAIL] " + message)


def require(rel: str, *needles: str) -> str:
    path = ROOT / rel
    if not path.is_file():
        fail(f"missing {rel}")
    source = path.read_text(encoding="utf-8")
    for needle in needles:
        if needle not in source:
            fail(f"missing {needle!r} in {rel}")
    return source


def main() -> None:
    meta = json.loads((ROOT / "RELEASE_VERSION.json").read_text(encoding="utf-8"))
    if meta.get("full") != "0.3.5+186":
        fail("release must be 0.3.5+186 while retaining the V185 table-store feature")

    main_source = require(
        "flutter_app/lib/main.dart",
        "part 'v185_world_class.dart';",
        "WorldClassTableStoreV185(",
        "this.initialCategory = 'tables'",
        "Future<bool> activateProduct",
        "warqnaProductionMode && !serverConnected",
        "ReferenceCatalogTablePreviewV185(product: skin, sourceRect: catalogRect)",
    )
    if "if (p.id.startsWith('table_reference_')) return false;" in main_source:
        fail("approved V185 reference tables are still hidden")
    for approved_name in (
        "Gilded Inlaid Yacht",
        "Classic Chrome Jet",
        "Inlaid Jewel Wood",
        "Musical Panel",
        "Polished Black Sword",
        "Stone Black Buddha",
        "Polar Leather Panel",
    ):
        if approved_name not in main_source:
            fail(f"approved reference-table name is missing: {approved_name}")

    marketplace = require(
        "flutter_app/lib/v185_world_class.dart",
        "class WorldClassTableStoreV185",
        "class ReferenceCatalogTablePreviewV185",
        "referenceTableCatalogRectV185",
        "CustomScrollView",
        "SliverGrid",
        "table_reference_",
        "reference_1",
        "reference_4",
        "v173_royal",
        "v173_showcase",
        "controller.activateProduct(product)",
        "server-authoritative",
    )
    for language in ("ar", "en", "de", "tr", "fr", "es"):
        if f"'{language}':" not in marketplace:
            fail(f"V185 marketplace translation is missing: {language}")

    require(
        "flutter_app/pubspec.yaml",
        "assets/images/tables/reference/",
    )
    require(
        "flutter_app/lib/v183_overhaul.dart",
        "WarqnaTableAssetV185(",
        "availableWidth.clamp(1.0",
    )
    catalog_asset = ROOT / "flutter_app/assets/images/tables/reference/reference_catalog_v185.png"
    if not catalog_asset.is_file():
        fail("the exact V185 catalog reference image is missing")
    catalog_bytes = catalog_asset.read_bytes()
    if hashlib.sha256(catalog_bytes).hexdigest() != "848aad6410899e58c02400ea831c7b92eeb9984b747dc2ee0989a9fc395b4227":
        fail("the V185 catalog reference image no longer matches the approved design")
    if catalog_bytes[:8] != b"\x89PNG\r\n\x1a\n" or struct.unpack(">II", catalog_bytes[16:24]) != (1536, 1024):
        fail("the V185 catalog reference image has invalid dimensions")

    catalog = require(
        "backend-laravel/app/Services/WarqnaPro/StoreCatalogService.php",
        "V185: the four approved reference-table batches",
        "$approvedNames=[",
        "'table_reference_38'=>['لوحة جلد قطبي','Polar Leather Panel']",
        "where('key','like','table_reference_%')->update(['active'=>true",
    )
    if "where('key','like','table_reference_%')->update(['active'=>false" in catalog:
        fail("backend still deactivates V185 reference tables")

    controller = require(
        "backend-laravel/app/Http/Controllers/MobileApiController.php",
        "$activatableCategories=[",
        "'table','card_back'",
        "whereIn('category',$activatableCategories)",
        "latest('id')->lockForUpdate()->first()",
        "$this->activateStoreItem($user,$item);",
        "تم تفعيل العنصر المملوك واعتماده على الحساب",
    )
    if "where('category','xp_booster')->where('active',true)" in controller:
        fail("mobile activation endpoint is still limited to boosters")

    require(
        "backend-laravel/routes/api.php",
        "Route::post('/store/activate'",
        "throttle:warqna-sensitive",
    )
    require(
        "backend-laravel/tests/Feature/V185WorldClassTableStoreTest.php",
        "reference_tables_are_public_after_catalog_sync",
        "owned_table_activation_is_server_authoritative",
        "unowned_table_cannot_be_activated_by_a_forged_client_request",
    )
    print("[PASS] V185 world-class table marketplace, additive catalog and secure activation contract")


if __name__ == "__main__":
    main()
