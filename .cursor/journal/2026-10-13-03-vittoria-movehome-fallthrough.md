# Vittoria Reaction_01014 moveHome fall-through

## Symptom
BGA SQL error: `WHERE card_id = moveHome` / Unknown column 'moveHome'. Stack: actReactionForState → Reaction_01014::performReaction → getCharacterById("moveHome").

Not injection — player clicked the real Move Home button. DB.php interpolates card ids raw (latent risk); user said leave that alone for now.

## Cause
`performReaction` had three separate `if` blocks (`moveHome`, `inPlayThug`, `inHandThug`). Phases are meant to be exclusive, but if a stale `inHandThug`/`inPlayThug` flag was still set when `moveHome` was true, the later block ran after the moveHome handler and did:

```php
$characterId = str_replace("putIntoPlay-", "", $reactionId); // "moveHome"
$game->theah->getCharacterById($characterId);
```

How flags go stale is still a bit fuzzy (double-event? serialization edge?). Doesn't matter — fall-through was enough to blow up.

## Fix
- `if / else if / else if` so only one phase handles the click
- Clear sibling flags when entering moveHome / after successful redirect
- `(int)` cast on the putIntoPlay- id parse (belt; not the real fix)

## Test
`moveHome ignores stale inHandThug flag` — after successful redirect, force `inHandThug=true` via reflection, click `moveHome`, assert move queued and no muster.

All 12 Reaction_01014 tests pass.
