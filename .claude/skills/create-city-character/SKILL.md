---
name: create-city-character
description: Implement or finish a City Character (modules/php/cards/<expansion>/_NNNNN.php where the class extends CityCharacter). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a City Character, or when they reference a city-deck character whose class extends CityCharacter and has unimplemented Text. Triggers on phrases like "implement this city character", "finish _03cdNN" (when it extends CityCharacter), "wire up the City Forced", "wire up the City Action", or natural-language descriptions of a character that lives in the city deck and is mustered out of it (Penya-style).
---

# Creating a City Character

City characters are city-deck cards that are **playable Characters** (not events, not attachments) — they sit at a city location until a player musters them, then they enter play as a Character with stats, traits, and abilities. They combine the `Character` lineage (stats, wounds, attachments, techniques) with the `CityDeckCardTrait` lineage (`CityCardNumber`, lives in the city deck, can be shuffled back in).


## How to use this skill (progressive disclosure)

1. Confirm `extends CityCharacter`. Classify each printed Text clause with the **Pick the Right Ability Shape** table below.
2. **Read only the companion files that match** - do not load every pattern file.
3. Mirror a code exemplar from [references.md](references.md) rather than inventing.
4. Before finishing, run the deep checklist in [checklist.md](checklist.md).

### Companion files

| File | Read when |
|---|---|
| [pattern-a.md](pattern-a.md) | Hard ban (canIntervene / canChallenge + eventCheck) |
| [pattern-b.md](pattern-b.md) | City Forced via handleEvent |
| [pattern-c.md](pattern-c.md) | City Action / Action |
| [pattern-d.md](pattern-d.md) | Reaction |
| [pattern-e.md](pattern-e.md) | Technique / Maneuver pointer |
| [pattern-f.md](pattern-f.md) | Continuous location-scoped passive (stat bonus or trait copy) |
| [wiring.md](wiring.md) | JS hooks, pre-commit, style |
| [helpers.md](helpers.md) | cross-cutting helpers |
| [references.md](references.md) | exemplars |
| [checklist.md](checklist.md) | full finish checklist |

When in doubt, mirror a reference rather than invent.

> **Sibling skills:**
> - `create-city-event-card` — for stubs that `extends CityEventCard`.
> - `create-city-attachment` — for stubs that `extends CityAttachment`.
> If the stub extends one of those instead of `CityCharacter`, use the matching sibling.

## Base Anatomy

`CityCharacter extends Character implements ICityDeckCard, IWealthCost` and mixes in `CityDeckCardTrait` + `WealthCostTrait`. It adds a single field — `public bool $Negotiable` — over the base `Character`.

That means a CityCharacter is, in code, a fully-featured Character (stats, wounds, attachments, techniques via `IHasTechniques`) that ALSO lives in the city deck and costs Wealth to muster (with optional parley).

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;

class _03cdNN extends CityCharacter implements IHasActions   // + IHasReactions / IHasManeuvers / etc. as the text requires
{
    use ActionTrait;   // only if IHasActions
    // use ReactionTrait;
    // use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('...');
        $this->Image           = '03cdNN.jpg';
        $this->ExpansionName   = 'faf';     // or _7s5s / tac
        $this->ExpansionNumber = 3;
        $this->CardNumber      = 0;         // city-deck cards: keep CardNumber = 0
        $this->CityCardNumber  = NN;        // the visible city-deck number on the card

        $this->Title   = clienttranslate('...');     // flavor subtitle ("Hard Knocks Hustler")
        $this->Resolve = 1;

