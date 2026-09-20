# 01 — What is a Character?

← [[Index|Implementing a Character Card]] · Next: [[02 — The card class|Character Guide The Card Class]]

## Character vs Leader vs CityCharacter

The game has three "people in play" base types. Pick the right one from the stub file you were given.

| Class | Lives in | How it enters play | Extra fields |
|---|---|---|---|
| **`Character` (direct)** | Player's faction deck / hand | Recruited (pays Wealth) | Resolve, Combat, Finesse, Influence, Traits |
| `Leader extends Character` | In play from game start | Setup places it — never recruited | Same as Character **+ `CrewCap` + `Panache`** |
| `CityCharacter extends Character` | City deck | Mustered from the city (WealthCost) | WealthCost, Negotiable, CityCardNumber |

**If your stub says `extends CityCharacter`, stop.** Use the [[CityCharacter guide|Implementing a CityCharacter Card]] instead.

**If your stub says `extends Leader`, use the [[Leader guide|Implementing a Leader Card]]** instead. Ability code (Actions / Reactions / Techniques) is the same family — only the card-class skeleton differs.

**If your stub says `extends Character`, you are in the right place.**

## What "City" means on ability text

Printed keywords like **City Action** or **City Reaction** do **not** change the base class.

- **City** = the ability can only be used while the card is at a city location (Docks, Forum, Bazaar, …), not at Home.
- Aldo is a Character with a City Action. He still `extends Character`.

## Where files live

Card id `01007` in expansion `_7s5s` (base game):

```
modules/php/cards/_7s5s/
  _01007.php                 ← Character card class
  actions/
    Action_01007.php         ← City Action
  reactions/                 ← if the card has Reactions
  techniques/                ← if the card has Techniques
  maneuvers/                 ← if the card has Maneuvers

modules/php/States/_7s5s/    ← or States/faf/, States/bas/, …
  State_highDramaPhase01007.php   ← only if the Action needs a picker state

modules/js/
  OnEnteringState.7s5s.js    ← (or .faf.js / .bas.js) highlight selectable cards
  OnUpdateActionButtons.7s5s.js
  OnLeavingState.7s5s.js
```

Ise's dual Reactions (`03016`) live under `modules/php/cards/faf/reactions/` with **no** state classes and **no** JS — button Reactions usually need neither.

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

## How Characters enter play

Unlike Leaders (placed at setup), Characters are:

1. Drawn into hand (or played from Approach during the Approach phase).
2. **Recruited** during High Drama by paying Wealth (Parley / recruit flow).
3. Placed at a city location or Home depending on the recruit path.

That is why muster text often must listen to **both** `EventCharacterMustered` **and** `EventApproachCharacterPlayed` — see [[04 — Passives|Character Guide Passives]].

## Base Character behavior (do not reimplement)

`modules/php/cards/Character.php` already owns:

1. Stats, wounds, attachments, dashed-stat flags.
2. Default `canChallenge` / `canIntervene` / related predicates.
3. `TechniqueTrait` — every Character can own Techniques without re-declaring the interface.

Because of that: **always call `parent::handleEvent($event)` first** in any Character override, and **do not** re-`implements IHasTechniques` / re-`use TechniqueTrait` on the card class.

## Next

Fill in the card class skeleton → [[02 — The card class|Character Guide The Card Class]]
