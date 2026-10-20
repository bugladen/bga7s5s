# Tempest (_05Teapot) En Garde Sorcerer Reaction

Printed: En Garde Sorcerer Reaction: After your performer moves to a City location • Move target opposing character Home.

## Shape

Single RiskReaction (Pattern D / D.2 flavour). Skill had no exact row; closest are Reaction_01173 (trigger on `EventCardMoved`, RiskReaction), Reaction_04058 (Sorcerer + pick target before pay + Sorcerer start/played) and Reaction_04020 (public fields, reaction buttons for the character chooser). Not a Maneuver/Action, no states, no JS.

## WHY these choices

- **Prototype CAD: no translate anywhere** (user asked for this card only). Plain strings in Name, description, buttons, notify, UserException. `createButtonProperty` still calls `$game->translate` internally - that's base-class, left alone.
- **Trigger-named performer**: "your performer" = the character in `EventCardMoved.cardId`. Gate `hasTrait('Sorcerer')` on it directly, no search for another Sorcerer.
- **En Garde gate is `Engaged || $event->engage`, not just `Engaged`.** `EventCardMoved` has `runEventHubAfterCards = true`, so card handlers run BEFORE the hub sets `Engaged = true` for an engaging move. A normal Move action is `engage=true`, so checking only `$performer->Engaged` would offer the Reaction for a performer who is engaged by the time it is played. (Monet `Reaction_04023` only checks `Engaged`; I did not touch it - possibly a latent bug there, not verified.) Consequence: after a basic Move this card is NOT offered. It only fires after non-engaging moves (reaction/technique/ability moves). That is by the rules text but may surprise playtesters.
- **Target location = `$event->toLocation`, not performer->Location**, at handler time (hub hasn't moved the performer yet). Stored in `$toLocation` and reused for button list / validation later.
- **Target picked before pay** (buttons `character-{id}`), effect applied on `EventRiskReactionTriggered` (cancel-during-pay discipline from D.2). `setUsed` only there. Pass does not setUsed.
- **`eventCheck` probe in performReaction**, mirrored from Reaction_03068: a "cannot move" effect throws UserException before the player pays. The real move event is queued without eventCheck at Triggered time (throwing inside event processing would be worse).
- **Move event carries `sourceId=risk`, `abilityId=reaction Id`** and Reaction implements `IAbilityThatTargetsCharacters` so Cross of the Martyrs style redirects (`getAbilityById`) treat it as a targeted ability. Risk implements `IRiskThatTargetsCharacters` (print says "target"). Did NOT add Cesca `beginCopy` support - Cesca copy is opt-in and was not requested.
- **Hide when no opposing controlled character at destination** - dead play otherwise (same discipline as D.1.1).
- Fields are `public` so they survive serialize across the offer / pick / pay requests.
- `engage=false` on the move Home: print says Move only.
- "Opposing" = opposing the performer at its (new) City location, so Home is never a target location.

## Tests

`tests/regression/cad/Reaction_05Teapot_Test.php` - 6 cases. `php tests/run.php`: 5032 passed, 0 failed.

## Opinion

Nothing hard here; the only real trap is the hub-after-cards ordering on `EventCardMoved`. No skill file update done - the skill has no row for "After your performer moves" Risk Reactions; worth adding one later if more of these show up.

## Skill update (create-risk)

Added Pattern D.7 to pattern-d.md, a shape-table row + companion index + trigger phrases + sibling sentence in SKILL.md, checklist item 77, and a references.md row. WHY these specifics: the one real trap is EventCardMoved's hub-after-cards ordering (engage/Location not applied at handler time), plus not resetting state from a failing trigger and eventCheck-probing before pay. Flagged Monet _04023 as a possible latent mismatch rather than fixing it.
