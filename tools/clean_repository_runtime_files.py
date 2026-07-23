#!/usr/bin/env python3
from __future__ import annotations

import argparse
import json
import shutil
import subprocess
from datetime import datetime
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
BACKUP_PARENT = ROOT.parent / "Warqnaa_LOCAL_BACKUP"
SECRET_PATHS = [
    Path("backend-laravel/.env"),
    Path("flutter_app/android/key.properties"),
    Path("flutter_app/android/app/upload-keystore.jks"),
]


def git_tracked(rel: Path) -> bool:
    result = subprocess.run(
        ["git", "ls-files", "--error-unmatch", rel.as_posix()],
        cwd=ROOT,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
        check=False,
    )
    return result.returncode == 0


def lock_is_stale(path: Path) -> bool:
    if not path.is_file():
        return False
    try:
        data = json.loads(path.read_text(encoding="utf-8"))
    except (OSError, json.JSONDecodeError):
        return True
    names = {p.get("name") for p in data.get("packages-dev", []) if isinstance(p, dict)}
    return not {"phpunit/phpunit", "mockery/mockery"}.issubset(names)


def main() -> int:
    parser = argparse.ArgumentParser(description="Clean stale runtime files before committing Warqnaa V187.")
    parser.add_argument("--apply", action="store_true", help="Apply changes; otherwise only report them.")
    args = parser.parse_args()

    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    backup_root = BACKUP_PARENT / timestamp
    actions: list[tuple[str, Path]] = []

    for rel in SECRET_PATHS:
        path = ROOT / rel
        if path.exists():
            actions.append(("secret", rel))

    lock_rel = Path("backend-laravel/composer.lock")
    if lock_is_stale(ROOT / lock_rel):
        actions.append(("stale-lock", lock_rel))

    if not actions:
        print("[OK] No real .env/signing files or stale Composer lock were found.")
        return 0

    for kind, rel in actions:
        tracked = git_tracked(rel)
        print(f"[FOUND] {kind}: {rel.as_posix()}" + (" (tracked by Git)" if tracked else ""))

    if not args.apply:
        print("Run with --apply to back up secrets and remove these files from the project.")
        return 2

    backup_root.mkdir(parents=True, exist_ok=True)
    for kind, rel in actions:
        path = ROOT / rel
        if kind == "secret" and path.is_file():
            destination = backup_root / rel
            destination.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy2(path, destination)
        if path.is_file() or path.is_symlink():
            path.unlink()
        elif path.is_dir():
            shutil.rmtree(path)

    print(f"[OK] Local secret backup: {backup_root}")
    print("[OK] Runtime files removed. Commit the displayed deletions in GitHub Desktop.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
