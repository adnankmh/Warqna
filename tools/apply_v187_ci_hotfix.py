#!/usr/bin/env python3
"""Warqnaa V0.3.6+187 CI hotfix.

Fixes two release blockers without weakening validation:
1) removes secret-bearing .env files from the shippable tree and Git index;
2) synchronizes Composer's lock before the preserved composer install command.

It can also generate clean FULL and PATCH ZIP archives from the user's current v187 tree.
"""
from __future__ import annotations

import argparse
import datetime as dt
import hashlib
import json
import os
import re
import shutil
import stat
import subprocess
import sys
import tempfile
import zipfile
from pathlib import Path
from typing import Iterable

HOTFIX_ID = "WARQNAA_V187_CI_SECRET_COMPOSER_HOTFIX_R1"
DEFAULT_FULL_NAME = "Warqnaa_V0.3.6_B187_FULL_CI_FIXED.zip"
DEFAULT_PATCH_NAME = "Warqnaa_V0.3.6_B187_PATCH_CI_FIXED.zip"

GITIGNORE_BLOCK = """# BEGIN WARQNAA V187 SECRET SAFETY\n.env\n.env.*\n!.env.example\n!.env.*.example\nbackend-laravel/.env\nbackend-laravel/.env.*\n!backend-laravel/.env.example\n!backend-laravel/.env.*.example\nflutter_app/android/key.properties\n*.jks\n*.keystore\n# END WARQNAA V187 SECRET SAFETY\n"""

BACKEND_GITIGNORE_BLOCK = """# BEGIN WARQNAA V187 SECRET SAFETY\n.env\n.env.*\n!.env.example\n!.env.*.example\n*.jks\n*.keystore\nstorage/oauth-private.key\nfirebase-service-account*.json\n# END WARQNAA V187 SECRET SAFETY\n"""

EXCLUDED_DIR_NAMES = {
    ".git", "vendor", "node_modules", "build", ".dart_tool", ".idea", ".vscode",
    ".gradle", "Pods", "DerivedData", ".terraform", "coverage", ".pytest_cache",
    "__pycache__",
}

SECRET_FILE_PATTERNS = (
    re.compile(r"(^|/)\.env($|\.)", re.IGNORECASE),
    re.compile(r"(^|/)key\.properties$", re.IGNORECASE),
    re.compile(r"\.(jks|keystore)$", re.IGNORECASE),
    re.compile(r"(^|/)oauth-private\.key$", re.IGNORECASE),
    re.compile(r"(^|/)firebase-service-account.*\.json$", re.IGNORECASE),
)

ALLOWED_SECRET_LIKE_PATTERNS = (
    re.compile(r"(^|/)\.env(\.[^/]*)?\.example$", re.IGNORECASE),
    re.compile(r"(^|/)\.env\.example$", re.IGNORECASE),
)

WORKFLOW_GLOBS = (
    ".github/workflows/*.yml", ".github/workflows/*.yaml",
    "backend-laravel/.github/workflows/*.yml", "backend-laravel/.github/workflows/*.yaml",
)


def log(kind: str, message: str) -> None:
    print(f"[{kind}] {message}")


def find_project_root(start: Path) -> Path:
    candidates = [start.resolve(), *start.resolve().parents]
    for candidate in candidates:
        if (candidate / "RELEASE_VERSION.json").is_file() and (candidate / "backend-laravel/composer.json").is_file():
            return candidate
    raise SystemExit(
        "[FAIL] لم يتم العثور على جذر Warqnaa. شغّل الملف من داخل مشروع V0.3.6+187."
    )


def read_text(path: Path) -> str:
    return path.read_text(encoding="utf-8-sig")


def atomic_write(path: Path, text: str) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with tempfile.NamedTemporaryFile("w", encoding="utf-8", newline="\n", delete=False, dir=path.parent) as tmp:
        tmp.write(text)
        tmp_path = Path(tmp.name)
    os.replace(tmp_path, path)


