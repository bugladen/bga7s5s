> This page replaces the older short Risk stub. Work through the linked pages in order the first time. After that, jump to whichever page matches the ability you are writing.

# Creating a Risk — Beginner Guide

Welcome. This guide teaches you how to implement a **Risk** card for *7th Sea: City of Five Sails* on Board Game Arena.

You do not need prior experience with this codebase. Risks are **faction-deck combat cards**: a player draws them into hand, may play them as **Actions / City Actions / Reactions** from hand, and plays them as **combat cards (Maneuvers)** during duels. They are **not** city-deck cards.

## When to use Risk (the use-case)

Use this guide when your stub says:

```php
class _NNNNN extends Risk
```

| You want… | Correct base class |
|---|---|
| A combat card with Riposte / Parry / Thrust that lives in a **player's faction deck**, is drawn into hand, and is played in duels (and sometimes as Action / Reaction from hand) | **`Risk`** ← this guide |
| A person with Resolve / Combat who fights | `Character` / `Leader` → [[Character guide|Implementing a Character Card]] / [[Leader guide|Implementing a Leader Card]] |
| A Planning card with Initiative / Panache | `Scheme` → [[Scheme guide|Implementing a Scheme Card]] |
| Faction-deck gear that equips onto a character | `FactionAttachment` → [[FactionAttachment guide|Implementing a FactionAttachment Card]] |
| City-deck hireling / city gear | `CityCharacter` / `CityAttachment` → their guides |

**In plain English:** if the printed card is a *combat Risk* (Wealth cost + Riposte/Parry/Thrust) that belongs in a faction deck — you are writing a Risk.

## What you will build

A complete Risk usually means several PHP classes working together:

| Piece | Example file | What it does |
|---|---|---|
| Card class | `modules/php/cards/faf/_03008.php` | Name, WealthCost, combat stats, Traits, Text, which abilities it owns |
| Action class(es) | `.../actions/Action_03008.php` | Player-chosen **City Action** / **Action** from hand |
| Maneuver class(es) | `.../maneuvers/Maneuver_03008.php` | Duel-round modifier when this Risk is the combat card |
| Reaction class(es) | `.../reactions/Reaction_03012.php` | Triggered **Reaction** / **City Reaction** played from hand |
| State class(es) | `modules/php/States/faf/State_....php` | Multi-step UI flows (only when the player must pick things) |
| JS wiring | `modules/js/OnEnteringState.faf.js` (etc.) | Highlights and Confirm buttons for those states |

Not every Risk needs every piece. Some Risks are **only** combat stats + a Maneuver. Some are **only** a City Action. Many combine several.

## How to use this guide

1. [[01 — What is a Risk?|Risk Guide What Is A Risk]] — concepts and where files live
2. [[02 — The card class|Risk Guide The Card Class]] — fill in `_NNNNN.php`
3. [[03 — Classify the printed text|Risk Guide Classify The Printed Text]] — decide which pattern each clause uses
4. Then implement only the patterns your card needs:
   - [[04 — City Actions|Risk Guide City Actions]]
   - [[05 — Actions|Risk Guide Actions]]
   - [[06 — Maneuvers|Risk Guide Maneuvers]]
   - [[07 — Reactions|Risk Guide Reactions]]
   - [[08 — Passives, Forced, and discounts|Risk Guide Passives Forced And Discounts]]
5. [[09 — Wiring states and JavaScript|Risk Guide Wiring States And JavaScript]] — when you added custom states
6. [[10 — Finish checklist|Risk Guide Finish Checklist]] — before you call it done
7. [[11 — Example Risks to copy|Risk Guide Example Risks To Copy]] — real cards to mirror
8. [[12 — Glossary and helpers|Risk Guide Glossary And Helpers]] — jargon and useful Theah methods

## Golden rules

1. **Mirror an existing Risk.** Do not invent a new architecture. Find a Risk whose text is similar and copy its shape.
2. **Classify before you code.** Split the printed Text into clauses and assign each a pattern first.
3. **Call `initializeFaction(...)`.** Risks belong to a faction deck (including Neutral).
4. **Set `WealthCost` and combat stats.** Every Risk has a printed Wealth cost. Set Riposte / Parry / Thrust (and `DashedX` when the print shows a dashed value).
5. **"City Action" ≠ city-deck card.** The word *City* on an ability means "performer must be in the city." Your card still `extends Risk`. Use `RiskCityAction` for City Actions and `RiskAction` for plain Actions.
6. **"City Reaction" is still `RiskReaction`.** There is no `RiskCityReaction` base. Add a city-presence gate yourself.
7. **Always call `parent::handleEvent($event)` first** when you override `handleEvent`.

## Walking examples

Throughout this guide we use these Risks you can keep open:

**Arrogant** (`_03008`) — City Action + Gambling Maneuver:

> **City Action:** Target an opposing character with Influence 2 or less • Your performer issues a Combat challenge…  
> **Gambling Maneuver:** If your participant has more Influence than the adversary • +1 Riposte. Draw a card.

That is one City Action challenge + one gated Maneuver.

**Follow the Thread** (`_03009`) — plain Action + Maneuver:

> **Sorcerer Strega Action:** Move your performer to an adjacent location where there is an enemy character or an available Mercenary.  
> **Strega Maneuver:** −1 Thrust. Wound the adversary.

That is a location-chooser Action (home performers allowed) + a Maneuver.

**Glorious** (`_03033`) — Forced on the card + Gambling Maneuver:

> **Forced:** After your adversary is destroyed, if this card is in your dueling line • Heal your participant.  
> **Gambling Maneuver:** If your participant has equal or greater Influence than the adversary • Wound the adversary.

That is Forced on the Risk class itself (no separate Forced file) + a pure-resolve Maneuver.

For Reactions, also keep **Subtle** (`_03012`) or **Passionate** (`_03046`) open.

## Related guides

- [[Creating a Character|Implementing a Character Card]] / [[Creating a Leader|Implementing a Leader Card]] — Action / Reaction / Technique shapes overlap; Risks use `RiskAction` / `RiskCityAction` / `RiskReaction` / `Maneuver`
- [[Creating a Scheme|Implementing a Scheme Card]] — also faction-deck, but Planning lifecycle (not combat cards)
- [[Creating a FactionAttachment|Implementing a FactionAttachment Card]] — faction-deck gear that equips; different lifecycle
