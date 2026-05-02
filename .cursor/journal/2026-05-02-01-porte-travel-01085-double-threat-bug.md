# Porté Travel (01085) — Doubling adversary threat bug

## The bug
User reported: when 01077 (Broken-Time) is played, its maneuver activates, and 01085 is chosen as the second combat card, extra threat ended up being added to the adversary's column instead of just the actor's threat being zeroed.

## Root cause
`createThreatModifiedEvent` is processed by `updateRoundThreats` (modules/php/theah/DB.php:515) as a **delta**, not a set:

```sql
ending_challenger_threat = ending_challenger_threat + {$challengerThreat}
ending_defender_threat   = ending_defender_threat   + {$defenderThreat}
```

01085 was building the event like:

```php
$modifiedChallengerThreat = $actor->Id == $challengerId ? $challengerThreat * -1 : $challengerThreat;
$modifiedDefenderThreat   = $actor->Id == $defenderId  ? $defenderThreat * -1   : $defenderThreat;
```

When the challenger is the actor:
- challenger column delta = `-challengerThreat` → zeroes it ✓
- defender column delta = `+defenderThreat` (positive!) → **doubles** any existing defender threat

In the 01077 → 01085 flow, defender column might already carry threat from a thrust earlier in the round, leftover threat carried from a prior round, or a Riposte that pushed threat back. 01085 was effectively doubling all of that.

## Fix
First attempt was to zero only the actor's column and leave the adversary's column alone (`0` delta). User then clarified the intended rule: **the card supersedes any Riposte/Thrust the actor pushed onto the adversary earlier in the round, so both columns should be zeroed.** Treating the actor as leaving the engagement entirely — all threat in the duel that is "theirs" (incoming or outgoing) dissolves.

Final code:

```php
$challengerThreat = $game->theah->getCurrentDuelThreat($challengerId);
$defenderThreat   = $game->theah->getCurrentDuelThreat($defenderId);

$event = EventFactory::createThreatModifiedEvent(-$challengerThreat, -$defenderThreat);
```

WHY: `updateRoundThreats` applies values as deltas, so the negative of the current value is what zeroes a column. Both columns get zeroed, capturing both the actor's incoming threat and any threat they pushed onto the adversary.

Also removed four `var_dump` debug calls the user had added while diagnosing.

## What I'd flag for future sessions
- The `createThreatModifiedEvent` API is delta-based but reads ambiguous — it would be easy to write similar bugs in any future card that "sets" threat. Worth being suspicious of any code that reads `getCurrentDuelThreat` and then passes that value into `createThreatModifiedEvent`.
- Maneuver_01082.php also uses `createThreatModifiedEvent` — quick scan looked OK (it uses computed `*Added` deltas, not absolute reads), but worth re-checking if a similar bug surfaces.

## Follow-up audit pass against the Text

After fixing the threat bug the user asked for a full audit of `_01085` and `Action_01085` against the card Text:

> **Forced:** When played during a duel and if you control a Sorcerer • Wound them. Move your participant to their location and discard all your threat. (Immediately after playing.)
> **Sorcerer Action:** Wound your performer • Move target character you control to your performer's location. Repeatable.

Forced flow was structurally complete. Sorcerer Action was structurally complete and correctly Repeatable (re-queues "01085" transition; Done branch on `id == 0`). All pre-commit hook required calls present.

Three additional fixes applied:

1. **`_01085.php`** — replaced three `\BgaUserException` calls with `Bga\GameFramework\UserException` (per memory: deprecated). Added the import.
2. **`_01085.php`** — replaced `$game->getActivePlayerName()` with `$game->getPlayerNameById($this->ControllerId)` (per memory: deprecated). At this point in the flow the active player is always the card's controller.
3. **`Action_01085.php`** — added `$character->ControllerId != $performer->ControllerId` check inside `isValidTargetForAbility`. The Text says "target character **you control**" but server-side validation only enforced that the target wasn't the performer and wasn't already at the performer's location. UI args correctly filtered, but a malformed action could have moved an opponent's character. Convention is set in `Action_01044::isValidTargetForAbility` and `Action_01073::isValidTargetForAbility` — ownership belongs in `isValidTargetForAbility`.

WHY add the ControllerId check even though the UI args filter it: `actFromActionWithId` is a system boundary (player input). The CLAUDE.md guidance to skip defensive validation applies to internal calls, not boundaries.
