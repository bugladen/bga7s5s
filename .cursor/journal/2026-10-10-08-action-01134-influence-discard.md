# Action_01134 — server-side Influence discard cap

## Bug
Matushka's Sight: discard peeked cards up to performer's Influence. Client (`EventHandlers.js` highDramaPhase01134_2) caps via `performer.modifiedInfluence`. `actFromActionWithIds` state `_2` moved whatever IDs arrived — no count check. Suite had pinned this as SUSPECTED BUG.

## Fix
Before card validation/moves in state `_2`, compare `count($ids)` to `$performer->ModifiedInfluence` and throw BgaUserException if over. WHY ModifiedInfluence (not base Influence): matches client `modifiedInfluence` and what the game treats as current Influence after modifiers.

## Test
Added `discarding more than performer Influence throws` (Influence 1, two IDs → exception, cards stay in deck, no transition). Removed the SUSPECTED BUG comment on the happy-path discard test.
