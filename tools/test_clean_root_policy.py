#!/usr/bin/env python3
"""Regression test for Warqna's repository-aware clean-root policy."""
from __future__ import annotations

import importlib.util
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
SPEC = importlib.util.spec_from_file_location("warqna_validate_release", ROOT / "tools/validate_release.py")
if SPEC is None or SPEC.loader is None:
    raise SystemExit("Unable to load tools/validate_release.py")
module = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(module)

accepted = set(module.ROOT_ALLOWED_ENTRIES) | {
    ".git", ".gitattributes", ".gitmodules", ".editorconfig", ".DS_Store", "Thumbs.db"
}
assert module.unexpected_root_entries(accepted) == [], "Standard repository metadata must be accepted"
assert module.unexpected_root_entries({
    "APPLY_PATCH_AR.txt",
    "CHANGELOG_V0.2.1_AR.md",
    "FILES_MANIFEST.txt",
    "VALIDATION_V0.2.1.txt",
}) == [], "Known patch metadata must be accepted"
assert module.unexpected_root_entries({
    "FEATURES_IMPLEMENTED_AR.md",
    "GITHUB_BUILD_FIX_V143_AR.md",
    "QUALITY_REPORT_V142_AR.md",
    "README_AR.md",
    "RELEASE_MANIFEST_V142.json",
    "START_WEB_DEMO.bat",
    "START_WEB_DEMO.sh",
    "WARQNA_V142_REAL_ENGINES_FULLSTACK_AR.md",
    "web_demo",
}) == [], "Exact legacy V142/V143 root entries must remain patch-compatible"
assert module.unexpected_root_entries({"rogue.txt"}) == ["rogue.txt"], "Unexpected root files must remain rejected"
assert module.unexpected_root_entries({".git", "rogue.txt"}) == ["rogue.txt"]
print("[PASS] Clean-root policy accepts Git/patch/legacy metadata and still rejects project-root clutter")
