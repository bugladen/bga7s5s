# 01 — What is a CityCharacter?

← [[Index|Implementing a CityCharacter Card]] · Next: [[02 — The card class|CityCharacter Guide The Card Class]]

## The use-case in one paragraph

A **CityCharacter** is a hireable person who starts in the **city deck**. During the game they sit face-up at a city location. A player may **muster** them by paying their **WealthCost** (and may **parley** if the card is printed **Negotiable**). After muster they are a normal Character under that player's control — they can take wounds, equip attachments, fight in duels, and use Actions / Reactions / Techniques like anyone else.

If your card is *not* that kind of hireling (for example it is only an event that fires and leaves, or an attachment that equips), stop — you have the wrong guide.

## CityCharacter vs Character vs Leader

| Class | Lives in | How it enters play | Extra fields |
|---|---|---|---|
| `Character` | Player's faction deck / hand | Recruited (pays Wealth from hand / Approach) | Resolve, Combat, Finesse, Influence, Traits + **`initializeFaction`** |
| `Leader extends Character` | In play from game start | Setup places it — never recruited | Same as Character **+ `CrewCap` + `Panache`** |
| **`CityCharacter extends Character`** | **City deck** | **Mustered from the city** (WealthCost) | **`WealthCost`, `Negotiable`, `CityCardNumber`** |

**If your stub says `extends Character` or `extends Leader`, stop.** Use the [[Character|Implementing a Character Card]] or [[Leader|Implementing a Leader Card]] guide instead.

**If your stub says `extends CityCharacter`, you are in the right place.**

## What "City" means on ability text

Printed keywords like **City Action**, **City Forced**, or **City Reaction** do **not** change the base class by themselves.

- **City** on an ability = "this ability cares about being in / at the city."
- For a CityCharacter, that usually means gate with `$theah->cardInCity($this)` (or `$owner`) while they are still sitting as a city hireling, or while the text scopes "in the city."
- A faction Character can also have a City Action (Aldo). That Character still `extends Character`.

The **base class** comes from where the card lives and how it enters play — not from the word "City" in the ability line.

## Lifecycle (beginner mental model)

1. **In the city deck / at a city location** — uncontrolled (`ControllerId` often `0`). Players can see them and muster them.
2. **Muster** — a player pays `WealthCost` (parley allowed only if `Negotiable === true`). The CityCharacter becomes controlled.
3. **In play as a Character** — same rules as any Character for wounds, engage, challenges, attachments.
4. **Special Forced / return-to-deck effects** — some CityCharacters (Penya) shuffle back into the city deck when Forced triggers. That is card text, not automatic for every CityCharacter.

## Where files live

Card id `03cd01` (Penya) in expansion `faf`:

```
modules/php/cards/faf/
  _03cd01.php                 ← CityCharacter card class
  actions/
    Action_03cd01.php         ← City Action
  reactions/                  ← if the card has Reactions (Julius: Reaction_03cd10.php)
  techniques/
  maneuvers/

modules/php/States/faf/
  State_highDramaPhase03cd01.php      ← only if the Action needs a picker state
  State_highDramaPhase03cd01_2.php    ← step 2 of a multi-step Action

modules/js/
  OnEnteringState.faf.js
  OnUpdateActionButtons.faf.js
  OnLeavingState.faf.js
```

City-deck card numbers look like `03cd01`, `03cd10` — the `cd` means city deck. Filename is `_03cd01.php`.

Expansion folders:

| Folder | Expansion |
|---|---|
| `_7s5s` | Base game |
| `tac` | Tooth and Claw |
| `faf` | Fate & Faith |
| `bas` | Blood and Steel (and similar newer expansions) |

## How the game runs (very short)

1. The backend is a **state machine** defined in `states.inc.php`.
2. Cards react to **events** (`EventDuelStarted`, `EventCharacterBeingWounded`, …) through `handleEvent`.
3. Player choices go through **Actions** (High Drama menu) or **Reactions** (prompts when something happens).
4. Multi-step choices use custom **states** + a little **JavaScript** to highlight cards and enable Confirm.

You almost never write UI from scratch. You copy an existing state's JS hooks and change the state name.

## What CityCharacter already gives you

`CityCharacter` extends `Character` and mixes in city-deck + wealth-cost traits. You get:

- Full Character behavior (stats, wounds, attachments, default Techniques support)
- `WealthCost` (muster cost)
- `Negotiable` (parley allowed when paying)
- `CityCardNumber` (printed city-deck index)
- Client property `negotiable` via `getPropertyArray`

Because of that: **always call `parent::handleEvent($event)` first** in any override, and **do not** re-`implements IHasTechniques` / re-`use TechniqueTrait` on the card class.

## Next

Fill in the card class skeleton → [[02 — The card class|CityCharacter Guide The Card Class]]
