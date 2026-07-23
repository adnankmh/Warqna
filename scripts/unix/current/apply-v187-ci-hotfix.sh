#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT"
python3 tools/test_v187_ci_hotfix.py
python3 tools/apply_v187_ci_hotfix.py
printf '\n[SUCCESS] Hotfix applied. FULL and PATCH ZIP files were created beside the project folder.\n'
