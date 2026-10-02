# Shell and PowerShell Quality

Scripts are non-destructive by default, quote paths and variables, expose meaningful failures, and return correct exit codes. Bash scripts use `set -euo pipefail` where their control flow supports it; PowerShell scripts use `$ErrorActionPreference = 'Stop'` where appropriate and do not swallow failures.

Do not hide failed commands, overwrite `.env` files, delete development volumes by default, or use unsafe broad targets. Keep `scripts/dev.sh` and `scripts/dev.ps1` behavior aligned. CLI output is legitimate; do not treat it as production application logging. Production code uses its established logging infrastructure rather than ad-hoc debug prints.
