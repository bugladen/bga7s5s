> This page replaces the older short Character stub. Work through the linked pages in order.

# Creating a Character — Beginner Guide

Welcome. This guide teaches you how to implement a **Character** card for *7th Sea: City of Five Sails* on Board Game Arena.

You do not need prior experience with this codebase. Work through the pages in order the first time. After that, jump to whichever page matches the ability you are writing.

## What you will build

A complete Character usually means several PHP classes working together:

| Piece | Example file | What it does |
|---|---|---|
| Card class | `modules/php/cards/_7s5s/_01007.php` | Stats, traits, text, and which abilities the Character owns |
| Action class(es) | `.../actions/Action_01007.php` | Player-chosen **Action** / **City Action** |
| Reaction class(es) | `.../reactions/Reaction_03016a.php` | Triggered **Reaction** / **City Reaction** |
| Technique / Maneuver | `.../techniques/Technique_NNNNN.php` | Duel abilities |
| State class(es) | `modules/php/States/.../State_....php` | Multi-step UI flows (only when the player must pick things) |
| JS wiring | `modules/js/OnEnteringState.*.js` (etc.) | Front-end highlights and Confirm buttons for those states |

Not every Character needs every piece. A passive-only Character may be just the card class (`_03015` Joern). Aldo Bussotti (`_01007`) needs a card class + Action. Schwester Ise (`_03016`) needs a card class + two Reactions (and a passive on the card).

## How to use this guide

1. [[01 — What is a Character?|Character Guide What Is A Character]] — concepts and where files live
2. [[02 — The card class|Character Guide The Card Class]] — fill in `_NNNNN.php`
3. [[03 — Classify the printed text|Character Guide Classify The Printed Text]] — decide which pattern each clause uses
4. Then implement only the patterns your card needs:
   - [[04 — Passives|Character Guide Passives]]
   - [[05 — Actions|Character Guide Actions]]
   - [[06 — Reactions|Character Guide Reactions]]
   - [[07 — Techniques and Maneuvers|Character Guide Techniques And Maneuvers]]
   - [[08 — Challenge Actions|Character Guide Challenge Actions]]
5. [[09 — Wiring states and JavaScript|Character Guide Wiring States And JavaScript]] — when you added custom states
6. [[10 — Finish checklist|Character Guide Finish Checklist]] — before you call it done
7. [[11 — Example Characters to copy|Character Guide Example Characters To Copy]] — real cards to mirror
8. [[12 — Glossary and helpers|Character Guide Glossary And Helpers]] — jargon and useful Theah methods

## Golden rules

1. **Mirror an existing card.** Do not invent a new architecture. Find a Character whose text is similar and copy its shape.
2. **Classify before you code.** Read every sentence of the printed Text and map it to a pattern first.
3. **Call `initializeFaction(...)`.** Regular Characters live in a faction deck — this sets `$this->Factions`. Leaders often skip it; Characters must not.
4. **"City Action" does not mean CityCharacter.** The word *City* on an ability means "must be in the city to use it." Your card still `extends Character`.
5. **Always call `parent::handleEvent($event)` first** when you override `handleEvent` — base Character logic (wounds, destruction thresholds, etc.) still needs to run.

## Walking examples

Throughout this guide we use two Characters you can keep open:

**Aldo Bussotti** (`_01007`) — simplest complete Character:

> Aldo gains +1 Influence for each Renown at this location.  
> **City Action:** Move a Renown from a location you control to this one.

That is one passive + one Action.

**Schwester Ise** (`_03016`) — multi-ability Character:

> During Dusk, you may choose not to move Ise Home.  
> Ise has +1 Combat while wounded.  
> **Reaction:** After an enemy character moves to this location • Move another character you control to this location.

That is one passive + two Reactions — a realistic "complete set" without Actions.

For dual City Actions and state/JS wiring, also open **Damya Kahina** (`_03038`).

## Related guides

- [[Creating a Leader|Implementing a Leader Card]] — same ability patterns; Leaders add `CrewCap`, `Panache`, and the `Leader` trait.
- [[Creating a Scheme|Implementing a Scheme Card]] — Planning resolve + `SchemeCityAction`; different lifecycle (Home until Dusk).
- [[Creating a CityCharacter|Implementing a CityCharacter Card]] — city-deck hirelings (`extends CityCharacter`); do not use this Character guide for those.
