# 02 — The card class

← [[01 — What is a FactionAttachment?|FactionAttachment Guide What Is A FactionAttachment]] · [[Index|Implementing a FactionAttachment Card]] · Next: [[03 — Classify the printed text|FactionAttachment Guide Classify The Printed Text]]

The card class is the attachment's identity: name, Wealth cost, stat buffs, combat-card values, traits, printed Text, and which ability objects it owns.

## Minimal FactionAttachment skeleton

File: `modules/php/cards/<expansion>/_NNNNN.php`

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
// Add these only when the printed text needs them:
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\TechniqueTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions\Action_NNNNN;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions\Reaction_NNNNN;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\techniques\Technique_NNNNN;

class _NNNNN extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('Example Attachment');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = '_7s5s';   // '_7s5s' | 'tac' | 'faf' | 'bas' | …
        $this->ExpansionNumber = 1;
        $this->CardNumber      = NN;       // must match the digits in the filename

        // REQUIRED — including Neutral attachments
        $this->initializeFaction('Montaigne');

        // REQUIRED — Wealth paid to equip from hand
        $this->WealthCost = 1;

        // Stat buffs on the equipped character (additive across attachments)
        $this->ResolveModifier   = 0;
        $this->CombatModifier    = 1;
        $this->FinesseModifier   = 0;
        $this->InfluenceModifier = 0;

        // Combat-card stats when this attachment is used in a duel
        // ALWAYS set Riposte (pre-commit). Use 0 if unused.
        $this->Riposte = 0;
        $this->Parry   = 0;
        $this->Thrust  = 2;
        // $this->DashedParry  = true;  // when the printed combat stat is conditional/"—"
        // $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate('Weapon'),
            clienttranslate('Melee'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate('<p>Printed ability text goes here.</p>');

        $this->resetCard();

        // Only if the card has Actions / Reactions / Techniques / Maneuvers:
        // $this->Actions    = [ new Action_NNNNN() ];
        // $this->Reactions  = [ new Reaction_NNNNN() ];
        // $this->Techniques = [ new Technique_NNNNN() ];
        // $this->Maneuvers  = [ new Maneuver_NNNNN() ];
    }
}
```

Cavalier Hat's real constructor is in `modules/php/cards/_7s5s/_01073.php`.  
Tabard's is in `modules/php/cards/_7s5s/_01075.php`. Keep one open while you work.

## Field checklist

| Field | Notes |
|---|---|
| `Name` | Always wrap player-visible strings in `clienttranslate('...')`. |
| `Image` | `'NNNNN.jpg'` matching the asset. |
| `CardNumber` | Same number as the filename (`_01073` → `73`, `_03065` → `65`). **Not** `CityCardNumber` — that is for city-deck cards. |
| `initializeFaction` | **Required.** Castille / Eisen / Montaigne / Ussura / Vodacce / Vesten / Vendel / **Neutral** / etc. Spelling: `Ussura` (not `Usurra`). |
| `WealthCost` | Equip cost from hand. |
| `*Modifier` | Buffs applied to the equipped character while attached. Default `0`. |
| `Riposte` / `Parry` / `Thrust` | Combat-card values. **Always set `Riposte`** (even `0`). |
| `Traits` | Every printed trait. New trait strings also go in `modules/php/TraitNames.php` (alphabetical). Common: `Weapon`, `Melee`, `Ranged`, `Attire`, `Hat`, `Tabbard`, `Talisman`, `Unique`, … |
| `OffHand` | Set `$this->OffHand = true` when printed **Offhand**. |
| `CanEquipToOpponents` | Default `false`. Set `true` only when text equips to an **opposing** character (rare). |
| `resetCard()` | Call after setting fields, before attaching Actions/Reactions/Techniques. |

## What FactionAttachments do *not* set

These belong to other card types — leave them alone:

| Field | Belongs to |
|---|---|
| `Resolve` / `Combat` / `Finesse` / `Influence` (base stats) | Characters |
| `CrewCap` / `Panache` | Leaders |
| `CityCardNumber` / `Negotiable` | CityCharacters / CityAttachments |
| `Initiative` / `PanacheModifier` | Schemes |

## Interfaces and traits (ability ownership)

The card class declares *what kinds* of abilities it has. The actual logic lives in separate Action / Reaction / Technique / Maneuver files.

| Printed text has… | On the card class |
|---|---|
| Action / City Action | `implements IHasActions` + `use ActionTrait` + `$this->Actions = [...]` |
| Reaction / City Reaction | `implements IHasReactions` + `use ReactionTrait` + `$this->Reactions = [...]` |
| Technique | `implements IHasTechniques` + `use TechniqueTrait` + `$this->Techniques = [...]` |
| Maneuver | `implements IHasManeuvers` + `use ManeuverTrait` + `$this->Maneuvers = [...]` |

Unlike `Character` (which already mixes in Technique support), **FactionAttachment does not** come with Techniques built in — you must add the interface + trait yourself when the card has one.

Register ability instances **after** `$this->resetCard()`.

## Combining multiple shapes

One attachment often stacks several patterns. Example shape:

```php
class _NNNNN extends FactionAttachment implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        // … fields …
        $this->resetCard();
        $this->Actions   = [new Action_NNNNN()];
        $this->Reactions = [new Reaction_NNNNN()];
    }
}
```

The framework hydrates each ability separately. Equip restrictions and Forced effects usually live on the **card class** itself (`eventCheck` / `handleEvent`), not in a separate file.

## Next

Classify every line of printed Text → [[03 — Classify the printed text|FactionAttachment Guide Classify The Printed Text]]
