# 09 — Wiring states and JavaScript

← [[08 — Challenges|Leader Guide Challenge Actions]] · [[Index|Implementing a Leader Card]] · Next: [[10 — Checklist|Leader Guide Finish Checklist]]

You need this page when an Action or Technique asks the player to **pick something** (character, location, hand card, attachment). Pure button Reactions usually skip this entirely.

## The three backend pieces

For each new interactive step you typically add:

1. **State constant** in `modules/php/States.php`
2. **State class** in `modules/php/States/<expansion>/State_....php`
3. **Transition entry** in `states.inc.php` (and usually `states.7s5s.php` — keep them in sync)

### State id convention

- High Drama Action step 1: `4` + 5-digit card number → e.g. Cesca `403001`
- Further steps: append `2`, `3`, … → `4030012`
- Dual Actions `a`/`b`: append which-action digit → Damya `4030381` / `4030382` / `40303822`

### Transition keys

`EventFactory::createTransitionEvent(..., "NNNNN", ...)` looks up `"NNNNN"` in the EVENTS state's transition table in `states.inc.php`.

Only add `"NNNNN_2"` there if something actually queues `createTransitionEvent(..., "NNNNN_2", ...)`. Some multi-step flows move step 1 → step 2 only via the state's own `nextState("...")` map.

Challenge Actions are the common case that **do** need both `"NNNNN"` and `"NNNNN_2"` — see [[08|Leader Guide Challenge Actions]].

## The three frontend pieces

For every new state name (example `highDramaPhase03001`):

| File | Job |
|---|---|
| `modules/js/OnEnteringState.<expansion>.js` | Highlight what can be selected; store args |
| `modules/js/OnUpdateActionButtons.<expansion>.js` | Add Confirm / Pass buttons |
| `modules/js/OnLeavingState.<expansion>.js` | Clear highlights / selection modes |

Copy an existing same-shaped state in the same expansion file and rename the key.

### Character picker (in play)

```js
// OnEnteringState
'highDramaPhaseNNNNN': () => {
    if (this.isCurrentPlayerActive()) {
        this.highlightCardsAsSelectable(this.gamedatas.gamestate.args.args.ids);
    }
},

// OnUpdateActionButtons
'highDramaPhaseNNNNN': () => {
    this.addActionButton('actChooseCardSelected', _('Confirm'), () => this.onChooseInPlayCardConfirmed());
    dojo.addClass('actChooseCardSelected', 'disabled');
},

// OnLeavingState
'highDramaPhaseNNNNN': () => {
    // clear selectable styling the same way sibling states do
},
```

Exact cleanup helpers vary — mirror a neighbor state in the same file rather than inventing names.

### Hand card picker

Use `factionHand`, not `highlightCardsAsSelectable`. Also add an `EventHandlers.js` entry so Confirm enables when a hand card is clicked.

### City location picker

- State `#[PossibleAction]` must be `actFromCardWithLocations`, **not** `actFromCardWithIds`.
- JS Confirm calls `onCityLocationsSelected()`.
- Leaving cleanup uses `resetCityLocations()` — there is no `clearCityLocationAsSelectable`.
- Build the location list with `array_keys($theah->getCityLocations())` (player-count aware). Do not hardcode colloquial constant names like `LOCATION_BORDELLO` — they do not exist.

### chooseList multi-select / reorder

`OnEnteringState` + buttons are not enough. Clicks go through `EventHandlers.js` → `onChooseCardClicked`. The default branch only enables Confirm for exactly one selection and never adds reorder number chips. Mirror `04cd15` / `04001` entries.

## Args shape gotcha

In handlers:

- `OnEnteringState` often reads nested `args.args.args.*`
- `OnUpdateActionButtons` often reads `args.args.*`

When in doubt, `console.log` the args object of a working sibling state and match its depth.

## Pre-commit reminders related to wiring

See [[10 — Checklist|Leader Guide Finish Checklist]] for the full list. The ones that bite during wiring:

- CharacterAction must still end with `createActionResolvedEvent` (except challenge hand-off)
- Reaction classes need literal `$this->setUsed(` and `$this->isAvailable(`
- Sorcerer abilities need start + played events

## Next

Walk the finish list → [[10|Leader Guide Finish Checklist]]
