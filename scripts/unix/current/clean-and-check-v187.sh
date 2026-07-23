#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT"
python3 tools/clean_repository_runtime_files.py --apply
bash scripts/unix/current/check-v187.sh
echo "Warqnaa V187 repository cleanup and validation completed successfully."
