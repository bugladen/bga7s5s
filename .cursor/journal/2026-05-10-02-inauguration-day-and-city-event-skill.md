# Inauguration Day (`_03cd08`) + Create-City-Event-Card Skill

## What happened

Finished the Forced ability on `modules/php/cards/faf/_03cd08.php` (Inauguration Day) and authored a new project-scoped skill at `.claude/skills/create-city-event-card/SKILL.md` to make future City Event Cards faster to stub out.

## WHY the implementation looks the way it does

The card text — "When a pressure occurs at this location • Count only the performer and en garde characters" — is *literally* the same effect as Claude de la Roche's City Reaction (`_01184`). I deliberately reused `Game::CLAUDE_PRESSURE_TYPE` + `Game::CLAUD_ID` rather than minting a new flag.

Reasons:
- `UtilitiesTrait::pressureLocation()` already filters via `$character->Id == $performer?->Id || !$character->Engaged` keyed off `CLAUD_ID`'s `Location`. That's exactly the requested behavior. A new flag would be duplicated logic.
- `CLAUD_ID` is "the card whose location defines the affected pressure," not "Claude the character" — the name is misleading but the contract fits this card too. Setting `CLAUD_ID = $this->Id` makes the location filter resolve to the Inauguration Day card's own location.
- The flag is binary, so reusing it doesn't interfere with any other pressure flag that might be set simultaneously.

The Forced vs Reaction distinction is purely about user choice: Claude's effect is opt-in (Reaction asks), Inauguration Day's is mandatory (Forced fires in `handleEvent`). That meant no Reaction class, no buttons, no `nextState`/transition — just set the flag and notify. Same data, simpler control flow.

### Alternatives considered
- **New `INAUGURATION_DAY_PRESSURE_TYPE` flag + branch in `pressureLocation`.** Rejected: it would be a copy-paste of the `CLAUDE_PRESSURE_TYPE` branch. If a future card ever wants performer/en-garde-only counting *plus* something else, that's the time to split.
- **Trigger on `EventLocationPressured` instead of `EventPressureOccuring`.** Rejected: by then `pressureLocation()` has already tallied with the unfiltered character list. `EventPressureOccuring` fires before the tally, which is where the existing pattern hooks in (verified via `_01006` Don Constanzo and `tac/_02044` Solomonia).

## WHY the skill exists

I had to re-derive the CityEventCard mental model from scratch:
- which base class is in use,
- whether City Actions / Reactions / Forced use different scaffolding,
- which pre-commit hook patterns apply to event cards specifically,
- which pressure-type flag already exists for "performer + en garde only."

A future session implementing `_03cdNN` will hit the same questions. The skill captures:
- The base anatomy (`Name`, `Image`, `CityCardNumber = N`, `CardNumber = 0`, etc.).
- The three ability shapes (Forced / City Action / City Reaction) and which interfaces + traits + sibling-class files each requires.
- The pressure-flag reuse table — so future implementers don't add a redundant flag.
- The pre-commit hook rules (lifted from CLAUDE.md but contextualized for events).
- A reference-card list pointing at the cleanest exemplars in `faf/` and `_7s5s/`.

### Skill scoping decisions
- Made it project-local (`.claude/skills/create-city-event-card/`) rather than global — it's tightly coupled to this repo's class layout.
- Description is "pushy" per the skill-creator guidance — names the file pattern (`_03cdNN.php`) and the trigger phrases ("implement this city event", "finish _03cdNN") so it actually fires on natural user prompts.
- No external assets / scripts — the entire skill fits in `SKILL.md`. Anything more would be premature.

## Skill rewrite after re-examining the `faf` branch

User pointed out I'd written the skill against the wrong branch state — I'd only seen the bare `_03cd08` stub and Inauguration Day's Forced. The `faf` branch actually has fully-implemented `_03cd01` (Penya), `_03cd03` (Chance Meeting), and `_03cd05` (Devil Jonah's Bones), plus their state files, JS wiring, and journal entries. The first draft of the skill missed several patterns:

- **State class files in `modules/php/States/<expansion>/`** that extend `Bga\GameFramework\States\GameState` with `#[PossibleAction]` attributes. New states are NOT added to `states.7s5s.php` — only the transition strings are registered in `states.inc.php` under `HIGH_DRAMA_PLAYER_TURN_EVENTS`.
- **State ID convention** `403XXXX` for expansion 3 (4 = high drama, 03 = expansion, XX = card number, optional `_2` suffix).
- **`EventCityAction` vs `CharacterAction`** as base classes — events that get discarded after one use extend `EventCityAction`, while a `CityCharacter`'s action extends `CharacterAction`. Penya is a CityCharacter, not a CityEventCard, so its action is `CharacterAction`; Chance Meeting is a true event so it uses `EventCityAction`.
- **Per-expansion JS files** (`OnEnteringState.faf.js`, `OnLeavingState.faf.js`, `OnUpdateActionButtons.faf.js`) — without these, the state activates server-side but the UI is dead.
- **Multi-player sequential loops** via queued `EventTransition` per eligible player (initiative order), with a remaining-players JSON global popped after each acts. NOT `multipleactiveplayer`, because text says "in order of Initiative."
- **Event priority gotcha**: `TRANSITION_PRIORITY=8` vs `MEDIUM_PRIORITY=3` (lower = runs first). If you queue discard up front alongside transitions, the discard fires before any transition resolves. Solution: defer discard until the loop ends. This is captured in the Chance Meeting journal and now in the skill.
- **`CURRENT_PLAYER` vs `getActivePlayerId()`** at the end of a multi-player loop — the active player is whoever just acted, not the player whose high-drama turn this is. Use `CURRENT_PLAYER` for action-resolved.
- **CLAUDE.md is right about `setUsed`/`announceAction`/`resetPlayerPassCount`**: NOT called from CharacterAction subclasses — central code handles them. The Penya journal's "✓" claim is misleading; Action_03cd01.php doesn't actually call them.
- **Discard helper**: `EventFactory::createCardAddedToCityDiscardPileEvent($playerId, $cardId, $location, $sourceId, $asEffect=true)`.

Also reorganized the reference table — `_03cd01` is now flagged as the canonical full-flow example because it shows the most layers in action.

## Things I'm not sure about

- The `CLAUD_ID` global lifetime. I assumed it gets cleared on dusk/new-day with the other pressure-type flags, since Reaction_01184 has no explicit cleanup either and `setUsed` only resets the reaction availability — not the global. Worth grepping for where `PRESSURE_TYPE` resets if Inauguration Day ever exhibits sticky behavior between days.
- I did not verify in BGA Studio (deployment is SFTP-only). Smoke test: place Inauguration Day at a city location, trigger any pressure there, confirm only the performer + en garde characters contribute to the tally.
- The CLAUDE.md says the journal path for this project is `.cursor/journal/` (project-local), not `C:\repos\magnus\journal\7s5s\`. I followed the project-local path since the instructions say "if {project-name} is 7s5s write notes in `{project-name}\.cursor\journal\datetime.md`". The session-start guidance about `C:\repos\magnus\journal\` is contradictory but I trust the explicit project rule.
