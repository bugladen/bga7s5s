# 02 — The card class

← [[01 — What is a Risk?|Risk Guide What Is A Risk]] · [[Index|Implementing a Risk Card]] · Next: [[03 — Classify the printed text|Risk Guide Classify The Printed Text]]

The card class is the Risk's identity: name, Wealth cost, combat stats, traits, printed Text, and which ability objects it owns.

## Minimal Risk skeleton

File: `modules/php/cards/<expansion>/_NNNNN.php`

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
// Add these only when the printed text needs them:
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions\Action_NNNNN;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\maneuvers\Maneuver_NNNNN;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions\Reaction_NNNNN;

class _NNNNN extends Risk
{
    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('Example Risk');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = 'faf';   // '_7s5s' | 'tac' | 'faf' | 'bas' | …
        $this->ExpansionNumber = 3;
        $this->CardNumber      = NN;       // must match the digits in the filename

        // REQUIRED — Risks live in a faction deck (including Neutral)
        $this->initializeFaction('Vodacce');

        // REQUIRED — printed Wealth cost
        $this->WealthCost = 1;

        // Combat-card stats — set what the print shows (0 is fine if absent)
        $this->Riposte = 2;
        $this->Parry   = 0;
        $this->Thrust  = 1;
        // $this->DashedParry = true;  // when the print shows a dashed / "—" value

        $this->Traits = [
            clienttranslate('Flourish'),
            clienttranslate('Hubris'),
        ];

        $this->Text = clienttranslate('<p>Printed ability text goes here.</p>');

        $this->resetCard();

        // Only if the card has Actions / Maneuvers / Reactions:
        // $this->Actions   = [ new Action_NNNNN() ];
        // $this->Maneuvers = [ new Maneuver_NNNNN() ];
        // $this->Reactions = [ new Reaction_NNNNN() ];
    }
}
```

Arrogant's real constructor is in `modules/php/cards/faf/_03008.php`.  
Follow the Thread's is in `modules/php/cards/faf/_03009.php`. Keep one open while you work.

## Field checklist

| Field | Notes |
|---|---|
| `Name` | Always wrap player-visible strings in `clienttranslate('...')`. |
| `Image` | `'NNNNN.jpg'` matching the asset. |
| `CardNumber` | Same number as the filename (`_03008` → `8`, `_01115` → `115`). |
| `initializeFaction` | **Required.** Castille / Eisen / Montaigne / Ussura / Vodacce / Vesten / Vendel / **Neutral** / etc. Spelling: `Ussura` (not `Usurra`). |
| `WealthCost` | **Required.** Cost to play from hand / pay as combat card. |
| `Riposte` / `Parry` / `Thrust` | Combat-card values. Default `0` if the print has no number. |
| `DashedRiposte` / `DashedParry` / `DashedThrust` | Set `true` when the print shows a dashed value for that stat. |
| `Traits` | Every printed trait. New trait strings also go in `modules/php/TraitNames.php` (alphabetical). Watch stub typos (`Bureaucracy`, not `Beauracracy`). |
| `resetCard()` | Call after setting fields, before attaching Actions/Maneuvers/Reactions. |

## What Risks do *not* set

These belong to other card types — leave them alone:

| Field | Belongs to |
|---|---|
| `Resolve` / `Combat` / `Finesse` / `Influence` (character stats) | Characters |
| `CrewCap` / `Panache` | Leaders |
| `CityCardNumber` / `Negotiable` | City cards |
| `Initiative` / `PanacheModifier` | Schemes |
| `ResolveModifier` / equip modifiers | Attachments |

## Interfaces and traits (ability ownership)

The card class declares *what kinds* of abilities it has. The actual logic lives in separate Action / Maneuver / Reaction files.

| Printed text has… | On the card class |
|---|---|
| Action / City Action | `implements IHasActions` + `use ActionTrait` + `$this->Actions = [...]` |
| Maneuver / Gambling Maneuver / Duelist Maneuver / … | `implements IHasManeuvers` + `use ManeuverTrait` + `$this->Maneuvers = [...]` |
| Reaction / City Reaction | `implements IHasReactions` + `use ReactionTrait` + `$this->Reactions = [...]` |

Two distinct trait-prefixed Maneuvers or Reactions on one Risk → split into `Maneuver_NNNNNa` / `Maneuver_NNNNNb` (or Reaction a/b). Do not merge into one class with a mode field. See `_03046`, `_03047`.

## When to mark "targets characters"

Only when the printed text uses **"Target"** / **"target"** (Rules Team wording) and the ability hands the player a character chooser — including friendly choosers.

| Situation | Mark interfaces? |
|---|---|
| Text says **Target** an opposing / another character | Yes — `IRiskThatTargetsCharacters` on the Risk + `IAbilityThatTargetsCharacters` on the ability |
| Location chooser only (`_03009`, `_03032`, `_03045`) | **No** |
| Hand-discard chooser (`_03036`) | **No** |
| Fixed-trigger Reaction with no chooser (`_03012`, `_03046`) | **No** |
| Chooser whose text never says "target" (heal another, swap other character, engage opposing without "Target") | **No** — see `_03060`, `_03069`, `_03071` |

Wrong marks break Cesca-style copy Reactions. When unsure, mirror a reference from [[11 — Examples|Risk Guide Example Risks To Copy]].

## Forced and passives live on the Risk class

**Forced:** and some always-on discounts / duel modifiers often go in `_NNNNN::handleEvent` (or discount methods on the Maneuver / Action) — **not** a separate Forced file. See [[08 — Passives, Forced, and discounts|Risk Guide Passives Forced And Discounts]].

## Next

Classify each printed clause → [[03 — Classify the printed text|Risk Guide Classify The Printed Text]]
