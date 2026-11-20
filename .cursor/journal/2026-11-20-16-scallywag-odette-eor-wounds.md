# Scallywag reaction missed Odette end-of-round wounds

## Report
Eddie: `Reaction_05Scallywag` not triggering when Odette ended the round and took 2 wounds (leftover threat → CharacterBeingWounded → CharacterWounded).

## Root causes (two)

### 1. Challenger-only duel gate too narrow
Original: in duel, owner must control the **challenger**. Text "during their duel" — "their" reads as the **Leader's** duel. If Odette challenged and then took EoR leftover threat, we were defender → silent no-offer.

**Fix:** In duel, offer when owner controls **any** participant and wounded Leader is a participant (either seat). Non-duel refuse path unchanged (CHOSEN_PERFORMER + CHOSEN_TARGET).

### 2. Leftover-threat CharacterWounded ran *after* EventDuelEndOfRound
`stDuelEndOfRound` queues BeingWounded then EndOfRound (both MEDIUM). Hub converts BeingWounded → CharacterWounded as a *new* MEDIUM event → later event_id → drains **after** EndOfRound movers. WHY comment wanted wounds before movers; async queue broke that. Wound reactions (Scallywag) saw the board post-mover; also risked odd location capture.

**Fix:** leftover-threat BeingWounded = `HIGH_PRIORITY`; EventHub CharacterWounded **inherits** BeingWounded priority so the wound pair drains before MEDIUM EndOfRound.

## Not the bug (still silent skips)
- No Pirate in Approach → no offer (by design; Muster = Approach).
- Scheme not at Home / already Used.

## Tests
- Scallywag: defender-side offer + spectator no-offer (8 pass).
- StatesTrait_DuelEnd: assert HIGH_PRIORITY on leftover BeingWounded (30 pass).

## Skill docs
create-scheme SKILL / reactions / checklist / helpers / references updated away from challenger-only.
