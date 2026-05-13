---
name: create-character
description: Implement or finish a Character or Leader card (modules/php/cards/<expansion>/_NNNNN.php where the class directly extends Character or Leader). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Character/Leader card, or when they reference a faction-deck character whose class extends Character (not CityCharacter) and has unimplemented Text. Triggers on phrases like "implement this character", "implement this leader", "finish _NNNNN" (when it extends Character or Leader), "wire up the City Action on Cesca", "wire up the Reaction on this Leader", or natural-language descriptions of a non-city-deck character (lives in a player's faction deck or is a Leader).
---

# Creating a Character or Leader

This skill covers cards that directly extend `Character` (regular faction-deck characters) or `Leader` (which itself extends `Character`). These cards live in a player's faction deck (or are placed at game start as the player's Leader) — they are **not** in the city deck.

Canonical references:
- `modules/php/cards/_7s5s/_01007.php` (Aldo Bussotti) — straightforward `Character` with a passive stat-modifying handleEvent and a City Action.
- `modules/php/cards/_7s5s/_01006.php` (Don Constanzo Scarpa) — `Leader` with a setup-time `IHasReactions` Reaction, a passive `EventPressureOccuring` listener, and multi-step setup states.
- `modules/php/cards/_7s5s/_01089.php` (Soline el Gato) — `Leader` with a passive duel-stat hook (`EventDuelStarted` / `EventDefenderSwapped` / `EventChallengerSwapped`) and a button-based City Reaction.
- `modules/php/cards/_7s5s/_01116.php` (Yevgeni) — `Leader` with a passive `EventDuelCalculateCombatCardStats` hook and two paired Reactions.
- `modules/php/cards/faf/_03001.php` (Cesca del Rosso) — `Leader` with an `EventPhaseDawnEnding` draw effect, a button-based City Reaction triggered by `EventSorcererAbilityPlayed`, and a two-step City Action (CharacterAction with state classes).

When in doubt, mirror one of those rather than invent.

> **Sibling skills:**
> - `create-city-character` — for stubs that `extends CityCharacter` (city-deck, mustered with WealthCost, `CityCardNumber`).
> - `create-city-event-card` — for stubs that `extends CityEventCard`.
> - `create-city-attachment` — for stubs that `extends CityAttachment`.
>
> All three of those city-deck siblings also descend from `Character`/`Card` ultimately, so **a lot of the runtime semantics overlap** with this skill. Use them when the stub literally extends one of those classes; use this skill when the stub extends `Character` or `Leader` directly. The most relevant overlap with `create-city-character` is Pattern C (CharacterAction + state classes + JS wiring) and Pattern D (button-based Reactions) — those patterns are essentially identical and were trimmed here rather than duplicated. Read the city-character skill alongside this one when implementing a multi-step action or reaction.

## Distinction: Character vs CityCharacter vs Leader

| Class | Lives in | Cost to put in play | Key fields |
|---|---|---|---|
| `Character` (direct) | Player's faction deck (or hand) | Wealth cost paid via standard recruit action | Resolve, Combat, Finesse, Influence (+ dashed variants), Traits |
| `Leader extends Character` | In play from game start, never recruited | None (placed during setup) | All Character fields + `CrewCap`, `Panache` |
| `CityCharacter extends Character` | City deck | WealthCost; can be Negotiable | All Character fields + `Negotiable`, `WealthCost`, `CityCardNumber` |

If the stub says `extends CityCharacter`, switch to `create-city-character`. If it says `extends Character` or `extends Leader`, you're in the right place.

A "City Action" or "City Reaction" in the card text does **not** make a card a CityCharacter. The "City" prefix on those keywords is about the ability scope (must be in the city to use it), not about where the card lives. A Leader like Cesca del Rosso has a City Action — and Cesca still `extends Leader`, not `CityCharacter`.

## Base Anatomy — Character

`Character extends Card implements IHasTechniques` and mixes in `TechniqueTrait`. It adds stat fields (`Resolve`, `Combat`, `Finesse`, `Influence` + `Modified*` and `Dashed*` variants), the `Title` flavor subtitle, `Wounds` tracking, and the `Attachments` array.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

class _NNNNN extends Character implements IHasActions   // + IHasReactions / IHasManeuvers / etc. as text requires
{
    use ActionTrait;
    // use ReactionTrait;
    // use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('...');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = '_7s5s';   // or 'tac' / 'faf'
        $this->ExpansionNumber = 1;
        $this->CardNumber      = NN;        // matches the file name's NNNNN

        $this->initializeFaction('Vodacce');   // mandatory for non-Leader Characters — sets $Factions
        $this->Title    = clienttranslate('...');

        $this->Resolve   = 4;
        $this->Combat    = 1;
        $this->Finesse   = 3;
        $this->Influence = 1;
        // $this->DashedCombat = true; // when stat is printed as "—"

