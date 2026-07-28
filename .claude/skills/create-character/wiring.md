> Part of **create-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## JS Wiring (required for new state classes)

Same as `create-city-character`'s "JS Wiring" section. For every new state, wire BOTH:

- `modules/js/OnEnteringState.<expansion>.js` — highlight selectables, mark already-chosen characters.
- `modules/js/OnUpdateActionButtons.<expansion>.js` — `Confirm` button (`actChooseCardSelected` + `onChooseInPlayCardConfirmed`).
- `modules/js/OnLeavingState.<expansion>.js` — cleanup highlights when leaving the state.

Reusable client-side handlers:
- Character / in-play card selection: `onChooseInPlayCardConfirmed()` + `highlightCardsAsSelectable(ids)`.
- Location selection: `onCityLocationsSelected()` + `makeCityLocationSelectable(element)`.
- Marking a "chosen" character: `dojo.addClass($(`${card.divId}_image`), '_7sfs-chosen')`.

If your state reuses an existing client action (e.g. `onMusterCardSelected`), extend the action map in `modules/js/PlayerActions.js`.

For new expansion JS files (`*.<expansion>.js`), make sure the chain to the master JS files exists — `faf`, `tac`, and `_7s5s` are already chained.

### City-location picker — full wiring (Technique example)

