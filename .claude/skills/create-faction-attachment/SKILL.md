---
name: create-faction-attachment
description: Implement or finish a Faction Attachment (modules/php/cards/<expansion>/_NNNNN.php where the class extends FactionAttachment). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Faction Attachment, or when they reference a faction-deck attachment whose class extends FactionAttachment and has unimplemented Text. Triggers on phrases like "implement this faction attachment", "finish _NNNNN" (when it extends FactionAttachment), "wire up the equip restriction", "add the City Reaction to this weapon", "the equipped Strega does X", or natural-language descriptions of a faction-deck attachment (Weapon / Attire / Hat / Tabbard / Talisman that lives in a player's faction deck and equips onto one of their characters).
---

# Creating a Faction Attachment

Faction attachments are *player-deck cards* that equip onto one of the controller's own characters from hand (paying `WealthCost`), modifying that character's stats and granting Forced abilities, Actions, Reactions, Techniques, or Maneuvers. They live in the player's faction deck and discard pile — unlike `CityAttachment`s, they are *not* drawn from the city deck and they are not "neutral" — they belong to a faction.


## How to use this skill (progressive disclosure)

1. Confirm `extends FactionAttachment`. Classify each printed Text clause with the **Pick the Right Ability Shape** table below.
2. **Read only the companion files that match** - do not load every pattern file.
3. Mirror a code exemplar from [references.md](references.md) rather than inventing.
4. Before finishing, run the deep checklist in [checklist.md](checklist.md).

### Companion files

| File | Read when |
|---|---|
| [pattern-a.md](pattern-a.md) | Equip restriction |
| [pattern-b.md](pattern-b.md) | Passive trait grant |
| [pattern-c.md](pattern-c.md) | AttachmentAction |
| [pattern-d.md](pattern-d.md) | AttachmentReaction |
| [pattern-e.md](pattern-e.md) | Technique / Maneuver |
| [compose.md](compose.md) | multiple shapes on one attachment |
| [helpers.md](helpers.md) | cross-cutting helpers |
| [wiring.md](wiring.md) | pre-commit + style |
| [references.md](references.md) | exemplars |
| [checklist.md](checklist.md) | full finish checklist |

When in doubt, mirror a reference rather than invent.

> **Sibling skills:** `create-city-attachment` (city-deck attachments), `create-character` (the Strega/Mercenary/etc. that the attachment equips to), `create-scheme` (overlapping multi-stage Reaction patterns). A lot of runtime semantics overlap — read the sibling skill alongside this one when the attachment has a Reaction or Action shape that closely matches a non-attachment card.

## Base Anatomy

Faction attachments live under `modules/php/cards/<expansion>/` (e.g. `faf/`, `_7s5s/`, `tac/`) and extend `FactionAttachment`. `FactionAttachment` extends `Attachment`, implements `IFactionCard` + `IWealthCost`, and mixes in `FactionCardTrait` and `WealthCostTrait`.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;

class _0300N extends FactionAttachment
{
    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('...');
        $this->Image           = '0300N.jpg';
        $this->ExpansionName   = 'faf';     // or _7s5s / tac
        $this->ExpansionNumber = 3;
        $this->CardNumber      = N;

        $this->initializeFaction('Vodacce');  // mandatory — faction attachments belong to a faction deck

        $this->WealthCost = 1;                // mandatory — the equip cost

        // Stat buffs applied to the equipped character while equipped. Default 0.
        $this->ResolveModifier   = 0;
        $this->CombatModifier    = 1;
        $this->FinesseModifier   = 0;
        $this->InfluenceModifier = 0;

        // Combat-card stats (when used in a duel as a weapon/parry tool)
        $this->Riposte      = 0;
        $this->Parry        = 0;
        $this->Thrust       = 2;
        // Optional "dashed" markers — the stat exists only conditionally
        // $this->DashedParry  = true;
        // $this->DashedThrust = true;

        $this->Traits = [
            clienttranslate('Weapon'),
            clienttranslate('Melee'),
            clienttranslate('Unique'),
        ];

        $this->Text = clienttranslate("<p>...</p>");

        $this->resetCard();
    }
}
```

Field notes:
- **`initializeFaction(<Faction>)` is mandatory** — faction attachments belong to a player's faction deck (Castille / Eisen / Montaigne / Ussura / Vodacce / Vesten / Vendel / etc.). Without it, the card has no home deck.
- **`CardNumber` matches the `NNNNN` in the filename.** Unlike `CityAttachment` (where `CardNumber = 0` and `CityCardNumber` carries the visible number), faction attachments use `CardNumber` directly.
- **`WealthCost` is the equip cost** — the controller pays this from their hand of wealth-spending cards when equipping.
- **`ResolveModifier` / `CombatModifier` / `FinesseModifier` / `InfluenceModifier`** apply to the equipped character's printed stat while attached. These are *additive*, summed across all equipped attachments.
- **`Riposte` / `Parry` / `Thrust`** are combat-card values — used when this attachment is played as a combat card during duels (only weapons typically have non-zero Thrust). `DashedParry` / `DashedThrust` flags mark stats that exist only conditionally.
- **`Traits`** must exist in `TraitNames::$TraitsJson` (`modules/php/TraitNames.php`). Add missing ones in alphabetical order. (Memory feedback.) Common traits: `Weapon`, `Melee`, `Ranged`, `Attire`, `Hat`, `Tabbard`, `Talisman`, `Boon`, `Unique`, plus the faction-themed traits (`Dar Matushki`, `Oathsworn`, `Red Hand`, ...).
- **`CanEquipToOpponents = false`** by default on `FactionAttachment`. Set `true` only when the card text explicitly equips to an opposing character (rare — most curses live as `CityAttachment`).
- **`OffHand = true`** when printed text says **Offhand** — does not count against the one-Armor / one-Weapon limit; still limited to one Offhand per character (enforced in `UtilitiesTrait::characterHasAttachmentOfType`). See `_03044`, `_01047`, `_02056`.

Key state-on-the-instance (inherited from `Attachment`):
- `$this->Id` — the attachment's card id.
- `$this->AttachedToId` — the character id it's equipped to (0 if not equipped).
- `$this->ControllerId` — the player who controls the equipped character.
- `$this->Location` — the location of the equipped character (mirrored from the character on equip).
- `$this->Engaged` — engagement is a property of the attachment itself; many Reactions and Actions cost "Engage this card."
- `$this->OffHand` — Offhand attachments bypass Armor/Weapon uniqueness (but not Offhand uniqueness).

Helpers from `Attachment`:
- `$this->isAttached(): bool` — true when `AttachedToId > 0`. Always gate "equipped character" effects on this.
- `$this->attachedTo(Theah $theah): ?Card` — returns the character, or null when not equipped.
- `$this->canAttachTo(Character $c): bool` — override if the card text restricts targets ("equip to your Strega"). Default `true`.


## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code. The first paragraph of an attachment's text is usually an **equip restriction** ("May only equip to your X") or a **passive grant** ("...and they gain Trait"). Subsequent paragraphs are typed keywords (Forced / Action / Reaction / Technique / Maneuver).

| Card phrase | Pattern |
|---|---|
| **Stat modifier** (`+1 [Combat]`, `+2 [Influence]`) | Set `CombatModifier` / `FinesseModifier` / etc. in the constructor. No further code. |
| **"May only equip to your X"** | Pattern A — equip restriction. Implement BOTH `eventCheck(EventAttachmentEquipping)` (throws UserException on mismatch) AND `canAttachTo(Character)` (returns false on mismatch). |
| **"...and they gain Trait"** | Pattern B — passive trait grant. Pair `EventAttachmentEquipped` (addTrait) with `EventAttachmentUnequipped` (removeTrait). |
| **"(If they lose their Weapon, destroy this card.)"** | Pattern B' — conditional auto-destroy. Watch `EventAttachmentUnequipped` on the *other* attachment and queue unequip + discard for self. See `_01050`. |
| **`<b>Forced:</b>`** — auto-triggers, no choice | Override `handleEvent` directly. No Action/Reaction class. See `_01075`'s passive grant (technically a Forced grant on equip). |
| **`<b>Action:</b>` or `<b>City Action:</b>`** | Pattern C — `AttachmentAction` (no `AttachmentCityAction` base). City → `cardInCity` gate. Engage-this-card / choose-location move: see `_03055`. |
| **`<b>Reaction:</b>` or `<b>City Reaction:</b>`** | Pattern D — `AttachmentReaction`. Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_NNNNN.php`. |
| **"Cancel … Maneuver/Technique unless they discard"** / **"would resolve a Maneuver or Technique"** | Pattern D + **cancel-unless-discard** — listen `EventTechniqueActivated` / `EventManeuverActivated` (not `EventResolve*`), cancel-first on Engage, adversary discard restores. See `_03044`. |
| **`<b>Offhand</b>`** | Set `$this->OffHand = true` in the constructor. No further ability class. |
| **`<b>Technique:</b>`** | Pattern E — `Technique`. Implement `IHasTechniques`, `use TechniqueTrait`, create `techniques/Technique_NNNNN.php`. Used during duels — `Thrust`/`Parry` modifiers are common. |
| **`<b>Gambling Technique:</b>`** / **`<b>Gambling Maneuver:</b>`** | Pattern E + **Gambling gate** — NOT a trait gate. Actor must have gambled for their combat card this round (`Game::DUEL_GAMBLED`). See Pattern E subsection below and `_03043` / `Technique_03002` / `Maneuver_03008`. |
| **`<b>Maneuver:</b>`** | Pattern E' — `Maneuver`. Implement `IHasManeuvers`, `use ManeuverTrait`, create `maneuvers/Maneuver_NNNNN.php`. |
| **`<b>Sorcerer Action:</b>` / `<b>Sorcerer Reaction:</b>` / `<b>Sorcerer City Reaction:</b>`** | The Action / Reaction class additionally `implements ISorcererAbility` and emits `createSorcererAbilityStartEvent()` + `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces both literal calls). Often combined with a trait gate ("Strega" performer restriction). |
| **`<b>Strega Reaction:</b>`** / **`<b>Diplomat Action:</b>`** / **`<b>Musketeer …:</b>`** / etc. | Trait-prefixed keywords are **performer-trait gates**, NOT Sorcerer abilities. The chosen performer (= attached character) must have that trait. Enforce via `$performer->hasTrait("Strega")`. Do NOT `implement ISorcererAbility` unless the literal word "Sorcerer" is also there. (Memory feedback.) |

A single attachment commonly combines several — `_01075` mixes an equip restriction, a passive trait grant, AND a City Action. `_03007` mixes an equip restriction with a Sorcerer City Reaction.


## Finish (short)

1. Walk each printed Text clause to exactly one pattern (see shape table).
2. Match constructor fields / Traits / CardNumber to the printed card.
3. Put abilities in the correct subdirectory files; wire states + JS when needed - see companions.
4. Satisfy pre-commit literals; run `php -l` on touched PHP.
5. FactionAttachment: initializeFaction, WealthCost, always set Riposte (pre-commit).

**Deep checklist:** [checklist.md](checklist.md)
