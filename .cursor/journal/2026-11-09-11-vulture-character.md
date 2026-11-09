# _05Vulture (Gusev Grabiezca) — finish Character

## Prior context
Refuge Risk done earlier today. Vulture stub open with Text set; CAD prototype (no clienttranslate).

## Classification
1. **Covert Action: Wound target opposing Scion or equipped character.** → Pattern C CharacterAction + `IAbilityThatTargetsCharacters` (text says target). Covert = `cardInCity` + uncontrolled. No Engage printed → trichotomy (c) never engages. Same-location opposing; Scion OR `countsAsEquipped`. State `405023`.
2. **Reaction: When a character at this location is destroyed • Take control of their equipped attachment and equip it to a character you control paying all costs.** → Pattern D multi-stage CardReaction. Mirror Tomas stash-on-Unequip (`IsDying`) + CharacterDestroyed trigger; Teens take-from-discard + ControllerId; Tomas/Kaj click-to-pay (never PAY_STATE_EQUIP_ATTACHMENT — destroy can fire outside HD).

## WHY these mirrors
- Attachments unequip+discard BEFORE CharacterDestroyed (Character::unEquipAllAttachments). Stash on Unequip when host `IsDying` so non-destroy unequips do not pollute.
- Teens uses PAY_STATE because it is an HD Action; Vulture Reaction must use in-reaction click-to-pay (Tomas/Kaj).
- Equip hosts = controlled non-dying at Gusev's location (user: same location as destroy trigger / Tomas scope).
- No Artifact exclusion (Tomas text says non-Artifact; Vulture does not).
- Self-destroy legal (text lacks "another" — Odette excludes self; Vulture does not).
- FakeAttachment skipped (Teens discipline).
- Covert Action Back is legal (no Engage paid) — contrast Teens.

## Touched
- `_05Vulture.php` — IHasActions + IHasReactions
- `actions/Action_05Vulture.php` — Covert wound
- `reactions/Reaction_05Vulture.php` — destroy salvage
- `States/cad/State_highDramaPhase05Vulture.php`, States.php `405023`, states.inc.php `"05Vulture"`
- On*.cad.js trio
- `tests/regression/cad/Action_05Vulture_Test.php` (4) + `Reaction_05Vulture_Test.php` (6) — 10/10 PASS
- Skill: SKILL.md shape row, pattern-d subsection + table, references.md, checklist 78a

## Unfinished
None for this card.

## Bug report 2026-11-09 — Action unavailable vs opposing Scion
User: Gusev at unclaimed location with opposing Scion; Action not offered.
Root cause: opposing Scion was Kaspar `_03014` (`canBeWoundedByOpponentAbilities()=false`). Availability filters immune targets (Cesca shape) so a wound Action with only Kaspar present correctly stays hidden. Not a Covert/`isControlled` bug. Added regression test.
