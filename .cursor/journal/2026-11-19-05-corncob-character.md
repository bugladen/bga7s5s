# _05Corncob (Cobb Roy) — finish Character

## Prior context
Setup firstPlayer null crash fix earlier today. Corncob stub open with Text; CAD prototype (no clienttranslate).

## Classification
1. **Forced: At the end of planning • Discard the top 3 cards of each deck.** → Pattern A on `EventPhasePlanningEnd`. "each deck" = each player's faction deck (City Deck is named explicitly elsewhere). Gate: ControllerId + `!characterIsInDiscardOrLocker`. Deck→player-discard via notify + `moveCard` (Reaction_04023 / Action_01134). Still-in-this-deck Location gate so FakeGame's shared `topFactionCards` does not re-mill into player 2's discard.
2. **Covert Action: Take control of a Weapon in any discard pile. Equip it to your character at this location, paying all costs.** → Pattern C CharacterAction. Covert = `cardInCity` + uncontrolled. No Engage → Back legal on Weapon pick (Vulture). "any discard pile" = city + all player piles. Hosts = controlled characters at Owner's location (04029 wording). Flow: Weapon chooseList → host Confirm → Teens take-control (ControllerId + remove-from-pile + hand + PAY_STATE_EQUIP_ATTACHMENT) → pay state. State ids `405013` / `4050132` / `4050133` (CardNumber 13; Snow White scheme also uses 13 but has no HD states). Only `"05Corncob"` + `"05Corncob_3"` on EVENTS — step 2 is `nextState("weaponChosen")` on the state's own transitions.
3. **Covert Technique: +1 [Parry].** → Pattern E subclass `Technique_PlusOneParry` + Covert gate (uncontrolled city) + duel actor-is-owner. Parry stays duel-only (parent generic). Pre-commit: `EventTechniqueCanceled` comment.

## WHY these mirrors
- Forced mills every faction deck, not City Deck — printed "each deck" vs cards that say "City Deck".
- Take-control pay uses Teens/01113 hand→PAY_STATE path so discount calc + equip finalize stay shared; not Tomas in-reaction pay (HD Action can use PAY_STATE).
- Covert Technique mirrors En Garde Technique_04021b shape but gates uncontrolled location instead of `!$Engaged`.
- Novel trait **Insimore** → TraitNames.
- Prototype CAD: plain strings, no clienttranslate.
- CardNumber 13 collides with Snow White scheme numbering — state id still 405013 because Snow White has no HD states.

## Touched
- `_05Corncob.php` — Forced + IHasActions + Techniques
- `actions/Action_05Corncob.php` — Covert Weapon salvage
- `techniques/Technique_05Corncob.php` — Covert +1 Parry
- States `405013`/`2`/`3`, states.inc.php, TraitNames Insimore
- On*.cad.js trio + EventHandlers pay
- tests: Card (3) + Action (7) + Technique (2) — 12/12 PASS

## Unfinished
None for this card.

## Follow-up 2026-11-19 — City Deck mill + per-deck announce
User: Forced did not hit City Deck; want a log announce per deck manipulated.
WHY prior miss: I read "each deck" as faction-only because other cards name "City Deck" explicitly — wrong for this print.
Fix: also mill top 3 of City Deck (01134 notify+moveCard). Per faction/city deck with ≥1 card: one announce message before the card-level discard notifs. Faction still Eisen (user/stub change). Test updated.
