# 02 — The card class

← [[01 — What is a CityAttachment?|CityAttachment Guide What Is A CityAttachment]] · [[Index|Implementing a CityAttachment Card]] · Next: [[03 — Classify the printed text|CityAttachment Guide Classify The Printed Text]]

The card class is the attachment's identity: name, Wealth cost, city number, stat buffs, traits, printed Text, and which ability objects it owns.

## Minimal CityAttachment skeleton

File: `modules/php/cards/<expansion>/_03cdNN.php` (or `_NNNNN.php` for base-game city cards)

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
// Add these only when the printed text needs them:
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions\Action_03cdNN;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions\Reaction_03cdNN;

class _03cdNN extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('Example City Attachment');
        $this->Image           = '03cdNN.jpg';
        $this->ExpansionName   = 'faf';   // '_7s5s' | 'tac' | 'faf' | 'bas' | …
        $this->ExpansionNumber = 3;

        // City-deck numbering (see table below)
        $this->CardNumber      = 0;
        $this->CityCardNumber  = NN;     // visible city number on the card

        // REQUIRED — Wealth paid to equip from the city
        $this->WealthCost = 1;

        // Stat buffs on the equipped character (additive across attachments)
        $this->ResolveModifier   = 0;
        $this->CombatModifier    = 0;
        $this->FinesseModifier   = 0;
        $this->InfluenceModifier = 0;

        $this->Traits = [
            clienttranslate('Artifact'),
            clienttranslate('Trinket'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate('<p>Printed ability text goes here.</p>');

        $this->resetCard();

        // Only if the card has Actions / Reactions:
        // $this->Actions   = [ new Action_03cdNN() ];
        // $this->Reactions = [ new Reaction_03cdNN() ];
    }
}
```

Devil Jonah's Bones' real constructor is in `modules/php/cards/faf/_03cd05.php`.  
Guild Triskelion's is in `modules/php/cards/_7s5s/_01198.php`. Keep one open while you work.

## Field checklist

| Field | Notes |
|---|---|
| `Name` | Always wrap player-visible strings in `clienttranslate('...')`. |
| `Image` | `'03cdNN.jpg'` or `'NNNNN.jpg'` matching the asset. |
| `CityCardNumber` | **Required.** Visible city-deck number printed on the card. |
| `CardNumber` | **Newer expansions (`faf`, etc.):** keep `0`. **Base game (`_7s5s`):** often set to the image id (`198` for `_01198`). Match siblings in the same folder. |
| `WealthCost` | Equip cost from the city. |
| `*Modifier` | Buffs applied to the equipped character while attached. Default `0`. |
| `Traits` | Every printed trait. New trait strings also go in `modules/php/TraitNames.php` (alphabetical). |
| `resetCard()` | Call after setting fields, before attaching Actions/Reactions. |

## What CityAttachments do *not* set

These belong to other card types — leave them alone:

| Field | Belongs to |
|---|---|
| `initializeFaction(...)` | FactionAttachments / Characters |
| `Riposte` / `Parry` / `Thrust` | **FactionAttachments** (combat-card stats). CityAttachments are **not** required to set `Riposte` — the pre-commit hook only enforces it on `FactionAttachment`. |
| `Resolve` / `Combat` / `Finesse` / `Influence` (base stats) | Characters |
| `CrewCap` / `Panache` | Leaders |
| `Negotiable` | CityCharacters |
| `Initiative` / `PanacheModifier` | Schemes |

## Interfaces and traits (ability ownership)

The card class declares *what kinds* of abilities it has. The actual Action / Reaction logic lives in separate files.

| Printed text has… | On the card class |
|---|---|
| Action / City Action | `implements IHasActions` + `use ActionTrait` + `$this->Actions = [...]` |
| Reaction / City Reaction | `implements IHasReactions` + `use ReactionTrait` + `$this->Reactions = [...]` |

Forced effects, passive trait grants, and steady-state overrides usually live **on the card class itself** (`handleEvent` / `get*` methods) — no separate ability file.

Register ability instances **after** `$this->resetCard()`.

## Combining multiple shapes

One attachment often stacks several patterns. Example shape:

```php
class _03cdNN extends CityAttachment implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        // … fields …
        $this->resetCard();
        $this->Actions   = [new Action_03cdNN()];
        $this->Reactions = [new Reaction_03cdNN()];
    }
}
```

Devil Jonah's Bones needs **no** Action/Reaction arrays — its Forced and gamble logic live entirely on the card class plus a custom state.

## Next

Classify every line of printed Text → [[03 — Classify the printed text|CityAttachment Guide Classify The Printed Text]]