        $this->Traits = [
            clienttranslate('Diplomat'),
            clienttranslate('Red Hand'),
            clienttranslate('Vodacce'),
        ];

        $this->Text = clienttranslate("<p>...</p>");

        $this->resetCard();   // copies stats into Modified* fields

        $this->Actions = [ new Action_NNNNN() ];  // only if IHasActions
    }
}
```

## Base Anatomy — Leader

`Leader extends Character` and adds `CrewCap` and `Panache` (with `Modified*` variants). Leaders also have built-in `handleEvent` logic for `EventCharacterDestroyed` (renown loss / game end) and `EventSchemeCardRevealed` (Panache modifier from schemes). **You must call `parent::handleEvent($event)` first in any override** so this logic still runs.

```php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;

class _NNNNN extends Leader implements IHasActions, IHasReactions
{
    use ActionTrait;
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name            = clienttranslate('...');
        $this->Image           = 'NNNNN.jpg';
        $this->ExpansionName   = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber      = 1;
        $this->Title           = clienttranslate('...');

        $this->Resolve   = 7;
        $this->Combat    = 1;
        $this->Finesse   = 2;
        $this->Influence = 4;

        $this->CrewCap = 6;       // Leader-only: maximum number of crew this Leader can field
        $this->Panache = 2;       // Leader-only: scheme-resolve order tiebreaker

        $this->Traits = [
            clienttranslate('Leader'),    // canonical — every Leader has "Leader" as a trait
            clienttranslate('Villain'),
            clienttranslate('Sorcerer'),
            clienttranslate('Strega'),
            clienttranslate('Red Hand'),
            clienttranslate('Vodacce'),
        ];

        $this->Text = clienttranslate("<p>...</p>");

        $this->resetCard();

        $this->Actions   = [ new Action_NNNNN() ];
        $this->Reactions = [ new Reaction_NNNNN() ];
    }
}
```

Differences from regular Character:
- **Do NOT call `initializeFaction()`** on a Leader — the framework sets the faction from the player's faction selection at setup. The Leader's `Factions` is implicit. (Look at `_01006`, `_01089`, `_01116` — none call `initializeFaction`. `_01035` Kaspar does, but `_01089` Soline doesn't, and `_03001` Cesca doesn't. The base game's Leader setup populates this regardless.) If you're scaffolding and unsure, omit it for Leaders.
- **Always include `"Leader"` in `Traits`.** Cards filter on `hasTrait("Leader")` (e.g., "target a non-Leader" effects), so this is load-bearing.
- **`CrewCap` and `Panache` are required.** Don't leave them at the constructor defaults of 0.

Field notes (apply to both Character and Leader):

- **`Resolve`** is wound capacity. Required.
- **`DashedCombat` / `DashedFinesse` / `DashedInfluence`** match the printed dashes on the card's stat block. Dashed stats are visually `—`; the character cannot use them in pressures/challenges. Set the underlying numeric stat to `0` when dashed.
- **`CardNumber`** matches the NNNNN in the filename. Regular Characters use this — only CityCharacters override it to `0` and use `CityCardNumber` instead.
- **`Factions`** is set by `initializeFaction(string $faction)` for regular Characters; populated by the framework's setup flow for Leaders.

Key runtime state inherited from `Character` / `Card`:
- `$this->Id` — this character's card id.
- `$this->ControllerId` — the player currently controlling. `0` for cards not yet in play.
- `$this->Location` — current location string. While in deck/hand, this is a deck/hand location; once mustered into play, a city location or Home.
- `$this->Engaged` — engagement state.
- `$this->Wounds`, `$this->ModifiedResolve` — wound tracking.

## Pick the Right Ability Shape

Read each clause of the printed Text and classify it before writing code. A single Character/Leader commonly combines several. Cesca has all three of: a passive End-of-Dawn effect, a button-based City Reaction, and a multi-step City Action.

| Card phrase | Pattern |
|---|---|
| **Stat printed as a dash (`—`)** | Set the matching `Dashed<Stat> = true;` flag + numeric stat to `0`. |
| **"<Name> cannot intervene/challenge/pressure"** | Override the predicate AND `eventCheck`. See "Pattern A — Hard ban" in the `create-city-character` skill — the implementation is identical. |
| **"When <X> happens, <passive thing>"** (no player choice) | Override `handleEvent`. Gate on event type + identity + location/scope. See Pattern A below. |
| **"At the end of Dawn"** / **"At the beginning of Dawn"** | `handleEvent` on `EventPhaseDawnEnding` / `EventPhaseDawnBeginning`. See Pattern A below. |
| **"At/During <phase>"** broadly | One of the phase events: `EventNewDay`, `EventPhaseDawnBeginning`, `EventPhaseDawnEnding`, `EventDuskEndOfDay`, `EventPressureOccuring`, `EventDuelStarted`, etc. See "Phase / lifecycle events" below. |
| **"<Stat> increases by N"** / **"<Stat> is reduced by N"** | Queue `createCharacter<Stat>ModifiedEvent` (e.g., `createCharacterInfluenceModifiedEvent`). See `_01007` Aldo for renown-driven Influence modification. |
| **`<b>Action:</b>`** / **`<b>City Action:</b>`** | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_NNNNN.php` extending `CharacterAction`. State class(es) + JS wiring per Pattern C. **"City Action" only differs by the `cardInCity` gate** in `isAvailableToPlayer`. |
| **`<b>Reaction:</b>`** / **`<b>City Reaction:</b>`** | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_NNNNN.php` extending `CardReaction`. Button-based reactions need **no** state class, **no** `states.inc.php` edits, **no** JS wiring. See Pattern D. |
| **`<b>Sorcerer …</b>`** (Sorcerer Action / Sorcerer Reaction) | The Action/Reaction class additionally `implements ISorcererAbility`. **Must** call `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces). See `Action_01076` and `Reaction_02001`. |
| **`<b>Technique:</b>` / `<b>Maneuver:</b>`** | The Character lineage already brings `TechniqueTrait`. Add `IHasManeuvers` + `ManeuverTrait` for maneuvers. Implement under `cards/<expansion>/techniques/` or `cards/<expansion>/maneuvers/`. |

