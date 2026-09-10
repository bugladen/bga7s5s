# Relentless Reaction_02060b: skip if participant in Locker

## Ask
Reaction_02060b should not trigger if the actor or adversary is in the locker.

## Why
Card wounds both your participant and the opposing adversary when the duel ends. If either is already destroyed (Location `Locker-*`, or `Discard-*` for Brutes), offering the reaction is wrong — one/both wound targets are gone.

## Fix
In `Reaction_02060b::handleEvent` on `EventDuelEnd`, resolve challenger + defender and bail before queuing the reaction transition when either is null or `characterIsInDiscardOrLocker()`.

Used the shared helper (not locker-only) because duel death for Brutes lands in Discard, same as StatesTrait's actor/adversary dead checks. Colloquial "locker" = destroyed.

## Not changed
Reaction_02060a (challenge refused) — both characters are still on the board when a challenge is refused; no locker gate needed there.
