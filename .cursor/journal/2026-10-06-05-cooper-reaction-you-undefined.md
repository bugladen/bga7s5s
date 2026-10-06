# Reaction_05Cooper undefined $you — surface as 04040_2 crash

## Symptom
User clicked a button in `highDramaPhase04040_2` and got:
`Undefined variable $you` in `Reaction_05Cooper.php:31`.

Stack: args refresh → `argsForStatePrivate` → `Card::argsFromCard` →
`updateArgsFromReaction` → `getReactionDescription`. So the *action* was 04040;
the *fatal* was Cooper's reaction description being rebuilt while assembling args.

## Cause
```php
. "${you} may ignore a wound from an opponent's ability: ";
```
Double-quoted PHP string → interpolates `$you` as a PHP var. Never defined → ErrorException.
`${you}` is a BGA client substitution token; every other reaction uses single-quoted
`$theah->game->translate('${you} ...')`.

## Fix
Match Spaulders `Reaction_04053` pattern: translate + single quotes.
WHY comment left so nobody "fixes" it back to double quotes for consistency with
the surrounding style.

## Why it looked like 04040
Any state that rebuilds card/reaction args while Cooper is in play will hit this.
04040 wounding into Cooper's ignore offer is a likely path; even without offering,
`argsFromCard` walks reactions. Not a bug in Action_04040 itself.
