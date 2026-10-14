# Duel round-1 End Round stuck: empty hand + 0 gamble reveals

## Bug

Accept duel with empty hand when gambling is reduced to 0 reveal cards. UI: Combat Card greyed (needs hand selection), no useful Gamble, End Round throws "must either gamble or a combat card must be played." Softlock.

## Root cause

`actDuelDoneRound` first-round gate:

```php
if ($combatCardsCount == 0 && ($handCardsCount > 0 || $gamblesLeft > 0))
```

Comment said you can pass if you can't gamble / have no hand, but the code only passed when **both** hand empty **and** `gamblesLeft <= 0`. It ignored:

1. Reveal count reduced to 0 (Gallegos Blade Technique_01101 -1 stacked, etc.) — Finesse still left, Gamble button still offered, but choosing it reveals nothing useful.
2. Gamble blocked by card effects (`EventDuelAttemptGamble`) — button hidden via args, but End Round still saw `gamblesLeft > 0` and threw.
3. Missing `buildCity()` — reveal modifiers and consistent ModifiedFinesse need the built city (args already builds; DoneRound did not).

## Fix

Gate only when the actor can still produce a combat card:

- `$canPlayFromHand = hand > 0`
- `$canGamble = gamblesLeft > 0 && gambleAllowedByCardEffects && revealCount > 0`

Throw only if no combat card yet AND (`canPlayFromHand` OR `canGamble`).

Also mirror `revealCount > 0` into `argsChooseDuelAction` `gambleAvailable` so the Gamble button disappears in the same dead-end.

## WHY not just `gamblesLeft <= 0`

User's report was specifically "gambling reduced to **0 cards**" — that is reveal count, not Finesse remaining. A finesse-only check would still softlock under Gallegos-style -1 stacks.
