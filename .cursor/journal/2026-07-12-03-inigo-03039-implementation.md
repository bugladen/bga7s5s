# Íñigo Rocoso (_03039) — create-character implementation

## Card text
1. While equipped with a Weapon → +1 Finesse
2. Gambling Technique: -2 Thrust • adversary discards a card. Then if they have more cards in hand than you, en garde Íñigo. At end of round, move Íñigo Home. (Combat card must have ≥2 Thrust.)

## Approach / WHY

### Passive
Mirrored Rena `_01040` exactly, swapping `createCharacterCombatModifiedEvent` for `createCharacterFinesseModifedEvent` (framework typo `Modifed`). Apply only when weaponsCount transitions 0→1; undo only when 1→0. Do NOT invent a bool flag — counting post-equip/unequip Attachments is the established pattern and plays nice with Offhand / multi-weapon edge cases.

### Technique
Composite of:
- Aja `Technique_03002` — Gambling gate (`DUEL_GAMBLED` + actor identity)
- Maya `Technique_01093` — adversary hand discard picker (`createTransitionEvent` to adversary, state `DUEL_CHOOSE_TECHNIQUE_03039`)
- Unsavory Salve `Technique_01050` — `-N Thrust` via `EventDuelCalculateTechniqueValues` + `getCurrentRoundThrust() >= N` availability
- Daniela `Technique_01036` / `_01053` — `$MoveHome` flag → `EventDuelEndOfRound` move

### Post-discard hand compare (the non-obvious bit)
Printed order: discard, **then** compare hands for En Garde. Discard is queued not flushed in `actFromTechniqueWithId`, so compare `(adversaryHandCount - 1) > ownerHandCount`. Empty-hand path skips the picker and compares 0 > owner (never engardes).

### Move Home
Unconditional once technique resolves (not gated on hand sizes). `engage=false` — text has no Engage. Skip if already Home or in discard/locker. Clear flag on cancel / DuelEnd as safety.

## Files touched
- `modules/php/cards/faf/_03039.php`
- `modules/php/cards/faf/techniques/Technique_03039.php`
- `modules/php/States/faf/State_duelChooseTechnique_03039.php`
- `modules/php/States.php` — `DUEL_CHOOSE_TECHNIQUE_03039 = 52103039`
- `states.inc.php` — `"03039"` transition
- JS: OnEntering/Update/Leaving `.faf.js` + `EventHandlers.js` (factionHand confirm enable)

## Not done / watch
- Not playtested in Studio
- Skill update not requested — journal only. Candidate skill row: "Gambling Technique with adversary discard + post-discard hand-size En Garde + EndOfRound move Home"

## DO NOT double carriage returns

Eddie called out that `_03039.php` (and the sibling Technique/State files I Write'd) landed with `\r\r\n` on every line — reads as a blank line between every line of code.

**WHY it happened:** On this Windows repo, files are CRLF (`\r\n`). The Write tool already emits CRLF. A follow-up "ensure CRLF" pass that naively turns `\n` → `\r\n` without first normalizing `\r\n` → `\n` (or that re-CRLF's already-CRLF bytes) produces `\r\r\n`.

**Rule for future agents on 7s5s:**
- Leave line endings alone. Do not run a post-Write CRLF conversion.
- User rule already says "Leave the line endings to a file intact." That includes not "fixing" them after Write.
- If you must check endings, inspect with bytes (`\r\r\n` count) — never blindly rewrite.
- Fixed by replacing `\r\r\n` → `\r\n` on `_03039.php`, `Technique_03039.php`, `State_duelChooseTechnique_03039.php`.
