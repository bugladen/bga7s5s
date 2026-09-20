> Work through the linked pages in order the first time. After that, jump to whichever page matches the ability you are writing.

# Creating a CityAttachment — Beginner Guide

Welcome. This guide teaches you how to implement a **City Attachment** card for *7th Sea: City of Five Sails* on Board Game Arena.

You do not need prior experience with this codebase. City Attachments are gear — artifacts, trinkets, weapons — that live in the **city deck**, get placed at city locations, and **equip** onto a character by paying a Wealth cost. Unlike City Event Cards, they stay in play across pressures until destroyed or unequipped.

## When to use CityAttachment (the use-case)

Use this guide when your stub says:

```php
class _03cdNN extends CityAttachment
```

or (base-game style):

```php
class _01198 extends CityAttachment
```

City Attachments are **not** faction-deck attachments and **not** people.

| You want… | Correct base class |
|---|---|
| A weapon / attire / artifact that starts in the **city deck** and is equipped from the city | **`CityAttachment`** ← this guide |
| A Weapon / Attire / Hat / Talisman that lives in a **player's faction deck** and equips from hand | `FactionAttachment` → [[FactionAttachment guide|Implementing a FactionAttachment Card]] |
| A mercenary / hireling that sits in the city until paid for | `CityCharacter` → [[CityCharacter guide|Implementing a CityCharacter Card]] |
| A one-shot city event that is discarded after use | `CityEventCard` → city-event skill (different guide) |
| A Character that lives in a player's faction deck | `Character` → [[Character guide|Implementing a Character Card]] |

**In plain English:** if the printed card is *equipment* that belongs to the **city deck**, equips onto a character for a Wealth cost, and buffs that character or grants them abilities — you are writing a CityAttachment.

**Devil Jonah's Bones** is the classic complete example: Forced wound on equip, a steady +1 gamble reveal, and a mid-duel player choice (top vs bottom of deck).

## What you will build

A complete CityAttachment usually means several PHP classes working together:

| Piece | Example file | What it does |
|---|---|---|
| Card class | `modules/php/cards/faf/_03cd05.php` | Name, WealthCost, CityCardNumber, stat modifiers, Traits, Text, ability ownership |
| Action class(es) | `.../actions/Action_01187.php` | Player-chosen **Action** / **City Action** while equipped |
| Reaction class(es) | `.../reactions/Reaction_01181.php` | Triggered **Reaction** / **City Reaction** while equipped |
| State class(es) | `modules/php/States/faf/State_duelGambleSetup_03cd05.php` | Multi-step UI flows (only when the player must pick things) |
| JS wiring | `modules/js/OnUpdateActionButtons.faf.js` (etc.) | Buttons and highlights for those states |

Not every attachment needs every piece. A pure Forced / passive card may be **only** the card class. Guild Triskelion (`_01198`) needs card + Action. Sorte Deck (`_01181`) needs card + Reaction. Devil Jonah's Bones (`_03cd05`) needs card + a custom duel-setup state + JS (no Action file).

## How to use this guide

1. [[01 — What is a CityAttachment?|CityAttachment Guide What Is A CityAttachment]] — use-case, lifecycle, where files live
2. [[02 — The card class|CityAttachment Guide The Card Class]] — fill in `_03cdNN.php` / `_NNNNN.php`
3. [[03 — Classify the printed text|CityAttachment Guide Classify The Printed Text]] — decide which pattern each clause uses
4. Then implement only the patterns your card needs:
   - [[04 — Passives and Forced|CityAttachment Guide Passives And Forced]]
   - [[05 — Actions|CityAttachment Guide Actions]]
   - [[06 — Reactions|CityAttachment Guide Reactions]]
   - [[07 — Steady-state and custom states|CityAttachment Guide Steady State And Custom States]]
5. [[08 — Wiring states and JavaScript|CityAttachment Guide Wiring States And JavaScript]] — when you added custom states
6. [[09 — Finish checklist|CityAttachment Guide Finish Checklist]] — before you call it done
7. [[10 — Example CityAttachments to copy|CityAttachment Guide Example CityAttachments To Copy]] — real cards to mirror
8. [[11 — Glossary and helpers|CityAttachment Guide Glossary And Helpers]] — jargon and useful Theah methods

## Golden rules

1. **Mirror an existing CityAttachment.** Prefer Devil Jonah's Bones / Guild Triskelion / Smuggled Item / Sorte Deck / Silver Spine over inventing a new architecture.
2. **Classify before you code.** Read every sentence of the printed Text and map it to a pattern first.
3. **Do not call `initializeFaction(...)`.** City Attachments are city-deck cards, not faction-deck cards.
4. **Set `CityCardNumber`.** That is the visible city-deck number on the card. Prefer `CardNumber = 0` for newer expansions (`faf` / `tac` style); base-game stubs often keep `CardNumber` = the image id — match neighbors in the same folder.
5. **"City Action" does not mean CityEventCard.** The word *City* on an ability means "must be in the city to use it." Your card still `extends CityAttachment`. There is **no** `AttachmentCityAction` base — gate with `cardInCity` yourself.
6. **Forced abilities live on the card class** (`handleEvent`), not in a Reaction file.
7. **Steady-state bonuses override a `get*` method** — do not mutate globals from `handleEvent` for permanent properties like "+1 gamble reveal."
8. **Always call `parent::handleEvent($event)` first** when you override `handleEvent` (except cancel-first Pattern G branches that intentionally return early — see [[04|CityAttachment Guide Passives And Forced]]).
9. **Destroy goes to the city discard**, not a player's faction discard.

## Walking examples

Throughout this guide we use these attachments you can keep open:

**Devil Jonah's Bones** (`_03cd05`) — the canonical complete CityAttachment:

> **Forced:** When a character equips this card • Wound them.  
> When the equipped character gambles… reveal an additional card. You may reveal from the bottom…

That is Forced + steady-state override + custom mid-duel state.

**Guild Triskelion** (`_01198`) — passive + City Action:

> The equipped character gains Duelist.  
> **City Action:** Engage… Issue a Finesse challenge…

**Smuggled Item** (`_01187`) — destroy-cost Action.  
**Sorte Deck** (`_01181`) — AttachmentReaction.  
**Silver Spine** (`_03cd21`) — Forced once-per-Day cancel of opponent Risks.

## Related guides

- [[Creating a FactionAttachment|Implementing a FactionAttachment Card]] — faction-deck gear (sibling pattern for Actions / Reactions)
- [[Creating a CityCharacter|Implementing a CityCharacter Card]] — city-deck *people*, not gear
- [[Creating a Character|Implementing a Character Card]] — faction-deck people
- [[Creating a Scheme|Implementing a Scheme Card]] — scheme deck cards
