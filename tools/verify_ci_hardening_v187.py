#!/usr/bin/env python3
from __future__ import annotations

import json
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]


def fail(message: str) -> None:
    print(f"[FAIL] {message}")
    raise SystemExit(1)


def require_text(rel: str, needles: list[str]) -> str:
    path = ROOT / rel
    if not path.is_file():
        fail(f"Missing required file: {rel}")
    text = path.read_text(encoding="utf-8")
    for needle in needles:
        if needle not in text:
            fail(f"{rel} is missing CI hardening token: {needle}")
    return text


def main() -> None:
    forbidden = [
        "backend-laravel/.env",
        "flutter_app/android/key.properties",
        "flutter_app/android/app/upload-keystore.jks",
    ]
    for rel in forbidden:
        if (ROOT / rel).exists():
            fail(f"Runtime secret/signing file exists in source package: {rel}")

    composer = json.loads((ROOT / "backend-laravel/composer.json").read_text(encoding="utf-8"))
    require_dev = composer.get("require-dev", {})
    for package in ["phpunit/phpunit", "mockery/mockery"]:
        if package not in require_dev:
            fail(f"composer.json require-dev is missing {package}")

    require_text(".gitignore", [
        "backend-laravel/.env",
        "backend-laravel/.env.*",
        "firebase-service-account*.json",
        "key.properties",
    ])
    require_text("backend-laravel/.gitignore", ["/.env", "/vendor/"])
    require_text(".github/workflows/backend-ci.yml", [
        "actions/checkout@v6",
        "composer validate --no-check-lock --strict",
        "Repair stale Composer lock safely",
        "composer validate --no-check-publish",
        "rm -f composer.lock",
        "composer install --prefer-dist --no-interaction --no-progress",
        "composer show phpunit/phpunit --locked",
        "composer show mockery/mockery --locked",
        "Remove temporary Laravel environment",
    ])
    require_text(".github/workflows/flutter-android.yml", [
        "actions/checkout@v6",
        "actions/upload-artifact@v7",
        "python3 tools/validate_release.py",
    ])
    require_text(".github/workflows/flutter-ios.yml", [
        "actions/checkout@v6",
        "actions/upload-artifact@v7",
        "python3 tools/validate_release.py",
    ])
    require_text(".github/workflows/flutter-web-pages.yml", [
        "actions/checkout@v6",
        "actions/configure-pages@v5",
        "actions/upload-pages-artifact@v4",
        "actions/deploy-pages@v4",
        "python3 tools/validate_release.py",
    ])
    require_text(".github/workflows/production-release-check.yml", [
        "actions/checkout@v6",
        "python3 tools/validate_release.py",
    ])

    for path in sorted((ROOT / ".github/workflows").glob("*.yml")):
        text = path.read_text(encoding="utf-8")
        for stale in [
            "actions/checkout@v4",
            "actions/checkout@v5",
            "actions/upload-artifact@v4",
            "actions/upload-artifact@v5",
            "actions/upload-artifact@v6",
            "actions/upload-pages-artifact@v3",
        ]:
            if stale in text:
                fail(f"Outdated action reference in {path.relative_to(ROOT)}: {stale}")

    print("[PASS] V187 secret, Composer-lock and GitHub Actions hardening contract")


if __name__ == "__main__":
    main()
