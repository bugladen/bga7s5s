> This page replaces the older short Leader stub. Work through the linked pages in order.

# Creating a Leader — Beginner Guide

Welcome. This guide teaches you how to implement a **Leader** card for *7th Sea: City of Five Sails* on Board Game Arena.

You do not need prior experience with this codebase. Work through the pages in order the first time. After that, jump to whichever page matches the ability you are writing.

## What you will build

A complete Leader usually means several PHP classes working together:

| Piece | Example file | What it does |
|---|---|---|
| Card class | `modules/php/cards/faf/_03001.php` | Stats, traits, text, and which abilities the Leader owns |
| Action class(es) | `.../actions/Action_03001.php` | Player-chosen **Action** / **City Action** |
| Reaction class(es) | `.../reactions/Reaction_03001.php` | Triggered **Reaction** / **City Reaction** |
| Technique / Maneuver | `.../techniques/Technique_NNNNN.php` | Duel abilities |
| State class(es) | `modules/php/States/faf/State_....php` | Multi-step UI flows (only when the player must pick things) |
| JS wiring | `modules/js/OnEnteringState.faf.js` (etc.) | Front-end highlights and Confirm buttons for those states |

Not every Leader needs every piece. A passive-only Leader may be just the card class. Cesca del Rosso (`_03001`) needs a card class + Action + Reaction + two state steps + JS.

## How to use this guide

1. [[01 — What is a Leader?|Leader Guide What Is A Leader]] — concepts and where files live
2. [[02 — The card class|Leader Guide The Card Class]] — fill in `_NNNNN.php`
3. [[03 — Classify the printed text|Leader Guide Classify The Printed Text]] — decide which pattern each clause uses
4. Then implement only the patterns your card needs:
   - [[04 — Passives|Leader Guide Passives]]
   - [[05 — Actions|Leader Guide Actions]]
   - [[06 — Reactions|Leader Guide Reactions]]
   - [[07 — Techniques and Maneuvers|Leader Guide Techniques And Maneuvers]]
   - [[08 — Challenge Actions|Leader Guide Challenge Actions]]
5. [[09 — Wiring states and JavaScript|Leader Guide Wiring States And JavaScript]] — when you added custom states
6. [[10 — Finish checklist|Leader Guide Finish Checklist]] — before you call it done
7. [[11 — Example Leaders to copy|Leader Guide Example Leaders To Copy]] — real cards to mirror
8. [[12 — Glossary and helpers|Leader Guide Glossary And Helpers]] — jargon and useful Theah methods

## Golden rules

1. **Mirror an existing card.** Do not invent a new architecture. Find a Leader whose text is similar and copy its shape.
2. **Classify before you code.** Read every sentence of the printed Text and map it to a pattern first.
3. **Leaders are Characters with extras.** Most ability code is identical to a regular Character. Leaders add `CrewCap`, `Panache`, and always have the `Leader` trait.
4. **"City Action" does not mean CityCharacter.** The word *City* on an ability means "must be in the city to use it." Leaders still `extends Leader`.
5. **Always call `parent::handleEvent($event)` first** when you override `handleEvent` on a Leader — the base class handles destroyed-Leader renown loss and scheme Panache.

## Walking example

Throughout this guide we use **Cesca del Rosso** (`_03001`) as the main example:

> At the end of Dawn, draw five cards.  
> **City Reaction:** After Cesca performs a Sorcerer ability • Wound an opposing character.  
> **City Action:** Target an opposing non-Leader • Move a wound from your Strega at this location to that character.

That is one passive + one Reaction + one Action — a complete, realistic Leader.

## Related guides

- [[Creating a Character|Implementing a Character Card]] — same ability patterns; Characters require `initializeFaction` and do not use `CrewCap` / `Panache`.
- [[Creating a Scheme|Implementing a Scheme Card]] — Planning resolve + `SchemeCityAction`; different lifecycle (Home until Dusk).
- City-deck characters (`extends CityCharacter`) are a different skill — do not use this guide for those.
