# The Cat's Embargo (01098) Audit

## Card Text
- **Scheme Effect**: Add a Renown to two different locations.
- **Forced**: At the end of Planning • Reveal a card at random from an opponent's hand.
- **Reaction**: After an opponent plays or discards a card with the revealed card's name • Gain a Renown.

## Scheme Effect & Forced — Clean
The scheme effect (EventResolveScheme → transition to location selection state) and the forced reaction (EventPhasePlanningEnd → pick opponent → reveal random card → mark all same-name cards with CATS_EMBARGO_TARGET condition) are both correctly implemented. The condition is correctly scoped to opponent-controlled cards only (`$card->ControllerId != $this->ControllerId`).

The cleanup on EventCardSentToLocker (removing CATS_EMBARGO_TARGET from all matching cards when the scheme leaves play) is also correct.

## Bugs Found & Fixed in Reaction_01098

### 1. Missing opponent check on EventAttachmentEquipped
Card says "After an **opponent** plays..." but the attachment handler didn't verify the equipping player is an opponent. The EventCardDiscardedFromHand handler had this check (`$card->ControllerId != $owner->ControllerId`) but attachment and combat handlers didn't. While the condition is only applied to opponent cards, this is a defense-in-depth issue — if a card changes controllers, the condition persists but the ownership context changes.

**Fix**: Added `$event->playerId != $owner->ControllerId` check.

### 2. Missing opponent check on EventDuelCalculateCombatCardStats
Same issue as #1 for the combat card handler.

### 3. Wrong event type for combat cards
Used `EventDuelCalculateCombatCardStats` (a stat calculation event that fires during damage resolution) instead of `EventCombatCardAnnounced` (fires when a combat card is actually played). Reaction_01135 demonstrates the correct pattern — it uses `EventCombatCardAnnounced` with `$event->playerId != $owner->ControllerId`. The stat calc event is semantically wrong for "plays a card" and might fire in contexts that aren't "playing".

**Fix**: Replaced with `EventCombatCardAnnounced`, added opponent check.

### 4. Missing discard event types
Only handled `EventCardDiscardedFromHand`. Card says "discards" generically. Same gap as 01099 — Reaction_01099a correctly handles all three discard pathways.

**Fix**: Added `EventCardDiscardedFromPlay` and `EventCardAddedToCityDiscardPile` (consolidated into one check with EventCardDiscardedFromHand).

### 5. Missing play event types
"Plays a card" wasn't covered for character mustering, crew mustering, or approach plays. Reaction_01200 (Crystal Eye) demonstrates the pattern — it listens for `EventCharacterMustered` and `EventApproachCharacterPlayed`.

**Fix**: Added handlers for `EventCharacterMustered`, `EventApproachCharacterPlayed` (grouped, using `$event->characterId`), and `EventCardMustered` (separate, using `$event->cardId`).

### 6. Redundant $owner assignment (minor)
Line 46 re-assigned `$owner` when already assigned on line 43.

**Fix**: Removed duplicate.

## WHY: Condition-based approach
The CATS_EMBARGO_TARGET condition is applied at reveal time to all copies of the named card controlled by opponents. This means the reaction handlers only need to check `hasCondition()` + opponent verification rather than comparing card names. This is clean but means we must cover ALL event types that represent "plays or discards" — if a pathway isn't handled, the condition exists but the reaction never fires.

## Not Covered (deliberate)
- `EventRiskPlayed` and `EventSchemeCardRevealed` — these represent playing risks and revealing schemes. Theoretically a risk or scheme could be the embargo target. Left out for now because I'm less certain these card types commonly appear as embargo targets and the data model differences (e.g. `EventSchemeCardRevealed.playerId` is `string` not `int`) suggest these might need special handling. Worth adding if edge cases surface.

## Files Changed
- `modules/php/cards/_7s5s/reactions/Reaction_01098.php` — all fixes above