def ensure_block(path: Path, block: str) -> bool:
    old = read_text(path) if path.exists() else ""
    start_marker = block.splitlines()[0]
    end_marker = block.splitlines()[-1]
    pattern = re.compile(
        rf"(?ms)^\s*{re.escape(start_marker)}\s*$.*?^\s*{re.escape(end_marker)}\s*$\n?"
    )
    cleaned = pattern.sub("", old).rstrip()
    new = (cleaned + "\n\n" if cleaned else "") + block
    if new != old:
        atomic_write(path, new)
        return True
    return False


def run_git(root: Path, args: list[str], check: bool = False) -> subprocess.CompletedProcess[str] | None:
    if not shutil.which("git") or not (root / ".git").exists():
        return None
    return subprocess.run(
        ["git", *args], cwd=root, text=True, capture_output=True, check=check
    )


def is_allowed_secret_like(rel: str) -> bool:
    return any(pattern.search(rel) for pattern in ALLOWED_SECRET_LIKE_PATTERNS)


def is_secret_path(rel: str) -> bool:
    normalized = rel.replace("\\", "/")
    if is_allowed_secret_like(normalized):
        return False
    return any(pattern.search(normalized) for pattern in SECRET_FILE_PATTERNS)


def discover_secret_files(root: Path) -> list[Path]:
    candidates: list[Path] = []
    for path in root.rglob("*"):
        if not path.is_file():
            continue
        try:
            rel = path.relative_to(root).as_posix()
        except ValueError:
            continue
        if any(part in EXCLUDED_DIR_NAMES for part in path.relative_to(root).parts):
            continue
        if is_secret_path(rel):
            candidates.append(path)
    return sorted(candidates)


def backup_and_remove_secrets(root: Path) -> tuple[list[str], Path | None]:
    secrets = discover_secret_files(root)
    if not secrets:
        log("OK", "لا توجد ملفات أسرار قابلة للشحن داخل المشروع")
        return [], None

    stamp = dt.datetime.now().strftime("%Y%m%d_%H%M%S")
    backup_root = root.parent / f"{root.name}_LOCAL_SECRETS_BACKUP_{stamp}"
    removed: list[str] = []

    for path in secrets:
        rel_path = path.relative_to(root)
        rel = rel_path.as_posix()
        destination = backup_root / rel_path
        destination.parent.mkdir(parents=True, exist_ok=True)
        shutil.copy2(path, destination)

        tracked = False
        result = run_git(root, ["ls-files", "--error-unmatch", "--", rel])
        if result is not None and result.returncode == 0:
            tracked = True

        if tracked:
            removal = run_git(root, ["rm", "-f", "--ignore-unmatch", "--", rel])
            if removal is not None and removal.returncode != 0:
                raise RuntimeError(f"تعذر حذف الملف المتتبع من Git: {rel}\n{removal.stderr}")
        elif path.exists():
            path.unlink()

        removed.append(rel)
        log("FIX", f"تم نسخ السر احتياطيًا خارج المشروع وحذفه من الحزمة: {rel}")

    return removed, backup_root


def ensure_composer_requirements(composer_json: Path) -> bool:
    data = json.loads(read_text(composer_json))
    require_dev = data.setdefault("require-dev", {})
    changed = False
    expected = {
        "phpunit/phpunit": "^11.0",
        "mockery/mockery": "^1.6",
    }
    for package, version in expected.items():
        if package not in require_dev:
            require_dev[package] = version
            changed = True
            log("FIX", f"تمت إضافة متطلب Composer المفقود: {package} {version}")

    if changed:
        # Stable, readable ordering while preserving top-level order.
        data["require-dev"] = dict(sorted(require_dev.items(), key=lambda item: item[0].lower()))
        atomic_write(composer_json, json.dumps(data, ensure_ascii=False, indent=2) + "\n")
    else:
        log("OK", "composer.json يحتوي PHPUnit وMockery")
    return changed


