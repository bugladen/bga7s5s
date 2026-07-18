> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Reference Implementations

| File | What it demonstrates |
|---|---|
| `modules/php/cards/faf/_03cd01.php` | **Canonical CityCharacter.** Negotiable + dashed stats + `canIntervene` ban + `eventCheck` backstop + paired City Forced (duel and would-be-wounded) + self-listening `EventCardRemovedFromPlay` cleanup + IHasActions wiring. |
| `modules/php/cards/faf/actions/Action_03cd01.php` | Two-step `CharacterAction` (companion → adjacent location), `CHOSEN_TARGET` global between steps, `engage as cost / move with engage=false`. |
| `modules/php/States/faf/State_highDramaPhase03cd01.php` | First-step state (character picker). |
| `modules/php/States/faf/State_highDramaPhase03cd01_2.php` | Second-step state with `<` back button + location picker. |
| `modules/js/OnEnteringState.faf.js` | UI setup for both Penya steps. |
| `modules/js/OnUpdateActionButtons.faf.js` | Action buttons for both Penya steps. |
| `modules/php/cards/_7s5s/_01186.php` (Maryam) | Comparison for `EventCharacterBeingWounded` + `canceled = true` pattern, with a source filter Penya intentionally omits. |
| `modules/php/cards/faf/_03cd10.php` (Julius Caligari) | **Canonical CityCharacter Reaction.** Negotiable + `IHasReactions`/`ReactionTrait` wiring. |
| `modules/php/cards/faf/reactions/Reaction_03cd10.php` | Multi-step button-based reaction (letter → trait → opposing-character target). `EventCharacterRecruited` + `EventCardMoved` triggers, valid-target precondition gate, the `EventCardMoved.runEventHubAfterCards = true` gotcha, `TraitNames::$TraitsJson` consumption, `getOpposingCharactersAtLocation`, random reveal from hand, conditional wound. |
| `modules/php/cards/_7s5s/reactions/Reaction_01014.php` (Vittoria) | Multi-step reaction on a non-City Character — same button-cycling pattern, broader event coverage (engage / engard / move / wound / heal / challenge). |
| `modules/php/cards/faf/_03cd18.php` (Kalla and Adelheide) | CityCharacter with branching post-recruit Reaction. |
| `modules/php/cards/faf/reactions/Reaction_03cd18.php` | Branching "Choose one" reaction: choose → searchA \| moveB → destroyB. Demonstrates per-option validity gates at the choose stage, dedupe-by-Name in the deck picker, "no `< Back` once events commit" (destroyB omits it because moveB queued an `EventCardMoving`), the search-deck recipe with mandated shuffle, and the unequip+discard destroy recipe. |
| `modules/php/cards/_7s5s/_01098.php` (Cat's Embargo) | "Reveal a random card from a hand" reference implementation. |
| `modules/php/cards/tac/actions/Action_02045.php` (Path to Poluchatel) | "Search your deck for a card matching a Trait, reveal it, add to hand, shuffle" reference (Scheme City Action, but the search recipe applies anywhere). |
| `modules/php/cards/_7s5s/actions/Action_01174.php` | "Destroy a non-Unique attachment in play" reference — the canonical unequip + discard sequence. |
| `modules/php/Traits.php` | `TraitNames::$TraitsJson` — canonical Trait list for "Name a Trait" pickers. Add new Traits in alphabetical order. |
| `modules/php/cards/CityCharacter.php` | Base class. Read for the `Negotiable` field and inheritance chain. |
| `modules/php/cards/Character.php` | Parent. `canIntervene` / `canChallenge` defaults and wound/heal handling live here. |
| `modules/php/theah/Theah.php::interventionCheck` (~line 1651) | Where `canIntervene` is consumed by the engine. |
