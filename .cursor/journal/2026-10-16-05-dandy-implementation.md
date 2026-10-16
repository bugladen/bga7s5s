# Claude de la Roche (_05Dandy)

Prototype CAD leader. User said do not add clienttranslate / _() on this card. Left Name, Title, Traits, Text, action names, state descriptions, and the wealth-pay status line as plain strings. Fixed the stub's unclosed `<p>` on the Influence line so the text box closes.

## Setup draw

EventTableSetup queues up to 4 EventCardDrawn. WHY no extra shuffle: buildDecks already shuffles the faction deck before SETUP_TABLE. The parenthetical "(After shuffling)" is that order. Cap with getCardsInLocation count, not countCardsInLocation — the test deck stub only implements getCardsInLocation, and an empty deck must not fall into playerDrawCard's discard reshuffle.

## -1 Influence per Renown

Aldo (_01007) is the same shape with the sign flipped, but Aldo sets Influence = printed + count and wipes attachment modifiers. This one keeps UnclampedInfluence (may be negative) and AppliedRenownPenalty.

WHY Unclamped: EventHub clamps ModifiedInfluence at 0. If Claude has 4 Influence and 10 Renown, the chip is 0. Restoring from the chip would not return to 4. Unclamped remembers -6 so dropping the Renown adds the penalty back.

WHY write ModifiedInfluence inside sync, not only via the queued event: CardMoved can be processed before that modifier event. If the chip still shows the old number, the Home restore sees "already 4" and skips, then the stale penalty event paints the dock penalty back on. Writing the chip immediately makes the next sync queue a real delta. The event is still queued so the client notif fires. NewInfluence is the clamped chip, never a negative.

Hooks: EventCardMoved (toLocation — hub has not written Location yet), EventCharacterMustered (event location — he can still be sitting in the faction deck on this object; hub usually moves him first, but the destination is the authority), EventRenownAdded/Removed at his current location (hub already updated CityLocation.Renown). Home and non-city = 0. onAbilitiesBlanked / Unblanked even though Fate's Silence cannot equip to a Leader. Someone else mustering at his location does not change his penalty; Renown did not change.

External EventCharacterInfluenceModified (reason is not our inject code) adds its delta onto Unclamped. Own events are ignored there so we don't apply the penalty twice.

User came back convinced this was a pressure reaction. It is not, and must not become one. Aníbal (_01094) and base Claude (_01184) add Influence only inside getInfluencePressureValue, so the card chip never moves and the bonus exists when a pressure is totaled. Dandy's text is a standing stat: -1 Influence per Renown at his location. Pressures read ModifiedInfluence afterwards. Do not move this onto EventPressureOccuring.

The location Influence labels were the thing that looked pressure-tied. They refreshed on moves and claims, not when Influence itself changed, so the board number stayed at the old total until a pressure recomputed it. eventAffectsLocationInfluenceTotals now includes EventCharacterInfluenceModified. The chip is already written before that event is queued, and the hub applies it before the label refresh, so the label matches the card.

## Skill

Wrote the traps into create-character so the next card does not "simplify" them away. Shape table + Pattern A "±N Influence per Renown" + "Setup draw after the existing shuffle" + Pattern C "In-play Action: Pay N Wealth" + references row + checklist 114–116. Aldo's reference row now says not to copy the absolute formula when the result can clamp at 0.

## Two actions

Action_05Dandya: Pay 1 Wealth, then move to any other city. Engage is not printed, so createCardMovingEvent engage=false.

Action_05Dandyb: Pay 2 Wealth, then remove 1 Renown from his city and createPlayerGainsReknownEvent. Offered only in a city with Renown > 0 and handWealthCount >= 2. If Renown is gone by the time he pays, he still paid (confirm already setUsed) and we notify instead of collecting.

User corrected the collect cost from 1 to 2 after the first pass. The move action stays at 1. Do not "simplify" them back to the same cost.

WHY pay is a hand pick, not PAY_STATE_IN_HAND_ACTION: that state is the in-hand Risk/Scheme pipeline. In-play confirm does not enter it. isValidWealthPayment: exact total, or total == cost+1 when a Wealth-trait card is included. Cost 1 is therefore any single hand card. Cost 2 is two plain cards, one Wealth card, or one Wealth card plus one plain card. Two Wealth cards are 4 and are rejected. The collect hand uses selection mode multiple; the move hand stays single. Confirm enables only on a legal total.

States return to HIGH_DRAMA_PLAYER_TURN_EVENTS after the discard is queued so the card leaves the hand before the location pick (05Dandya_2). Collect resolves in the same state after the discards are queued.

State ids: 4050141 pay-move, 40501412 location, 4050142 collect. Same 4 + card number + action digit scheme as Damya.

DandyWealthPayment is a private-method trait. playerCanPayOneWealth / queueOneWealthPayment are cost-1 wrappers so the move action does not repeat the overpay rule. Collect calls the cost argument directly.

Zombie on collect prefers one Wealth card, else the first two plain cards. A short hand resolves the action without paying. WHY: sending one plain card into queueWealthPayment throws, and a disconnected player should not stall the state.
