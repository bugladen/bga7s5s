# 01 — What is a FactionAttachment?

← [[Index|Implementing a FactionAttachment Card]] · Next: [[02 — The card class|FactionAttachment Guide The Card Class]]

## The use-case in one paragraph

A **FactionAttachment** is equipment that starts in a player's **faction deck**. Players draw it into hand, then **equip** it onto a character during High Drama by paying its **WealthCost**. While equipped it modifies that character's stats and/or grants Forced abilities, Actions, Reactions, Techniques, or Maneuvers. When discarded or destroyed it usually returns to that player's discard (not the city discard).

If your card is a *person*, a *scheme*, a *risk*, or gear that starts in the **city deck**, stop — you have the wrong guide.

## FactionAttachment vs CityAttachment vs Character

| Class | Lives in | How it enters play | Extra fields |
|---|---|---|---|
| **`FactionAttachment`** | **Player's faction deck / hand** | **Equipped from hand** (pay WealthCost) | `initializeFaction`, `WealthCost`, stat modifiers, Riposte/Parry/Thrust |
| `CityAttachment` | City deck | Equipped from the city | `CityCardNumber`, city-deck lifecycle |
| `Character` / `Leader` | Faction deck / setup | Recruited / setup | Resolve, Combat, Finesse, Influence (people, not gear) |

**If your stub says `extends CityAttachment`, stop.** Use [[CityAttachment guide|Implementing a CityAttachment Card]].

**If your stub says `extends FactionAttachment`, you are in the right place.**

## Why FactionAttachment exists (design intent)

The game separates two kinds of gear:

- **Faction gear** — personal kit from your deck (your musketeer's hat, your Strega's shears). You build it into your deck and pay Wealth to put it on *your* characters (usually).
- **City gear** — items sitting in the city that anyone might grab.

Code mirrors that split with two base classes so equip costs, discard destinations, and deck ownership stay correct.

**Neutral** faction attachments still use `FactionAttachment` + `initializeFaction('Neutral')`. Examples: Harpoon `_03064`, Lodestone `_03065`, Shackles `_03066`, Main Gauche `_02056`. They are *not* city-deck cards.

## What "City" means on ability text

Printed **City Action** / **City Reaction** does **not** change the base class.

- **City** on an ability = the equipped character (or relevant card) must be at a city location.
- Your file still `extends FactionAttachment`.
- There is **no** `AttachmentCityAction` class — use `AttachmentAction` and gate with `$theah->cardInCity($owner)`.

## Lifecycle (beginner mental model)

1. **In the faction deck / hand** — not equipped (`AttachedToId` is `0`).
2. **Equip** — controller pays `WealthCost`, chooses a legal character, attachment attaches (`AttachedToId` set, Location mirrors the character).
3. **While equipped** — stat modifiers apply; Actions / Reactions / Techniques become available per their own gates.
4. **Unequip / destroy / sink** — attachment leaves the character. Passive trait grants and while-equipped conditions must clear here (see [[05|FactionAttachment Guide Passives And Forced]]).

Important instance fields after equip:

| Field | Meaning |
|---|---|
| `$this->AttachedToId` | Character id it sits on (`0` if not equipped) |
| `$this->ControllerId` | The **equipping player** (not always the character's controller — see opponent-equip) |
| `$this->Location` | Same location as the equipped character |
| `$this->Engaged` | Attachments can be engaged as a cost ("Engage this card") |
| `$this->OffHand` | Offhand gear bypasses the one-Weapon / one-Armor uniqueness rule |

Helpers you will use constantly:

- `$this->isAttached()` — true when equipped
- `$this->attachedTo($theah)` — the character card, or null
- `$this->canAttachTo($character)` — override for equip restrictions

## Where files live

Card id `01073` (Cavalier Hat) in expansion `_7s5s`:

```
modules/php/cards/_7s5s/
  _01073.php                 ← FactionAttachment card class
  actions/
    Action_01073.php         ← City Action
  reactions/                 ← if the card has Reactions
  techniques/                ← if the card has Techniques
  maneuvers/                 ← if the card has Maneuvers

modules/php/States/_7s5s/    ← only if an Action/Technique needs a picker state
  State_highDramaPhase....php

modules/js/
  OnEnteringState.7s5s.js    ← or OnEnteringState.faf.js / .tac.js
  OnUpdateActionButtons.*.js
  OnLeavingState.*.js
```

Newer expansions (`faf`, `tac`, `bas`) use the same layout under their folder names.

| Folder | Expansion |
|---|---|
| `_7s5s` | Base game |
| `tac` | Tooth and Claw |
| `faf` | Fate & Faith |
| `bas` | Blood and Steel (and similar newer expansions) |

## How the game runs (very short)

1. The backend is a **state machine** defined in `states.inc.php`.
2. Cards react to **events** (`EventAttachmentEquipped`, `EventActionTriggered`, …) through `handleEvent`.
3. Player choices go through **Actions** (High Drama menu) or **Reactions** (prompts when something happens).
4. Multi-step choices use custom **states** + a little **JavaScript** to highlight cards and enable Confirm.

You almost never write UI from scratch. You copy an existing state's JS hooks and change the state name.

## What FactionAttachment already gives you

`FactionAttachment` extends `Attachment` and mixes in faction-deck + wealth-cost traits. You get:

- Attachment attach/detach plumbing (`AttachedToId`, `isAttached`, `canAttachTo`)
- `WealthCost` (equip cost from hand)
- Faction membership via `initializeFaction`
- Combat-card stats (`Riposte` / `Parry` / `Thrust`) for when the attachment is used in a duel
- Stat modifiers applied to the equipped character while attached

Because of that: **always call `parent::handleEvent($event)` / `parent::eventCheck($event)` first** in any override.

## Next

Fill in the card class skeleton → [[02 — The card class|FactionAttachment Guide The Card Class]]
