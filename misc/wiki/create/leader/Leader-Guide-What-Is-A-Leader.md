# 01 — What is a Leader?

← [[Index|Implementing a Leader Card]] · Next: [[02 — The card class|Leader Guide The Card Class]]

## Leader vs Character vs CityCharacter

The game has three "people in play" base types. Pick the right one from the stub file you were given.

| Class | Lives in | How it enters play | Extra fields |
|---|---|---|---|
| `Character` | Player's faction deck / hand | Recruited (pays Wealth) | Resolve, Combat, Finesse, Influence, Traits |
| **`Leader` extends `Character`** | In play from game start | Setup places it — never recruited | Same as Character **+ `CrewCap` + `Panache`** |
| `CityCharacter` | City deck | Mustered from the city (WealthCost) | WealthCost, Negotiable, CityCardNumber |

**If your stub says `extends CityCharacter`, stop.** Use the [[CityCharacter guide|Implementing a CityCharacter Card]] instead.

**If your stub says `extends Leader` or `extends Character`, you are in the right place.** This guide focuses on Leaders, but Actions / Reactions / Techniques work the same for both.

## What "City" means on ability text

Printed keywords like **City Action** or **City Reaction** do **not** change the base class.

- **City** = the ability can only be used while the card is at a city location (Docks, Forum, Bazaar, …), not at Home.
- Cesca is a Leader with a City Action. She still `extends Leader`.

## Where files live

Card id `03001` in expansion `faf` (Fate & Faith):

```
modules/php/cards/faf/
  _03001.php                 ← Leader card class
  actions/
    Action_03001.php         ← City Action
  reactions/
    Reaction_03001.php       ← City Reaction
  techniques/                ← if the card has Techniques
  maneuvers/                 ← if the card has Maneuvers

modules/php/States/faf/
  State_highDramaPhase03001.php      ← only if the Action needs a picker state
  State_highDramaPhase03001_2.php    ← step 2 of a multi-step Action

modules/js/
  OnEnteringState.faf.js             ← highlight selectable cards when entering a state
  OnUpdateActionButtons.faf.js       ← Confirm / Pass buttons
  OnLeavingState.faf.js              ← clean up highlights when leaving
```

Expansion folders you will see:

| Folder | Expansion |
|---|---|
| `_7s5s` | Base game |
| `tac` | Tooth and Claw |
| `faf` | Fate & Faith |
| `bas` | Blood and Steel (and similar newer expansions) |

## How the game runs (very short)

1. The backend is a **state machine** defined in `states.inc.php`.
2. Cards react to **events** (`EventPhaseDawnEnding`, `EventCardMoved`, …) through `handleEvent`.
3. Player choices go through **Actions** (menu buttons during High Drama) or **Reactions** (prompts when something happens).
4. Multi-step choices use custom **states** + a little **JavaScript** to highlight cards and enable Confirm.

You almost never write UI from scratch. You copy an existing state's JS hooks and change the state name.

## Built-in Leader behavior (do not reimplement)

`modules/php/cards/Leader.php` already handles:

1. **Scheme Panache** — when your scheme is revealed, it can modify your Leader's Panache.
2. **Leader destroyed** — in 2-player, that often ends the game; in multiplayer, the controller loses half their Renown.

Because of that: **always call `parent::handleEvent($event)` first** in any Leader override.

## Next

Fill in the card class skeleton → [[02 — The card class|Leader Guide The Card Class]]
