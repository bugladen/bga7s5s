# Regression tests reorganized by expansion

User asked to move card tests into per-expansion subfolders: `core` (01), `tac` (02), `faf` (03), `bas` (04), and then `cad` (05, added mid-task).

Moved with `git mv` (history kept): core 434, tac 137, faf 191, bas 156, cad 6.
Left in `tests/regression/` on purpose (not card-specific): `Card_ClearAbilityUsedFlags`, `EventDuelCalculateCombatCardStats`, `Leader_Assassination`, `PressureLocation`.

Changes forced by the move:
- 13 tests read card source with `dirname(__DIR__, 2)`; now `dirname(__DIR__, 3)` because they are one level deeper.
- `tests/run.php` globs `regression/*_Test.php` plus each expansion folder explicitly. Explicit list (not a recursive glob) so a new expansion folder needs adding there. WHY: keeps ordering deterministic and avoids accidentally picking up stray files.
- Docs updated: CLAUDE.md, `.cursor/rules/run-regression-tests.mdc`.

Full run after the move: 5026 passed, 0 failed.

Gotcha for next time: single-suite invocation paths are now `tests/regression/<exp>/Card_XXXXX_Test.php`. Old journals mention the flat paths; they're historical.
Another gotcha: `git mv` of ~900 files takes ~30s in PowerShell, so the shell backgrounds the command. Wait for it before checking.
New 05 tests go in `cad/`.
