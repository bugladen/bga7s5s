# Bleed Out (01160) missed Cloud Bottle targeting

## Report
Reaction_05Terraria did not offer when Action_01160 (Bleed Out) was played on the equipped character.

## Root cause
Bleed Out implemented `IAbilityThatTargetsCharacters` but `actFromActionWithId` queued `EventCharacterBeingWounded` + `ActionResolved` directly — never `EventCharacterTargeted`. Terraria (and the modern "when targeted" cancel shape) only intercepts CharacterTargeted / ChallengeIssued. UL still caught Bleed Out via its wound hook; Cloud Bottle could not.

Same class as Torvo / Sanjay / Shoddy Craftsmanship / Red Tape: effects must wait for the targeting hook to survive.

## Fix
Mirror 01174 / Red Tape on `Action_01160`:
1. act pick → announce + batched `EventCharacterTargeted` only, bare `nextState()`
2. handleEvent on surviving CharacterTargeted → wound + ActionResolved with shared batchId
3. canceled CharacterTargeted → no wound

WHY not change Terraria: reaction was correct; the Risk was the missing emitter.

WHY not add ActionResolved special-case on Terraria cancel: same as Hexenjagd / UL-vs-01174 — canceled CharacterTargeted-gated Risk leaves EVENTS empty and advances; ActionResolved special-cases stay for second-state gates (01078 / 02034) only.

## Tests
- `Action_01160_Test`: act = CharacterTargeted only; survive = wound+resolve; cancel = empty
- `Card_05Terraria_Test`: Bleed Out abilityId offers the reaction
- 28/28 pass on those two suites

## Unfinished
Did not audit other core Risks that still wound/effect without CharacterTargeted despite `IAbilityThatTargetsCharacters` (e.g. older 01* wounders). Only fixed the reported card.

## Follow-up: "still not triggering"
User re-reported after CharacterTargeted fix. Integration script (discarded Bleed Out → act → fire CharacterTargeted through all cards):

- Uncontrolled Docks: Terraria offers, cancels hook, strips wound. Works.
- Controlled Docks: Covert gate blocks; CharacterTargeted survives; wound queues. Correct Covert behavior.

So remaining live misses are most likely (1) host at a controlled City location, or (2) Studio still running old Action_01160 without the CharacterTargeted emit. Not changing Terraria Covert gate — same as Seadog/Wardog/Quack.
