---
name: create-city-event-card
description: Implement or finish a City Event Card (modules/php/cards/faf/_03cdNN.php and similar). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a City Event Card, or when they reference a card whose class extends CityEventCard and has unimplemented Text. Triggers on phrases like "implement this city event", "finish _03cdNN", "wire up the Forced ability", or "add a City Action to this event card."
---

# Creating a City Event Card

City event cards are city-deck cards that sit at a city location and modify play through Forced abilities, City Actions, or City Reactions. This skill is the playbook for fleshing out a `_03cdNN.php` (or any `extends CityEventCard`) stub into a working card.

The `faf` branch has established a fairly rich pattern for these cards — separate State classes, dedicated JS files per expansion, multi-player sequential loops via queued transitions. Follow it, even when it feels heavier than strictly needed for a one-clause card.

## How to use this skill (progressive disclosure)

1. Confirm `extends CityEventCard`. Classify each printed Text clause with the **Pick the Right Ability Shape** table below.
2. **Read only the companion files that match** - do not load every pattern file.
3. Mirror a code exemplar from [references.md](references.md) rather than inventing.
4. Before finishing, run the deep checklist in [checklist.md](checklist.md).

### Companion files

| File | Read when |
|---|---|
| [pattern-a.md](pattern-a.md) | Forced ability |
| [pattern-b.md](pattern-b.md) | City Action (full faf flow) |
| [pattern-c.md](pattern-c.md) | City Reaction / Reaction while in Home |
| [wiring.md](wiring.md) | pre-commit + style |
| [sub-patterns.md](sub-patterns.md) | reusable sub-patterns (loops, choosers, etc.) |
| [references.md](references.md) | exemplars |
| [checklist.md](checklist.md) | full finish checklist |

When in doubt, mirror a reference rather than invent.

## Base Anatomy

Every `CityEventCard` lives under `modules/php/cards/<expansion>/` (e.g. `faf/`) and inherits from `CityEventCard`, which itself extends `Card` and uses `CityDeckCardTrait`. Required scaffolding (already present in stubs):

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;

class _03cdNN extends CityEventCard
{
    public function __construct()
    {
        parent::__construct();

        $this->Name           = clienttranslate('...');
        $this->Image          = '03cdNN.jpg';
        $this->ExpansionName  = 'faf';   // or _7s5s / tac
        $this->ExpansionNumber = 3;
        $this->CardNumber     = 0;       // city deck cards keep CardNumber = 0
        $this->CityCardNumber = NN;      // the visible city number on the card

        $this->Traits = [ clienttranslate('...'), ];

        $this->Text = clienttranslate("...");

        $this->resetCard();
    }
}
```

Key facts:
- The card's runtime `$this->Location` is the city location it currently occupies (e.g. `Game::LOCATION_CITY_OLES_INN`). Use `$event->theah->cardInCity($this)` before reacting.
- `CityEventCard::handleEvent` clears per-day usage tracking on `EventNewDay`. Always call `parent::handleEvent($event)` first when overriding.
- Text tooltips for events are already wired in `modules/js/Utilities.js` (`createTextTooltipForEvent`) — no JS changes needed for a new event's tooltip.
- File naming: leading underscore + the city-card image stem, e.g. `_03cd08.php` for `03cd08.jpg`. Class name matches the filename.


## Pick the Right Ability Shape

Read the card's `Text` and classify each clause before writing any code:

| Card phrase | Pattern |
|---|---|
| **`<b>Forced:</b>` / `<b>City Forced:</b>`** — auto-triggers, no choice | Override `handleEvent` directly on the card class. No Action/Reaction/State files needed. |
| **`<b>City Action:</b>`** — player spends an action | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_03cdNN.php`. State class(es) + JS wiring if it needs interactive steps. |
| **`<b>City Reaction:</b>`** — player chooses to trigger in response to an event while the card is in a city location | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_03cdNN.php`. |
| **`<b>Reaction:</b>`** (no "City" prefix) — player chooses to trigger while the card is in their **Home** | Same `IHasReactions` + `ReactionTrait` + `reactions/Reaction_03cdNN.php` plumbing as City Reaction. The only difference is the `handleEvent` location guard: check `$owner->Location == Game::LOCATION_PLAYER_HOME` instead of `cardInCity($owner)`. See `_03cd20` (Early Morning Arrangements) — first CityEventCard precedent for a Home-located reaction. Requires the card to actually be able to *land* in a player's Home, which is its own sub-pattern (below). |

A single card can combine these (e.g. Penya `_03cd01` has both a City Forced and a City Action; `_03cd20` has a Reaction at end of Planning while in Home AND a City Action that puts itself into Home).


## Finish (short)

1. Walk each printed Text clause to exactly one pattern (see shape table).
2. Match constructor fields / Traits / CardNumber to the printed card.
3. Put abilities in the correct subdirectory files; wire states + JS when needed - see companions.
4. Satisfy pre-commit literals; run `php -l` on touched PHP.
5. CityEventCard: set CityCardNumber; Forced often lives on the card class itself.

**Deep checklist:** [checklist.md](checklist.md)