For a Technique/Action that prompts "choose any city location" (`Technique_03025b` Angeline's Gambling relocation):

**Backend — the technique:**

```php
public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
{
    $args = parent::getArgsFromTechnique($game, $state, $stateName);
    if ($state == States::DUEL_CHOOSE_TECHNIQUE_03025B) {
        $args["locationIds"] = array_keys($game->theah->getCityLocations());  // not hardcoded
    }
    return $args;
}

public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void
{
    parent::actFromTechniqueWithIds($game, $state, $stateName, $ids);
    if ($state == States::DUEL_CHOOSE_TECHNIQUE_03025B) {
        $location = $ids[0];
        if (! array_key_exists($location, $game->theah->getCityLocations())) {
            throw new \Bga\GameFramework\UserException($game->translate('Invalid location.'));
        }
        // ... queue createCardMovingEvent etc.
    }
}
```

WHY `$theah->getCityLocations()` and not a hardcoded array: the city has 3 locations in 2p, 4 in 3p, 5 in 4p (Ole's Inn and Governor's Garden are excluded in smaller games). Hardcoding breaks player-count adaptation. Also, the constants `Game::LOCATION_BORDELLO` / `LOCATION_CATHEDRAL` / `LOCATION_DOCKS` / `LOCATION_MARKET` / `LOCATION_OLES_INN` **do not exist** — the real constants are `LOCATION_CITY_DOCKS`, `LOCATION_CITY_FORUM`, `LOCATION_CITY_BAZAAR`, `LOCATION_CITY_OLES_INN`, `LOCATION_CITY_GOVERNORS_GARDEN`. `getCityLocations()` sidesteps both problems.

**State class — the `#[PossibleAction]` must be `actFromCardWithLocations`:**

```php
#[PossibleAction]
public function actFromCardWithLocations(string $locations): void
{
    $this->game->actFromCardWithLocations($locations);
}
```

**NOT** `actFromCardWithIds`. The JS calls `onCityLocationsSelected()` → `bgaPerformAction('actFromCardWithLocations', { locations: JSON.stringify(...) })`. If the state's `#[PossibleAction]` is `actFromCardWithIds`, the framework reports "This move is not authorized now" — the action name doesn't match. The framework's `actFromCardWithLocations` then JSON-decodes the locations and forwards them as the `$ids` array into the card's `actFromCardWithIds` → `actFromTechniqueWithIds`, so the technique still receives the locations through the `$ids` parameter — only the entry-point name differs.

**JS — `OnEnteringState.<expansion>.js`:**

```js
'duelChooseTechnique_03025b': () => {
    if (this.isCurrentPlayerActive()) {
        this.clientStateArgs.locationIds = this.gamedatas.gamestate.args.locationIds;
        this.numberOfCityLocationsSelectable = 1;
        this.selectedCityLocations = [];
        this.clientStateArgs.locationIds.forEach((locationId) => {
            const imageElement = this.getCityLocationElement(locationId);
            this.makeCityLocationSelectable(imageElement);
        });
    }
},
```

**JS — `OnUpdateActionButtons.<expansion>.js`:**

```js
'duelChooseTechnique_03025b': () => {
    this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
    dojo.addClass('actCityLocationsSelected', 'disabled');
},
```

**JS — `OnLeavingState.<expansion>.js` — use `resetCityLocations()`, NOT `clearCityLocationAsSelectable`:**

```js
'duelChooseTechnique_03025b': () => {
    if (this.isCurrentPlayerActive()) {
        this.resetCityLocations();
        this.selectedCityLocations = [];
        this.numberOfCityLocationsSelectable = 0;
    }
},
```

There is no `clearCityLocationAsSelectable` function — that's a hallucinated name. The existing helper is `resetCityLocations()` (in `modules/js/Utilities.js`), which strips `_7sfs-selectable` / `_7sfs-selected` / `_7sfs-chosen` and the pointer cursor from every active city location element (plus the player Home endcap). Every existing location-picker cleanup in `OnLeavingState.tac.js` uses it; mirror that.

### chooseList sink / reorder — `EventHandlers.js` is mandatory

`OnEnteringState` + `OnUpdateActionButtons` alone are **not** enough for chooseList multi-select or reorder chips. Selection clicks route through `EventHandlers.js` → `onChooseCardClicked`. The **default** else branch only enables Confirm when `getSelectedItems().length === 1` and never calls `addSortTagToCard`.

| State purpose | Required `onChooseCardClicked` behavior | Mirror |
|---|---|---|
| Multi-select sink ("sink any / one or both") | Enable Confirm when `length > 0` | `highDramaPhase04cd15`, `duelChooseTechnique_04001` |
| Reorder ("return in any order") | `this.addSortTagToCard(item_id)` + enable when all items selected | `highDramaPhase04cd15_2`, `duskPhaseBegin03052_2`, `duelChooseTechnique_04001_2` |

Symptom if missing reorder wiring: cards select but **no number-order chips** appear. Symptom if missing multi-sink wiring: Confirm stays disabled when 2+ cards are selected.

Private Look states read cards from `args.args._private.args.cards` (from `argsForStatePrivate`), not `args.args.args.cards`.

## Pre-Commit Hook (relevant subset)

`.githooks/pre-commit` enforces, for the files you touch when implementing a Character or Leader:

| Pattern | Required |
|---|---|
| `extends CardAction/RiskAction/RiskCityAction` (regex literal — does NOT match `CharacterAction` directly, but the convention still applies) | `createActionResolvedEvent()` somewhere in the class. |
| **Forbidden in `CharacterAction` subclasses** | `$this->setUsed()` / `$this->resetPlayerPassCount()` / `$this->announceAction()` — these run centrally. |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed(` AND `$this->isAvailable(` (literal strings; the hook is grep-based). For a Continuous Reaction (no actual `setUsed(true)` call at runtime), keep the literal in a comment so grep matches — see "Continuous Reaction" in Pattern D. |
| `implements ISorcererAbility` | both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()`. |
| Class implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` | **Forbidden.** Split into two classes. |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()`. |

The card class itself (`_NNNNN extends Character` / `extends Leader`) has no hook-mandated calls — the requirements apply to the Action/Reaction subclasses that live next to it.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Card class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:     `...\cards\<expansion>\actions`
  - Reaction:   `...\cards\<expansion>\reactions`
  - State:      `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`
- **"Opposing"** means BOTH different controller AND same location. Never roll your own `ControllerId !=` filter.
- **`TraitNames::$TraitsJson`** (`modules/php/TraitNames.php`) is the canonical Trait list for "Name a Trait" pickers. Add new Traits in alphabetical order when a card introduces one (e.g. Protégé on `_04001`).

