# Deal with the Devil (_03062)

## Classification
1. Pattern A trivial resolve — Forum + Docks Renown (fixed; matches art icons)
2. Villain City Action — SchemeCityAction; Villain trait gate (NOT ISorcererAbility); wound performer; muster non-Undead non-Mercenary from locker at performer location; grant Monster+Undead; end of Dusk → locker

## Scaffold fixes
- Traits on scaffold were Virtue+Pact; art is **Villainous – Pact**. Correcting.

## WHY dusk send lives on Character condition, not Action/Scheme
Chosen schemes go to locker in `stDuskPhaseCleanup` (before `EventDuskPhaseEnd`). `buildCity()` does not load locker into `$this->cards`, so Action/Scheme `handleEvent` never sees DuskPhaseEnd. Mustered character is at Home by then and IS in `$this->cards`. Pattern: stamp `Game::DEAL_WITH_THE_DEVIL` (+ optional GRANTED_MONSTER) on the character; Character.php clears traits and queues `createCardSentToLockerEvent` on `EventDuskPhaseEnd`.

## WHY remove gained traits before locker
`EventCardSentToLocker` does not recreate the card (unlike destroy). Leaving Undead on them would permanently block re-muster by this action's non-Undead filter.

## Uncertain
- Leader in locker eligible? Text says "characters" with only Undead/Mercenary exclusions — allowing Leaders.

## Implemented
Shipped. pendingMusterId needs updateCardObjectInDb (learned from 03029 MoveMode — IsUpdated alone is not flushed before stRunEvents rebuild). Character dusk path: strip traits on DuskPhaseEnd, unequip, queue locker, recreate on EventCardSentToLocker while condition still set.

## Watch
- Client locker chooseList: ids coerced with Number() both sides.
- If Eddie wants Leaders excluded, add hasTrait("Leader") filter in getEligibleLockerCharacters.

## Debug locker (2026-07-19 follow-up)
Eddie asked to add debug fn to put a card in a player's locker. **Already exists:** `debug_SetCardInPlayerLocker($className, $playerId)` with `#[Debug(reload: true)]` — same pattern as discard pile Set helpers. No new code needed unless they want Add naming alias / move-by-id / city locker.
