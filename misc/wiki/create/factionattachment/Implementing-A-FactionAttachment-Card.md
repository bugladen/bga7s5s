> Work through the linked pages in order the first time. After that, jump to whichever page matches the ability you are writing.

# Creating a FactionAttachment — Beginner Guide

Welcome. This guide teaches you how to implement a **Faction Attachment** card for *7th Sea: City of Five Sails* on Board Game Arena.

You do not need prior experience with this codebase. Faction Attachments are gear — weapons, attire, hats, talismans — that live in a player's **faction deck**, get drawn into hand, and **equip** onto a character by paying a Wealth cost.

## When to use FactionAttachment (the use-case)

Use this guide when your stub says:

```php
class _NNNNN extends FactionAttachment
```

Faction Attachments are **not** city-deck attachments and **not** Characters.

| You want… | Correct base class |
|---|---|
| A Weapon / Attire / Hat / Talisman / Trinket that lives in a **player's faction deck**, equips from hand onto a character, and modifies that character | **`FactionAttachment`** ← this guide |
| A weapon / attire that starts in the **city deck** and is equipped from the city | `CityAttachment` → city-attachment skill (different guide) |
| A person with Resolve / Combat who fights | `Character` / `Leader` / `CityCharacter` → their guides |
| A Planning card with Initiative / Panache | `Scheme` → [[Scheme guide|Implementing a Scheme Card]] |
| A combat Risk played from hand during a duel | `Risk` → risk skill (different guide) |

**In plain English:** if the printed card is *equipment* that belongs to a faction deck (or Neutral cards that still use faction-deck numbering), equips onto a character for a Wealth cost, and buffs that character or grants them abilities — you are writing a FactionAttachment.

**Cavalier Hat** is a classic example: it only equips to a Duelist, gives combat-card stats, and has a City Action while equipped.

## What you will build

A complete FactionAttachment usually means several PHP classes working together:

| Piece | Example file | What it does |
|---|---|---|
| Card class | `modules/php/cards/_7s5s/_01073.php` | Name, WealthCost, stat modifiers, Riposte/Parry/Thrust, Traits, Text, ability ownership |
| Action class(es) | `.../actions/Action_01073.php` | Player-chosen **Action** / **City Action** while equipped |
| Reaction class(es) | `.../reactions/Reaction_01022.php` | Triggered **Reaction** / **City Reaction** while equipped |
| Technique / Maneuver | `.../techniques/Technique_01050.php` | Duel abilities granted by the attachment |
| State class(es) | `modules/php/States/.../State_....php` | Multi-step UI flows (only when the player must pick things) |
| JS wiring | `modules/js/OnEnteringState.*.js` (etc.) | Highlights and Confirm buttons for those states |

Not every attachment needs every piece. A pure stat-buff weapon may be **only** the card class. Cavalier Hat (`_01073`) needs card + Action. Unsavory Salve (`_01050`) needs card + Technique. Torres Cloak (`_03044`) needs card + Reaction (and no new High Drama Action state).

## How to use this guide

1. [[01 — What is a FactionAttachment?|FactionAttachment Guide What Is A FactionAttachment]] — use-case, lifecycle, where files live
2. [[02 — The card class|FactionAttachment Guide The Card Class]] — fill in `_NNNNN.php`
3. [[03 — Classify the printed text|FactionAttachment Guide Classify The Printed Text]] — decide which pattern each clause uses
4. Then implement only the patterns your card needs:
   - [[04 — Equip restrictions|FactionAttachment Guide Equip Restrictions]]
   - [[05 — Passives and Forced|FactionAttachment Guide Passives And Forced]]
   - [[06 — Actions|FactionAttachment Guide Actions]]
   - [[07 — Reactions|FactionAttachment Guide Reactions]]
   - [[08 — Techniques and Maneuvers|FactionAttachment Guide Techniques And Maneuvers]]
5. [[09 — Wiring states and JavaScript|FactionAttachment Guide Wiring States And JavaScript]] — when you added custom states
6. [[10 — Finish checklist|FactionAttachment Guide Finish Checklist]] — before you call it done
7. [[11 — Example FactionAttachments to copy|FactionAttachment Guide Example FactionAttachments To Copy]] — real cards to mirror
8. [[12 — Glossary and helpers|FactionAttachment Guide Glossary And Helpers]] — jargon and useful Theah methods

## Golden rules

1. **Mirror an existing FactionAttachment.** Prefer Cavalier Hat / Tabard / Lodestone / Torres Cloak over inventing a new architecture.
2. **Classify before you code.** Read every sentence of the printed Text and map it to a pattern first.
3. **Call `initializeFaction(...)`.** Faction attachments belong to a faction deck — including **Neutral** ones (`initializeFaction('Neutral')`).
4. **Always set `Riposte`** (even to `0`). The pre-commit hook expects it on FactionAttachment subclasses.
5. **"City Action" does not mean CityAttachment.** The word *City* on an ability means "must be in the city to use it." Your card still `extends FactionAttachment`. There is **no** `AttachmentCityAction` base — gate with `cardInCity` yourself.
6. **Equip restrictions need both gates:** `canAttachTo` (UI) **and** `eventCheck` (server). One is not enough.
7. **Always call `parent::handleEvent($event)` first** when you override `handleEvent`.

## Walking examples

Throughout this guide we use these attachments you can keep open:

**Cavalier Hat** (`_01073`) — simplest complete attachment with an Action:

> May only equip to your Duelist.  
> **City Action:** …

That is one equip restriction + one City Action.

**Tabard of the Fallen Musketeer** (`_01075`) — multi-shape attachment:

> May only equip to a non-Diplomat. Equipped character gains Musketeer.  
> **City Action:** …

That is equip restriction + passive trait grant + City Action.

**Lodestone** (`_03065`) — Neutral attachment with a lasting restriction + sink Action:

> While equipped, opponents' abilities cannot move this character Home.  
> **City Action:** Sink this card • Move the equipped character Home.

For Reactions, also keep **Torres Cloak** (`_03044`) or **Matushka's Shears** (`_03007`) open.

## Related guides

- [[Creating a Character|Implementing a Character Card]] — people who wear these attachments; Action / Reaction shapes overlap
- [[Creating a CityCharacter|Implementing a CityCharacter Card]] — city-deck hirelings (not gear)
- [[Creating a Scheme|Implementing a Scheme Card]] — scheme deck cards
- City attachments use a different skill — do not use this guide for `extends CityAttachment`
