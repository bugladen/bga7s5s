> Work through the linked pages in order the first time. After that, jump to whichever page matches the ability you are writing.

# Creating a CityCharacter — Beginner Guide

Welcome. This guide teaches you how to implement a **CityCharacter** card for *7th Sea: City of Five Sails* on Board Game Arena.

You do not need prior experience with this codebase. CityCharacters are playable people — like regular Characters — but they live in the **city deck** until someone musters them.

## When to use CityCharacter (the use-case)

Use this guide when your stub says:

```php
class _03cdNN extends CityCharacter
```

CityCharacters are **not** faction-deck Characters and **not** Leaders.

| You want… | Correct base class |
|---|---|
| A mercenary / hireling that sits face-up at a city location until paid for | **`CityCharacter`** ← this guide |
| A Character that lives in a player's faction deck / hand | `Character` → [[Character guide|Implementing a Character Card]] |
| The faction Leader placed at setup | `Leader` → [[Leader guide|Implementing a Leader Card]] |
| A one-shot city event that is discarded after use | `CityEventCard` → city-event skill (different guide) |
| A weapon / attire that starts in the city deck | `CityAttachment` → city-attachment skill (different guide) |

**In plain English:** if the printed card is a *person* with Resolve / Combat / Finesse / Influence, a Wealth cost to hire them from the city, and they can fight once controlled — you are writing a CityCharacter.

**Penya** is the classic example: he sits in the city until someone pays his Wealth cost, then he becomes a normal Character under that player's control (with his special Forced and Action still active while relevant).

## What you will build

A complete CityCharacter usually means several PHP classes working together:

| Piece | Example file | What it does |
|---|---|---|
| Card class | `modules/php/cards/faf/_03cd01.php` | Stats, traits, WealthCost, Negotiable, Text, ability ownership |
| Action class(es) | `.../actions/Action_03cd01.php` | Player-chosen **Action** / **City Action** |
| Reaction class(es) | `.../reactions/Reaction_03cd10.php` | Triggered **Reaction** / **City Reaction** |
| Technique / Maneuver | `.../techniques/Technique_….php` | Duel abilities (same as Character) |
| State class(es) | `modules/php/States/faf/State_….php` | Multi-step UI flows (only when the player must pick things) |
| JS wiring | `modules/js/OnEnteringState.faf.js` (etc.) | Highlights and Confirm buttons for those states |

Not every CityCharacter needs every piece. A Forced-only hireling may be mostly the card class. Penya (`_03cd01`) needs card + Action + two state steps + JS. Julius Caligari (`_03cd10`) needs card + Reaction (no new states / no JS).

## How to use this guide

1. [[01 — What is a CityCharacter?|CityCharacter Guide What Is A CityCharacter]] — use-case, lifecycle, where files live
2. [[02 — The card class|CityCharacter Guide The Card Class]] — fill in `_03cdNN.php`
3. [[03 — Classify the printed text|CityCharacter Guide Classify The Printed Text]] — decide which pattern each clause uses
4. Then implement only the patterns your card needs:
   - [[04 — Hard bans, Forced, and passives|CityCharacter Guide Passives]]
   - [[05 — Actions|CityCharacter Guide Actions]]
   - [[06 — Reactions|CityCharacter Guide Reactions]]
   - [[07 — Techniques and Maneuvers|CityCharacter Guide Techniques And Maneuvers]]
   - [[08 — Challenge Actions|CityCharacter Guide Challenge Actions]]
5. [[09 — Wiring states and JavaScript|CityCharacter Guide Wiring States And JavaScript]] — when you added custom states
6. [[10 — Finish checklist|CityCharacter Guide Finish Checklist]] — before you call it done
7. [[11 — Example CityCharacters to copy|CityCharacter Guide Example CityCharacters To Copy]] — real cards to mirror
8. [[12 — Glossary and helpers|CityCharacter Guide Glossary And Helpers]] — jargon and useful Theah methods

## Golden rules

1. **Mirror an existing CityCharacter.** Prefer Penya / Julius / Kalla over inventing a new architecture.
2. **Classify before you code.** Read every sentence of the printed Text and map it to a pattern first.
3. **CityCharacter is still a Character.** Stats, wounds, attachments, and Techniques work like any Character. You add city-deck fields (`WealthCost`, `Negotiable`, `CityCardNumber`) and city-scope gates.
4. **"City Action" on a faction Character ≠ CityCharacter.** The word *City* on an ability means "must be at a city location." Aldo's City Action still `extends Character`. Your stub must say `extends CityCharacter`.
5. **City Action on a CityCharacter uses `CharacterAction`, not `EventCityAction`.** EventCityAction is for one-shot city events that discard themselves. Penya stays in play / in the deck after his Action.
6. **Always call `parent::handleEvent($event)` first** when you override `handleEvent` — base Character logic still needs to run.
7. **Do not call `initializeFaction(...)`.** CityCharacters are not faction-deck cards.

## Walking example

Throughout this guide we use **Penya** (`_03cd01`) as the main example:

> Penya cannot intervene.  
> **City Forced:** When Penya participates in a duel or would be wounded • Put the top card of the City Deck at his location. Then, shuffle him into the City Deck.  
> **City Action:** Engage Penya • Move Penya and another of your characters at this location to the same adjacent **City** location.

That is one hard ban + one City Forced + one multi-step City Action — a complete, realistic CityCharacter.

For Reactions, also keep **Julius Caligari** (`_03cd10`) open.

## Related guides

- [[Creating a Character|Implementing a Character Card]] — faction-deck people; ability patterns overlap
- [[Creating a Leader|Implementing a Leader Card]] — setup Leaders with `CrewCap` / `Panache`
- [[Creating a Scheme|Implementing a Scheme Card]] — scheme deck cards, not people
