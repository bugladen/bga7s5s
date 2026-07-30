---
name: create-faction-attachment
description: Implement or finish a Faction Attachment (modules/php/cards/<expansion>/_NNNNN.php where the class extends FactionAttachment). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Faction Attachment, or when they reference a faction-deck attachment whose class extends FactionAttachment and has unimplemented Text. Triggers on phrases like "implement this faction attachment", "finish _NNNNN" (when it extends FactionAttachment), "wire up the equip restriction", "equip to an opposing character", "add the City Reaction to this weapon", "the equipped Strega does X", "opponents cannot move Home", "equipped character cannot move", "destroy this attachment at the end of High Drama", "sink this card", "use a condition for the restriction", "during a duel while their adversary is wounded", "reveals an additional card when they gamble", "gains +1 Finesse during a duel", or natural-language descriptions of a faction-deck attachment (Weapon / Attire / Hat / Mantel / Tabbard / Talisman / Trinket / Compass / Chains that lives in a player's faction deck and equips onto one of their characters).
---

# Creating a Faction Attachment

Faction attachments are *player-deck cards* that equip onto one of the controller's own characters from hand (paying `WealthCost`), modifying that character's stats and granting Forced abilities, Actions, Reactions, Techniques, or Maneuvers. They live in the player's faction deck and discard pile — unlike `CityAttachment`s, they are *not* drawn from the city deck. Most belong to a named faction; **Neutral** faction attachments also exist (`initializeFaction('Neutral')` — e.g. Harpoon `_03064`, Lodestone `_03065`, Shackles `_03066`, Main Gauche `_02056`) and still use `FactionAttachment` + `CardNumber` (not city-deck numbering). A few (`CanEquipToOpponents`) equip onto **opposing** characters instead — see Pattern A.


## How to use this skill (progressive disclosure)

1. Confirm `extends FactionAttachment`. Classify each printed Text clause with the **Pick the Right Ability Shape** table below.
2. **Read only the companion files that match** - do not load every pattern file.
3. Mirror a code exemplar from [references.md](references.md) rather than inventing.
4. Before finishing, run the deep checklist in [checklist.md](checklist.md).

### Companion files

| File | Read when |
|---|---|
| [pattern-a.md](pattern-a.md) | Equip restriction |
| [pattern-b.md](pattern-b.md) | Passive trait grant / while-equipped condition / duel-scoped conditional stat or gamble reveal |
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
- **`initializeFaction(<Faction>)` is mandatory** — Castille / Eisen / Montaigne / Ussura / Vodacce / Vesten / Vendel / **Neutral** / etc. Without it, the card has no home deck. Neutral still lives in a player's faction deck as a Neutral card.
- **`CardNumber` matches the `NNNNN` in the filename.** Unlike `CityAttachment` (where `CardNumber = 0` and `CityCardNumber` carries the visible number), faction attachments use `CardNumber` directly.
- **`WealthCost` is the equip cost** — the controller pays this from their hand of wealth-spending cards when equipping.
- **`ResolveModifier` / `CombatModifier` / `FinesseModifier` / `InfluenceModifier`** apply to the equipped character's printed stat while attached. These are *additive*, summed across all equipped attachments.
- **`Riposte` / `Parry` / `Thrust`** are combat-card values — used when this attachment is played as a combat card during duels (only weapons typically have non-zero Thrust). `DashedParry` / `DashedThrust` flags mark stats that exist only conditionally.
- **`Traits`** must exist in `TraitNames::$TraitsJson` (`modules/php/TraitNames.php`). Add missing ones in alphabetical order. (Memory feedback.) Common traits: `Weapon`, `Melee`, `Ranged`, `Attire`, `Hat`, `Mantel`, `Tabbard`, `Talisman`, `Boon`, `Stealth`, `Unique`, plus the faction-themed traits (`Dar Matushki`, `Oathsworn`, `Red Hand`, ...).
- **`CanEquipToOpponents = false`** by default on `FactionAttachment`. Set `true` only when the card text explicitly equips to an opposing character (rare — `_01021` Legion's Caress, `_03066` Shackles; most curses live as `CityAttachment`). See Pattern A — opponent-equip collapses `CHOSEN_PERFORMER` to the **target**.
- **`OffHand = true`** when printed text says **Offhand** — does not count against the one-Armor / one-Weapon limit; still limited to one Offhand per character (enforced in `UtilitiesTrait::characterHasAttachmentOfType`). See `_03044`, `_01047`, `_02056`.

Key state-on-the-instance (inherited from `Attachment`):
- `$this->Id` — the attachment's card id.
- `$this->AttachedToId` — the character id it's equipped to (0 if not equipped).
- `$this->ControllerId` — after equip, **the equipping player** (`EventHub` sets `$attachment->ControllerId = $event->playerId`). This is **not** always the equipped character's controller — for `CanEquipToOpponents` cards the attachment stays under the equipper while attached to an opponent. Use `$attachedCharacter->ControllerId` when you need the victim's player.
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
| **Stat modifier** (`+1 [Combat]`, `+2 [Influence]`) — always while equipped | Set `CombatModifier` / `FinesseModifier` / etc. in the constructor. No further code. |
| **"May only equip to your X"** / **"… your X, Y, or Z"** | Pattern A — equip restriction. Implement BOTH `eventCheck(EventAttachmentEquipping)` (throws UserException on mismatch) AND `canAttachTo(Character)` (returns false on mismatch). Multi-trait OR: any listed trait passes (`_04006`). |
| **"May only equip to an opposing character"** / **`CanEquipToOpponents`** | Pattern A + set `$this->CanEquipToOpponents = true`. HD Equip sets `CHOSEN_PERFORMER` to the **target** (including opponents). **"Opposing"** = different controller **and** same location. See Pattern A / `_01021` / `_03066`. |
| **"... less [Stat] than your performer"** (with opponent-equip) | Pattern A — BGA has no separate performer picker. Resolve as: you control a character at the target's location with greater `Modified*` than the target. See Pattern A / `_03066`. |
| **"...and they gain Trait"** | Pattern B — passive trait grant. Pair `EventAttachmentEquipped` (addTrait) with `EventAttachmentUnequipped` (removeTrait). |
| **"(If they lose their Weapon, destroy this card.)"** | Pattern B' — conditional auto-destroy. Watch `EventAttachmentUnequipped` on the *other* attachment and queue unequip + discard for self. See `_01050`. |
| **"Opponent's abilities cannot …"** / **"Equipped character cannot move"** / while-equipped lasting restriction (not duel-scoped) | Pattern B'' — **while-equipped condition**. Stamp `Game::*_CONDITION` on equip, clear on unequip; enforce in `Character::eventCheck` (not Attachment-only). Scope the gate to printed text (Home-only + opponent-ability vs all moves). See Pattern B / `_03065` (Lodestone) / `_03066` (Shackles). |
| **"During a duel, while …"** / **"gains +N [Stat]"** (conditional) / **"reveals an additional card when they gamble"** | Pattern B''' — **duel-scoped conditional**. Do **not** use constructor `*Modifier`. Stat: Benci-style applied flag + `createCharacter*ModifiedEvent`; recompute on DuelStarted / Wounded / Healed / Swapped / Equipped; clear on DuelEnd / Unequipped. Gamble +1: override `getNumberOfGambleCardsToReveal` (Gallegos `_01101`). No Action/Reaction class unless a keyword is also printed. See Pattern B / `_04006`. |
| **`<b>Forced:</b>`** — auto-triggers, no choice | Override `handleEvent` directly. No Action/Reaction class. See `_01021` (wound on en garde), `_03066` (destroy at end of High Drama via `EventHighDramaPhaseEnd`). |
| **`<b>Forced:</b> At the end of High Drama • Destroy this attachment`** | `EventHighDramaPhaseEnd` + `isAttached()` → notify → unequip → `createCardDiscardedFromPlayEvent(..., asEffect=true)`. Trigger mirror `_01025_Burden`; destroy chain mirror `_01153` / `_01050`. Unequip clears any while-equipped condition. |
| **`<b>Action:</b>` or `<b>City Action:</b>`** | Pattern C — `AttachmentAction` (no `AttachmentCityAction` base). City → `cardInCity` gate. Immediate resolve (no picker): `_03065`. Engage-this-card / choose-location move: `_03055`. **"Sink this card"** cost: unequip → removeFromPlay → faction-deck bottom (`Technique_02055` / `Action_03065`). |
| **`<b>Reaction:</b>` or `<b>City Reaction:</b>`** | Pattern D — `AttachmentReaction`. Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_NNNNN.php`. |
| **"Cancel … Maneuver/Technique unless they discard"** / **"would resolve a Maneuver or Technique"** | Pattern D + **cancel-unless-discard** — listen `EventTechniqueActivated` / `EventManeuverActivated` (not `EventResolve*`), cancel-first on Engage, adversary discard restores. See `_03044`. |
| **`<b>Offhand</b>`** | Set `$this->OffHand = true` in the constructor. No further ability class. |
| **`<b>Technique:</b>`** | Pattern E — `Technique`. Implement `IHasTechniques`, `use TechniqueTrait`, create `techniques/Technique_NNNNN.php`. Used during duels — `Thrust`/`Parry` modifiers are common. |
| **`<b>Gambling Technique:</b>`** / **`<b>Gambling Maneuver:</b>`** | Pattern E + **Gambling gate** — NOT a trait gate. Actor must have gambled for their combat card this round (`Game::DUEL_GAMBLED`). See Pattern E and `_03043` / `Technique_03002` / `Maneuver_03008` / `_03064`. |
| **"Engage this card • … for the remainder of the duel"** / **"-1 [Stat], cannot be swapped, cannot move"** | Pattern E + **remainder-of-duel condition** — stamp a `Game::*_CONDITION` on the affected character; enforce move/swap via condition checks; clear on `EventDuelEnd` / `*Canceled`. See Pattern E subsection and `_03064` (Harpoon). |
| **`<b>Maneuver:</b>`** | Pattern E' — `Maneuver`. Implement `IHasManeuvers`, `use ManeuverTrait`, create `maneuvers/Maneuver_NNNNN.php`. |
| **`<b>Sorcerer Action:</b>` / `<b>Sorcerer Reaction:</b>` / `<b>Sorcerer City Reaction:</b>`** | The Action / Reaction class additionally `implements ISorcererAbility` and emits `createSorcererAbilityStartEvent()` + `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces both literal calls). Often combined with a trait gate ("Strega" performer restriction). |
| **`<b>Strega Reaction:</b>`** / **`<b>Diplomat Action:</b>`** / **`<b>Musketeer …:</b>`** / etc. | Trait-prefixed keywords are **performer-trait gates**, NOT Sorcerer abilities. The chosen performer (= attached character) must have that trait. Enforce via `$performer->hasTrait("Strega")`. Do NOT `implement ISorcererAbility` unless the literal word "Sorcerer" is also there. (Memory feedback.) |

A single attachment commonly combines several — `_01075` mixes an equip restriction, a passive trait grant, AND a City Action. `_03007` mixes an equip restriction with a Sorcerer City Reaction. `_03043` mixes a passive trait grant with a Gambling Technique. `_03064` is a single Gambling Technique that stamps a remainder-of-duel condition (no equip restriction). `_03065` mixes a while-equipped condition restriction (B'') with an immediate-resolve sink + move-Home City Action (C). `_03066` mixes opponent-equip + performer-Finesse gate (A), while-equipped cannot-move (B''), and Forced end-of-HD destroy. `_04006` mixes multi-trait equip (A) with duel-scoped wounded-adversary +1 Finesse **and** +1 gamble reveal (B''') — no keyword ability class.


## Finish (short)

1. Walk each printed Text clause to exactly one pattern (see shape table).
2. Match constructor fields / Traits / CardNumber to the printed card.
3. Put abilities in the correct subdirectory files; wire states + JS when needed - see companions.
4. Satisfy pre-commit literals; run `php -l` on touched PHP.
5. FactionAttachment: initializeFaction, WealthCost, always set Riposte (pre-commit).

**Deep checklist:** [checklist.md](checklist.md)
