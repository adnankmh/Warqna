#!/usr/bin/env python3
"""Remove runtime secrets/signing files from a CI checkout.

This script is intentionally narrow: it only removes files that must never ship
inside the Warqnaa source package. Example templates remain untouched.
"""
from __future__ import annotations

import argparse
import os
import subprocess
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
FORBIDDEN = (
    "backend-laravel/.env",
    "flutter_app/android/key.properties",
    "flutter_app/android/app/upload-keystore.jks",
)


def remove_path(path: Path) -> bool:
    if not path.exists() and not path.is_symlink():
        return False
    if path.is_dir() and not path.is_symlink():
        raise RuntimeError(f"Refusing to remove directory through secret sanitizer: {path}")
    path.unlink()
    return True


def untrack_in_ephemeral_index(rel: str) -> None:
    """Remove a forbidden path from the ephemeral CI index when Git is present.

    This does not modify the remote repository. The permanent installer stages
    the same deletion in the user's real repository so the next commit removes it.
    """
    git_dir = ROOT / ".git"
    if not git_dir.exists():
        return
    subprocess.run(
        ["git", "-C", str(ROOT), "rm", "-f", "--cached", "--ignore-unmatch", "--", rel],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
        check=False,
    )


def main() -> int:
    parser = argparse.ArgumentParser()
    parser.add_argument("--ci", action="store_true", help="allow cleanup only in CI")
    parser.add_argument("--check", action="store_true", help="check only; do not remove")
    args = parser.parse_args()

    ci = os.environ.get("CI", "").strip().lower() in {"1", "true", "yes"}
    if args.ci and not ci:
        print("[FAIL] --ci cleanup is only allowed when CI=true")
        return 2

    found = [rel for rel in FORBIDDEN if (ROOT / rel).exists() or (ROOT / rel).is_symlink()]
    if args.check:
        if found:
            for rel in found:
                print(f"[FAIL] Runtime secret/signing file exists: {rel}")
            return 1
        print("[PASS] No runtime secret/signing files exist in the source tree")
        return 0

    removed: list[str] = []
    for rel in found:
        path = ROOT / rel
        if remove_path(path):
            removed.append(rel)
            if ci:
                untrack_in_ephemeral_index(rel)

    if removed:
        for rel in removed:
            print(f"[WARN] Removed forbidden runtime file from workspace: {rel}")
    else:
        print("[OK] CI workspace already contains no runtime secret/signing files")

    remaining = [rel for rel in FORBIDDEN if (ROOT / rel).exists() or (ROOT / rel).is_symlink()]
    if remaining:
        for rel in remaining:
            print(f"[FAIL] Could not remove forbidden runtime file: {rel}")
        return 1
    print("[PASS] CI workspace secret/signing sanitization completed")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
