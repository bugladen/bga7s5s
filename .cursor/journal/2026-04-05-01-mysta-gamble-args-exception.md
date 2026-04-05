## Mysta Technique_02037 — End Round / argsChooseDuelAction crash

**Symptom:** After using Mysta's technique, clicking End Round surfaced `Mysta's Technique prevents the adversary from gambling this round.` during `eventCheck`.

**Cause:** `argsChooseDuelAction` probes gamble legality with `theah->eventCheck(EventDuelAttemptGamble)` inside `try/catch (\BgaUserException)`. `Technique_02037` was throwing `Bga\GameFramework\UserException`, which is a **different class** than `\BgaUserException`, so the catch never ran and the exception escaped when the client re-fetched args (e.g. after state change).

**Fix:** Throw `\BgaUserException` from `Technique_02037::eventCheck` (same as `Technique_01186` / most of the codebase), using `$game->translate()` for the message. Also broadened the args catch to `\BgaUserException|\Bga\GameFramework\UserException` so any future `eventCheck` using the framework class is still swallowed for UI purposes.

**WHY two changes:** Primary fix is aligning exception type with what args already catches; secondary catch is defense-in-depth if another card throws the framework type during the same probe.

**Follow-up:** `\BgaUserException` is deprecated; `Technique_02037::eventCheck` now throws `Bga\GameFramework\UserException` (same as `Reaction_02037`). `argsChooseDuelAction` keeps catching both legacy and framework types because other cards may still throw `\BgaUserException` during the same probe.