def composer_sync_lines(indent: str, install_command: str, prefix_commands: list[str] | None = None) -> list[str]:
    prefix_commands = prefix_commands or []
    body = indent + "  "
    lines = [indent + "run: |", body + f"# {HOTFIX_ID}"]
    lines.extend(body + command for command in prefix_commands)
    lines.extend([
        body + "composer update phpunit/phpunit mockery/mockery --with-all-dependencies --prefer-dist --no-interaction --no-progress",
        body + "composer validate --no-check-publish --no-interaction",
        body + install_command.strip(),
    ])
    return lines


def patch_workflow_text(text: str) -> tuple[str, int]:
    if HOTFIX_ID in text:
        return text, 0

    lines = text.splitlines()
    output: list[str] = []
    replacements = 0

    single_run = re.compile(r"^(?P<indent>\s*)run:\s*(?P<command>.+?)\s*$")

    for line in lines:
        stripped = line.strip()
        if not stripped or stripped.startswith("#") or "composer install" not in line:
            output.append(line)
            continue

        match = single_run.match(line)
        if match:
            indent = match.group("indent")
            command = match.group("command")
            prefix_commands: list[str] = []
            install_command = command

            cd_match = re.match(r"^cd\s+([^&]+?)\s*&&\s*(composer\s+install\b.*)$", command)
            if cd_match:
                prefix_commands.append(f"cd {cd_match.group(1).strip()}")
                install_command = cd_match.group(2)

            output.extend(composer_sync_lines(indent, install_command, prefix_commands))
            replacements += 1
            continue

        # Multiline YAML block: preserve indentation and keep the original install command.
        indent = line[: len(line) - len(line.lstrip())]
        install_command = stripped
        output.extend([
            indent + f"# {HOTFIX_ID}",
            indent + "composer update phpunit/phpunit mockery/mockery --with-all-dependencies --prefer-dist --no-interaction --no-progress",
            indent + "composer validate --no-check-publish --no-interaction",
            indent + install_command,
        ])
        replacements += 1

    suffix = "\n" if text.endswith("\n") else ""
    return "\n".join(output) + suffix, replacements


def patch_workflows(root: Path) -> list[str]:
    changed: list[str] = []
    found = 0
    for glob_pattern in WORKFLOW_GLOBS:
        for path in sorted(root.glob(glob_pattern)):
            found += 1
            old = read_text(path)
            new, replacements = patch_workflow_text(old)
            if replacements:
                atomic_write(path, new)
                rel = path.relative_to(root).as_posix()
                changed.append(rel)
                log("FIX", f"تمت مزامنة Composer قبل install في {rel} ({replacements})")

    if found == 0:
        raise RuntimeError("لم يتم العثور على ملفات GitHub Actions داخل المشروع")

    if not changed:
        # Either already patched, or no composer install in workflows.
        already = any(HOTFIX_ID in read_text(path) for pattern in WORKFLOW_GLOBS for path in root.glob(pattern))
        if already:
            log("OK", "ملفات GitHub Actions مطبّق عليها إصلاح Composer مسبقًا")
        else:
            raise RuntimeError("لم يتم العثور على أمر composer install داخل workflows لتحديثه")
    return changed


def verify_workflows(root: Path) -> list[str]:
    problems: list[str] = []
    for pattern in WORKFLOW_GLOBS:
        for path in root.glob(pattern):
            text = read_text(path)
            if "composer install" in text and HOTFIX_ID not in text:
                problems.append(f"Composer install غير محمي في {path.relative_to(root)}")
    return problems


def verify_no_secrets(root: Path) -> list[str]:
    return [path.relative_to(root).as_posix() for path in discover_secret_files(root)]


