# Reaction_03003 did not fire after Action_01019 destroyed Buratino

## Report
03003 in play, Buratino self-destroyed via Action_01019, Bruno (_05Thomas) in hand, Dante (_01020) in discard. Reaction never offered.

## Root cause (two stacked bugs)

### 1. Discard eligibility used `card_location_arg` filter
`getEligibleThugs('discard')` called:
```php
getCardObjectsAtLocation($discardName, $owner->ControllerId)
```
That adds `AND card_location_arg = $playerId`. Destroyed Brutes go to discard via `moveCardInDeck($id, $discard)` with **default locationArg=0**. Most hand→discard moves also pass 0. So Dante was invisible to eligibility.

Action_01024 already gets discard with **no** playerId arg — pile name is per-player. We matched that.

WHY TestTheah masked this: it filters by `Card->ControllerId`, not location_arg. Production SQL uses location_arg. Regression places Dante with `ControllerId = 0` to simulate the empty query.

### 2. Bruno counted as a hand replacement
Bruno cannot enter play from hand except during a duel (`canBePlayedAsBruteFromHand=false` + muster eventCheck). Counting him as eligible:
- gave a button that would throw on muster, and
- with bug (1), if someone later filtered Bruno without fixing discard, the reaction would stay dead for this board.

Hand eligibility now requires `canBePlayedAsBruteFromHand()` for Character cards.

## Why "did not trigger" with Bruno in hand
If Don was in a City location, current pre-fix code *should* have offered via Bruno alone (illegal pick). User saw no offer → either Don was at **Home** (City Reaction correctly gated by `cardInCity`) **or** production only had Dante as the intended legal target and something else about hand listing failed. Discard bug is definite for Dante-only boards. Home gate is rules-correct — call out if user had Don at Home.

## Fix
- `Reaction_03003::getEligibleThugs` — discard without playerId; hand keeps playerId; exclude hand-banned Brutes.
- `tests/regression/Reaction_03003_Test.php` — Buratino+Dante(ControllerId0)+Bruno; Bruno-only; Home; legal hand Thug; enemy Thug.

## Do not regress
- Hand still filters by playerId (shared HAND location).
- City Reaction still requires Don in city, not Home.
- Discard Thugs with normal ControllerId still work.

## Follow-up: other hand→play Thug paths (user ask)

Audit of `createCharacterMusteredEvent` / hand Thug pickers:

| Path | Needs Recruit-Brute gate? |
|---|---|
| Play Brute (`getBrutesAvailableToPlayer`) | Already uses `canBePlayedAsBruteFromHand` |
| **Vittoria `Reaction_01014`** | **Yes — was listing all hand Thugs** |
| Don `Reaction_03003` | Yes — fixed earlier; refined for duel |
| Bravos `Action_01024` | No — discard only |
| Unyielding `Reaction_01032` | No — discards Thug as cost, no muster |
| CAD Recruit Maneuvers | No — muster from dueling line |
| Approach / Locker musters | No — not hand (Brutes can't enter Approach) |

Added `Character::canEnterPlayFromHand(Game)` — `canBePlayedAsBruteFromHand()` OR `IN_DUEL`. WHY not bare Play-Brute flag on Vittoria/Don: printed exception is during duel; SKILL already says Vittoria duel musters stay legal. Wired into both reactions. Tests cover Bruno blocked outside duel / allowed in duel.
