#!/usr/bin/env python3
from __future__ import annotations

import json
import os
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
FORBIDDEN = [
    "backend-laravel/.env",
    "flutter_app/android/key.properties",
    "flutter_app/android/app/upload-keystore.jks",
]


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


def sanitize_ci_workspace() -> None:
    ci = os.environ.get("CI", "").strip().lower() in {"1", "true", "yes"}
    if not ci:
        return
    result = subprocess.run(
        [sys.executable, str(ROOT / "tools/sanitize_ci_workspace_v187.py"), "--ci"],
        cwd=ROOT,
        text=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.STDOUT,
    )
    if result.stdout.strip():
        print(result.stdout.strip())
    if result.returncode != 0:
        fail("CI workspace sanitization failed")


def main() -> None:
    # GitHub Actions sets CI=true. Sanitize first so a legacy tracked .env from an
    # older commit cannot break every workflow before its deletion commit lands.
    sanitize_ci_workspace()

    for rel in FORBIDDEN:
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
    require_text("tools/sanitize_ci_workspace_v187.py", [
        "backend-laravel/.env",
        "CI workspace secret/signing sanitization completed",
    ])
    require_text(".github/workflows/backend-ci.yml", [
        "actions/checkout@v6",
        "Sanitize checked-out source",
        "sanitize_ci_workspace_v187.py --ci",
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
        "Sanitize checked-out source",
        "sanitize_ci_workspace_v187.py --ci",
        "actions/upload-artifact@v7",
        "python3 tools/validate_release.py",
    ])
    require_text(".github/workflows/flutter-ios.yml", [
        "actions/checkout@v6",
        "Sanitize checked-out source",
        "sanitize_ci_workspace_v187.py --ci",
        "actions/upload-artifact@v7",
        "python3 tools/validate_release.py",
    ])
    require_text(".github/workflows/flutter-web-pages.yml", [
        "actions/checkout@v6",
        "Sanitize checked-out source",
        "sanitize_ci_workspace_v187.py --ci",
        "actions/configure-pages@v5",
        "actions/upload-pages-artifact@v4",
        "actions/deploy-pages@v4",
        "python3 tools/validate_release.py",
    ])
    require_text(".github/workflows/production-release-check.yml", [
        "actions/checkout@v6",
        "Sanitize checked-out source",
        "sanitize_ci_workspace_v187.py --ci",
        "python3 tools/validate_release.py",
    ])

    for path in sorted((ROOT / ".github/workflows").glob("*.yml")):
        text = path.read_text(encoding="utf-8")
        if "actions/checkout@v6" in text and "sanitize_ci_workspace_v187.py --ci" not in text:
            fail(f"Workflow checkout is missing the source sanitizer: {path.relative_to(ROOT)}")
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
