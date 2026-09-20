# 02 — The card class

← [[01 — What is a Leader?|Leader Guide What Is A Leader]] · [[Index|Implementing a Leader Card]] · Next: [[03 — Classify the printed text|Leader Guide Classify The Printed Text]]

The card class is the Leader's identity: name, stats, traits, printed Text, and which ability objects it owns.

## Minimal Leader skeleton

File: `modules/php/cards/<expansion>/_NNNNN.php`

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
// Add these only when the printed text needs them:
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions\Action_NNNNN;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions\Reaction_NNNNN;

class _NNNNN extends Leader
{
    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('Example Name');
        $this->Title           = clienttranslate('Flavor Subtitle');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = 'faf';       // '_7s5s' | 'tac' | 'faf' | 'bas' | …
        $this->ExpansionNumber = 3;
        $this->CardNumber      = NN;         // must match the digits in the filename

        // Optional on Leaders (framework sets faction at setup).
        // FAF Leaders often call it anyway — match nearby Leaders.
        // Spelling: Ussura (not "Usurra").
        $this->initializeFaction('Vodacce');

        $this->Resolve   = 7;   // wound capacity
        $this->Combat    = 1;
        $this->Finesse   = 2;
        $this->Influence = 4;

        // Leader-only — do not leave these at 0
        $this->CrewCap = 6;
        $this->Panache = 2;

        $this->Traits = [
            clienttranslate('Leader'),   // REQUIRED — many effects check hasTrait("Leader")
            clienttranslate('Villain'),
            clienttranslate('Vodacce'),
            // … every printed trait
        ];

        $this->Text = clienttranslate('<p>Printed ability text goes here.</p>');

        $this->resetCard();   // copies base stats into Modified* fields

        // Only if the card has Actions / Reactions:
        // $this->Actions   = [ new Action_NNNNN() ];
        // $this->Reactions = [ new Reaction_NNNNN() ];
    }
}
```

Cesca's real constructor is in `modules/php/cards/faf/_03001.php` — open it and keep it open while you work.

## Field checklist

| Field | Notes |
|---|---|
| `Name` / `Title` | Always wrap player-visible strings in `clienttranslate('...')`. |
| `Image` | `'NNNNN.jpg'` matching the asset. |
| `CardNumber` | Same number as the filename (`_03001` → `1`, `_01116` → `116`). |
| `Resolve` | How many wounds before destruction. |
| `Combat` / `Finesse` / `Influence` | Printed stats. If the card shows a dash (`—`), set the number to `0` and `$this->DashedCombat = true` (or Finesse / Influence). |
| `CrewCap` | Max crew this Leader can field. Required. |
| `Panache` | Tiebreaker for scheme resolve order. Required. |
| `Traits` | Must include `"Leader"`. Add every printed trait. New trait strings also go in `modules/php/TraitNames.php` (alphabetical). |
| `resetCard()` | Call after setting stats, before attaching Actions/Reactions. |

## Interfaces and traits (ability ownership)

The card class declares *what kinds* of abilities it has. The actual logic lives in separate Action / Reaction / Technique files.

| Printed text has… | On the card class |
|---|---|
| Action / City Action | `implements IHasActions` + `use ActionTrait` + `$this->Actions = [...]` |
| Reaction / City Reaction | `implements IHasReactions` + `use ReactionTrait` + `$this->Reactions = [...]` |
| Technique | Put instances in `$this->Techniques = [...]`. Do **not** re-declare `IHasTechniques` / `TechniqueTrait` — `Character` already has them. |
| Maneuver | `implements IHasManeuvers` + `use ManeuverTrait` + `$this->Maneuvers = [...]` |

Two Actions on one card → two classes (`Action_NNNNNa`, `Action_NNNNNb`) and both in the `$this->Actions` array.

## Passive text on the card class

If the card says something with **no player choice** (for example Cesca's "At the end of Dawn, draw five cards"), you override `handleEvent` on the card class itself:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);   // REQUIRED on Leaders

    // … gate on event type, then do the effect
}
```

Details → [[04 — Passives|Leader Guide Passives]].

## Common mistakes

1. Forgetting `"Leader"` in `Traits` — break effects that say "non-Leader".
2. Leaving `CrewCap` / `Panache` at `0`.
3. Overriding `handleEvent` without `parent::handleEvent($event)` first.
4. Calling `initializeFaction('Usurra')` — correct spelling is **`Ussura`**.
5. Treating a City Action as if the card must `extends CityCharacter`.

## Next

Read the printed Text and classify each clause → [[03 — Classify the printed text|Leader Guide Classify The Printed Text]]
