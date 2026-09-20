# 02 — The card class

← [[01 — What is a Scheme?|Scheme Guide What Is A Scheme]] · [[Index|Implementing a Scheme Card]] · Next: [[03 — Classify the printed text|Scheme Guide Classify The Printed Text]]

The card class is the Scheme's identity: name, faction, Initiative, Panache, traits, printed Text, and which ability objects it owns. Most resolve logic also lives here in `handleEvent`.

## Minimal Scheme skeleton

File: `modules/php/cards/<expansion>/_NNNNN.php`

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
// Add these only when the printed text needs them:
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions\Action_NNNNN;
// use Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\reactions\Reaction_NNNNN;

class _NNNNN extends Scheme
{
    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('Example Scheme');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = 'faf';   // '_7s5s' | 'tac' | 'faf' | 'bas' | …
        $this->ExpansionNumber = 3;
        $this->CardNumber      = NN;     // must match the digits in the filename

        // REQUIRED — schemes belong to a faction deck
        // Spelling: Ussura (not "Usurra").
        $this->initializeFaction('Vodacce');

        $this->Initiative      = 91;   // never leave at 0
        $this->PanacheModifier = -1;   // often 0, +1, or -1

        $this->Traits = [
            clienttranslate('Villainous'),
            clienttranslate('Duress'),
        ];

        $this->Text = clienttranslate('<p>Printed ability text goes here.</p>');

        $this->resetCard();

        // Only if the card has Actions / Reactions:
        // $this->Actions   = [ new Action_NNNNN() ];
        // $this->Reactions = [ new Reaction_NNNNN() ];
    }
}
```

Crash the Party's real constructor is in `modules/php/cards/tac/_02004.php`.  
No Mercy's is in `modules/php/cards/faf/_03005.php`. Keep one open while you work.

## Field checklist

| Field | Notes |
|---|---|
| `Name` | Always wrap player-visible strings in `clienttranslate('...')`. |
| `Image` | `'NNNNN.jpg'` matching the asset. |
| `CardNumber` | Same number as the filename (`_02004` → `4`, `_03005` → `5`, `_01044` → `44`). |
| `initializeFaction` | **Required.** Sets `$this->Factions`. |
| `Initiative` | Printed initiative. Non-zero. Lower resolves earlier. |
| `PanacheModifier` | Printed panache modifier while chosen. |
| `Traits` | Every printed trait. New trait strings also go in `modules/php/TraitNames.php` (alphabetical). Cross-check the JPG — stubs sometimes misspell traits. |
| `Text` | Printed ability HTML. The horizontal rule (`<hr>`) separates resolve text from Action/Reaction keywords. |
| `resetCard()` | Call after setting fields, before attaching Actions/Reactions. |

## What Schemes do *not* set

Leave these alone (they belong to Characters / Leaders):

- `Resolve` / `Combat` / `Finesse` / `Influence`
- `CrewCap` / `Panache` (Leader fields — Schemes use `PanacheModifier`, not `Panache`)
- `"Leader"` trait

## Interfaces and traits (ability ownership)

The card class declares *what kinds* of abilities it has. Resolve / When-Revealed / Forced logic usually stays on the scheme class. Actions and Reactions live in separate files.

| Printed text has… | On the card class |
|---|---|
| Text above `<hr>` (resolve) | `handleEvent` for `EventResolveScheme` — see [[04|Scheme Guide Resolve Effects]] |
| "When this scheme is revealed…" | `hasWhenRevealedEffect(): true` + `EventCardWhenRevealedEffect` |
| `<b>Forced:</b>` (no player menu) | `handleEvent` on the scheme class |
| Action / City Action | `implements IHasActions` + `use ActionTrait` + `$this->Actions = [...]` |
| Reaction / City Reaction | `implements IHasReactions` + `use ReactionTrait` + `$this->Reactions = [...]` |

## Common mistakes

1. Forgetting `initializeFaction(...)`.
2. Leaving `Initiative` at `0`.
3. Using old stub style `$this->Faction = "Eisen"` instead of `initializeFaction('Eisen')`.
4. Assuming the scheme goes to discard after resolve — it stays at Home until Dusk.
5. Overriding `handleEvent` without `parent::handleEvent($event)` first.
6. Putting City Action logic on the scheme class instead of `actions/Action_NNNNN.php`.
7. Copying a Character Action base (`CharacterAction`) — schemes use `SchemeCityAction` / `SchemeAction`.

## Next

Read the printed Text and classify each clause → [[03 — Classify the printed text|Scheme Guide Classify The Printed Text]]
