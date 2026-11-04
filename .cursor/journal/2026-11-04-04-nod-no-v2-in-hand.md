# User: v2 was not in hand

Pushback on the LIKE collision as the cause of their game. Fair — starter decks only list `01109` (count 2), never auto-swap to v2. No catalog alias.

## Re-checked without v2

- Opponent payment cannot discard the canceling player's NoD (payment is acting player's hand). Subagent "paid with 01109" idea is wrong for the cancel scenario.
- Action_02029 always goes through announceAction → EventActionActivated.
- Lone original Reaction_01109 offers on 02029 Action in unit tests.
- No eventCheck throws on ActionActivated.
- Lorenzo null crash only for LocationAction sourceId=0, not Risk Actions.

## Honest status

With Action + original 01109 and no v2, there is **no remaining code path that silently skips the offer**. De-dupe boundary fix stays as hardening (real footgun, just not their table).

Situational leftovers only:
1. `Used=true` already that day (cancel earlier; decline does not set Used)
2. UI/state missed (chooseNext fog, tab focus, network) while backend offered
3. Misread of when the window is — announce log line vs claim resolving

Game log that would settle it: announce message for Claim Location (Diplomats), any prior "uses Night of Drinking to cancel" that day, whether playerReaction/chooseNext appeared for the NoD controller.
