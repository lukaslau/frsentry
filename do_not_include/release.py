#!/usr/bin/env python3
"""
frsentry release script.
Usage:
  python do_not_include/release.py           # build zip (default)
  python do_not_include/release.py --no-zip  # copy files to release/ folder only
"""

import sys
import os
import shutil
import subprocess
import zipfile
import fnmatch

# ---------------------------------------------------------------------------
# Config
# ---------------------------------------------------------------------------
SCRIPT_DIR  = os.path.dirname(os.path.abspath(__file__))
ROOT        = os.path.dirname(SCRIPT_DIR)
MODULE_NAME = 'frsentry'
RELEASE_DIR = os.path.join(ROOT, 'release', MODULE_NAME)
OUT_ZIP     = os.path.join(ROOT, f'{MODULE_NAME}.zip')
CREATE_ZIP  = '--no-zip' not in sys.argv

EXCLUDE_DIRS = {'.git', '.idea', '.claude', 'do_not_include', 'node_modules', 'release'}
EXCLUDE_FILE_PATTERNS = [
    '*.bat', '*.zip',
    '.gitignore', '.gitattributes',
    '.php_cs', '.php-cs-fixer.cache',
    'composer.json', 'composer.lock',
]


def run(cmd, **kwargs):
    """Run a shell command; exit on failure."""
    result = subprocess.run(cmd, shell=True, cwd=ROOT, **kwargs)
    if result.returncode != 0:
        print(f'ERROR: command failed: {cmd}')
        sys.exit(1)


def matches_pattern(name, patterns):
    return any(fnmatch.fnmatch(name, p) for p in patterns)


def copy_tree(src, dst):
    """Recursively copy src → dst, applying exclusion rules."""
    os.makedirs(dst, exist_ok=True)
    for item in os.listdir(src):
        if item in EXCLUDE_DIRS:
            continue
        if matches_pattern(item, EXCLUDE_FILE_PATTERNS):
            continue

        src_path = os.path.join(src, item)
        dst_path = os.path.join(dst, item)

        if os.path.isdir(src_path):
            shutil.copytree(src_path, dst_path, dirs_exist_ok=True)
        else:
            shutil.copy2(src_path, dst_path)


def normalize_line_endings(src_dir):
    """Convert CRLF → LF in all text files under src_dir."""
    text_extensions = {'.php', '.tpl', '.js', '.css', '.json', '.xml', '.txt', '.md', '.htaccess'}
    for dirpath, _, files in os.walk(src_dir):
        for fname in files:
            if os.path.splitext(fname)[1].lower() in text_extensions or fname.startswith('.'):
                abs_path = os.path.join(dirpath, fname)
                try:
                    with open(abs_path, 'rb') as f:
                        data = f.read()
                    if b'\r\n' in data or (b'\r' in data and b'\n' not in data):
                        with open(abs_path, 'wb') as f:
                            f.write(data.replace(b'\r\n', b'\n').replace(b'\r', b'\n'))
                except (IOError, OSError):
                    pass


def create_zip(src_dir, zip_path, module_name):
    """Create a zip with forward-slash paths (ZIP spec compliant)."""
    if os.path.exists(zip_path):
        os.remove(zip_path)
    with zipfile.ZipFile(zip_path, 'w', zipfile.ZIP_DEFLATED) as zf:
        for dirpath, _, files in os.walk(src_dir):
            for fname in files:
                abs_path  = os.path.join(dirpath, fname)
                rel_path  = os.path.relpath(abs_path, src_dir).replace('\\', '/')
                zf.write(abs_path, f'{module_name}/{rel_path}')
    size_kb = os.path.getsize(zip_path) / 1024
    with zipfile.ZipFile(zip_path, 'r') as zf:
        count = len(zf.namelist())
    print(f'  {zip_path} ({size_kb:.1f} KB, {count} entries)')


# ---------------------------------------------------------------------------
# Steps
# ---------------------------------------------------------------------------
os.chdir(ROOT)

# Clean previous release folder
if os.path.exists(os.path.dirname(RELEASE_DIR)):
    print('Cleaning previous release directory...')
    shutil.rmtree(os.path.dirname(RELEASE_DIR))

print('[1/4] Running PHP CS Fixer...')
run('php do_not_include/php-cs-fixer.phar fix --config=do_not_include/.php-cs-fixer.dist.php')

print('[2/4] Installing production dependencies...')
run('composer install --no-dev --optimize-autoloader')

print('[3/4] Copying module files...')
copy_tree(ROOT, RELEASE_DIR)
normalize_line_endings(RELEASE_DIR)
print(f'  Copied to {RELEASE_DIR}')

if CREATE_ZIP:
    print('[4/4] Creating zip...')
    create_zip(RELEASE_DIR, OUT_ZIP, MODULE_NAME)
    print(f'Release ready: {OUT_ZIP}')
else:
    print('[4/4] Skipping zip.')

shutil.rmtree(os.path.dirname(RELEASE_DIR))

print()
print('Done.')
