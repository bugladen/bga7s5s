# Mourad (_02003) Audit

## Context

Continuing the card audit pattern from _02001 and _02002. Eddie asked to audit _02003 (Mourad) against its card text. Three abilities: passive sorcerer immunity, reaction for Strega intervention, technique for Sorcery combat card draw.

## Findings

### 1 bug found, fixed:

**interventionCheck blocks Mourad's "even while engaged" intervention**

The reaction correctly detects when a Strega at Mourad's location is challenged — and importantly does NOT check Mourad's engagement in `handleEvent`, so the reaction fires correctly. But `performReaction` calls `interventionCheck($owner)` which checks `$character->Engaged` for everyone except _01178 (Carmella Vanessa Slavaggi). So Mourad gets shown the "Intervene or Pass" buttons, clicks Intervene, and gets blocked.

Fix: Added `_02003` alongside `_01178` in the instanceof check in `interventionCheck`. Both skip the `$character->Engaged` check.

WHY this approach over alternatives:
- Could have done custom validation in `performReaction` instead of calling `interventionCheck`, but that would duplicate the location check and challenge-type checks (Legendary Reputation, Valeri Mikhailov)
- Could have overridden `canIntervene()` on _02003, but `interventionCheck` checks `$character->Engaged` SEPARATELY from `canIntervene()`, so it wouldn't help
- The special-case pattern is already established for _01178 and is clean

The slight over-permissiveness concern: this makes Mourad bypass engagement for ALL intervention, not just reaction-triggered. But the standard intervention UI (`ArgumentsTrait`) already filters engaged characters from the selectable list, so a player can't normally select engaged Mourad for standard intervention. Only a modified client could try, and `canIntervene()` still validates control.

### Two things that looked suspicious but are correct:

**Technique's getCombatCardsForCurrentRound() checking all cards**
Initially looked like it would trigger on the opponent's Sorcery card too. But the duel round structure means only the actor's card is in the table when techniques are evaluated. Each round has one actor, one combat card. Techniques are checked after card play, before round resolution. Since the technique is only in Mourad's technique list, it only surfaces when Mourad is the actor.

**Passive sorcerer immunity using eventCheck instead of isValidTargetForAbility**
The eventCheck approach throws at action time, not at target selection time (player sees Mourad as a target, selects him, THEN gets blocked). Could be improved with UI filtering, but architecturally this is the right approach for a universal immunity — adding filtering to every individual sorcerer ability's `isValidTargetForAbility` would be fragile and hard to maintain.

## Pattern notes

The intervention engagement bypass pattern now has two characters using it: _01178 (Carmella) and _02003 (Mourad). Carmella's is conditional (can intervene while engaged only if ability unused). Mourad's is unconditional via the reaction. Both use the same code path in `interventionCheck`. If more characters get this ability, might want to add a `canInterveneWhileEngaged()` method instead of instanceof checks. For now, two special cases is fine.
