---
name: create-city-attachment
description: Implement or finish a City Attachment (modules/php/cards/<expansion>/_NNNNN.php where the class extends CityAttachment). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a City Attachment, or when they reference a city-deck attachment whose class extends CityAttachment and has unimplemented Text. Triggers on phrases like "implement this city attachment", "finish _03cdNN" (when it extends CityAttachment), "wire up the equip wound", "the equipped character does X", or "add an Action to this attachment."
---

# Creating a City Attachment

City attachments are city-deck cards that *equip onto a character* at a city location, modifying that character's stats and granting Forced abilities, Actions, or Reactions tied to "the equipped character." Unlike City Event Cards, they have stat modifiers, a `WealthCost`, an `AttachedToId`, and they stay in play across pressures until destroyed or unequipped.

The canonical reference for the full pattern is `_03cd05.php` (Devil Jonah's Bones): a Forced wound on equip + a steady-state gamble modifier + a custom state inserted into the duel flow. Read it before scaffolding anything novel.

> **Sibling skill:** `create-city-event-card` covers `CityEventCard`. If the stub `extends CityEventCard` (not `CityAttachment`), use that skill instead.

## How to use this skill (progressive disclosure)

1. Confirm `extends CityAttachment`. Classify each printed Text clause with the **Pick the Right Ability Shape** table below.
2. **Read only the companion files that match** - do not load every pattern file.
3. Mirror a code exemplar from [references.md](references.md) rather than inventing.
4. Before finishing, run the deep checklist in [checklist.md](checklist.md).

### Companion files

| File | Read when |
|---|---|
| [pattern-a.md](pattern-a.md) | Forced on equip / while equipped |
| [pattern-b.md](pattern-b.md) | Passive grant via Equip/Unequip |
| [pattern-c.md](pattern-c.md) | AttachmentAction |
| [pattern-d.md](pattern-d.md) | AttachmentReaction |
| [pattern-e.md](pattern-e.md) | Steady-state override (not event mutation) |
| [pattern-f.md](pattern-f.md) | Custom state inserted into core flow |
| [pattern-g.md](pattern-g.md) | Forced once-per-day cancel of opponent Risks |
| [helpers.md](helpers.md) | helpers |
| [wiring.md](wiring.md) | pre-commit + style |
| [references.md](references.md) | exemplars |
| [checklist.md](checklist.md) | full finish checklist |

When in doubt, mirror a reference rather than invent.

## Base Anatomy

City attachments live under `modules/php/cards/<expansion>/` (e.g. `faf/`, `_7s5s/`, `tac/`, `bas/`) and extend `CityAttachment`. `CityAttachment` extends `Attachment` and mixes in `CityDeckCardTrait`.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;

class _03cdNN extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name           = clienttranslate('...');
        $this->Title          = clienttranslate('...'); // optional subtitle on Attachment
        $this->Image          = '03cdNN.jpg';
        $this->ExpansionName  = 'faf';     // or _7s5s / tac / bas
        $this->ExpansionNumber = 3;
        $this->CardNumber     = 0;          // city deck cards: keep CardNumber = 0
        $this->CityCardNumber = NN;         // visible city number on the card

        $this->WealthCost = 1;              // attachment equip cost

        // Stat buffs applied while equipped. Default 0.
        $this->ResolveModifier   = 0;
        $this->CombatModifier    = 0;
        $this->FinesseModifier   = 0;
        $this->InfluenceModifier = 0;
        // Do NOT set Riposte — CityAttachment has no FactionCardTrait.

        $this->Traits = [ clienttranslate('Artifact'), /* ... */ ];

        $this->Text = clienttranslate("<p><b>Forced:</b> ...</p>");

        $this->resetCard();
    }
}
```

Key state-on-the-instance:
- `$this->Id` — the attachment's card id.
- `$this->AttachedToId` — the character id it's equipped to (0 if not equipped).
- `$this->ControllerId` — the player who controls the equipped character.
- `$this->Location` — the city location of the equipped character (mirrored from the character on equip).
- `$this->Engaged` — engagement is a property of the attachment itself; many Reactions cost "Engage this card."

Helpers from `Attachment`:
- `$this->isAttached(): bool` — true when `AttachedToId > 0`. Always gate "equipped character" effects on this.
- `$this->attachedTo(Theah $theah): ?Card` — returns the character, or null when not equipped.
- `$this->canAttachTo(Character $c): bool` — override if the card text restricts targets ("equip to a Sorcerer", etc.). Default `true`.
- `$this->getRequiredAttachTargetId(Theah, int $originalTargetId): int` — override if the equip target must be redirected. **Pre-commit hook requires that any call site of `EventFactory::createAttachmentEquippedEvent` also references `getRequiredAttachTargetId`** — usually handled by the calling Action, not this card.


## Pick the Right Ability Shape

Read each clause of the card text and classify it before writing any code:

| Card phrase | Pattern |
|---|---|
| **Stat modifier** (`+1 [Combat]`, `+2 [Influence]`) | Set `CombatModifier` / `FinesseModifier` / etc. in the constructor. No further code. |
| **Passive grant on the equipped character** ("Gains Duelist", "Cannot be wounded by Risks") | Override `handleEvent` and react to `EventAttachmentEquipped` (add) + `EventAttachmentUnequipped` (remove). See `_01198` (Guild Triskelion) and `tac/_02047` (Temnota) for the canonical `addTrait` / `removeTrait` pair. |
| **`<b>Forced:</b>`** — auto-triggers, no choice | Override `handleEvent` directly. No Action/Reaction class needed. See `_03cd05` Forced wound on equip. |
| **`<b>Forced:</b> the first time an opponent's Risk targets the equipped character each Day** | Pattern G below — cancel five event types, set a once-per-Day condition, clear at `EventDuskEndOfDay`. See `_03cd21` (Silver Spine), modeled on `_01186` (Maryam). |
| **`<b>Action:</b>` or `<b>City Action:</b>`** — player spends an action | Pattern C — `IHasActions` + `ActionTrait` + `cards/<expansion>/actions/Action_NNNNN.php` extending `AttachmentAction`. Multiple printed Actions → multiple Action classes. City → `cardInCity` gate. Engage-this-card + adjacent move: `_04cd01` / `_03055`. **"Sink this card"** → City Deck bottom (not discard). Play Risk from opponent discard → RiskClone (`_04cd01b` / `_01106`). |
| **`<b>Reaction:</b>` or `<b>City Reaction:</b>`** — opt-in response to an event | Implement `IHasReactions`, `use ReactionTrait`, create `cards/<expansion>/reactions/Reaction_NNNNN.php` extending `AttachmentReaction`. |
| **Steady-state property of the play area** ("reveal an additional gamble card", "this character has +1 wounds capacity") | Override the matching `Card::get*` method (e.g. `getNumberOfGambleCardsToReveal`) — *not* via event mutation. See "Steady-state overrides" below. |

A single card commonly combines several — `_03cd05` mixes a Forced (handleEvent), a steady-state override, and a custom-state prompt all in one class.


## Finish (short)

1. Walk each printed Text clause to exactly one pattern (see shape table).
2. Match constructor fields / Traits / CardNumber to the printed card.
3. Put abilities in the correct subdirectory files; wire states + JS when needed - see companions.
4. Satisfy pre-commit literals; run `php -l` on touched PHP.
5. CityAttachment: WealthCost, CityCardNumber. (Riposte pre-commit applies only to FactionAttachment.)

**Deep checklist:** [checklist.md](checklist.md)