## Pattern A — Passive ability via `handleEvent`

For text that has no player choice ("At the end of Dawn, draw five cards", "Your adversaries at Soline's location have -1 Finesse", "When Yevgeni plays a combat card, it gains +1 Thrust") — override `handleEvent` and gate the body on event type + identity + scope. Always call `parent::handleEvent($event)` first.

### Identity and scope gates

1. **Event type** — `instanceof EventXxx`.
2. **Identity** — usually `$event->cardId == $this->Id`, `$event->characterId == $this->Id`, `$event->playerId == $this->ControllerId`, or `$event->actorId == $this->Id`. The exact field depends on the event class; **read the event source file** to confirm.
3. **Liveness / scope** — at minimum a "this card is in play" check. For a Leader, the right check is usually `! $event->theah->game->characterIsInDiscardOrLocker($this)` (and `$this->ControllerId > 0` as a cheap pre-check). For an "in city" effect, also gate on `$event->theah->cardInCity($this)`.

### End-of-Dawn draw (canonical example — Cesca)

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventPhaseDawnEnding && $this->ControllerId > 0)
    {
        $game = $event->theah->game;
        if ($game->characterIsInDiscardOrLocker($this))
        {
            return;   // dead Leader / destroyed Character — skip the effect
        }

        $game->notify->all("message", clienttranslate('${leader_inject_code}: ${player_name} draws five cards at the end of Dawn.'), [
            "leader_inject_code" => $this->getInjectCode(),
            "player_name"        => $game->getPlayerNameById($this->ControllerId),
        ]);

        for ($i = 0; $i < 5; $i++)
        {
            $drawEvent = EventFactory::createCardDrawnEvent($this->ControllerId, $this->getInjectCode());
            $event->theah->queueEvent($drawEvent);
        }
    }
}
```

WHY `characterIsInDiscardOrLocker` and not just `isControlled()`:

- A destroyed Leader still has a non-zero `ControllerId` — `isControlled()` returns true.
- The actual signal that the Leader is out of play is the `Location` (discard/locker).
- See `UtilitiesTrait::characterIsInDiscardOrLocker` for the canonical check.

Apply the same check on any Character ability that triggers off phase events.

### Drawing cards

- One card: `EventFactory::createCardDrawnEvent($playerId, $reason)` then `queueEvent`.
- N cards: loop and queue N events. The framework draws one card per event. (Yes, `_03001` literally queues five draw events in a loop.)
- The `$reason` string shows in the log alongside the draw. Use `$this->getInjectCode()` so the log links back to your card.

### Passive stat modifiers

For "Your <stat> increases / decreases by N":

```php
private function lowerFinesse(Character $character, Theah $theah)
{
    $event = EventFactory::createCharacterFinesseModifedEvent(
        $this->ControllerId,
        $character->Id,
        $character->ModifiedFinesse,                    // from
        $character->ModifiedFinesse - 1,                 // to
        $this->getInjectCode()                           // reason for log
    );
    $theah->queueEvent($event);
}
```

The factories are:
- `createCharacterCombatModifiedEvent`
- `createCharacterFinesseModifedEvent` (note the typo in the framework — `Modifed`, not `Modified`)
- `createCharacterInfluenceModifiedEvent`
- `createCharacterResolveModifiedEvent`
- `createCharacterPanacheModifiedEvent` (Leader only)

When the predicate that drives the modifier changes (a character moves into/out of the affected location, a duel ends), queue the inverse event to undo it. See `_01089` Soline el Gato — `lowerFinesse` on `EventDuelStarted`, `raiseFinesse` on `EventDuelEnd` / opposite swap. Track which character was affected on `$this->AffectedCharacterId` and set `$this->IsUpdated = true` so the change persists.

### Phase / lifecycle events worth knowing

| Event | When it fires | Typical use |
|---|---|---|
| `EventNewDay` | Start of each Day | Reset per-day flags |
| `EventPhaseDawnBeginning` | Dawn begins | "At the beginning of Dawn …" |
| `EventPhaseDawnEnding` | Dawn ends (fired by `StatesTrait::stDawnEnding`) | "At the end of Dawn …" |
| `EventDuskEndOfDay` | End of Day | Reset per-day Used flags (base classes handle this for Actions/Reactions automatically) |
| `EventPressureOccuring` | A pressure is happening at a location | "When pressuring …", `_01006` Don Constanzo |
| `EventDuelStarted` / `EventDuelEnd` | Duel boundaries | Passive duel stat modifiers, `_01089` |
| `EventDuelCalculateCombatCardStats` | Combat card stats are being computed for a duel | "+X to combat card stats", `_01116` Yevgeni |
| `EventChallengerSwapped` / `EventDefenderSwapped` | A challenge had its participant changed | Re-evaluate any duel-time modifier you applied, `_01089` |
| `EventTableSetup` | Game setup | Initial decisions like "during setup, reveal X from your deck", `_01006` |
| `EventSchemeCardRevealed` | A scheme is revealed | Leaders react via the base `Leader::handleEvent`; only override if you have card-specific logic |
| `EventCharacterDestroyed` | A character is destroyed | Leaders have built-in renown-loss logic in `Leader::handleEvent` — don't reinvent |
| `EventSorcererAbilityPlayed` | A sorcerer ability resolved | "After <X> performs a Sorcerer ability …" reactions, Pattern D below |
| `EventActionResolved` | An action just resolved | "After an Action resolves …" reactions, `Reaction_01089` |

## Pattern C — Action / City Action (CharacterAction)

This pattern is **the same as in `create-city-character`'s Pattern C**. The action class extends `CharacterAction` regardless of whether the owning card is a Character, Leader, or CityCharacter. Read the city-character skill's Pattern C for the full template, state class skeleton, and JS wiring. Below are the Character/Leader-specific notes.

### Eligibility differences

- **Regular Action** (`<b>Action:</b>`) — usually requires the character to be in play (`cardInPlay`) but not in the city. The base `parent::isAvailableToPlayer()` covers most of this; add specific preconditions.
- **City Action** (`<b>City Action:</b>`) — additionally gate on `$theah->cardInCity($owner)`. The character must be at one of the city locations to use the ability.

```php
public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
{
    if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
    {
        return false;
    }

    $owner = $this->getOwningCharacter($theah);

    if (! $theah->cardInCity($owner))      // City Action — drop this gate for a non-city Action
    {
        return false;
    }

    // Card-specific preconditions go here.
    return true;
}
```

### CharacterAction does NOT call setUsed / resetPlayerPassCount / announceAction

Per CLAUDE.md, those are run centrally in `actHighDramaInPlayActionConfirm` / `stHighDramaInPlayActionDispatch`. Calling them from a `CharacterAction` subclass causes duplicates.

Still required: **call `createActionResolvedEvent()` once at the end of resolution.** (The pre-commit hook's regex doesn't directly match `extends CharacterAction` — but the call is still mandatory per CLAUDE.md and the convention in every existing CharacterAction.)

### State ID encoding

For regular Character cards (not city deck), use `4` + the 5-digit `CardNumber` for step 1. Append `2`/`3`/`4` for multi-step suffixes. Examples:

- `_01007` (Aldo) step 1: `HIGH_DRAMA_PLAYER_TURN_01007 = 401007`
- `_01008` (Cesca Scarpa) step 1: `HIGH_DRAMA_PLAYER_TURN_01008 = 401008`
- `_01008` step 2/3/4: `4010082` / `4010083` / `4010084`
- `_03001` (Cesca del Rosso) step 1: `HIGH_DRAMA_PLAYER_TURN_03001 = 403001`
- `_03001` step 2: `HIGH_DRAMA_PLAYER_TURN_03001_2 = 4030012`

**Don't engineer around hypothetical city-deck-card collisions.** Memory `feedback_state_id_encoding.md`: the user prefers the simple `4` + cardId scheme. If a future CD card wants the same number, that collision gets resolved then.

### `states.inc.php` transition-name mapping

When you call `EventFactory::createTransitionEvent($playerId, $cardId, $transitionName, $abilityId)`, the framework looks `$transitionName` up in `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions` to know which state to enter. So you need an entry for **every transition name your action passes to `createTransitionEvent`** — and only those.

```php
"03001"   => States::HIGH_DRAMA_PLAYER_TURN_03001,        // entered from EventActionTriggered
```

**Do NOT blindly add `"03001_2"`** unless your action's `handleEvent` actually calls `createTransitionEvent($playerId, $cardId, "03001_2", ...)`. The step 1 → step 2 jump normally happens via `$game->gamestate->nextState("stregaChosen")` using the state's own `transitions` array — not via the lookup table.

The only existing card that legitimately uses a `<card>_2` transition-event name is `Action_03cd03` (Chance Meeting), which rotates through opponents by queueing transitions directly into the muster state. If your card doesn't have a similar "queue into a later state from outside the normal flow" pattern, don't add the `_2` entry. (`"03cd01_2"` is in the file too — but it's dead code; lifted by copy-paste and never actually consulted.)

### Action examples

| File | Demonstrates |
|---|---|
| `Action_01008` | Multi-step Sorcerer Action; reveal-top-of-deck → optional sink. Branching states (`_2`, `_3`, `_4`). |
| `Action_01076` | Sorcerer Action; multi-step with `RequiresPerformerSelected`, location + character pick, queues `createSorcererAbilityStartEvent` / `createSorcererAbilityPlayedEvent` pair. |
| `Action_02010` | Two-step "move wound from character A to character B"; the heal+wound recipe. |
| `Action_03001` | Two-step "move wound from your Strega to opposing non-Leader"; the heal+wound recipe applied to a Leader's City Action. |
| `Action_01035` | Engage-as-cost + reveal-from-city-deck-until-Mercenary action on a Leader. |

### Move-a-wound recipe

```php
$healEvent = EventFactory::createCharacterBeingHealedEvent(
    $sourceCharacter->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
);
$game->theah->queueEvent($healEvent);

