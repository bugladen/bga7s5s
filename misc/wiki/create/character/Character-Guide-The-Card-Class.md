# 02 — The card class

← [[01 — What is a Character?|Character Guide What Is A Character]] · [[Index|Implementing a Character Card]] · Next: [[03 — Classify the printed text|Character Guide Classify The Printed Text]]

The card class is the Character's identity: name, stats, traits, printed Text, and which ability objects it owns.

## Minimal Character skeleton

File: `modules/php/cards/<expansion>/_NNNNN.php`

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
// Add these only when the printed text needs them:
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions\Action_NNNNN;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions\Reaction_NNNNN;

class _NNNNN extends Character
{
    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('Example Name');
        $this->Title           = clienttranslate('Flavor Subtitle');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = '_7s5s';   // '_7s5s' | 'tac' | 'faf' | 'bas' | …
        $this->ExpansionNumber = 1;
        $this->CardNumber      = NN;       // must match the digits in the filename

        // REQUIRED for regular Characters (sets $this->Factions)
        // Spelling: Ussura (not "Usurra").
        $this->initializeFaction('Vodacce');

        $this->Resolve   = 4;   // wound capacity
        $this->Combat    = 1;
        $this->Finesse   = 3;
        $this->Influence = 1;
        // $this->DashedCombat = true;  // when the printed stat is "—"

        $this->Traits = [
            clienttranslate('Diplomat'),
            clienttranslate('Red Hand'),
            clienttranslate('Vodacce'),
            // … every printed trait — do NOT add "Leader" unless the card is a Leader
        ];

        $this->Text = clienttranslate('<p>Printed ability text goes here.</p>');

        $this->resetCard();   // copies base stats into Modified* fields

        // Only if the card has Actions / Reactions / Techniques:
        // $this->Actions    = [ new Action_NNNNN() ];
        // $this->Reactions  = [ new Reaction_NNNNN() ];
        // $this->Techniques = [ new Technique_NNNNN() ];
    }
}
```

Aldo's real constructor is in `modules/php/cards/_7s5s/_01007.php`.  
Ise's is in `modules/php/cards/faf/_03016.php`. Keep one open while you work.

## Field checklist

| Field | Notes |
|---|---|
| `Name` / `Title` | Always wrap player-visible strings in `clienttranslate('...')`. |
| `Image` | `'NNNNN.jpg'` matching the asset. |
| `CardNumber` | Same number as the filename (`_01007` → `7`, `_03016` → `16`, `_01123` → `123`). |
| `initializeFaction` | **Required** for regular Characters. Leaders often omit this. |
| `Resolve` | How many wounds before destruction. |
| `Combat` / `Finesse` / `Influence` | Printed stats. If the card shows a dash (`—`), set the number to `0` and `$this->DashedCombat = true` (or Finesse / Influence). |
| `Traits` | Every printed trait. New trait strings also go in `modules/php/TraitNames.php` (alphabetical). Do **not** invent a `"Leader"` trait on a non-Leader. |
| `resetCard()` | Call after setting stats, before attaching Actions/Reactions. |

## What Characters do *not* set

These belong to **Leaders** only — leave them alone on a Character:

- `CrewCap`
- `Panache`
- Mandatory `"Leader"` trait

## Interfaces and traits (ability ownership)

The card class declares *what kinds* of abilities it has. The actual logic lives in separate Action / Reaction / Technique files.

| Printed text has… | On the card class |
|---|---|
| Action / City Action | `implements IHasActions` + `use ActionTrait` + `$this->Actions = [...]` |
| Reaction / City Reaction | `implements IHasReactions` + `use ReactionTrait` + `$this->Reactions = [...]` |
| Technique | Put instances in `$this->Techniques = [...]`. Do **not** re-declare `IHasTechniques` / `TechniqueTrait` — `Character` already has them. |
| Maneuver | `implements IHasManeuvers` + `use ManeuverTrait` + `$this->Maneuvers = [...]` |

Two Actions on one card → two classes (`Action_NNNNNa`, `Action_NNNNNb`) and both in the `$this->Actions` array. Reference: Damya `_03038`.

Two Reactions on one card → same idea (`Reaction_NNNNNa`, `Reaction_NNNNNb`). Reference: Ise `_03016`.

## Passive text on the card class

If the card says something with **no player choice** (for example Aldo's Influence-from-Renown, or Ise's "+1 Combat while wounded"), you override `handleEvent` on the card class itself:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);   // REQUIRED

    // … gate on event type, then do the effect
}
```

Details → [[04 — Passives|Character Guide Passives]].

## Common mistakes

1. Forgetting `initializeFaction(...)` — Character has no faction affiliation in the engine.
2. Calling `initializeFaction('Usurra')` — correct spelling is **`Ussura`**.
3. Adding `"Leader"` to Traits on a non-Leader.
4. Setting `CrewCap` / `Panache` on a Character (copy-paste from a Leader skeleton).
5. Overriding `handleEvent` without `parent::handleEvent($event)` first.
6. Re-declaring `IHasTechniques` / `TechniqueTrait` — Character already provides them.
7. Treating a City Action as if the card must `extends CityCharacter`.

## Next

Read the printed Text and classify each clause → [[03 — Classify the printed text|Character Guide Classify The Printed Text]]
