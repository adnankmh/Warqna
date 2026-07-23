#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
cd "$ROOT"
python3 tools/verify_release_versions.py
python3 tools/test_clean_root_policy.py
python3 tools/test_flutter_ci_contract.py
python3 tools/test_v02_daily_prize_boxes_contract.py
python3 tools/test_v030_contract.py
python3 tools/test_v170_contract.py
python3 tools/test_v171_controller_references.py
python3 tools/test_v172_brand_table_contract.py
python3 tools/test_v173_online_engagement_contract.py
python3 tools/test_v174_offline_progression_navigation_contract.py
python3 tools/test_v175_xp_challenges_pasha_designer_contract.py
python3 tools/test_v176_daily_pack_inventory_contract.py
python3 tools/test_v181_ci_regression_contract.py
python3 tools/test_v182_rewards_contract.py
python3 tools/test_v183_overhaul_contract.py
python3 tools/test_v184_flutter_quality_contract.py
python3 tools/test_v184_official_game_rules_contract.py
python3 tools/test_v185_world_class_contract.py
python3 tools/test_v186_engine_integrity_contract.py
python3 tools/test_v187_global_offline_contract.py
php backend-laravel/tools/test-v183-engine-overhaul.php
php backend-laravel/tools/test-v184-official-rules-audit.php
php backend-laravel/tools/test-v184-engine-stress.php
php backend-laravel/tools/test-v186-engine-integrity.php
python3 tools/validate_v030_static.py
php backend-laravel/tools/test-engine-adapters.php
php backend-laravel/tools/test-v142-rule-cores.php
php backend-laravel/tools/test-v030-banakil.php
python3 tools/validate_release.py
echo "Warqnaa V0.3.6 v187 source package passed local preflight."
