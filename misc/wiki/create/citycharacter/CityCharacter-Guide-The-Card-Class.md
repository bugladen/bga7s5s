# 02 — The card class

← [[01 — What is a CityCharacter?|CityCharacter Guide What Is A CityCharacter]] · [[Index|Implementing a CityCharacter Card]] · Next: [[03 — Classify the printed text|CityCharacter Guide Classify The Printed Text]]

The card class is the CityCharacter's identity: name, stats, city-deck numbers, Wealth cost, Negotiable, traits, printed Text, and which ability objects it owns.

## Minimal CityCharacter skeleton

File: `modules/php/cards/<expansion>/_03cdNN.php`

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
// Add these only when the printed text needs them:
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions\Action_03cdNN;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions\Reaction_03cdNN;

class _03cdNN extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('Example Name');
        $this->Title           = clienttranslate('Flavor Subtitle');
        $this->Image           = '03cdNN.jpg';
        $this->ExpansionName   = 'faf';   // '_7s5s' | 'tac' | 'faf' | 'bas' | …
        $this->ExpansionNumber = 3;

        // City-deck convention:
        $this->CardNumber     = 0;    // always 0 for city-deck cards
        $this->CityCardNumber = NN;   // the printed city-deck number on the card

        $this->Resolve   = 1;   // wound capacity
        $this->Combat    = 0;
        $this->DashedCombat = true;   // when the printed Combat is "—"
        $this->Finesse   = 2;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->WealthCost = 1;        // cost to muster from the city
        $this->Negotiable = true;     // only if the card prints "Negotiable"

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Hero'),
            // … every printed trait
        ];

        $this->Text = clienttranslate('<p>Printed ability text goes here.</p>');

        $this->resetCard();   // copies base stats into Modified* fields

        // Only if the card has Actions / Reactions / Maneuvers:
        // $this->Actions   = [ new Action_03cdNN() ];
        // $this->Reactions = [ new Reaction_03cdNN() ];
        // $this->Maneuvers = [ new Maneuver_03cdNN() ];
        // Techniques: $this->Techniques = [ new Technique_03cdNN() ];
        //   (do NOT re-declare IHasTechniques / TechniqueTrait — Character already has them)
    }
}
```

Penya's real constructor is in `modules/php/cards/faf/_03cd01.php`.  
Julius's is in `modules/php/cards/faf/_03cd10.php`. Keep one open while you work.

## Field checklist

| Field | Notes |
|---|---|
| `Name` / `Title` | Always wrap player-visible strings in `clienttranslate('...')`. |
| `Image` | `'03cdNN.jpg'` matching the asset. |
| `CardNumber` | **Always `0`** for city-deck cards. |
| `CityCardNumber` | The printed city-deck index (Penya = `1`, Julius = `10`). |
| `WealthCost` | Muster cost from the city. |
| `Negotiable` | `true` only if the card prints **Negotiable** (parley allowed). Default from the base class is `false`. |
| `Resolve` | How many wounds before destruction. |
| `Combat` / `Finesse` / `Influence` | Printed stats. If the card shows a dash (`—`), set the number to `0` and `$this->DashedCombat = true` (or Finesse / Influence). |
| `Traits` | Every printed trait. New trait strings also go in `modules/php/TraitNames.php` (alphabetical). |
| `resetCard()` | Call after setting stats, before attaching Actions/Reactions. |

## What CityCharacters do *not* set

| Field / call | Why |
|---|---|
| `initializeFaction(...)` | Faction-deck Characters only. CityCharacters are not in a faction deck. |
| `CrewCap` / `Panache` | Leaders only. |
| Mandatory `"Leader"` trait | Leaders only. |

## Interfaces and traits (ability ownership)

The card class declares *what kinds* of abilities it has. The actual logic lives in separate Action / Reaction / Technique files.

| Printed text has… | On the card class |
|---|---|
| Action / City Action | `implements IHasActions` + `use ActionTrait` + `$this->Actions = [...]` |
| Reaction / City Reaction | `implements IHasReactions` + `use ReactionTrait` + `$this->Reactions = [...]` |
| Technique | Put instances in `$this->Techniques = [...]`. Do **not** re-declare `IHasTechniques` / `TechniqueTrait`. |
| Maneuver | `implements IHasManeuvers` + `use ManeuverTrait` + `$this->Maneuvers = [...]` |

## Passive / Forced / hard-ban text on the card class

If the card says something with **no player choice** (Penya's "cannot intervene", Penya's City Forced), you override methods on the **card class** itself:

- Hard ban → `canIntervene()` / `canChallenge()` + `eventCheck`
- City Forced / passive → `handleEvent`

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);   // REQUIRED

    // … gate on event type + cardInCity, then do the effect
}
```

Details → [[04 — Hard bans, Forced, and passives|CityCharacter Guide Passives]].

## Common mistakes

1. Setting `CardNumber` to the city number instead of leaving it `0` and using `CityCardNumber`.
2. Calling `initializeFaction(...)` (copied from a Character skeleton).
3. Setting `CrewCap` / `Panache` (copied from a Leader skeleton).
4. Forgetting `WealthCost` / leaving `Negotiable` wrong relative to the print.
5. Overriding `handleEvent` without `parent::handleEvent($event)` first.
6. Putting a City Action on `EventCityAction` — use `CharacterAction` (see [[05|CityCharacter Guide Actions]]).
7. Re-declaring `IHasTechniques` / `TechniqueTrait`.

## Next

Read the printed Text and classify each clause → [[03 — Classify the printed text|CityCharacter Guide Classify The Printed Text]]