        $this->Combat    = 0;
        $this->DashedCombat = true;                 // dashed = the stat is "—", cannot pressure with it
        $this->Finesse   = 2;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->WealthCost   = 1;            // cost to muster from the city deck
        $this->Negotiable   = true;         // text-driven: "Negotiable" means parley is allowed when paying

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Hero'),
            // ...
        ];

        $this->Text = clienttranslate("<p>...</p>");

        $this->resetCard();

        $this->Actions    = [ new Action_03cdNN() ];      // only if IHasActions
        // $this->Reactions  = [ new Reaction_03cdNN() ];
        // $this->Maneuvers  = [ new Maneuver_03cdNN() ];
    }
}
```

Field notes:
- **`Resolve`** is the wound capacity. Required for any non-attachment Character.
- **`DashedCombat` / `DashedFinesse` / `DashedInfluence`** match the printed dashes on a card's stat block. Dashed stats are visually `—` and the character cannot contribute to that pressure / cannot use that stat in challenges. Set the underlying numeric stat to `0` when dashed.
- **`Negotiable`** is the only field added by `CityCharacter` over `Character`. Set `true` if the card has the printed "Negotiable" keyword (allowing parley payment). Mirrored to the client via `getPropertyArray` automatically.
- **`WealthCost`** is the muster cost.
- **`CityCardNumber`** is the printed city-deck index (1 for Penya). `CardNumber` stays `0` — that is the convention for city-deck cards.

Key runtime state inherited from `Character` / `Card`:
- `$this->Id` — this character's card id.
- `$this->ControllerId` — the player currently controlling the character (0 while in the city deck before muster; the mustering player once in play).
- `$this->Location` — current location. While in the city deck this is the city location they sit at; once mustered, it's wherever they are in the city.
- `$this->Engaged` — engagement state, same semantics as any other Character.
- `$this->Wounds`, `$this->ModifiedResolve` — wound tracking, identical to any Character.


## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code. A single City Character commonly combines several — Penya has all four of: Negotiable, an `eventCheck` ban, a City Forced, and a City Action.

| Card phrase | Pattern |
|---|---|
| **"Negotiable"** keyword | `$this->Negotiable = true;` in the constructor. No further code. |
| **Stat printed as a dash (`—`)** | Set the matching `Dashed<Stat> = true;` flag + numeric stat to `0`. |
| **"<Name> cannot intervene."** (or any other "this character cannot do X") | Override `canIntervene()` (or `canChallenge()`) to return `false`. **Also** override `eventCheck(Event)` to throw a `UserException` when the engine attempts the banned action against this character — that surfaces the rule in the UI before the action commits. See "Pattern A — Hard ban via canIntervene + eventCheck." |
| **`<b>City Forced:</b>`** — auto-triggers while in the city; no choice | Override `handleEvent`. Gate on `$event->theah->cardInCity($this)` (or the equivalent for whatever scope the trigger covers). No Action/Reaction/State files. |
| **`<b>Forced:</b>`** (not City) — auto-triggers while in play | Same as City Forced but without the `cardInCity` gate. Gate on whatever the text scopes ("while engaged," "while at this location," etc.). |
| **`<b>City Action:</b>`** — player spends an action while the character sits in the city | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_03cdNN.php` extending `CharacterAction` (NOT `EventCityAction` — see "Action base class" below). State class(es) + JS wiring per the City Action flow. |
| **`<b>Action:</b>`** (not City) — player spends an action with the character once in play | Same as City Action — `CharacterAction` is the right base class either way. The eligibility check (in city vs in play) is what differs and goes in `isAvailableToPlayer`. |
| **`<b>City Reaction:</b>` or `<b>Reaction:</b>`** | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_03cdNN.php` extending `CardReaction`. See "Pattern D — Reaction on a CityCharacter." For "City Reaction" gate triggers on `$event->theah->cardInCity($owner)`. Button-based reactions need **no** new state class, **no** `states.inc.php` edits, **no** JS wiring. |
| **`<b>Technique:</b>` / `<b>Maneuver:</b>`** | The Character lineage already brings `TechniqueTrait`. Add `IHasManeuvers` + `ManeuverTrait` for maneuvers. Implement under `cards/<expansion>/techniques/` or `cards/<expansion>/maneuvers/`. |
| **"While you control … at \<Name\>'s location, …"** (stat bonus or gains trait; no player choice) | Continuous location-scoped passive. Override `handleEvent` and recompute — **not** Forced / Action / Reaction. No new state or JS. See "Pattern F — Continuous location-scoped passive." |


## Finish (short)

1. Walk each printed Text clause to exactly one pattern (see shape table).
2. Match constructor fields / Traits / CardNumber / WealthCost to the **printed card image** (stubs are often wrong or incomplete).
3. Put abilities in the correct subdirectory files; wire states + JS when needed - see companions. Pure passives (Pattern F) stay on the card class only.
4. Satisfy pre-commit literals; run `php -l` on touched PHP.
5. CityCharacter: set Negotiable, WealthCost, CityCardNumber; Traits in TraitNames.

**Deep checklist:** [checklist.md](checklist.md)
