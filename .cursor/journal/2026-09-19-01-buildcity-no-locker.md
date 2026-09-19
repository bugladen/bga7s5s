# buildCity must not load The Locker

## Context from prior session

`2026-09-17-04-stiletto-dead-participant-challenge.md` — soft-lock when Stiletto kills a challenge participant before Accept. Fix included loading every player's `Locker-*` into `Theah::buildCity()` so `getCharacterById` found the corpse.

## Problem

`runEvents` walks `$this->cards` and calls `handleEvent` on every card. Loading locker into that set made sunk schemes/auras/reactions keep firing (e.g. Contempt and Hatred-style auras, anything gated poorly on Location). Long-standing invariant (Deal with the Devil / create-scheme Pattern K docs): **locker is out of the event world**.

## Refactor (this session)

Removed locker load from `buildCity`. Stiletto path already had the right substitutes in the same commit:

- `argsHighDramaChallengeActionAcceptChallenge` — `getCardObjectFromDb` if null; `CHOSEN_LOCATION` for intervene list; `CHALLENGE_LAST_KNOWN_*` for threat / Daichi / RH
- `getCardById` — already DB-falls-back when id not in `$this->cards`
- Resolution / refuse — `characterIsInDiscardOrLocker` + last-known RH
- `interventionCheck` — null-safe `CHOSEN_LOCATION` default (don't require `$target->Location`)

## WHY not keep locker in buildCity

Belt-and-suspenders for one Accept UI soft-lock is not worth putting every locker card into the global event loop. Targeted DB + challenge snapshots are enough; discard piles stay loaded (separate historical choice).

## Note for later

Mid-request, `EventCharacterDestroyed` still `addCardToWorld`s the recreated locker corpse, and `EventCardSentToLocker` leaves the card in `$this->cards` until next `buildCity`. That is same-request leakage only; cross-request is what this revert fixes.

## Follow-up: Accept after Stiletto (2026-09-19)

Accept still runs `stHighDramaChallengeActionGenerateThreat` *before* Resolution's fizzle check. EventHub used `$theah->cards[$adversaryId]` for the notify → fatal when challenged was in Locker (id absent from world).

Fixed `EventGenerateChallengeThreat` hub handler:
- Resolve actor/adversary via `getCardById` + `CHALLENGE_LAST_KNOWN_*` (never raw `$theah->cards[]`)
- If challenger absent from `$theah->cards`, apply base Threat from last-known (Character::handleEvent never ran)

After hub succeeds, Resolution still fizzles Accept+dead-challenged as designed.
