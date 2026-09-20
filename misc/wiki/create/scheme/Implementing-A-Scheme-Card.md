> This page replaces the older short Scheme stub. Work through the linked pages in order.

# Creating a Scheme — Beginner Guide

Welcome. This guide teaches you how to implement a **Scheme** card for *7th Sea: City of Five Sails* on Board Game Arena.

You do not need prior experience with this codebase. Work through the pages in order the first time. After that, jump to whichever page matches the ability you are writing.

## What you will build

A complete Scheme usually means several PHP classes working together:

| Piece | Example file | What it does |
|---|---|---|
| Card class | `modules/php/cards/tac/_02004.php` | Initiative, Panache, traits, text, and which abilities the Scheme owns |
| Resolve logic | same `_NNNNN.php` `handleEvent` | Automatic Planning effect when the scheme resolves (often Renown) |
| Action class(es) | `.../actions/Action_03053.php` | Player-chosen **City Action** / **Action** during High Drama |
| Reaction class(es) | `.../reactions/Reaction_03005.php` | Triggered **Reaction** / **City Reaction** |
| State class(es) | `modules/php/States/faf/State_....php` | Multi-step UI flows (resolve picks, Forced picks, Action picks) |
| JS wiring | `modules/js/OnEnteringState.faf.js` (etc.) | Front-end highlights and Confirm buttons for those states |

Not every Scheme needs every piece. Crash the Party (`_02004`) is Renown resolve + one City Reaction. No Mercy (`_03005`) adds a discard pick during resolve. Curry Favor (`_03053`) adds a City Action with **no** High Drama sub-state.

## How to use this guide

1. [[01 — What is a Scheme?|Scheme Guide What Is A Scheme]] — concepts and where files live
2. [[02 — The card class|Scheme Guide The Card Class]] — fill in `_NNNNN.php`
3. [[03 — Classify the printed text|Scheme Guide Classify The Printed Text]] — decide which pattern each clause uses
4. Then implement only the patterns your card needs:
   - [[04 — Resolve effects|Scheme Guide Resolve Effects]]
   - [[05 — Actions|Scheme Guide Actions]]
   - [[06 — Reactions|Scheme Guide Reactions]]
   - [[07 — When Revealed, Forced, and Passives|Scheme Guide When Revealed Forced And Passives]]
   - [[08 — Challenge Actions|Scheme Guide Challenge Actions]]
5. [[09 — Wiring states and JavaScript|Scheme Guide Wiring States And JavaScript]] — when you added custom states
6. [[10 — Finish checklist|Scheme Guide Finish Checklist]] — before you call it done
7. [[11 — Example Schemes to copy|Scheme Guide Example Schemes To Copy]] — real cards to mirror
8. [[12 — Glossary and helpers|Scheme Guide Glossary And Helpers]] — jargon and useful Theah methods

## Golden rules

1. **Mirror an existing Scheme.** Do not invent a new architecture. Find a Scheme whose text is similar and copy its shape.
2. **Classify before you code.** Split the printed Text at the horizontal rule (`<hr>`). Above = scheme resolve. Below = Action / Reaction / Forced keywords.
3. **Call `initializeFaction(...)`.** Schemes belong to a faction deck.
4. **Chosen schemes sit at Home all day.** After Planning they are at `Game::LOCATION_PLAYER_HOME` until Dusk (then the Locker). They do **not** go to discard after resolve. Gate Forced / Actions / Reactions with that in mind.
5. **Always call `parent::handleEvent($event)` first** when you override `handleEvent`.
6. **New player-choice states use GameState classes** — do not copy the old inline-state pattern from core-set schemes unless you are fixing one of those legacy cards.

## Walking examples

Throughout this guide we use three Schemes you can keep open:

**Crash the Party** (`_02004`) — simplest complete Scheme with a Reaction:

> Add a Renown to the Bazaar. Add a Renown to the Forum.  
> **City Reaction:** After an opponent initiates a Pressure at a location where you have a character • Move one of your characters at an adjacent location to that location.

That is trivial resolve + one Reaction.

**No Mercy** (`_03005`) — resolve with a player pick + Reaction:

> Add Renown… Put a Gang, Crime, or Villainous card from your discard into your hand.  
> **Reaction:** After a Red Hand character you control has a challenge against them refused • Claim that location.

That is resolve (auto Renown + discard pick state) + one Reaction.

**Curry Favor** (`_03053`) — resolve pick + immediate City Action:

> Add a Renown to two different locations.  
> **City Action:** Spend a Renown • Claim your performer's location. Each opponent draws a card.

That is two-location resolve + a City Action that needs **no** High Drama GameState.

## Related guides

- [[Creating a Character|Implementing a Character Card]] / [[Creating a Leader|Implementing a Leader Card]] — Action / Reaction / Challenge shapes overlap a lot; Schemes use `SchemeCityAction` instead of `CharacterAction`.
- [[Creating a FactionAttachment|Implementing a FactionAttachment Card]] — faction-deck gear; different lifecycle (equip from hand).
- City-deck cards and Risks are different skills — do not use this guide for those.
