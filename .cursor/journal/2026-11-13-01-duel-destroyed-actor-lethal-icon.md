# Duel Actor column — lethal skill icon on destroyed participant

## Ask
When a character was destroyed last round and the duel continues (threat remains), impose the skill icon used to indicate lethal threat over the destroyed card in the Actor column.

## Context
Destroyed participants can still appear as Actor while the duel continues:
- Actor dies at end of round; adversary pool threat remains → next round is the living seat, but the corpse stays visible in the prior Actor cell.
- Stiletto / short-duel: challenger already in Locker before round 2; `stDuelNewRound` still serializes them via `getCardObjectFromDb` and the client paints that corpse as Actor.

## What
- `placeDuelDestroyedActorLethalIcon` — centered chip on the duel actor card face with the same skull (`&#9760;` / `_7sfs-lethal`) used on lethal threat chips.
- `markDestroyedDuelActorCards` / `isCharacterDestroyedOutOfPlay` — live location check covers JS `"Player Locker"`/`"Player Discard"` and PHP `Locker-*`/`Discard-*`.
- Wired from `displayDuelRow`, `notif_duelActorSwapped`, `notif_characterDestroyed`, and Brute path `notif_cardDiscardedFromPlay`.
- CSS `._7sfs-duel-actor-lethal-chip` — grey circle centered over the card (challenge-stat chip language, skull inside).

## WHY skull
User clarified: "SKULL icon used to indicate lethal threat" — not the Combat/Finesse/Influence skill sprite. First pass wrongly used the skill sprite; swapped chip contents to `&#9760;`. Layout/wiring unchanged.

## WHY live cardProperties location, not actor_serialized
Round actor snapshot is taken at round start (city location). After destroy the snapshot is stale; `cardProperties[id].location` is what destroy notifs and refresh agree on.

## Watch
Did not gate on "only the latest round" — every Actor cell for that destroyed id gets the chip while `inDuel`; easy to narrow if too noisy.

## Follow-up: refresh miss + 50% size
Live notif worked; refresh lost the skull. WHY: `isCharacterDestroyedOutOfPlay` only checked `cardProperties`, but locker/discard piles are never mirrored into that cache on setup (only opened on click). Duel `actor_serialized` still has the round-start city location. Fix: also scan `gamedatas.players[*].locker` / `.discard` for the id. Chip sized 36→54px, skull 18→27pt (+50%).
