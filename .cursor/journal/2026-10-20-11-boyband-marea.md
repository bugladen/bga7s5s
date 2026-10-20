# La Marea Di Ambrogia (_05BoyBand) Reaction

Previous session context: Teapot (_05Teapot) Tempest, same expansion, same "prototype, no translate" instruction.

Printed: Reaction: When the adversary announces a combat card • Swap your participant with your Thug or Duelist at this location. Then, add a threat to both participants.

## Shape

Single `RiskReaction` (`Reaction_05BoyBand`), no states / JS / Action / Maneuver. The skill has no exact row. I composed it from:
- Trigger: `Reaction_02039` / `Reaction_04022` (`EventCombatCardAnnounced`, `IN_DUEL`, announcer != owner controller, hand-only guard).
- Pick-before-pay with `character-{id}` reaction buttons + Pass, effect on `EventRiskReactionTriggered`: `Reaction_05Teapot`.
- Swap: `Theah::swapParticipantsInDuel` as in `Maneuver_03069a` (Harpoon gate).

## WHY these choices

- **Prototype CAD: no translate** (user asked, this card only). Plain strings in card, Name, description, buttons, notify, UserException. `createButtonProperty` still translates internally - base class, left alone.
- **No `IAbilityThatTargetsCharacters` / `IRiskThatTargetsCharacters`**: print says "Swap ... with your Thug or Duelist", never "target". Skill rule: friendly chooser without "target" wording is not Cesca-eligible.
- **"Your participant" = challenger/defender whose ControllerId == owner's** (not `getDuelRoundActor`): the adversary is the round actor when they announce, so the actor is the wrong side. Controller id is unambiguous in a 2-player duel.
- **Swap partner filter**: other characters I control at the participant's Location with trait Thug or Duelist. "At this location" = participant's location (the duel location).
- **Harpoon handled at three points** (hide offer, reject pick before pay, central gate in `swapParticipantsInDuel`): same discipline as `Maneuver_03069a` so the player never pays and then fails mid-event.
- **Effect deferred to `EventRiskReactionTriggered`**: a cancel reaction can fire between pay and trigger. `setUsed` only there; Pass does not spend (card stays in hand for a later announcement).
- **Threat = `createThreatModifiedEvent(1, 1)` queued AFTER the swap.** Mid-round, so ThreatModified (not PENDING_* like end-of-round producers). Threat is stored per side (challenger/defender), so (1,1) hits "both participants" including the freshly swapped-in one. "Then" ordering preserved by queue order.
- **Re-validate on trigger** (`isSwapStillValid`): if participant or partner changed during pay, fizzle with a notify, still spend the Risk (Teapot does the same - Risk was paid).
- Public fields `participantId` / `chosenSwapId` so state survives the offer / pick / pay requests.

## Not done / open

- Did not check whether the swapped-in character should be barred if engaged/wounded - print has no restriction, so none added.
- Skill updated afterwards (user asked): added Pattern D.8 to `pattern-d.md`, a shape-table row + companion-index text + trigger phrases + sibling sentence in `SKILL.md`, checklist item 78, references row. WHY a new pattern rather than folding into D.7: the reusable lessons are different from Teapot's (participant-by-controller vs round actor at announce time; mid-round ThreatModified vs PENDING; Harpoon in three places; harness quirks like button key `reaction` and no `Assert::throws`).
- No Cesca opt-in.

## Tests

`tests/regression/cad/Reaction_05BoyBand_Test.php` - 6 cases (offer+swap+threat, pass, gates, no partner, Harpoon, bad partner). Full `php tests/run.php`: 5038 passed, 0 failed.
Harness note: no `Assert::throws`; used a local `assertRejects` helper. Button array key is `reaction` (not `id`).

## Opinion

Straightforward once composed from existing pieces. The only real judgement call was identifying "your participant" by controller rather than round actor.
