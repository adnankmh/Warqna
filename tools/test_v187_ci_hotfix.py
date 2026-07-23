#!/usr/bin/env python3
"""Self-test for the Warqnaa V187 CI hotfix utility."""
from __future__ import annotations

import importlib.util
import json
import tempfile
from pathlib import Path

HERE = Path(__file__).resolve().parent
SPEC = importlib.util.spec_from_file_location("hotfix", HERE / "apply_v187_ci_hotfix.py")
assert SPEC and SPEC.loader
hotfix = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(hotfix)


def main() -> int:
    with tempfile.TemporaryDirectory() as temp:
        root = Path(temp) / "Warqnaa"
        (root / "backend-laravel").mkdir(parents=True)
        (root / ".github/workflows").mkdir(parents=True)
        (root / "RELEASE_VERSION.json").write_text(
            json.dumps({"version": "0.3.6", "build": 187, "full": "0.3.6+187"}), encoding="utf-8"
        )
        (root / "backend-laravel/composer.json").write_text(
            json.dumps({"require-dev": {"phpunit/phpunit": "^11.0", "mockery/mockery": "^1.6"}}),
            encoding="utf-8",
        )
        (root / "backend-laravel/.env").write_text("APP_KEY=secret\n", encoding="utf-8")
        workflow = root / ".github/workflows/backend-ci.yml"
        workflow.write_text(
            "name: test\njobs:\n  test:\n    runs-on: ubuntu-latest\n    steps:\n      - run: composer install --prefer-dist --no-interaction --no-progress\n",
            encoding="utf-8",
        )

        assert hotfix.ensure_block(root / ".gitignore", hotfix.GITIGNORE_BLOCK)
        removed, backup = hotfix.backup_and_remove_secrets(root)
        assert "backend-laravel/.env" in removed
        assert backup and (backup / "backend-laravel/.env").is_file()
        assert not (root / "backend-laravel/.env").exists()

        changed = hotfix.patch_workflows(root)
        assert ".github/workflows/backend-ci.yml" in changed
        patched = workflow.read_text(encoding="utf-8")
        assert hotfix.HOTFIX_ID in patched
        assert "composer update phpunit/phpunit mockery/mockery" in patched
        assert "composer install --prefer-dist --no-interaction --no-progress" in patched
        assert not hotfix.verify_no_secrets(root)
        assert not hotfix.verify_workflows(root)

    print("[PASS] Warqnaa V187 CI hotfix self-test")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
