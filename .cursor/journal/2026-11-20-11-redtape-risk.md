# _05RedTape Customs Inspection

Finishing CAD Risk via create-risk skill. User: no clienttranslate / translate / _().

## Orientation
Prior today: Terraria Covert Reaction cancel-on-targeted + Academic equip discount. Red Tape Text:
`Academic Action: Target an opposing engaged character • Their controller reveals four cards from hand. Choose a revealed card and sink it.`

Art stub: WealthCost 0, R/P/T 1/1/1, traits Beaurocracy(typo)/Duress, Castille, CardNumber 26.

## Text → shapes
1. Academic Action (not City) → RiskAction + Academic performer gate; home eligible in principle (opposing at Home is rare so greys).
2. Printed Target opposing engaged → Cesca (IRiskThatTargetsCharacters + IAbilityThatTargetsCharacters). Opposing = same location + different controller. Engaged gate. Grey when controller hand empty (cannot reveal/choose).
3. Reveal four from hand → random like Julius `_03cd10` / Technique_03043 (`array_rand`, `min(4, hand)`). Not Giacinto full-hand reveal.
4. You choose one revealed → sink → chooseList (Hacked / 03043 shape), not opponent factionHand pick (04032_5). Sink = Reaction_03006 hand→faction-deck bottom + `cardRemovedFromHand` (EventCardAddedToFactionDeck alone skips hand UI notify).

## WHY
- CharacterTargeted before reveal (Teens/04040) so UL/Terraria can cancel; reveal+`05RedTape_2` only on survival.
- Sticky `$revealedCardIds` on Action — Risk already in discard at resolve; buildCity loads discard.
- Bureaucracy not Beaurocracy (TraitNames + skill stub hygiene).
- Random four via array_rand min(4,hand) — print says "reveals four" without "choose which four"; Julius/03043 are the random-reveal mirrors. Giacinto reveals full hand for a different effect.
- Sink via Reaction_03006 sync path not EventCardAddedToFactionDeck — hand UI needs cardRemovedFromHand.
- Both EVENTS keys: `_2` is queued from CharacterTargeted handler via createTransitionEvent.

## Shipped
- `_05RedTape` + `Action_05RedTape` + States 405026/2 + cad JS trio
- Skill B.15 (pattern-b / SKILL / references / checklist)
- `Action_05RedTape_Test` — 7 pass

## Unfinished / watch
- Did not add Cesca Reaction_01008 allow-list — print has no "cannot be copied" and Cad prototypes usually skip copy until asked.
- Zombie on `_2` picks first revealed card; fine for harness.
