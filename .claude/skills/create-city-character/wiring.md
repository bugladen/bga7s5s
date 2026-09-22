> Part of **create-city-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## JS Wiring (required, easy to forget)

For every new state, wire BOTH:

### `modules/js/OnEnteringState.faf.js`

UI setup — highlight selectables, mark already-chosen characters, set selection counts.

```js
'highDramaPhase03cdNN': () => {
    if (this.isCurrentPlayerActive()) {
        this.numberOfCardsSelectable = 1;
        this.highlightCharacterChosen(args.args.args.performerId);
        this.clientStateArgs.performerId = args.args.args.performerId;

        this.clientStateArgs.ids = args.args.args.ids;
        this.highlightCardsAsSelectable(args.args.args.ids);
    }
},
```

For a location-selection second step:

```js
'highDramaPhase03cdNN_2': () => {
    if (this.isCurrentPlayerActive()) {
        this.numberOfCityLocationsSelectable = 1;
        args.args.args.locationIds.forEach((locationId) => {
            const imageElement = this.getCityLocationElement(locationId);
            this.makeCityLocationSelectable(imageElement);
        });

        // Visually mark already-chosen characters so the player remembers
        let card = this.cardProperties[args.args.args.performerId];
        dojo.addClass($(`${card.divId}_image`), '_7sfs-chosen');
        this.clientStateArgs.performerId = args.args.args.performerId;

        card = this.cardProperties[args.args.args.targetId];
        dojo.addClass($(`${card.divId}_image`), '_7sfs-chosen');
        this.clientStateArgs.targetId = args.args.args.targetId;
    }
},
```

### `modules/js/OnUpdateActionButtons.faf.js`

```js
'highDramaPhase03cdNN': () => {
    this.addActionButton(`actChooseCardSelected`, _('Confirm'), () => this.onChooseInPlayCardConfirmed());
    dojo.addClass('actChooseCardSelected', 'disabled');
},

'highDramaPhase03cdNN_2': () => {
    this.statusBar.addActionButton('<', () => this.bgaPerformAction('actBack', {}), { id: 'actBack', color: 'alert' });
    this.addActionButton(`actCityLocationsSelected`, _('Confirm Location'), () => this.onCityLocationsSelected());
    dojo.addClass('actCityLocationsSelected', 'disabled');
},
```

Reusable client-side handlers:
- **Character / in-play card selection**: `onChooseInPlayCardConfirmed()` + `highlightCardsAsSelectable(ids)`.
- **Location selection**: `onCityLocationsSelected()` + `makeCityLocationSelectable(element)`.
- **Marking a "chosen" character (carry-over visual)**: `dojo.addClass($(`${card.divId}_image`), '_7sfs-chosen')`.

If your state uses an existing client action like `onMusterCardSelected`, extend the action map in `modules/js/PlayerActions.js` to include the new state name. Forgetting this is a common cause of "the button does nothing in my new state."

Location confirm via `onCityLocationsSelected` defaults to `actFromCardWithLocations` when the state is not in the special `actionMap` — Astrid / Penya-style location pickers need **no** `PlayerActions.js` edit.

Character Confirm via `onChooseInPlayCardConfirmed` likewise defaults to `actFromCardWithId` when the state name is absent from that map — Forced opposing pickers (`highDramaPhase04cd14`) need **no** `PlayerActions.js` edit. Do **not** add a Pass button for interactive Forced.

The expansion JS files (`*.faf.js`, `*.bas.js`) are already chained from the master JS files — no extra include wiring needed for `faf` / `bas`. For a new expansion, ensure the chain is in place (`seventhseacityoffivesails.js` loads `OnEnteringState.<exp>.js`, `OnUpdateActionButtons.<exp>.js`, `OnLeavingState.<exp>.js`).

## Pre-Commit Hook (relevant subset)

`.githooks/pre-commit` enforces, for the files you touch when implementing a City Character:

| Pattern | Required |
|---|---|
| `extends CharacterAction/AttachmentAction/CardAction/RiskAction/...` | `createActionResolvedEvent()` somewhere in the class. |
| **Forbidden in `CharacterAction` subclasses** | `setUsed`/`resetPlayerPassCount`/`announceAction` — these run centrally. |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed()` AND `$this->isAvailable()`. |
| `implements ISorcererAbility` | both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()`. |
| Implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` on one class | **Forbidden.** Split into two classes. |

The Card class itself (the `_03cdNN extends CityCharacter` file) has no hook-mandated calls — the requirements apply to the Action/Reaction subclasses that live next to it.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- **Preserve line endings.** Leave CRLF files as CRLF. Do not introduce `\r\r\n` (editor shows a blank line between every real line — seen when writing Astrid files on Windows). Prefer editing in place with search/replace over rewriting whole files when possible.
- Namespaces:
  - Card class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:     `...\cards\<expansion>\actions`
  - Reaction:   `...\cards\<expansion>\reactions`
  - Technique:  `...\cards\<expansion>\techniques` (custom only; generics stay in `cards/techniques/`)
  - State:      `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`