$woundEvent = EventFactory::createCharacterBeingWoundedEvent(
    $targetCharacter->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
);
$game->theah->queueEvent($woundEvent);
```

Heal first, wound second. Both go through the standard event pipeline so other cards can react (Maryam's wound cancel, Silver Spine's risk-target cancel, etc.) — don't try to mutate `$character->Wounds` directly.

## Pattern D — Reaction / City Reaction (CardReaction)

This pattern is **the same as in `create-city-character`'s Pattern D**, with two Character/Leader-specific notes below. Read the city-character skill's Pattern D for the full template, multi-stage button flow, and `< Back` rules.

### Trigger gates for non-city-deck characters

Most Character/Leader reactions don't need a `cardInCity` gate (unless the card text says "City Reaction" — then add the gate). Key gates:

1. **`$this->isAvailable()`** — base `CardReaction::handleEvent` resets `Used = false` on `EventDuskEndOfDay`. Gate every branch on `isAvailable()` so the reaction doesn't double-fire within a day.
2. **Identity check** — usually `$event->sourceId == $owner->Id`, `$event->performerId == $owner->Id`, `$event->actorId == $owner->Id`, or `$event->cardId == $owner->Id`. The field depends on the event.
3. **City scope** (for "City Reaction" only) — `$event->theah->cardInCity($owner)`.
4. **Valid-target precondition** — if the effect requires a target (e.g., "wound an opposing character"), check that at least one valid target exists BEFORE queuing the reaction transition. Otherwise the player gets a useless prompt they can only Decline.
5. **"Opposing" semantics** — opposing means BOTH different controller AND same location. Use `Theah::getOpposingCharactersAtLocation($location, $playerId)` (or hand-filter with `isNotControlledByPlayer($controllerId) && Location == $owner->Location`), not a hand-rolled `ControllerId !=` filter.

### Triggering off a Sorcerer ability the owner just performed

For "After <X> performs a Sorcerer ability …" (Cesca del Rosso, Elina, Cesca Scarpa) — listen on `EventSorcererAbilityPlayed` and check both `sourceId` and `performerId`:

```php
if ($event instanceof EventSorcererAbilityPlayed && $this->isAvailable())
{
    $owner = $this->getOwningCharacter($event->theah);

    if (! $event->theah->cardInCity($owner))   // City Reaction gate — drop for non-city Reactions
    {
        return;
    }

    if ($event->sourceId != $owner->Id && $event->performerId != $owner->Id)
    {
        return;   // some other Sorcerer's ability — not this card's
    }

    // ... valid-target precondition ...

    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
    $event->theah->queueEvent($transition);
}
```

`sourceId` is the card whose ability fired; `performerId` is the character actually performing it. The ability may be on a card other than the owner (e.g., the owner cast a sorcery from her hand) — checking both covers both cases.

### Should the Reaction itself implement `ISorcererAbility`?

Only if the card text says "**Sorcerer** Reaction" or "**Sorcerer** City Reaction." Examples:
- `Reaction_02001` (Andriana, "**Sorcerer** Reaction: …") implements `ISorcererAbility`.
- `Reaction_03001` (Cesca del Rosso, "**City Reaction**: …") does NOT — the text doesn't carry the Sorcerer keyword.

This matters because if a Reaction is a Sorcerer ability and it wounds, that wound's `EventSorcererAbilityPlayed` would re-trigger the same "after a Sorcerer ability" type reaction in a loop. `setUsed` breaks the loop in practice, but the cleaner answer is: **follow the card text literally.** If the keyword isn't printed, the ability isn't Sorcerer.

When `implements ISorcererAbility`, you MUST also call both:
- `createSorcererAbilityStartEvent()` at the start of resolution
- `createSorcererAbilityPlayedEvent()` at the end of resolution

The pre-commit hook enforces this.

### Reaction examples

| File | Demonstrates |
|---|---|
| `Reaction_01006` | `IRiskReaction`-shaped pre-end-of-day cleanup ("Reaction: Before the end of the Day"). |
| `Reaction_01008` | "Cesca Scarpa copies the Sorcerer ability just played" — listens on `EventSorcererAbilityPlayed`, branches on the ability instance to copy actions/cards/etc. The original kitchen-sink Sorcerer-after-Sorcerer reaction. |
| `Reaction_01089` | Soline el Gato's "after an Action resolves" — `EventActionResolved` + button-per-adjacent-location. |
| `Reaction_01116a`, `Reaction_01116b` | Yevgeni's paired Reactions on a single Leader. |
| `Reaction_01118` | Elina's "after a Sorcerer ability targets a character at her location, move Renown to her location" — `sourceId == owner` OR `performerId == owner` pattern. |
| `Reaction_02001` | Andriana — Sorcerer Reaction (so implements `ISorcererAbility`); button-prompts to wound a non-Sorcerer. |
| `Reaction_03001` | Cesca del Rosso's "after Cesca performs a Sorcerer ability, wound an opposing character" — button-per-opposing-character target picker, with a Pass button. |

## Pattern E — Techniques and Maneuvers

Same as in `create-city-character`. The Character lineage already brings `TechniqueTrait`. Add `IHasManeuvers` + `ManeuverTrait` for maneuvers. Implement under `cards/<expansion>/techniques/` or `cards/<expansion>/maneuvers/`.

## JS Wiring (required for new state classes)

Same as `create-city-character`'s "JS Wiring" section. For every new state, wire BOTH:

- `modules/js/OnEnteringState.<expansion>.js` — highlight selectables, mark already-chosen characters.
- `modules/js/OnUpdateActionButtons.<expansion>.js` — `Confirm` button (`actChooseCardSelected` + `onChooseInPlayCardConfirmed`).
- `modules/js/OnLeavingState.<expansion>.js` — cleanup highlights when leaving the state.

Reusable client-side handlers:
- Character / in-play card selection: `onChooseInPlayCardConfirmed()` + `highlightCardsAsSelectable(ids)`.
- Location selection: `onCityLocationsSelected()` + `makeCityLocationSelectable(element)`.
- Marking a "chosen" character: `dojo.addClass($(`${card.divId}_image`), '_7sfs-chosen')`.

If your state reuses an existing client action (e.g. `onMusterCardSelected`), extend the action map in `modules/js/PlayerActions.js`.

For new expansion JS files (`*.<expansion>.js`), make sure the chain to the master JS files exists — `faf`, `tac`, and `_7s5s` are already chained.

## Pre-Commit Hook (relevant subset)

`.githooks/pre-commit` enforces, for the files you touch when implementing a Character or Leader:

| Pattern | Required |
|---|---|
| `extends CardAction/RiskAction/RiskCityAction` (regex literal — does NOT match `CharacterAction` directly, but the convention still applies) | `createActionResolvedEvent()` somewhere in the class. |
| **Forbidden in `CharacterAction` subclasses** | `$this->setUsed()` / `$this->resetPlayerPassCount()` / `$this->announceAction()` — these run centrally. |
| `extends CardReaction/AttachmentReaction` | `$this->setUsed(` AND `$this->isAvailable(` (literal strings; the hook is grep-based). |
| `implements ISorcererAbility` | both `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()`. |
| Class implementing both `IAbilityThatTargetsCharacters` and `IAbilityThatTargetsCards` | **Forbidden.** Split into two classes. |
| Calls `createAttachmentEquippedEvent()` | Must also call `getRequiredAttachTargetId()`. |

The card class itself (`_NNNNN extends Character` / `extends Leader`) has no hook-mandated calls — the requirements apply to the Action/Reaction subclasses that live next to it.

## Style / Memory Notes

- `getActivePlayerName()` is deprecated — use `$game->getPlayerNameById($id)`.
- `BgaUserException` is deprecated — throw `\Bga\GameFramework\UserException`.
- PHP: PSR-12, 4-space indent, braces on their own line.
- Namespaces:
  - Card class: `Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>`
  - Action:     `...\cards\<expansion>\actions`
  - Reaction:   `...\cards\<expansion>\reactions`
  - State:      `Bga\Games\SeventhSeaCityOfFiveSails\States\<expansion>`
- **"Opposing"** means BOTH different controller AND same location. Never roll your own `ControllerId !=` filter.
- **`TraitNames::$TraitsJson`** (`modules/php/Traits.php`) is the canonical Trait list for "Name a Trait" pickers. Add new Traits in alphabetical order.

## Cross-Cutting Helpers

- `$theah->cardInCity($card): bool` — true when the card is at a city location.
- `$theah->locationInCity(string $location): bool` — true for any of the 5 city locations. Use inside an `EventCardMoved` handler (the card's `Location` field hasn't been updated yet at that point).
- `$theah->getCharactersAtLocation(string $location, bool $includeUncontrolled = false): array` — all characters at a location (default excludes uncontrolled, which is usually what you want).
- `$theah->getCharactersAtLocationByPlayerId(string $location, int $playerId, bool $includeUncontrolled = false): array` — friendly characters at a location.
- `$theah->getOpposingCharactersAtLocation(string $location, int $playerId): array` — opposing = different controller AND same location.
- `$theah->getCharactersInPlayByPlayerId(int $playerId): array` — all characters in play controlled by a player.
- `$theah->getCharactersInCityByPlayerId(int $playerId): array` — characters in city (not Home, not approach).
- `$theah->getAdjacentCityLocations(string $location, bool $includeHome = true): array` — adjacency for move actions.
- `$game->characterIsInDiscardOrLocker(Character $character): bool` — "is this character out of play (discard or locker)?" The Leader-equivalent of `isInPlay`. Gate phase-event handlers on `! characterIsInDiscardOrLocker($this)`.
- `$this->getInjectCode()` — inline-styled card name for notifications (`${card_inject_code}` placeholder).
- `$this->hasTrait(string $trait): bool` — check a trait against `$this->ModifiedTraits`. English trait strings compare directly against `clienttranslate()`-wrapped values.

## Reference Implementations

| File | What it demonstrates |
|---|---|
| `modules/php/cards/_7s5s/_01007.php` (Aldo Bussotti) | **Canonical regular Character.** `initializeFaction`, `handleEvent` listening on `EventCardMoved` / `EventReknownAddedToLocation` / `EventReknownRemovedFromLocation` to keep Influence in sync with current-location Renown, paired with a one-step City Action. |
| `modules/php/cards/_7s5s/_01006.php` (Don Constanzo Scarpa) | **Leader with setup-time reaction.** `EventTableSetup` flow (reveal a Red Hand Thug from your deck), `EventPressureOccuring` listener that flips a pressure-type global, paired with multi-step setup states. |
| `modules/php/cards/_7s5s/_01089.php` (Soline el Gato) | **Leader with passive duel hook + City Reaction.** `EventDuelStarted` / `EventDuelEnd` / `EventDefenderSwapped` / `EventChallengerSwapped` keep the affected character's Finesse modified; `Reaction_01089` adds a button-based "move to adjacent location after an Action resolves" prompt. |
| `modules/php/cards/_7s5s/_01116.php` (Yevgeni) | **Leader with passive duel-stat hook + paired Reactions.** Demonstrates `EventDuelCalculateCombatCardStats`, `actorId == $this->Id` checks, and multi-reaction wiring. |
| `modules/php/cards/_7s5s/_01035.php` (Kaspar Dietrich) | **Leader with parley discount + City Action.** Demonstrates `getParleyDiscount` override and the reveal-from-city-deck-until-trait pattern. |
| `modules/php/cards/faf/_03001.php` (Cesca del Rosso) | **Leader with End-of-Dawn draw + button-based City Reaction + two-step City Action.** `EventPhaseDawnEnding` + `characterIsInDiscardOrLocker` gate, `EventSorcererAbilityPlayed` reaction with source/performer identity check, two-step CharacterAction with the move-wound (heal + wound) recipe. |
| `modules/php/cards/faf/reactions/Reaction_03001.php` | Button-per-opposing-character target picker; `IAbilityThatTargetsCharacters`; `isNotControlledByPlayer` + location filter for "opposing"; `setUsed`/`isAvailable` discipline. |
| `modules/php/cards/faf/actions/Action_03001.php` | Two-step CharacterAction; `cardInCity` gate; `IAbilityThatTargetsCharacters` interface for target hooks; `isValidTargetForAbility` double-checked at step 2; heal+wound recipe; `createActionResolvedEvent` at terminal state. |
| `modules/php/cards/_7s5s/actions/Action_01008.php` | Multi-state Sorcerer City Action with branching (`_2`, `_3`, `_4`). Reference for `ISorcererAbility` + sorcerer-start/played event discipline. |
| `modules/php/cards/_7s5s/actions/Action_01076.php` | Sorcerer Action with `RequiresPerformerSelected = true` and location + character pick. |
| `modules/php/cards/_7s5s/reactions/Reaction_01118.php` (Elina) | Button-based Reaction triggered by `EventSorcererAbilityPlayed`; the canonical "sourceId OR performerId OR targeted-at-my-location" idiom. |
| `modules/php/cards/tac/reactions/Reaction_02001.php` (Andriana) | Sorcerer Reaction (`implements ISorcererAbility, IAbilityThatTargetsCharacters`); demonstrates the start/played event discipline inside a reaction. |
| `modules/php/cards/Leader.php` | Base class. Read for `CrewCap`/`Panache`/`Modified*` fields, the built-in `EventCharacterDestroyed` renown-loss handler, and the `EventSchemeCardRevealed` Panache modifier. Always `parent::handleEvent($event)` first. |
| `modules/php/cards/Character.php` | Parent. `canIntervene` / `canChallenge` defaults, `Wounds` tracking, `Attachments`, `resetCard` copying stats into `Modified*`. |

## When You Finish

1. Walk each clause of the printed Text — confirm each maps to exactly one pattern (Dashed stat / Hard ban / Passive handleEvent / Action / Reaction / Sorcerer ability / Technique / Maneuver). Stat numbers go on the constructor and are not a "pattern."
2. For a Leader, confirm: `"Leader"` is in `Traits`, `CrewCap` and `Panache` are set, no `initializeFaction` call (the framework sets this from player faction selection).
3. For a regular Character, confirm: `initializeFaction(<faction>)` is called, `CardNumber` matches the filename's NNNNN.
4. Every new state class needs all three: the class file in `modules/php/States/<expansion>/`, the constant in `States.php`, and the transition entry in `states.inc.php`'s `HIGH_DRAMA_PLAYER_TURN_EVENTS.transitions`.
5. Only add `"<card>_2"` to `states.inc.php` if you actually call `EventFactory::createTransitionEvent(..., "<card>_2", ...)` somewhere — that lookup table is **only** consulted by `createTransitionEvent`, not by `nextState`. The step 1 → step 2 jump uses `nextState("...")` via the state's own transitions array. Most multi-step actions need only the step-1 entry.
6. State ID convention: `4` + 5-digit `CardNumber` for step 1; append `2`/`3`/`4` for subsequent steps. Don't engineer a separate prefix to dodge hypothetical CD-card collisions (per user feedback memory).
7. Every new state needs JS wiring in `OnEnteringState.<expansion>.js` AND `OnUpdateActionButtons.<expansion>.js`. Add `OnLeavingState.<expansion>.js` reset if you set selection modes or styling. Add to `PlayerActions.js` if you reuse a client action.
8. If you minted a new global, clear it in the matching cleanup state (or defensively at turn boundaries).
9. Mentally run pre-commit hook checks on every file you touched. Especially: `createActionResolvedEvent` in the action, no `setUsed`/`resetPlayerPassCount`/`announceAction` in the `CharacterAction` subclass, `$this->setUsed(` and `$this->isAvailable(` literal strings present in every `CardReaction` subclass, and `createSorcererAbilityStartEvent`/`createSorcererAbilityPlayedEvent` if implementing `ISorcererAbility`.
10. For each Reaction you added, walk the `handleEvent` triggers and confirm all required gates are in place: `isAvailable()`, identity check (`$event->sourceId/performerId/cardId == $owner->Id` etc.), scope gate (`cardInCity($owner)` for City Reactions), and a valid-target precondition if the effect needs a target. Missing the valid-target gate leaves the player with a useless "Decline" prompt.
11. For phase-event listeners on Leaders, confirm a `! characterIsInDiscardOrLocker($this)` guard — a destroyed Leader still has a `ControllerId` set, so `isControlled()` alone is insufficient.
12. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md`. Capture the **WHY** of any non-obvious decision — event-type choice, why the Reaction was not flagged `ISorcererAbility` (or why it was), what the identity-check field is on the event (`sourceId` vs `performerId` vs `cardId`), why a particular state-ID encoding, why a button-based Reaction was chosen over state classes. Read the Cesca journal (`2026-05-13-01-cesca-del-rosso-03001-implementation.md`) first — it captures the End-of-Dawn / Sorcerer-trigger / move-wound / state-ID-encoding decisions in detail.
