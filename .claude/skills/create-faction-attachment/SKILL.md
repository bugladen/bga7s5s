---
name: create-faction-attachment
description: Implement or finish a Faction Attachment (modules/php/cards/<expansion>/_NNNNN.php where the class extends FactionAttachment). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Faction Attachment, or when they reference a faction-deck attachment whose class extends FactionAttachment and has unimplemented Text. Triggers on phrases like "implement this faction attachment", "finish _NNNNN" (when it extends FactionAttachment), "wire up the equip restriction", "add the City Reaction to this weapon", "the equipped Strega does X", or natural-language descriptions of a faction-deck attachment (Weapon / Attire / Hat / Tabbard / Talisman that lives in a player's faction deck and equips onto one of their characters).
---

# Creating a Faction Attachment

Faction attachments are *player-deck cards* that equip onto one of the controller's own characters from hand (paying `WealthCost`), modifying that character's stats and granting Forced abilities, Actions, Reactions, Techniques, or Maneuvers. They live in the player's faction deck and discard pile — unlike `CityAttachment`s, they are *not* drawn from the city deck and they are not "neutral" — they belong to a faction.

The canonical references depend on which clauses your card has. Read at least one of these before writing code:

- `modules/php/cards/_7s5s/_01075.php` (Tabard of the Fallen Musketeer) — **Equip restriction (non-Diplomat) + passive Musketeer-trait grant + City Action.** Pairs `eventCheck` with `canAttachTo` and uses `EventAttachmentEquipped` / `EventAttachmentUnequipped` to add/remove a trait.
- `modules/php/cards/_7s5s/_01073.php` (Cavalier Hat) — **Equip restriction (Duelist-only) + City Action.** Simpler dual-gate template.
- `modules/php/cards/_7s5s/_01050.php` (Unsavory Salve) — **Equip restriction (must have Weapon) + auto-destroy when the prerequisite is lost** via `EventAttachmentUnequipped` watching the *other* attachment. Plus a Technique.
- `modules/php/cards/faf/_03007.php` + `reactions/Reaction_03007.php` (Matushka's Shears) — **Strega-only equip + Sorcerer City Reaction with engage cost.** Multi-stage button-driven Reaction (offer → choose → pick1/pick2) with cross-player transitions, sinking cards from opponent's hand, ISorcererAbility plumbing.
- `modules/php/cards/_7s5s/_01022.php` + `reactions/Reaction_01022.php` — **AttachmentReaction with engage cost.** Simpler single-decision shape (Wound Challenger / Wound Challenged / Pass).
- `modules/php/cards/_7s5s/_01181.php` + `reactions/Reaction_01181.php` (Sorte Deck) — `AttachmentReaction` with cancel + `releaseEvent` + `skipNextEvent` interpose pattern.
- `modules/php/cards/faf/_03019.php` + `reactions/Reaction_03019.php` (Kaiser Schnurrbart) — **Passive trait grant (Hunter) + City Reaction on `EventCardMoved`.** Cleanest "After an opposing character moves to an adjacent City location" template: gate on `fromLocation == owningCharacter->Location` (the "opposing" interpretation), queue a three-event chain (engage attachment / move equipped character with `engage=false` / engage triggering character) on accept.
- `modules/php/cards/faf/_03043.php` + `techniques/Technique_03043.php` (El Gato's Mask) — **Passive Scoundrel grant + Gambling Technique** that reveals random hand cards (multiplayer `chooseList` acknowledge) then discards one revealed attachment. Attachment-hosted technique transition `sourceId`, `DUEL_GAMBLED` gate, post-ack game-state branch via `stateFromTechnique`.
- `modules/php/cards/faf/_03044.php` + `reactions/Reaction_03044.php` (Torres Cloak) — **Offhand + Duelist-only equip + cancel Maneuver/Technique unless discard.** Multi-stage offer → threat, cancel-first on Engage, `HIGH_PRIORITY` reaction transitions, deferred `setUsed`. See Pattern D "Cancel unless discard".

When in doubt, mirror one of those rather than invent.

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
| **`<b>Action:</b>` or `<b>City Action:</b>`** | Pattern C — `AttachmentAction`. Implement `IHasActions`, `use ActionTrait`, create `actions/Action_NNNNN.php`. |
| **`<b>Reaction:</b>` or `<b>City Reaction:</b>`** | Pattern D — `AttachmentReaction`. Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_NNNNN.php`. |
| **"Cancel … Maneuver/Technique unless they discard"** / **"would resolve a Maneuver or Technique"** | Pattern D + **cancel-unless-discard** — listen `EventTechniqueActivated` / `EventManeuverActivated` (not `EventResolve*`), cancel-first on Engage, adversary discard restores. See `_03044`. |
| **`<b>Offhand</b>`** | Set `$this->OffHand = true` in the constructor. No further ability class. |
| **`<b>Technique:</b>`** | Pattern E — `Technique`. Implement `IHasTechniques`, `use TechniqueTrait`, create `techniques/Technique_NNNNN.php`. Used during duels — `Thrust`/`Parry` modifiers are common. |
| **`<b>Gambling Technique:</b>`** / **`<b>Gambling Maneuver:</b>`** | Pattern E + **Gambling gate** — NOT a trait gate. Actor must have gambled for their combat card this round (`Game::DUEL_GAMBLED`). See Pattern E subsection below and `_03043` / `Technique_03002` / `Maneuver_03008`. |
| **`<b>Maneuver:</b>`** | Pattern E' — `Maneuver`. Implement `IHasManeuvers`, `use ManeuverTrait`, create `maneuvers/Maneuver_NNNNN.php`. |
| **`<b>Sorcerer Action:</b>` / `<b>Sorcerer Reaction:</b>` / `<b>Sorcerer City Reaction:</b>`** | The Action / Reaction class additionally `implements ISorcererAbility` and emits `createSorcererAbilityStartEvent()` + `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces both literal calls). Often combined with a trait gate ("Strega" performer restriction). |
| **`<b>Strega Reaction:</b>`** / **`<b>Diplomat Action:</b>`** / **`<b>Musketeer …:</b>`** / etc. | Trait-prefixed keywords are **performer-trait gates**, NOT Sorcerer abilities. The chosen performer (= attached character) must have that trait. Enforce via `$performer->hasTrait("Strega")`. Do NOT `implement ISorcererAbility` unless the literal word "Sorcerer" is also there. (Memory feedback.) |

A single attachment commonly combines several — `_01075` mixes an equip restriction, a passive trait grant, AND a City Action. `_03007` mixes an equip restriction with a Sorcerer City Reaction.

## Pattern A — Equip Restriction ("May only equip to your X")

The dual-gate pattern: `eventCheck` enforces at the framework level when equip is actually attempted; `canAttachTo` gates the UI/discoverability before the player tries. **Implement both.**

```php
public function eventCheck(Event $event)
{
    parent::eventCheck($event);

    if ($event instanceof EventAttachmentEquipping && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        if (! $character->hasTrait("Strega"))
        {
            throw new UserException($event->theah->game->translate("Matushka's Shears can only be equipped to a Strega."));
        }
    }
}

public function canAttachTo(Character $character): bool
{
    if (! parent::canAttachTo($character))
    {
        return false;
    }

    return $character->hasTrait("Strega");
}
```

References: `_01073` (Duelist), `_01075` (non-Diplomat — note the inversion), `_03007` (Strega).

**Use `\Bga\GameFramework\UserException`, not `\BgaUserException`.** Older code uses `BgaUserException`; new code should use the framework path. (Memory feedback.) Older files (`_01050`, `_01073`, `_01075`, `_02006`, …) still throw `\BgaUserException` for historical reasons — don't follow that example, follow memory.

### Compound restriction ("must have a Weapon")

When the restriction is "the target already has another attachment," check via a character helper rather than re-iterating equipment in your card. `_01050` (Unsavory Salve) calls `$character->hasWeaponEquipped($event->theah)`:

```php
if (! $character->hasWeaponEquipped($event->theah))
{
    throw new UserException(/* "must have a weapon equipped" */);
}
```

Grep for the helper before writing one — `hasWeaponEquipped`, `hasOffHand`, etc. are already there.

### Auto-destroy when prerequisite is lost

Parenthetical "(If they lose their Weapon, destroy this card.)" on `_01050`: watch `EventAttachmentUnequipped` and check whether the equipped character STILL satisfies the constraint. If not, queue an unequip-self + discard-self:

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventAttachmentUnequipped && $this->isAttached())
    {
        $owner = $this->attachedTo($event->theah);
        if ($owner instanceof Character && ! $owner->hasWeaponEquipped($event->theah))
        {
            $event->theah->game->notify->all("message", clienttranslate('${attachment_inject_code} will be discarded from ${character_inject_code} because they no longer have a weapon equipped.'), [
                "attachment_inject_code" => $this->getInjectCode(),
                "character_inject_code" => $owner->getInjectCode(),
            ]);

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($this->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($unequipEvent);

            $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($this->ControllerId, $this->Id, $this->Location, $this->Id, $asEffect = true);
            $event->theah->queueEvent($discardEvent);
        }
    }
}
```

Reference: `_01050` (Unsavory Salve).

## Pattern B — Passive Trait Grant via Equip/Unequip Pair

For "the equipped character gains Trait," you need *both* halves of the pair. Forgetting `EventAttachmentUnequipped` leaks the trait when the attachment moves off (gets unequipped, attachment destroyed, character destroyed taking the attachment with them).

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventAttachmentEquipped && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        $character->addTrait($event->theah->game, "Musketeer");
    }

    if ($event instanceof EventAttachmentUnequipped && $event->attachmentId == $this->Id)
    {
        $character = $event->theah->getCharacterById($event->characterId);
        $character->removeTrait($event->theah->game, "Musketeer");
    }
}
```

References: `_01075` (Tabard → Musketeer), `_01198` (Guild Triskelion → Duelist), `tac/_02047` (Temnota → Sorcerer; technically a CityAttachment but same pattern).

## Pattern C — AttachmentAction

The character the attachment is equipped to performs the action. `AttachmentAction::getPerformersForAction` defaults to `[$this->getOwningCharacter($theah)]`, and `isAvailableToPlayer` already gates on the owning character being non-null.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;

class Action_0300N extends AttachmentAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("...");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $owner      = $this->getOwningCharacter($event->theah);

            // ... apply effect ...

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
```

### "Engage the equipped performer"

Common cost on attachment Actions. Queue `createCardEngagedEvent` against the *character*:

```php
$engageEvent = EventFactory::createCardEngagedEvent(
    $owner->ControllerId, $owner->Id, $owner->Id, $this->Id
);
$event->theah->queueEvent($engageEvent);
```

If the text is "engage this card" instead (engaging the attachment, not the performer), engage `$attachment->Id` rather than `$owner->Id` — see `Reaction_01022`.

### `setUsed` / `announceAction` / `resetPlayerPassCount`

**Do NOT call any of these from `AttachmentAction` subclasses.** They run centrally in `actHighDramaInPlayActionConfirm` and `stHighDramaInPlayActionDispatch`. (Per CLAUDE.md.) The pre-commit hook does not require them on these subclasses.

### Pre-commit hook on actions

`Action_NNNNN extends AttachmentAction → CardAction` — the hook requires `createActionResolvedEvent()` somewhere in the class. Make sure it's queued at the end of effect resolution (after any state loops complete).

Reference: `_01073`'s `Action_01073`, `_01075`'s `Action_01075`.

## Pattern D — AttachmentReaction

Extend `AttachmentReaction` (which extends `CardReaction`). It adds `ownerIsAttached(Theah)` so you can early-out when the parent attachment is detached.

**Pre-commit hook requires all three** literal strings in the class body:
- `$this->setUsed(`
- `$this->isAvailable(`
- `$this->ownerIsAttached(`

Skeleton (simple single-decision):

```php
class Reaction_NNNNN extends AttachmentReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("...");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah)
            . $theah->game->translate('${you} may ...');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Do It'), 'doEffect');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! $this->isAvailable()) return;
        if (! $this->ownerIsAttached($event->theah)) return;

        $owner = $this->getOwningAttachment($event->theah);
        if ($owner == null || $owner->Engaged) return;  // if engage is the cost

        if ($event instanceof EventSomething && /* trigger condition */)
        {
            $owner->IsUpdated = true;
            $transition = EventFactory::createReactionTransitionEvent(
                $owner->ControllerId, $owner->Id, $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId === 'doEffect')
        {
            $owner = $this->getOwningAttachment($game->theah);

            // Engage cost (common on attachment reactions):
            $engageEvent = EventFactory::createCardEngagedEvent(
                $owner->ControllerId, $owner->Id, $owner->Id, $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            // ... effect ...

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}
```

`CardReaction::setUsed` resets at dusk automatically (via `EventDuskEndOfDay`).

References: `Reaction_01022` (simple wound-challenger/wound-challenged/pass), `Reaction_01040`, `Reaction_01047` (hard cancel Technique), `Reaction_01146b` (hard cancel Maneuver or Technique on a Scheme), `Reaction_01181` (cancel + re-queue pattern), `Reaction_03044` (cancel unless discard).

### Engage as a cost — gate the trigger on `! $owner->Engaged`

When the card text says "engage this card" as a cost, gate the trigger so the reaction can't fire while already engaged:

```php
$owner = $this->getOwningAttachment($event->theah);
if ($owner == null || $owner->Engaged) return;
```

Then queue `createCardEngagedEvent` in `performReaction` when the player accepts. Engagement resets at dusk along with `Used`.

**Do NOT `setUsed(true)` on the Engage click if a later stage still needs a reaction transition.** `Theah::runEvents` skips `transition == "reaction"` when `!$reaction->isAvailable()` (to prevent duplicate offers). Marking Used early drops the next stage (e.g. adversary "unless discard" choice) and pending MEDIUM resolve events proceed uncanceled. Defer `setUsed` to finalize after the last stage — see `Reaction_03007::finalize`, `Reaction_03044::finalizeAfterEngage`.

### Cancel Maneuver / Technique ("would resolve" / "announces")

**Timing:** Listen on `EventTechniqueActivated` / `EventManeuverActivated`, not `EventResolveTechnique` / `EventResolveManeuver`. "Would resolve" / "announces" means interrupt after activation while Resolve + CalculateValues are still queued. Hard-cancel references: `Reaction_01047`, `Reaction_01146b`.

**`IN_DUEL` gate:** Cancel reactions that target duel Maneuvers/Techniques must require `Game::IN_DUEL` (rules-team ruling on `01146b`).

**Equipped participant's adversary gate — do not copy `Reaction_01047`'s id compare.** `getDuelOpponentId($actorId)` returns a **character** id. `EventTechniqueActivated::$playerId` is a **player** id. Correct gates:

```php
$owningCharacter = $this->getOwningCharacter($theah);
$actor = $theah->getDuelRoundActor();
$adversaryId = $theah->getDuelOpponentId($actor->Id);
// Cloak/attachment must be on the actor's duel adversary
if ($owningCharacter->Id != $adversaryId) return false;
// Activator must be the actor's controller
return $activatingPlayerId == $actor->ControllerId;
```

`Reaction_01047` compares `ControllerId == getDuelOpponentId(...)` and `actor->Id == event->playerId` — those mix player ids with character ids. Prefer the gates above (`Reaction_03044::isAdversaryActivating`).

**`HIGH_PRIORITY` on every reaction transition in the cancel chain.** `createReactionTransitionEvent` defaults to `REACTION_PRIORITY` (6), which is *later* than MEDIUM (3) Resolve events. Override to `Event::HIGH_PRIORITY` (2) on the offer transition *and* every follow-up cross-player transition, or Resolve fires mid-decision.

**Hard cancel (single click):** `deleteTechniqueEvents` / `deleteManeuverEvents`, clear `CHOSEN_TECHNIQUE` / `CHOSEN_MANEUVER` (+ `CHOSEN_TECHNIQUE_IS_MAIN`), queue `createTechniqueCanceledEvent` / `createManeuverCanceledEvent`. Store `$TechniqueId` / `$ManeuverId` as **public** fields (like `01047` / `01146b`).

### Cancel unless discard (Pattern D + multi-stage) — Torres Cloak `_03044`

Printed: "engage this card • Cancel the effects unless they discard a card."

Reading: cancel is the primary outcome; discard is the escape hatch that *keeps* the Maneuver/Technique. Compose `01047`/`01146b` cancel mechanics + `03007` multi-stage cross-player + `02033` `discardHand-{id}` buttons.

**Use cancel-first on Engage — do not leave Resolve queued during the threat wait.**

Cancel-later (delete only on Accept Cancel) races: Resolve can still fire if `TechniqueId` is lost across the multi-stage serialize, or if any transition is skipped — observed as the cloak player entering `duelChooseTechnique_01093` after Maya’s threat choice. Cancel-first is robust:

1. **Offer** (cloak controller): Engage / Pass.
2. **Engage:** queue `createCardEngagedEvent`; **immediately** `deleteTechniqueEvents` / `deleteManeuverEvents` + clear `CHOSEN_*`. Do **not** fire `*Canceled` yet (discard may restore). Store `actorId`, `adversaryCharacterId`, `activatingPlayerId`, `techniqueWasMain` for restore. Do **not** `setUsed` yet.
3. **Empty hand:** log why, fire `*Canceled`, `finalizeAfterEngage` (`setUsed` + reset).
4. **Threat** (adversary): `discardHand-{id}` → discard as effect + **re-queue** `createResolveTechniqueEvent` / `createResolveManeuverEvent` + CalculateValues + restore `CHOSEN_*`; **or** Accept Cancel → fire `*Canceled`. Then finalize.
5. Both offer and threat transitions: `priority = Event::HIGH_PRIORITY`.

**Rules check when playtesting:** discard-to-keep *should* let the technique resolve (e.g. cloak player may still enter `duelChooseTechnique_01093`). Accept Cancel must *not*.

**Persistence:** keep `$stage`, `$TechniqueId`, `$ManeuverId`, and restore context as **public** properties on the reaction (mirror `01047`). `$owner->IsUpdated = true` after every mutation.

### "After an opposing character moves to an adjacent location" triggers

Trigger on `EventCardMoved` (fires *after* the move resolves — `EventCardMoving` fires before). The classic gates:

```php
if (! ($event instanceof EventCardMoved)) return;
if (! $this->isAvailable()) return;
if (! $this->ownerIsAttached($event->theah)) return;

$owner = $this->getOwningAttachment($event->theah);
if ($owner == null || $owner->Engaged) return;  // if engage is the cost

$owningCharacter = $this->getOwningCharacter($event->theah);
if ($owningCharacter == null || ! $event->theah->cardInCity($owningCharacter)) return;

$character = $event->theah->getCardById($event->cardId);
if (! ($character instanceof Character)) return;
if ($character->ControllerId == $owningCharacter->ControllerId) return;

// "opposing" = same location before the move. After the move they are *not*
// at our location, so we check fromLocation, not the current location.
if ($event->fromLocation != $owningCharacter->Location) return;

if (! $event->theah->locationInCity($event->toLocation)) return;

$adjacent = $event->theah->getAdjacentCityLocations($owningCharacter->Location, false);
if (! in_array($event->toLocation, $adjacent)) return;
```

**Why `fromLocation == owningCharacter->Location`:** per `feedback_opposing_definition`, "opposing" requires same-location + different-controller. Since the move has already resolved by the time `EventCardMoved` fires, "opposing" can only be evaluated against the **prior** location. `_01066` (Horatio) is the canonical character-side mirror of this pattern; `_03019` (Kaiser Schnurrbart) is the attachment-side mirror.

Without the `fromLocation` gate, the reaction would fire on any enemy that happens to end at an adjacent location — including teleports, recruits-into-location, and far-away moves — which is broader than printed text.

### `createCardMovingEvent` and the `$engage` parameter

```php
public static function createCardMovingEvent(
    int $initiatingPlayerId, int $cardId,
    string $fromLocation, string $toLocation,
    bool $engage = true,                // <-- defaults to true!
    int $sourceId = 0, string $abilityId = ""
): EventCardMoving
```

The default `$engage = true` is for **moving-as-an-action** (the move costs the character an engagement). When the move is a Reaction or Forced effect (e.g. "Move X to Y and engage that character"), pass `false` and emit the engagement separately — see `Reaction_01037`, `Reaction_01066`, `Reaction_01173`, `Reaction_03019`. This lets you control *who* gets engaged (the mover vs. the prey vs. neither) instead of conflating the move with engagement.

### Resolving ambiguous "that character" / "their" antecedents

Card text like "Move the equipped character to **their** new location and engage **that character**" is grammatically ambiguous — "their" and "that character" can each plausibly refer to either the equipped character or the trigger character.

Resolution heuristics, in priority order:
1. **Pronoun chain consistency.** Once a pronoun ("their") locks onto an antecedent, the next demonstrative ("that character") usually keeps the same referent unless the next sentence explicitly switches. So "their new location" → trigger character → "that character" → trigger character.
2. **Thematic check.** Read the card's name/traits as a tiebreaker. A "Hunter" card pinning prey (engage opposing) reads cleaner than the dog handler exhausting themselves (engage equipped) after running.
3. **Cost/effect balance.** If one reading makes the cost (engage attachment) strictly worse than a baseline move, prefer the other reading.
4. **Flag the call in the journal.** Either way, write a journal entry naming the ambiguity, the chosen interpretation, and how to flip it (one-line edit). A future audit can revisit without re-deriving the analysis.

### Sorcerer Reactions — `implements ISorcererAbility`

If the keyword is **"Sorcerer Reaction:"** or **"Sorcerer City Reaction:"**, the reaction class additionally implements `ISorcererAbility`:

```php
class Reaction_NNNNN extends AttachmentReaction implements ISorcererAbility
{
    // ...
}
```

The pre-commit hook then requires both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` somewhere in the file. Standard idiom:

- Fire **Start** when the player accepts the reaction (just before queueing effect events).
- Fire **Played** in `finalize()` after all effects + before `setUsed`.

```php
private function finalize(Game $game, Card $owner): void
{
    $performer  = $this->getOwningCharacter($game->theah);
    $performerId = $performer ? $performer->Id : 0;

    $played = EventFactory::createSorcererAbilityPlayedEvent(
        $owner->ControllerId, $owner->Id, $this->Id, $performerId
    );
    $game->theah->queueEvent($played);

    $this->setUsed($game->theah, true);
    $this->resetStage();
    $owner->IsUpdated = true;
}
```

Reference: `Reaction_03007` (Matushka's Shears — Sorcerer City Reaction on a FactionAttachment).

### "Strega Reaction" / "Mercenary Reaction" / etc. are NOT Sorcerer abilities

Trait-prefixed keywords gate the *performer* trait, NOT the ability type. They use `hasTrait("Strega")` checks on the attached character but do NOT implement `ISorcererAbility`. They can stack with "Sorcerer" ("Sorcerer Strega Reaction" is both). (Memory feedback — `feedback_strega_vs_sorcerer_keyword.md`.)

### Multi-stage button-driven reactions (no sub-state)

When the reaction needs several player clicks in sequence (offer → choose → pick), or when the player who clicks *changes* between steps, use a `$stage` field plus `$owner->IsUpdated = true` to persist. Pattern source: `Reaction_03007` (Matushka's Shears), `Reaction_03006` (Premonition — on a Scheme but same shape).

Anatomy:
- A `private string $stage` field (e.g. `''` idle, `'offer'`, `'choose'`, `'pick1'`, `'pick2'`) plus per-stage context fields (`$opponentId`, `$cardsSunk`).
- `getReactionDescription` switches on `$stage` to return the right prompt.
- `getReactionButtonProperties` switches on `$stage` to render the right buttons (Engage/Pass on offer; one `card-{id}` button per hand card on pick stages).
- `performReaction` parses click via `str_starts_with($reactionId, 'card-')`, applies the per-step effect, advances `$stage`, and queues **another** `createReactionTransitionEvent` for whichever player acts next.
- `setUsed($theah, true)` only fires when the multi-stage flow is fully resolved (in `finalize` / the last stage).

```php
private function advanceToNextPick(Game $game, Card $owner): bool
{
    $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
    if (count($hand) == 0) return false;       // pool exhausted — caller treats as "finalize early"

    $this->stage = ($this->cardsSunk == 0) ? 'pick1' : 'pick2';
    $owner->IsUpdated = true;

    $transition = EventFactory::createReactionTransitionEvent(
        $this->opponentId, $owner->Id, $this->Id
    );
    $game->theah->queueEvent($transition);
    return true;
}
```

The `framework` re-enters `playerReaction` with the updated active player + button set. `playerReaction` exists alongside every events state, so this pattern works phase-independently (Planning, High Drama, Dawn, Duels all support it).

Reference: `Reaction_03007::advanceToNextPick`, `Reaction_03006::advanceToNextPick`.

### Cross-player flow ("the opponent must do part of the resolution")

When a Reaction's effect requires the **opposing** player to make a choice (e.g. "they must sink two cards from their hand"), DO NOT route through a dedicated GameState sub-state — Reactions can fire from any phase, and a sub-state mapped under one phase's `*_EVENTS` transitions table only works in that one phase.

Instead, queue `createReactionTransitionEvent($opponentId, $owner->Id, $this->Id)` with the opponent's playerId. The framework makes them the active player in `playerReaction`. Reference: `Reaction_03007`, `Reaction_03006`.

### Sinking cards from a player's hand

Same recipe as `Reaction_03006`/`Reaction_03007`'s `sinkOneFromHand`:

```php
private function sinkOneFromHand(Game $game, Card $owner, int $cardId): void
{
    $hand    = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->opponentId);
    $handIds = array_map(fn($c) => $c->Id, $hand);

    if (! in_array($cardId, $handIds))
    {
        throw new \Bga\GameFramework\UserException($game->translate("Selected card is not in your hand."));
    }

    $card     = $game->getCardObjectFromDb($cardId);
    $deck     = $game->getGameDeckObject();
    $deckName = $game->getPlayerFactionDeckName($this->opponentId);

    $deck->insertCardOnExtremePosition($cardId, $deckName, false);

    $game->notify->player($this->opponentId, "cardRemovedFromHand", clienttranslate('Private: ${reaction_inject_code}: you sink ${card_inject_code} from your hand.'), [
        "reaction_inject_code" => $owner->getInjectCode(),
        "card_inject_code"     => $card->getInjectCode(),
        "playerId"             => $this->opponentId,
        "cardId"               => $cardId,
        "handCount"            => count($deck->getPlayerHand($this->opponentId)),
    ]);

    $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} sinks a card from their hand.'), [
        "reaction_inject_code" => $owner->getInjectCode(),
        "player_name"          => $game->getPlayerNameById($this->opponentId),
    ]);
}
```

"Sink" = back to the faction deck (`insertCardOnExtremePosition($cardId, $deckName, false)`); "discard" = to the discard pile. They're different operations — match the literal printed word.

### "If able" loop termination

When the effect demands N items from a finite pool ("sink two cards", "discard three"), structure the loop to terminate gracefully when the pool is exhausted — the rules implicitly read "if able." Caller treats `false` from `advanceToNext*` as "finalize early":

```php
if ($this->cardsSunk < 2 && $this->advanceToNextPick($game, $owner))
{
    $game->gamestate->nextState("done");
    return;
}

$this->finalize($game, $owner);
```

Reference: `Reaction_03007::advanceToNextPick`, `Reaction_03006::advanceToNextPick`.

### Log when an effect happens automatically

When an edge case skips the player's choice and auto-applies one branch (e.g. "opponent has < 2 cards in hand so we auto-wound the Leader instead"), log the reason *before* the consequent effect:

```php
if (count($hand) < 2)
{
    $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${opponent_name} does not have enough cards in hand to sink two, so their Leader is wounded.'), [
        "reaction_inject_code" => $owner->getInjectCode(),
        "opponent_name"        => $game->getPlayerNameById($this->opponentId),
    ]);

    $this->woundLeader($game, $owner);
    return false;
}
```

Reference: `Reaction_03007::advanceToChoose`. Without the log, players see only the wound message and wonder why no choice was offered.

### Capturing context onto the reaction

The triggering event has only a snapshot of args (`$event->cardId`, `$event->playerId`, etc.). If `performReaction` needs context that isn't on the event (e.g. the destroyed character's name, the location of the triggering challenge), capture it into a `private` property on the reaction at trigger time, **then clear it** in `performReaction` (or `resetStage` for multi-stage reactions). `$owner->IsUpdated = true` persists the property to DB. See `Reaction_03006::$targetCharacterId` and `Reaction_03007::$opponentId` for the pattern.

**Surface captured context in the prompt.** The reaction-button screen is the player's first chance to see *why* they're being prompted — bake the relevant context into `getReactionDescription` so they can make an informed pass/play call.

## Pattern E — Technique / Maneuver

`Technique` and `Maneuver` cards fire during duels — Techniques during a normal duel turn, Maneuvers when a maneuver event is queued (typically from another card).

Both need:
- `implements IHasTechniques` / `implements IHasManeuvers` on the attachment.
- `use TechniqueTrait` / `use ManeuverTrait`.
- A `$this->Techniques = [new Technique_NNNNN()]` / `$this->Maneuvers = [...]` registration in the constructor **after** `$this->resetCard()` (same ordering as Actions/Reactions).

**Pre-commit hook on Maneuver:** must handle `EventManeuverCanceled` (either branch it in `handleEvent` or add a comment `// EventManeuverCanceled handler not needed`).

**Pre-commit hook on Technique:** same — must handle `EventTechniqueCanceled` or add the equivalent comment.

References: `Technique_01050` (Unsavory Salve — -1 Thrust + wound), `Maneuver_01133` (Matushka's Efficiency), `Technique_03043` (El Gato's Mask — Gambling + reveal/discard).

### Gambling Technique / Gambling Maneuver

**"Gambling"** is a *mechanical cost of how the combat card was obtained*, NOT a performer trait. Do NOT `hasTrait("Gambling")`. Gate availability with:

```php
if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
{
    return false;
}

$owner = $this->getOwningCharacter($theah);
$actor = $theah->getDuelRoundActor();
if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
{
    return false;
}
```

Also require `Game::IN_DUEL`. Optional printed conditions (greater Finesse than adversary, adversary wounded, combat card ≥ N Thrust) go in the same `isAvailableToPlayer` using `ModifiedFinesse` / `getDuelRoundOpponent()` / `getCurrentRoundThrust()` — see `Technique_03002`, `Maneuver_03008`, `Technique_03039`, `Technique_03043`.

### Attachment-hosted technique — transition `sourceId`

When the Technique lives on the **attachment** (not the character), `createTransitionEvent` / `createTechniqueTransitionEvent` must pass **`$this->getOwningCard($theah)->Id`** (the attachment) as `sourceId`, not `getOwningCharacter()`.

WHY: `FrameworkActionsTrait::actFromCardWithId` / `argsForState` hydrate the source card and call `getTechniqueById` on it. If `sourceId` is the character, the technique is invisible and the act/args path fails. Character-hosted techniques (`Technique_03039`, `Technique_01093`) correctly use the character id; attachment-hosted ones (`Technique_02006`, `Technique_03043`) use the attachment id.

### Revealing cards to all players (multiplayer acknowledge)

Log inject codes alone are **not** enough when printed text says "Reveal …". Players must see the cards in the shared `chooseList` stock. Canonical shapes:

| Reference | Context |
|---|---|
| `SETUP_TABLE_01006_2` (Constanzo) | Setup reveal ack — `stMultiPlayerInitSansInitiatingPlayer` |
| `Technique_01090` / `duelChooseTechnique_01090` | Duel technique reveal ack |
| `Maneuver_01077` / `duelResolveManeuver_01077` | Duel maneuver reveal → then choose |
| `Action_01035` / Kaspar | Multi-ack → game `stFromCard` branch |
| **`Technique_03043`** | Attachment Gambling Technique: reveal hand → multi-ack → game branch → optional discard |

Standard pipeline after picking the random card(s) in `EventResolveTechnique`:

1. Persist revealed ids on the Technique (`private array $revealedCardIds` / `$revealedAttachmentIds`) + `$owner->IsUpdated = true`.
2. Notify each reveal with inject codes (and optionally `"card" => $card->getPropertyArray($game)`).
3. `$game->globals->set(Game::MULTI_STATE_INITIATING_PLAYER, $attachment->ControllerId)`.
4. Queue `createTransitionEvent($controllerId, $attachment->Id, "NNNNN", $this->Id)` into a **multipleactiveplayer** GameState that:
   - Uses `stMultiPlayerInitSansInitiatingPlayer` (or `setAllPlayersMultiactive` + exclude initiator) in `onEnteringState`
   - Returns `cards` (property arrays) from `getArgsFromTechnique` for the chooseList
   - Transitions `"multipleOk"` → a **game** state
5. Game state calls `stFromCard()` → override `stateFromTechnique` to branch (`"done"` / `"discard"` / etc.).
6. Only **after** the acknowledge, resolve effects that depend on what was revealed (discard, wound, …). WHY: players must see the cards before consequences fire.

JS (expansion `OnEnteringState` / `OnUpdateActionButtons` / `OnLeavingState`):

```javascript
'duelChooseTechnique_NNNNN': () => {
    dojo.removeClass('choose_container', 'hidden');
    dojo.removeClass('chooseList', 'hidden');
    $('choose_container_name').innerHTML = _('Revealed Cards from Hand');
    (args.args.args.cards || []).forEach((card) => this.addCardToDeck(this.chooseList, card));
    this.chooseList.setSelectionMode(0);
},
// Update buttons: Ok → this.onMultipleOk()
// Leaving: hide choose_container / chooseList, chooseList.removeAll()
```

Add the multi state name to `ZombieTrait` multipleactiveplayer cases (`setPlayerNonMultiactive(..., 'multipleOk')`).

### Reveal-then-discard restricted choice

When text says "discard one **revealed** attachment (if any were attachments)":

- Filter revealed cards with `instanceof Attachment` (not a Trait).
- **0** among revealed → notify and `"done"` (parenthetical "if any").
- **1** → auto-discard (choice is forced; skip picker).
- **2+** → activeplayer picker with hand selection **restricted** to those ids:

```javascript
const cardIds = (args.args.args.cardIds || []).map((id) => parseInt(id));
const selectable = this.factionHand.getCards().filter((card) => cardIds.includes(parseInt(card.id)));
this.factionHand.setSelectionMode('single');
this.factionHand.setSelectableCards(selectable);
```

Server-side: reject ids not in `$this->revealedAttachmentIds`. Mirror Maya/Íñigo discard (`createCardDiscardedFromHandEvent` … `$asEffect = true`) but do **not** open the full hand.

When branching to the discard activeplayer from a game state, `changeActivePlayer($adversary->ControllerId)` before `nextState("discard")` — multiplayer leave leaves active player ambiguous.

### Wiring duel technique sub-states (modern GameState classes)

For faf/tac-style GameState classes under `modules/php/States/<expansion>/`:

1. Constant(s) in `States.php` (e.g. `DUEL_CHOOSE_TECHNIQUE_03043 = 52103043`, `_2`, `_3`).
2. Transition key on `DUEL_CHOOSE_TECHNIQUE_EVENTS` in `states.inc.php`: `"03043" => States::DUEL_CHOOSE_TECHNIQUE_03043`.
3. Further steps (`_2` game, `_3` discard) are **internal** transitions on those GameState classes — they do not need separate keys on the events table.
4. JS handlers in the expansion `OnEnteringState` / `OnUpdateActionButtons` / `OnLeavingState` files + `EventHandlers.js` when hand selection enables a Confirm button.

**Line endings:** On this Windows repo, do **not** post-process Write output to "ensure CRLF" — the Write tool already emits CRLF; a naive `\n` → `\r\n` pass produces `\r\r\n` (double blank lines). Leave endings alone.
## When the attachment carries multiple shapes

Combine the interfaces:

```php
class _NNNNN extends FactionAttachment implements IHasActions, IHasReactions, IHasTechniques
{
    use ActionTrait;
    use ReactionTrait;
    use TechniqueTrait;

    public function __construct()
    {
        parent::__construct();
        // ...
        $this->Actions    = [new Action_NNNNN()];
        $this->Reactions  = [new Reaction_NNNNN()];
        $this->Techniques = [new Technique_NNNNN()];
    }
}
```

The framework hydrates each ability separately. No cross-talk needed between them inside the card class.

## Cross-Cutting Helpers

- `$theah->getCharacterById(int $id): ?Character` — hydrate a character by id.
- `$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): Character[]` — every Character at a location. Filter by `ControllerId` for friend/foe.
- `$theah->getLeaderByPlayerId(int $playerId): ?Leader` — get a player's Leader (returns null if destroyed).
- `$theah->getCardObjectsAtLocation(string $location, int $playerId = 0): Card[]` — all cards in a generic location (`Game::LOCATION_HAND`, `Game::LOCATION_PLAYER_HOME`, etc.). For hand, pass the player id.
- `$theah->locationInCity(string $location): bool` — canonical "is this a City location" check.
- `$game->getCardObjectFromDb(int $id): ?Card` — hydrate any card from db (works even if it's not in `Theah::$cards`).
- `$game->getGameDeckObject(int $playerId = 0): Deck` — get a player's deck wrapper. `getCardsInLocation(getPlayerDiscardDeckName($playerId))` queries discard; `getPlayerHand($playerId)` queries hand.
- `$game->getPlayerFactionDeckName(int $playerId): string` — the deck-table location string for a player's faction deck.
- `$game->getPlayerNameById(int $playerId): string` — use this instead of deprecated `getActivePlayerName()`.
- `$card->hasTrait(string $trait): bool` — check a trait.
- `$character->addTrait(Game $game, string $trait)` / `$character->removeTrait(Game $game, string $trait)` — for passive trait grants.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${attachment_inject_code}` placeholder).

Event factories you'll likely need:
- `createAttachmentUnequippedEvent($playerId, $characterId, $attachmentId)`
- `createCardDiscardedFromPlayEvent($playerId, $cardId, $location, $sourceId, $asEffect)`
- `createCardEngagedEvent($playerId, $cardId, $sourceId, $abilityId)`
- `createCharacterBeingWoundedEvent($characterId, $sourceId, $wounds, $reason, $abilityId = '')`
- `createReactionTransitionEvent($playerId, $sourceId, $reactionId)`
- `createActionResolvedEvent($playerId)`
- `createSorcererAbilityStartEvent($playerId, $sourceId, $abilityId, $performerId, $targetId = 0, $targetLocation = '')`
- `createSorcererAbilityPlayedEvent($playerId, $sourceId, $abilityId, $performerId, $targetId = 0, $targetLocation = '')`

## Pre-Commit Hook (relevant subset)

`.githooks/pre-commit` enforces, for files you touch in the attachment patterns above:

| Pattern | Required |
|---|---|
| `extends AttachmentAction/CardAction/...` | `createActionResolvedEvent()` somewhere in the class |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed(` AND `$this->isAvailable(` |
| `extends AttachmentReaction` | additionally `$this->ownerIsAttached(` |
| `extends Maneuver` | handle `EventManeuverCanceled` (or add the literal comment `EventManeuverCanceled handler not needed`) |
| `extends Technique` | handle `EventTechniqueCanceled` (or add the literal comment `EventTechniqueCanceled handler not needed`) |
| `implements ISorcererAbility` | both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()` *in the same file* (concerns the Action that performs the equip, not the attachment being equipped) |
| **Forbidden in `AttachmentAction` subclasses** | `setUsed` / `resetPlayerPassCount` / `announceAction` — these run centrally |
| **Forbidden anywhere** | implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` on the same class |

The attachment card class itself (the file under `cards/<expansion>/_NNNNN.php`) usually has none of these checks active — the hook focuses on Action/Reaction/Technique/Maneuver files.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- **Typed PHP parameters required.** Every function/method signature must declare a type for every parameter — no bare `$foo`. Use concrete types (`Card $owner`, `Character $performer`, `Game $game`, `Theah $theah`, `Event $event`, `int $cardId`, `string $reactionId`). Add the `use` import.
- **Traits in `TraitNames::$TraitsJson`** — add missing ones in alphabetical order.
- **"Opposing"** means BOTH different controller AND same location.
- **"Strega" / "Mercenary" / "Diplomat" / etc.** are **mechanical performer-trait gates**, not flavor. Enforce via `hasTrait("Strega")` on the chosen performer. They are NOT Sorcerer abilities. Only the literal "Sorcerer" keyword triggers `ISorcererAbility`. They can stack.
- **"Action" vs "City Action" performer scope:** "City Action:" restricts performers to characters in the city; plain "Action:" includes characters at Home. (Memory feedback.)
- **"Gambling Technique/Maneuver"** is a duel-round gate (`DUEL_GAMBLED` + actor identity), not a trait. See Pattern E.
- **Cancel Maneuver/Technique:** Activated (not Resolve); `HIGH_PRIORITY` transitions; cancel-first when multi-stage "unless discard"; correct character/player id gates — do not copy `Reaction_01047`'s compare. See Pattern D / `_03044`.
- Namespaces:
  - Attachment class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:           `...\cards\<expansion>\actions`
  - Reaction:         `...\cards\<expansion>\reactions`
  - Technique:        `...\cards\<expansion>\techniques`
  - Maneuver:         `...\cards\<expansion>\maneuvers`

## Reference Implementations

| File | What it demonstrates |
|---|---|
| `modules/php/cards/FactionAttachment.php` | Base class. Extends `Attachment`, mixes in `FactionCardTrait` + `WealthCostTrait`. `CanEquipToOpponents = false` by default. |
| `modules/php/cards/Attachment.php` | Grand-parent base. `AttachedToId`, `isAttached`, `attachedTo`, `canAttachTo`, `getRequiredAttachTargetId`. |
| `modules/php/cards/_7s5s/_01073.php` + `actions/Action_01073.php` (Cavalier Hat) | **Equip restriction (Duelist) + City Action.** Cleanest dual-gate template. |
| `modules/php/cards/_7s5s/_01075.php` + `actions/Action_01075.php` (Tabard of the Fallen Musketeer) | **Equip restriction (non-Diplomat — inversion case) + passive Musketeer-trait grant + City Action.** |
| `modules/php/cards/_7s5s/_01050.php` + `techniques/Technique_01050.php` (Unsavory Salve) | **Equip restriction (must have Weapon) + auto-destroy when prerequisite is lost + Technique.** |
| `modules/php/cards/_7s5s/_01022.php` + `reactions/Reaction_01022.php` | **AttachmentReaction with engage cost.** Simple three-button reaction (Wound Challenger / Wound Challenged / Pass). |
| `modules/php/cards/_7s5s/_01181.php` + `reactions/Reaction_01181.php` (Sorte Deck) | `AttachmentReaction` with cancel + `releaseEvent` + `skipNextEvent` interpose pattern. Engage-cost reaction. |
| `modules/php/cards/_7s5s/_01198.php` + `actions/Action_01198.php` | Passive trait grant (Duelist) + City Action that issues a challenge. |
| `modules/php/cards/_7s5s/_01133.php` + `maneuvers/Maneuver_01133.php` (Matushka's Efficiency) | **Maneuver on a faction attachment.** ISorcererAbility + EventManeuverCanceled handling. |
| `modules/php/cards/faf/_03007.php` + `reactions/Reaction_03007.php` (Matushka's Shears) | **Strega-only equip + Sorcerer City Reaction.** Multi-stage button-driven Reaction with engage cost: `offer` (owner accepts/passes) → `choose` (opponent picks Sink/Wound) → `pick1`/`pick2` (opponent picks cards to sink). Cross-player `createReactionTransitionEvent` flow, `insertCardOnExtremePosition` for sinking, auto-wound + explanatory log when opponent has < 2 cards. Combines ISorcererAbility with `hasTrait("Strega")` performer gating from the equip restriction. |
| `modules/php/cards/faf/reactions/Reaction_03006.php` | Sister to `Reaction_03007` but on a Scheme — same multi-stage hand-pick pattern with cross-player transitions. |
| `modules/php/cards/faf/_03019.php` + `reactions/Reaction_03019.php` (Kaiser Schnurrbart) | **Passive trait grant (Hunter) + City Reaction on `EventCardMoved`.** "After an opposing character moves to an adjacent City location, engage this card • Move the equipped character to their new location and engage that character." Demonstrates the `fromLocation == owningCharacter->Location` gate for "opposing" semantics post-move, the three-event chain (engage attachment / move equipped with `engage=false` / engage triggering character), and resolving the "that character" antecedent ambiguity in favor of the trigger. |
| `modules/php/cards/faf/_03043.php` + `techniques/Technique_03043.php` (El Gato's Mask) | **Passive Scoundrel grant + Gambling Technique.** Reveal ≤2 random from adversary hand → multiplayer `chooseList` acknowledge (`stMultiPlayerInitSansInitiatingPlayer`) → game `stFromCard` / `stateFromTechnique` branch (0 / 1 auto-discard / 2 restricted hand pick). Attachment-hosted transition `sourceId` = `getOwningCard()`, `DUEL_GAMBLED` + actor Finesse > adversary gates, discard only after ack. |
| `modules/php/cards/faf/_03044.php` + `reactions/Reaction_03044.php` (Torres Cloak) | **Offhand + Duelist equip + cancel Maneuver/Technique unless discard.** `EventTechniqueActivated`/`EventManeuverActivated` + correct adversary id gates + `HIGH_PRIORITY` offer/threat transitions. Cancel-first on Engage (`delete*Events`, no `*Canceled` yet); discard re-queues Resolve/Calculate; Accept Cancel fires `*Canceled`. Deferred `setUsed` until finalize. Public `$TechniqueId`/`$stage`/restore context. |
| `modules/php/cards/_7s5s/reactions/Reaction_01047.php` (Kaspar's Panzerhand) | **Hard cancel adversary Technique** on `EventTechniqueActivated`. Single-decision; public `$TechniqueId`. Id-compare in the availability gate is buggy (mixes player/character ids) — prefer `_03044::isAdversaryActivating` when writing new cancel reactions. |
| `modules/php/cards/_7s5s/reactions/Reaction_01146b.php` | **Hard cancel Maneuver or Technique** (Scheme-hosted). Same Activated + `HIGH_PRIORITY` + `delete*Events` shape as `01047`. |
| `modules/php/cards/_7s5s/reactions/Reaction_01066.php` (Horatio) | Character-side mirror of the post-move-adjacent pattern. Same `fromLocation == owner location` gate, same `getAdjacentCityLocations` check, but moves Horatio (no engage cost). Read alongside `Reaction_03019` to see how the pattern adapts between Character host and Attachment host. |
| `modules/php/cards/_7s5s/_01006.php` + `SETUP_TABLE_01006_2` | Canonical **multiplayer reveal acknowledge** (`chooseList` + Ok). Sibling shapes: `Technique_01090`, `Maneuver_01077`, Kaspar `Action_01035`. Use when any ability reveals cards to the table. |

## When You Finish

1. Walk each clause of the printed Text — confirm each maps to exactly one pattern (Stat / Equip Restriction / Passive Grant / Forced / Action / Reaction / Technique / Maneuver). Stat numbers and combat-card stats go on the constructor and are not a "pattern."
2. Confirm: `initializeFaction(<faction>)` is called, `CardNumber` matches the filename's `NNNNN`, `WealthCost` is set, all stat modifiers are set (default 0), all Traits exist in `TraitNames::$TraitsJson`.
3. For equip restrictions, implement BOTH `eventCheck(EventAttachmentEquipping)` AND `canAttachTo(Character)`. Don't pick one — the UI uses `canAttachTo` to grey out invalid targets, and `eventCheck` is the server-side enforcement when the equip event fires.
4. For passive trait grants, implement BOTH `EventAttachmentEquipped` (add) AND `EventAttachmentUnequipped` (remove). Don't forget the unequip half.
5. **Parse keyword(s) literally** before picking interfaces:
   - "Sorcerer …" → `implements ISorcererAbility` + emit Start/Played events in the Action/Reaction class.
   - "Strega …" / "Mercenary …" / "Diplomat …" / etc. → performer-trait gate (`hasTrait("Strega")` on the equipped character or chosen performer). NOT a Sorcerer ability.
   - **"Gambling Technique/Maneuver"** → `Game::DUEL_GAMBLED` + actor identity gate. NOT a trait gate.
   - Both Sorcerer and trait gates can stack ("Sorcerer Strega Reaction" is both).
6. For Reactions with **engage cost**, gate the trigger on `! $owner->Engaged` AND `$this->isAvailable()`, then queue `createCardEngagedEvent` on the attachment in `performReaction`. The dusk reset handles both `Engaged` and `Used`. **If a later stage still needs a reaction transition, do not `setUsed` on Engage** — `runEvents` skips reaction transitions when `!isAvailable()`. Defer to finalize (`03007`, `03044`).
7. **Cross-player reactions** (opponent must do part of the resolve): use multi-stage `$stage` + `createReactionTransitionEvent($opponentId, ...)`. Do NOT create a dedicated sub-state — reactions can fire from any phase and a sub-state is only reachable from its phase's `*_EVENTS` transitions. During duel cancel interrupts, set **`HIGH_PRIORITY` on every** reaction transition in the chain (default `REACTION_PRIORITY` is later than MEDIUM Resolve).
8. For multi-step pools ("sink two cards"), structure `advanceToNext*` to return `false` when the pool is exhausted, and have the caller finalize early. "If able" is implicit in the rules text.
9. **Log auto-applied branches.** When an edge case skips the player's choice (no cards to sink, no Leader to wound, empty hand on "unless discard", etc.), notify *before* the consequent effect so players understand why the choice wasn't offered.
10. Capture event-time context onto the reaction/technique; clear it when the flow finishes / on cancel. Use `$owner->IsUpdated = true` to persist. For **cancel Maneuver/Technique** reactions, prefer **public** `$TechniqueId` / `$ManeuverId` / `$stage` / restore ids (mirror `01047` / `03044`) — nested private fields have been fragile across multi-stage playerReaction requests.
11. **Typed parameters** on every function/method signature. No bare `$foo`. Add `use ...\cards\Card;` (etc.) imports as needed.
12. Pre-commit hook checks on every file you touched:
    - **AttachmentReaction subclass:** `$this->setUsed(`, `$this->isAvailable(`, `$this->ownerIsAttached(`.
    - **AttachmentAction subclass:** `createActionResolvedEvent()` called. NO `setUsed`/`announceAction`/`resetPlayerPassCount`.
    - **`implements ISorcererAbility`:** both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()`.
    - **Maneuver/Technique:** handle the corresponding `*Canceled` event (or add the literal comment).
    - No class implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards`.
13. For **attachment-hosted Techniques** that transition into player states: `sourceId` = `getOwningCard()->Id` (attachment), not the equipped character.
14. When text **reveals** cards: add a multiplayer `chooseList` acknowledge state (Constanzo / Lorenzo / `_03043`). Do not rely on log messages alone. Resolve discard/wound/etc. **after** ack. Register the multi state in `ZombieTrait`.
15. **"Cancel unless they discard" Maneuver/Technique:** cancel-first on Engage (`delete*Events` + clear `CHOSEN_*`, no `*Canceled` yet); discard re-queues Resolve/Calculate; Accept Cancel fires `*Canceled`. Listen on `*Activated`, not `EventResolve*`. Use correct adversary id gates (character vs player). See `_03044`.
16. Lint touched PHP files (`php -l`) before committing. **Do not** rewrite line endings after Write — leaves `\r\r\n` on this Windows repo.
17. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>-implementation.md` covering the WHY: which existing patterns you mirrored, what alternatives you considered, anything that looks weird (defensive null checks, dual-gate equip restrictions, the order of Sorcerer Start/Played around effects). Read related faf journals first — they encode hard-won knowledge about edge cases.