def run_preflight(root: Path) -> None:
    validator = root / "tools/validate_release.py"
    if not validator.is_file():
        log("WARN", "tools/validate_release.py غير موجود؛ تم تخطي بوابة الإصدار")
        return
    log("RUN", "تشغيل بوابة الإصدار tools/validate_release.py")
    result = subprocess.run([sys.executable, str(validator)], cwd=root)
    if result.returncode != 0:
        raise RuntimeError("فشلت بوابة الإصدار بعد تطبيق الإصلاح؛ راجع الرسالة الظاهرة أعلاه")
    log("OK", "بوابة الإصدار نجحت بعد الإصلاح")


def path_is_excluded(root: Path, path: Path) -> bool:
    rel = path.relative_to(root)
    if any(part in EXCLUDED_DIR_NAMES for part in rel.parts):
        return True
    rel_posix = rel.as_posix()
    if is_secret_path(rel_posix):
        return True
    if rel_posix.startswith("backend-laravel/storage/logs/") and path.name != ".gitkeep":
        return True
    if rel_posix.startswith("backend-laravel/bootstrap/cache/") and path.suffix == ".php":
        return True
    if path.name in {".DS_Store", "Thumbs.db"}:
        return True
    return False


def zip_add_file(zf: zipfile.ZipFile, root: Path, path: Path) -> None:
    rel = path.relative_to(root).as_posix()
    info = zipfile.ZipInfo.from_file(path, arcname=rel)
    # Normalize timestamp to ZIP-supported range and preserve Unix executable bits.
    if info.date_time[0] < 1980:
        info.date_time = (1980, 1, 1, 0, 0, 0)
    with path.open("rb") as source, zf.open(info, "w") as target:
        shutil.copyfileobj(source, target, length=1024 * 1024)


def sha256_file(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b""):
            digest.update(chunk)
    return digest.hexdigest()


def create_full_zip(root: Path, output: Path) -> int:
    files = [path for path in root.rglob("*") if path.is_file() and not path_is_excluded(root, path)]
    files.sort(key=lambda p: p.relative_to(root).as_posix())
    output.parent.mkdir(parents=True, exist_ok=True)
    if output.exists():
        output.unlink()
    with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=7, allowZip64=True) as zf:
        for path in files:
            zip_add_file(zf, root, path)
    atomic_write(output.with_suffix(output.suffix + ".sha256"), f"{sha256_file(output)}  {output.name}\n")
    log("OK", f"تم إنشاء FULL نظيف: {output} ({len(files)} ملف)")
    return len(files)


def create_patch_zip(root: Path, output: Path, changed_paths: Iterable[str]) -> int:
    unique = sorted({item for item in changed_paths if (root / item).is_file()})
    output.parent.mkdir(parents=True, exist_ok=True)
    if output.exists():
        output.unlink()
    with zipfile.ZipFile(output, "w", compression=zipfile.ZIP_DEFLATED, compresslevel=9) as zf:
        for rel in unique:
            zip_add_file(zf, root, root / rel)
    atomic_write(output.with_suffix(output.suffix + ".sha256"), f"{sha256_file(output)}  {output.name}\n")
    log("OK", f"تم إنشاء PATCH: {output} ({len(unique)} ملف)")
    return len(unique)


def write_hotfix_report(root: Path, report: dict) -> str:
    rel = "releases/hotfixes/v187-ci/HOTFIX_APPLIED.json"
    path = root / rel
    path.parent.mkdir(parents=True, exist_ok=True)
    atomic_write(path, json.dumps(report, ensure_ascii=False, indent=2) + "\n")
    return rel


