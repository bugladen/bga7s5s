# 01 — What is a CityAttachment?

← [[Index|Implementing a CityAttachment Card]] · Next: [[02 — The card class|CityAttachment Guide The Card Class]]

## The use-case in one paragraph

A **CityAttachment** is equipment that starts in the **city deck**. It appears face-up at a city location (or is otherwise available from the city), then a player **equips** it onto a character by paying its **WealthCost**. While equipped it modifies that character's stats and/or grants Forced abilities, Actions, or Reactions tied to "the equipped character." When destroyed it usually goes to the **city discard**, not a player's faction discard. Unlike City Event Cards, it **stays in play** across pressures until unequipped or destroyed.

If your card is a *person*, a *scheme*, a *risk*, a one-shot *city event*, or gear that starts in a **player's faction deck**, stop — you have the wrong guide.

## CityAttachment vs FactionAttachment vs CityCharacter vs CityEventCard

| Class | Lives in | How it enters play | Extra fields |
|---|---|---|---|
| **`CityAttachment`** | **City deck** | **Equipped from the city** (pay WealthCost) | `CityCardNumber`, `WealthCost`, city-deck lifecycle |
| `FactionAttachment` | Player's faction deck / hand | Equipped from hand | `initializeFaction`, Riposte/Parry/Thrust |
| `CityCharacter` | City deck | Mustered / hired as a *person* | Resolve/Combat/… + `Negotiable` |
| `CityEventCard` | City deck | One-shot event, then discarded | No attach / no lasting equip |

**If your stub says `extends FactionAttachment`, stop.** Use [[FactionAttachment guide|Implementing a FactionAttachment Card]].

**If your stub says `extends CityAttachment`, you are in the right place.**

## Why CityAttachment exists (design intent)

The game separates two kinds of gear:

- **City gear** — items sitting in the city that anyone might grab (artifacts, smuggled goods, bureaucratic trinkets). Ownership follows the city deck; destroy → city discard.
- **Faction gear** — personal kit from your deck. You build it in, draw it, equip from hand; sink/discard usually returns to *your* piles.

Code mirrors that split with two base classes so equip costs, discard destinations, and deck ownership stay correct.

## What "City" means on ability text

Printed **City Action** / **City Reaction** does **not** change the base class.

- **City** on an ability = the equipped character (or relevant card) must be at a city location.
- Your file still `extends CityAttachment`.
- There is **no** `AttachmentCityAction` class — use `AttachmentAction` and gate with `$theah->cardInCity($owner)`.

## Lifecycle (beginner mental model)

1. **In the city deck / at a city location** — not equipped (`AttachedToId` is `0`).
2. **Equip** — a player pays `WealthCost`, chooses a legal character, attachment attaches (`AttachedToId` set, Location mirrors the character, ControllerId becomes that player's).
3. **While equipped** — stat modifiers apply; Forced / Actions / Reactions become available per their own gates.
4. **Unequip / destroy** — attachment leaves the character. Passive trait grants must clear here. Destroy typically queues unequip + **city discard**.

Important instance fields after equip:

| Field | Meaning |
|---|---|
| `$this->AttachedToId` | Character id it sits on (`0` if unequipped) |
| `$this->ControllerId` | The player who controls the equipped character (city attachments usually mirror the host) |
| `$this->Location` | Same location as the equipped character |
| `$this->Engaged` | Attachments can be engaged as a cost ("Engage this card") |

Helpers you will use constantly:

- `$this->isAttached()` — true when equipped. **Always gate "equipped character" effects on this.**
- `$this->attachedTo($theah)` — the character card, or null
- `$this->canAttachTo($character)` — override for equip restrictions ("equip to a Sorcerer", etc.)

## Where files live

Card id `03cd05` (Devil Jonah's Bones) in expansion `faf`:

```
modules/php/cards/faf/
  _03cd05.php                 ← CityAttachment card class
  actions/                    ← if the card has Actions
  reactions/                  ← if the card has Reactions

modules/php/States/faf/       ← only if you need a picker / mid-flow state
  State_duelGambleSetup_03cd05.php

modules/js/
  OnEnteringState.faf.js
  OnUpdateActionButtons.faf.js
  OnLeavingState.faf.js
```

Base-game city attachments use `_7s5s` and ids like `_01198.php`.

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
4. Multi-step choices use custom **states** + a little **JavaScript** to show buttons.

You almost never write UI from scratch. You copy an existing state's JS hooks and change the state name.

## What CityAttachment already gives you

`CityAttachment` extends `Attachment` and mixes in `CityDeckCardTrait`. You get:

- Attachment attach/detach plumbing (`AttachedToId`, `isAttached`, `canAttachTo`)
- City-deck identity (`CityCardNumber`, city discard paths)
- `WealthCost` (equip cost from the city)
- Stat modifiers applied to the equipped character while attached

Because of that: **always call `parent::handleEvent($event)` first** in normal overrides.

## Next

Fill in the card class skeleton → [[02 — The card class|CityAttachment Guide The Card Class]]