def main() -> int:
    parser = argparse.ArgumentParser(description="Apply Warqnaa V187 CI secret/Composer hotfix")
    parser.add_argument("--project-root", type=Path, default=Path.cwd())
    parser.add_argument("--no-preflight", action="store_true")
    parser.add_argument("--no-package", action="store_true")
    parser.add_argument("--output-dir", type=Path, default=None)
    args = parser.parse_args()

    root = find_project_root(args.project_root)
    output_dir = (args.output_dir.resolve() if args.output_dir else root.parent)
    log("INFO", f"جذر المشروع: {root}")

    release_meta = json.loads(read_text(root / "RELEASE_VERSION.json"))
    full_version = release_meta.get("full") or f"{release_meta.get('version')}+{release_meta.get('build')}"
    if str(full_version) != "0.3.6+187":
        log("WARN", f"الإصدار المكتشف {full_version}؛ سيُطبّق الإصلاح بصورة توافقية")
    else:
        log("OK", "تم اكتشاف Warqnaa 0.3.6+187")

    changed: list[str] = []

    if ensure_block(root / ".gitignore", GITIGNORE_BLOCK):
        changed.append(".gitignore")
        log("FIX", "تم تقوية .gitignore في جذر المشروع")
    if ensure_block(root / "backend-laravel/.gitignore", BACKEND_GITIGNORE_BLOCK):
        changed.append("backend-laravel/.gitignore")
        log("FIX", "تم تقوية backend-laravel/.gitignore")

    removed, backup_root = backup_and_remove_secrets(root)
    changed.extend(removed)

    if ensure_composer_requirements(root / "backend-laravel/composer.json"):
        changed.append("backend-laravel/composer.json")

    workflow_changes = patch_workflows(root)
    changed.extend(workflow_changes)

    secret_problems = verify_no_secrets(root)
    workflow_problems = verify_workflows(root)
    if secret_problems or workflow_problems:
        for problem in secret_problems:
            log("FAIL", f"ما زال ملف سر موجودًا: {problem}")
        for problem in workflow_problems:
            log("FAIL", problem)
        raise RuntimeError("فشل تحقق الإصلاح الداخلي")

    report = {
        "hotfix": HOTFIX_ID,
        "release": full_version,
        "applied_at": dt.datetime.now(dt.timezone.utc).isoformat(),
        "removed_secret_files": removed,
        "secret_backup_outside_project": str(backup_root) if backup_root else None,
        "workflow_files_patched": workflow_changes,
        "composer_require_dev": ["phpunit/phpunit", "mockery/mockery"],
        "strategy": "composer update targeted packages, validate, then preserved composer install",
    }
    report_rel = write_hotfix_report(root, report)
    changed.append(report_rel)

    if not args.no_preflight:
        run_preflight(root)

    if not args.no_package:
        patch_output = output_dir / DEFAULT_PATCH_NAME
        full_output = output_dir / DEFAULT_FULL_NAME
        # Include the hotfix utility and guides in patch output when present.
        for rel in (
            "tools/apply_v187_ci_hotfix.py",
            "tools/test_v187_ci_hotfix.py",
            "scripts/windows/current/APPLY_V187_CI_HOTFIX_WINDOWS.bat",
            "scripts/unix/current/apply-v187-ci-hotfix.sh",
            "docs/ar/troubleshooting/GITHUB_CI_SECRET_COMPOSER_FIX_V187_AR.md",
            "releases/hotfixes/v187-ci/PATCH_MANIFEST.json",
        ):
            if (root / rel).is_file():
                changed.append(rel)
        create_patch_zip(root, patch_output, changed)
        create_full_zip(root, full_output)

    log("DONE", "اكتمل إصلاح Warqnaa V187 بدون تعطيل أي ميزة أو بوابة أمان")
    if backup_root:
        log("SECURITY", f"نسخة الأسرار المحلية محفوظة خارج المشروع في: {backup_root}")
        log("SECURITY", "إذا سبق رفع .env إلى GitHub، غيّر المفاتيح وكلمات المرور الموجودة بداخله")
    return 0


if __name__ == "__main__":
    try:
        raise SystemExit(main())
    except KeyboardInterrupt:
        raise SystemExit(130)
    except Exception as exc:
        log("FAIL", str(exc))
        raise SystemExit(1)
