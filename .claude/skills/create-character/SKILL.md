---
name: create-character
description: Implement or finish a Character or Leader card (modules/php/cards/<expansion>/_NNNNN.php where the class directly extends Character or Leader). Use this skill whenever the user asks you to implement, finish, scaffold, or wire up a Character/Leader card, or when they reference a faction-deck character whose class extends Character (not CityCharacter) and has unimplemented Text. Triggers on phrases like "implement this character", "implement this leader", "finish _NNNNN" (when it extends Character or Leader), "wire up the City Action on Cesca", "wire up the Reaction on this Leader", or natural-language descriptions of a non-city-deck character (lives in a player's faction deck or is a Leader).
context: fork
model: haiku
effort: low
---

# Creating a Character or Leader

This skill covers cards that directly extend `Character` (regular faction-deck characters) or `Leader` (which itself extends `Character`). These cards live in a player's faction deck (or are placed at game start as the player's Leader) — they are **not** in the city deck.

Canonical references:
- `modules/php/cards/_7s5s/_01007.php` (Aldo Bussotti) — straightforward `Character` with a passive stat-modifying handleEvent and a City Action.
- `modules/php/cards/_7s5s/_01006.php` (Don Constanzo Scarpa) — `Leader` with a setup-time `IHasReactions` Reaction, a passive `EventPressureOccuring` listener, and multi-step setup states.
- `modules/php/cards/_7s5s/_01089.php` (Soline el Gato) — `Leader` with a passive duel-stat hook (`EventDuelStarted` / `EventDefenderSwapped` / `EventChallengerSwapped`) and a button-based City Reaction.
- `modules/php/cards/_7s5s/_01116.php` (Yevgeni) — `Leader` with a passive `EventDuelCalculateCombatCardStats` hook and two paired Reactions.
- `modules/php/cards/faf/_03001.php` (Cesca del Rosso) — `Leader` with an `EventPhaseDawnEnding` draw effect, a button-based City Reaction triggered by `EventSorcererAbilityPlayed`, and a two-step City Action (CharacterAction with state classes).
- `modules/php/cards/faf/_03002.php` (Aja) — `Character` with a City Action that **issues a Combat challenge with a custom challenge type** (intervention/refusal restricted by Finesse) and a **Gambling Technique** that grants Lethal in-duel.
- `modules/php/cards/faf/_03004.php` (Elena Agnelli) — `Character` with a **dynamic-recompute Finesse bonus tied to her dueling line** (+1 Finesse per Sorcery in her dueling line) and a **Technique gated on her combat card having the Sorcery trait** that adds +1 Parry and wounds the adversary.
- `modules/php/cards/faf/_03013.php` (Daniella Dietrich, Witch/Hunter) — `Leader` with a **continuous Action that tags opposing characters with a trait** (Sorcerer) for the duration of the player's turn, a **cost-reduction Reaction** (Faith/Sorcery card at -1 cost, cloned from `Reaction_01116b`), and a **Wound-then-Swap Technique** usable in BOTH challenge and duel contexts (two state classes, swap mechanics inline in `actFromTechniqueWithId`).
- `modules/php/cards/faf/_03014.php` (Kaspar Dietrich, Iron Reforged) — `Character` with a **wound-prevention passive via `eventCheck` on `EventCharacterBeingWounded`** (opponents' abilities cannot wound or move wounds to Kaspar — threat conversion still applies) and a **Technique gated on an Eisenfaust attachment OR an Eisenfaust card in the dueling line** that wounds the adversary.
- `modules/php/cards/faf/_03015.php` (Joern Kietelsson, Fury's Edge) — `Character` with three pure-passive abilities living entirely on the card class (no Action/Reaction/Technique files): a **Forced self-wound on muster** (must hook BOTH `EventCharacterMustered` AND `EventApproachCharacterPlayed`), a **phase-conditional Resolve penalty** ("During Dusk, -3 Resolve" — direct `ModifiedResolve` mutation gated by a private flag because there is no `EventCharacterResolveModifiedEvent` factory), and a **challenge-refused self-heal** on `EventChallengeRejected`.

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
| **"At/During <phase>"** broadly | One of the phase events: `EventNewDay`, `EventPhaseDawnBeginning`, `EventPhaseDawnEnding`, `EventDuskPhaseBegin`, `EventDuskPhaseEnd`, `EventDuskEndOfDay`, `EventPressureOccuring`, `EventDuelStarted`, etc. See "Phase / lifecycle events" below. |
| **"Forced: After <Owner> musters • <effect>"** / **"When <Owner> musters …"** | `handleEvent` on **BOTH** `EventCharacterMustered` AND `EventApproachCharacterPlayed`, OR'd, gated on `characterId == $this->Id`. Mustering via Approach emits a distinct event — hooking only `EventCharacterMustered` silently misses the Approach path. See Pattern A "Forced muster/approach triggers" below. Reference: `_03015` Joern, `_01009` Cirilo. |
| **"During <Phase>, <Owner> has -N Resolve"** (or any phase-conditional **Resolve** modifier) | There is no `createCharacterResolveModifiedEvent` factory — Resolve is not on the event-driven stat list. Directly mutate `$this->ModifiedResolve` on the phase-begin event, gated by a private bool flag (because attachments also mutate `ModifiedResolve` independently), and restore on the phase-end event. Manually emit `createCharacterDestroyedEvent` if the reduction crosses the wounds-equal-resolve threshold (the engine's destruction check only runs inside an `EventCharacterWounded` handler). See Pattern A "Phase-conditional Resolve modifier" below. Reference: `_03015` Joern. **For Combat/Finesse/Influence/Panache, use the matching `createCharacter<Stat>ModifiedEvent` factory instead** — they're event-driven the way Resolve isn't. |
| **"When <Owner>'s challenge is refused, <effect>"** / **"When a challenge to <Owner> is refused …"** | `handleEvent` on `EventChallengeRejected`. Fields: `$event->challengerId` (the one who issued), `$event->targetId` (the one who refused). Identity gate matches whichever role the text names. Reference: `_03015` Joern (challenger side, self-heal), `_01119` Nazem (challenger side, engage the refuser). |
| **"<Owner> has +N [Stat] while wounded"** (or any "while <condition-on-self>" stat bonus) | Pattern A passive with a private bool flag (e.g., `$WoundedCombatBonusApplied`). Hook `EventCharacterWounded` AND `EventCharacterHealed` with `characterId == $this->Id`, call `parent::handleEvent` first (so `$this->Wounds` is up-to-date), then re-derive the boolean and queue `createCharacter<Stat>ModifiedEvent(±1)` only on flag transition. Skip if `IsDying` or in discard/locker. See Pattern A "Stat bonus while a self-condition holds" below. Reference: `_03016` Ise. |
| **"During <Phase>, you may choose not to <auto-action on Owner>"** (opt-out of an auto-emitted event) | Pattern D Reaction listening on the *pre*-event (e.g., `EventCardMoving` for the Dusk move-home) with `sourceId == 0` (auto-emitter signal) + a phase gate (`TURN_PHASE == Game::DUSK`) + the `cancelDeclinedByCardIds` re-queue dance. Cancel the event, clone it, prompt the player; on "Keep" call `setUsed(true)`; on "Decline" re-queue the clone with `cancelDeclinedByCardIds[] = owner->Id` so the reaction doesn't immediately re-catch it. See Pattern D's "Cancel-and-reissue Reaction" subsection. Reference: `Reaction_03016a` (Ise Dusk opt-out), `Reaction_01140` (in-hand RiskReaction sibling). |
| **"<Stat> increases by N"** / **"<Stat> is reduced by N"** | Queue `createCharacter<Stat>ModifiedEvent` (e.g., `createCharacterInfluenceModifiedEvent`). See `_01007` Aldo for renown-driven Influence modification. |
| **"<Owner> has +N[Stat] for each X in her dueling line"** (or any duel-line-derived count) | Pattern A passive with a running `$<Stat>Bonus` field on the card. Recompute at `EventDuelEndOfRound` (the only clean boundary — there is no event fired when a card enters the dueling line; `cards->moveCard` is called directly). Reset at `EventDuelEnd` *before* the line is cleared. Gate on the owner being a duel participant (the dueling line is per-player, not per-character). See Pattern A "Dynamic stat bonuses tied to the dueling line" below. Reference: `_03004` Elena. |
| **"Opponents' abilities cannot wound (or move wounds to) <Owner>"** / "<Owner> ignores wounds from X" | Override `eventCheck` on the card class and zero `$event->wounds` on `EventCharacterBeingWounded`. Distinguish ability-emitted wounds (non-empty `abilityId`) from threat-conversion wounds (empty `abilityId`). See Pattern A "Wound-prevention passive" below. Reference: `_03014` Kaspar (opponent's-ability scope), `_01069` Maxime (own-Sorcerer scope), `_01153` Breastplate (in-duel reduction-by-one). |
| **`<b>Action:</b>`** / **`<b>City Action:</b>`** | Implement `IHasActions`, `use ActionTrait`, create `actions/Action_NNNNN.php` extending `CharacterAction`. State class(es) + JS wiring per Pattern C. **"City Action" only differs by the `cardInCity` gate** in `isAvailableToPlayer`. |
| **"Issue a [stat] challenge to target …"** (any flavor) | CharacterAction that sets `CHOSEN_PERFORMER`/`CHOSEN_TARGET`/`CHALLENGE_STAT`/`CHALLENGE_TYPE` and queues a transition into the challenge sub-state machine. See Pattern F. |
| **"Your <Trait> at this location issues a challenge"** (performer ≠ owner) | Two-step Pattern F: step 1 picks the *performer*, step 2 picks the target at the *performer's* location. If text doesn't print "Engage [self]", emit the engage event conditionally in step 2 and keep the new challenge type OUT of the auto-engage list. See Pattern F's "Performer ≠ action owner" subsection. Reference: `Action_03003`. |
| **`<b>Reaction:</b>`** / **`<b>City Reaction:</b>`** | Implement `IHasReactions`, `use ReactionTrait`, create `reactions/Reaction_NNNNN.php` extending `CardReaction`. Button-based reactions need **no** state class, **no** `states.inc.php` edits, **no** JS wiring. See Pattern D. |
| **"Reaction: After <enemy/X> character moves to this location • <effect>"** | Pattern D Reaction listening on `EventCardMoved` (past-tense). Gates: `event.cardId != owner.Id` (skip Owner's self-move), `event.toLocation == owner.Location`, `cardInCity(owner)` (enemies can't enter your Home), `$character instanceof Character`, `ControllerId != 0`, and the "enemy" controller check (`!= owner.ControllerId`). Pair with a valid-effect-target precondition. See Pattern D's "After a character moves to this location" subsection. Reference: `Reaction_03016b` (Ise). For the *self-moves* analogue ("after this character moves to a new location"), see `_01067` Jean Urbain and `_02022` Stranahan. |
| **"Move another character you control to this location"** (effect) | Queue `createCardMovingEvent($character.ControllerId, $character.Id, $character.Location, $owner.Location, $engage, $owner.Id, $this->Id)` for the chosen mover. Eligible movers = `getCharactersInPlayByPlayerId($owner.ControllerId)` minus the owner herself minus characters already at her location. Don't use any pull/teleport helper — there isn't one; the standard move event handles all the bookkeeping. Reference: `Reaction_03016b` (other character to here), `Reaction_01039` (move self to adjacent). |
| **"Reaction: … at -N cost"** / **"… pay N Wealth"** | Pattern D Reaction with **in-reaction click-to-pay** wealth tracking. Don't use `PAY_STATE_PLAY_BRUTE` — it's tied to the player-turn state cycle. See Pattern D's "Reactions that need to pay a wealth cost" subsection. Reference: `Reaction_03003`. |
| **"Reaction: Put a different X into play from your hand or discard pile"** | Pattern D Reaction. Filter eligibles separately from `LOCATION_HAND` and `getPlayerDiscardDeckName(...)`, exclude the just-destroyed card by id. `createCharacterMusteredEvent` does the actual move; `createCardRemovedFromPlayerDiscardPileEvent` is notification-only (fire it before the muster so JS clients sync correctly). Reference: `Reaction_03003`, `Action_01024` (Bravos). |
| **`<b>Sorcerer …</b>`** (Sorcerer Action / Sorcerer Reaction) | The Action/Reaction class additionally `implements ISorcererAbility`. **Must** call `createSorcererAbilityStartEvent()` and `createSorcererAbilityPlayedEvent()` (pre-commit hook enforces). See `Action_01076` and `Reaction_02001`. |
| **`<b>Technique:</b>` / `<b>Maneuver:</b>`** | The Character lineage already brings `TechniqueTrait`. Add `IHasManeuvers` + `ManeuverTrait` for maneuvers. Implement under `cards/<expansion>/techniques/` or `cards/<expansion>/maneuvers/`. See Pattern E. |
| **`<b>Gambling Technique:</b>`** | Same as Technique, but `isAvailableToPlayer` additionally gates on `Game::DUEL_GAMBLED` (actor gambled for their combat card this round). See Pattern E. |

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

### Dynamic stat bonuses tied to the dueling line

For text like "Elena has +1[Finesse] for each **Sorcery** in her dueling line" — the bonus changes round-to-round as cards enter the dueling line. There is no event fired when a card enters `LOCATION_DUELING_LINE` (`FrameworkActionsTrait::actDuelActionChooseCombatCard` and the maneuver paths call `$this->cards->moveCard(...)` directly, bypassing the `EventCardMoved` path). So we recompute at duel-round boundaries instead.

Pattern (mirror `_03004` Elena):

```php
public int $FinesseBonus = 0;   // running state — survives across reaction-loop iterations via IsUpdated

public function handleEvent(Event $event)
{
    parent::handleEvent($event);
    if ($this->ControllerId == 0) return;

    if ($event instanceof EventDuelEndOfRound)
    {
        $this->recomputeFinesseBonus($event->theah);
    }

    if ($event instanceof EventDuelEnd)
    {
        // Subtract the running bonus directly; do NOT recount.
        // EventDuelEnd fires BEFORE the dueling-line cards are discarded
        // in stDuelEnd, so a recount would still see Sorcery cards.
        $this->applyFinesseDelta(0, $event->theah);
    }
}

private function recomputeFinesseBonus(Theah $theah): void
{
    // "Her dueling line" — LOCATION_DUELING_LINE is keyed per-player_id,
    // not per character. If a different one of this player's characters is
    // the duelist, the cards in the line belong to *them*, not the owner.
    $challengerId = $theah->getDuelChallengerId();
    $defenderId   = $theah->getDuelDefenderId();
    if ($this->Id != $challengerId && $this->Id != $defenderId)
    {
        $this->applyFinesseDelta(0, $theah);
        return;
    }

    $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $this->ControllerId);
    $count = 0;
    foreach ($cards as $card)
    {
        if ($card->hasTrait("Sorcery"))  // or whatever trait the card text names
        {
            $count++;
        }
    }
    $this->applyFinesseDelta($count, $theah);
}

private function applyFinesseDelta(int $newBonus, Theah $theah): void
{
    $delta = $newBonus - $this->FinesseBonus;
    if ($delta == 0) return;

    $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
        $this->ControllerId, $this->Id,
        $this->ModifiedFinesse, $this->ModifiedFinesse + $delta,
        $this->getInjectCode()
    );
    $theah->queueEvent($finesseEvent);

    $this->FinesseBonus = $newBonus;
    $this->IsUpdated = true;
}
```

WHY recompute at `EventDuelEndOfRound` (not on every event):

- It is the cleanest boundary: both players' combat cards have resolved into the dueling line, and the *next* round's gambling hasn't fired yet. Gamble capacity is `ModifiedFinesse - gamblesCount` (see `FrameworkActionsTrait::actChooseGambleCard`) — recomputing here means the bonus is correct *before the next round's gambling*, which is when Finesse matters in a duel.
- Recomputing on a calc event (e.g. `EventDuelCalculateCombatCardStats`) is wrong because that event doesn't expose Finesse — it exposes parry/riposte/thrust on the combat card. The card text modifies *Finesse* itself; both consumers (gamble capacity and any other card reading `ModifiedFinesse`) must see the updated stat.

WHY reset at `EventDuelEnd` via `applyFinesseDelta(0, ...)` and NOT a recount:

- `StatesTrait::stDuelEnd` queues `EventDuelEnd` BEFORE queueing the `CardDiscardedFromHand` events that empty the dueling line. So at `EventDuelEnd` handling time, the dueling line still contains the round's Sorcery cards — a naive recount would re-apply the bonus instead of clearing it. Directly applying `delta = 0 - currentBonus` (the inverse-event approach) is correct.

WHY gate on the owner being a duel participant:

- `LOCATION_DUELING_LINE` is keyed per player_id in the deck table, not per character. If Elena's player has a *different* character dueling (e.g. Aja), Aja's combat cards land in the *same per-player dueling line* — a naive recount would credit Elena with cards she didn't play. Card text says "her dueling line", so gate on `$this->Id == challengerId || $this->Id == defenderId`.

Edge cases (Elena journal `2026-05-16-01-elena-agnelli-03004-implementation.md` flags these explicitly — re-read it before you implement a similar effect):

- **Card pulled from the dueling line mid-round.** The recount catches it at end-of-round; if anything pulls it earlier (rare), the bonus stays inflated for the rest of the current round. Acceptable — no event lets us hook arbitrary departures from the line.
- **Owner swapped into / out of an in-progress duel.** Not handled by the basic pattern. The next `EventDuelEndOfRound` recomputes from the player's line, which may already contain cards played by a prior duelist. Flag for QA if the text is sensitive to this; usually unimportant.
- **Owner destroyed mid-duel.** `EventDuelEnd` still fires and resets the bonus. `ModifiedFinesse` on a discarded card doesn't affect anything else, so no special handling needed.

### Forced muster/approach triggers — hook BOTH `EventCharacterMustered` AND `EventApproachCharacterPlayed`

For any "after X musters" / "when X musters" / "Forced after X musters" trigger, the conditional MUST hook both events:

```php
if (($event instanceof EventCharacterMustered
        || $event instanceof EventApproachCharacterPlayed)
    && $event->characterId == $this->Id)
{
    // ... effect ...
}
```

WHY both: the printed text says "musters" colloquially to cover every way a character enters play, but the engine emits a distinct `EventApproachCharacterPlayed` when an Approach card puts a character into play vs. the standard muster path (`createCharacterMusteredEvent` in the recruit / brute / muster-from-action flows). Hooking only `EventCharacterMustered` silently skips the Forced trigger when the character enters via Approach. The user has flagged this as a definitional miss — it's not a polish item.

Reference: `modules/php/cards/_7s5s/_01009.php` (Cirilo) line ~57 — the canonical OR pattern for "I added Brute to my Mercenaries when I muster or come in via Approach." `_03015` Joern uses the same pair for his self-wound Forced trigger.

If the trigger is "after **another** character musters" (not self), still hook both events; only the `characterId` filter changes.

### Phase-conditional Resolve modifier — direct `ModifiedResolve` mutation, no event factory

For text like "During Dusk, <Owner> has -N Resolve" or "At the beginning of Dawn, <Owner> has +N Resolve" — the engine does NOT have an `EventCharacterResolveModifiedEvent` factory. Resolve is not event-driven the way Combat/Finesse/Influence are. The pattern:

```php
private bool $DuskResolvePenaltyApplied = false;   // running flag — survives via IsUpdated

public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventDuskPhaseBegin
        && ! $this->DuskResolvePenaltyApplied
        && $this->isControlled())
    {
        $this->ModifiedResolve -= 3;
        $this->DuskResolvePenaltyApplied = true;
        $this->IsUpdated = true;

        $event->theah->game->notify->all("message",
            clienttranslate('${character_inject_code}: During Dusk, -3 Resolve (now ${resolve}).'),
            [
                "character_inject_code" => $this->getInjectCode(),
                "resolve"               => $this->ModifiedResolve,
            ]
        );

        // WHY: Character::handleEvent (~line 256) only triggers destruction inside
        // an EventCharacterWounded handler. If the Resolve drop crosses the
        // wounds-equal-resolve threshold with no concurrent wound event, the
        // engine won't notice. Mirror EventHub.php:251 (the unequip path):
        if ($this->Wounds >= $this->ModifiedResolve && ! $this->IsDying)
        {
            $this->IsDying = true;
            $this->unEquipAllAttachments($event->theah);
            $destroyEvent = EventFactory::createCharacterDestroyedEvent(
                $this->ControllerId, $this->Id, $this->getInjectCode()
            );
            $event->theah->queueEvent($destroyEvent);
        }
    }

    if ($event instanceof EventDuskEndOfDay && $this->DuskResolvePenaltyApplied)
    {
        $this->ModifiedResolve += 3;
        $this->DuskResolvePenaltyApplied = false;
        $this->IsUpdated = true;
    }
}
```

WHY a private bool flag (and NOT a recompute or a queued event):

- **No `createCharacterResolveModifiedEvent` factory exists.** Combat/Finesse/Influence each have `createCharacter<Stat>ModifiedEvent` factories; Resolve does not. The codebase mutates `ModifiedResolve` directly — see `Character::addAttachment` line 166 (`$this->ModifiedResolve += $attachment->ResolveModifier`).
- **Attachments mutate `ModifiedResolve` independently.** A naive `-= 3` / `+= 3` is fine if Dusk events are perfectly paired, but skipped/duplicated phase begins are not. A flag makes the apply idempotent: only one `-= 3` per Dusk, regardless of how the events fire.
- **Pattern A's "Dynamic stat bonuses" recompute approach (Elena) doesn't fit here.** Resolve has no naturally recurring "this card's snapshot of the world changes" event the way a dueling-line count does. The trigger is a phase boundary, not a stream of state changes.

WHY the manual destruction check:

- `Character::handleEvent`'s destruction check (line ~256) runs ONLY inside `EventCharacterWounded`. Lowering `ModifiedResolve` past `Wounds` outside a wound event silently leaves the character alive at `Wounds >= Resolve`.
- The card text's parenthetical reminder "(Characters are destroyed when their wounds equal their Resolve)" makes the rule explicit — the threshold check applies whenever it's crossed, not only on a wound event.
- Mirror the EventHub unequip pattern (`EventHub.php` ~251): `if ($character->Wounds >= $character->ModifiedResolve && ! $character->IsDying)` → flip `IsDying`, unequip attachments, queue `createCharacterDestroyedEvent`.

WHY restore unconditional on the flag (not `isControlled()` or `cardInCity`):

- If the character is destroyed mid-Dusk, the flag is still true and the EndOfDay restore still runs. The destroyed-character object is in the Locker; restoring its in-memory `ModifiedResolve` is harmless. Re-instantiation on re-recruit goes through the constructor + `resetCard()` which sets `ModifiedResolve = Resolve` anyway, but the unconditional restore is a defense against any hypothetical "return from Locker" path that bypasses construction.

WHY `EventDuskEndOfDay` for the restore (not `EventDuskPhaseEnd`):

- Dusk lifecycle is: `stDuskPhaseBegin` → `EventDuskPhaseBegin` → (reactions, cleanup, hand-discard, purgatory-discard) → `stDuskPhaseEnd` → `EventDuskPhaseEnd` → `stDuskEndOfDay` → `EventDuskEndOfDay`.
- "During Dusk" should cover every step in between. `EventDuskEndOfDay` is the last event of the day — restoring there guarantees nothing inside Dusk sees the restored value early.
- `EventDuskPhaseEnd` would work too (Brute discard at end-of-day doesn't read Resolve), but EndOfDay is the strict latest safe point.

Reference: `_03015` Joern Kietelsson. Note that the same pattern applies in reverse for "+N Resolve" phase-conditional buffs.

### Wound-prevention passive — `eventCheck` on `EventCharacterBeingWounded`

For text like "<Owner> ignores wounds from <X>" or "<Y>'s abilities cannot wound <Owner>" (`_03014` Kaspar, `_01069` Maxime, `_01153` Breastplate). Override `eventCheck` on the card class — NOT `handleEvent` — and zero `$event->wounds` on `EventCharacterBeingWounded`.

```php
public function eventCheck(Event $event)
{
    parent::eventCheck($event);   // propagates to your Techniques/Reactions/etc.

    if (! ($event instanceof EventCharacterBeingWounded)) return;
    if ($event->characterId != $this->Id || $event->wounds <= 0) return;

    // "(Threat is still converted to wounds.)" Threat conversion (StatesTrait
    // ~line 1500) emits with empty $abilityId; only block ability-emitted wounds.
    if ($event->abilityId == '') return;

    $source = $event->theah->getCardById($event->sourceId);
    if ($source == null || $source->ControllerId == 0
        || $source->ControllerId == $this->ControllerId) return;

    $oldWounds = $event->wounds;
    $event->wounds = 0;

    $event->theah->game->notify->all("message", clienttranslate(
        '${character_inject_code}: Opponents\' abilities cannot wound. '
        . '${oldWounds} wound(s) ignored from ${source_inject_code}.'
    ), [
        "character_inject_code" => $this->getInjectCode(),
        "source_inject_code"    => $source->getInjectCode(),
        "oldWounds"             => $oldWounds,
    ]);
}
```

WHY `eventCheck` on the *Being*-tense event (not `handleEvent` on `EventCharacterWounded`):

- `EventHub` only emits the past-tense `EventCharacterWounded` when `$event->wounds > 0` (see `EventHub.php` ~1988). Setting `wounds = 0` in `eventCheck` on `EventCharacterBeingWounded` means the past-tense event is *never created* — no other reaction/passive that listens to "when X is wounded" thinks Kaspar took a wound. Cleaner than Maxime's `handleEvent` pattern of skipping `parent::handleEvent` (which still propagates the event to other `Character::handleEvent` listeners).
- `Card::eventCheck` (Card.php ~371) is the framework's per-card check hook and runs BEFORE `handleEvent`. Override it on the *card class*, not on a Technique/Reaction — the passive is the card itself, not an ability.
- Always call `parent::eventCheck($event)` first — it dispatches to any Techniques/Reactions/Maneuvers/Actions on the card.

WHY `abilityId == ''` is the threat-conversion signal:

- The round-end threat-to-wounds conversion (`StatesTrait::stDuelEndOfRound` ~line 1500) emits `createCharacterBeingWoundedEvent($actor->Id, $adversary->Id, $wounds, $reason)` — note the missing 5th positional argument, so `abilityId` defaults to `''`.
- Every ability that emits a wound passes the ability id as the 5th argument (`Action_02010`, `Technique_03004`, all the Sorcerer Actions/Reactions). So `abilityId != ''` is a clean "this wound is from an ability" filter without needing to grep call sites.

WHY `source.ControllerId != $this->ControllerId` is "opponent's ability":

- The source card's `ControllerId` is the controlling player at the moment the wound is queued. For an opponent's Action/Reaction/Technique/Maneuver/Sorcery card causing the wound, that's a different player from Kaspar's controller.
- `source.ControllerId == 0` means uncontrolled (rare — usually a card in transit between zones). Treat that as "not an opponent" and let it through; nothing in the codebase emits an ability-typed wound from an uncontrolled source as of this writing, but the guard is cheap.
- For wound *movement* abilities (the heal+wound recipe, `Action_02010`): the wound half is queued from the action's owner with the action's id as `abilityId`. Same filter blocks it. Kaspar's text "or move wounds to Kaspar" comes free with the wound-block — don't add a special "move-wounds" handler.

Scope-matters: Maxime's text is about "abilities he performs" (own scope via `CHOSEN_PERFORMER` or Sorcery-trait source), so Maxime checks the source's identity / trait. Kaspar's text is about "opponents' abilities" (controller scope), so Kaspar checks the source's controller. Read the text literally — don't reuse the wrong helper.

For partial reduction (Breastplate `_01153` reduces by 1, not to 0), the same `eventCheck` pattern applies — just `$event->wounds--` with a floor at 0. Breastplate additionally tracks `$hasBlockedWound` to enforce "first time this duel."

### Stat bonus while a self-condition holds — flag-based recompute on wound/heal

For text like "<Owner> has +1 [Combat] while wounded" (`_03016` Ise). The condition is *on the Owner herself* (wounded / engaged / has-attachment / etc.), and the bonus should flip on/off as the condition changes.

Pattern (mirror `_03016` Ise):

```php
public bool $WoundedCombatBonusApplied = false;   // running flag — survives via IsUpdated

public function handleEvent(Event $event)
{
    parent::handleEvent($event);   // parent updates $this->Wounds BEFORE this runs

    if (($event instanceof EventCharacterWounded || $event instanceof EventCharacterHealed)
        && $event->characterId == $this->Id)
    {
        $this->recomputeWoundedCombatBonus($event->theah);
    }
}

private function recomputeWoundedCombatBonus(Theah $theah): void
{
    if ($this->ControllerId == 0) return;
    if ($theah->game->characterIsInDiscardOrLocker($this)) return;
    if ($this->IsDying) return;

    $shouldHaveBonus = $this->Wounds > 0;

    if ($shouldHaveBonus && ! $this->WoundedCombatBonusApplied)
    {
        $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
            $this->ControllerId, $this->Id,
            $this->ModifiedCombat, $this->ModifiedCombat + 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($combatEvent);
        $this->WoundedCombatBonusApplied = true;
        $this->IsUpdated = true;
    }
    else if (! $shouldHaveBonus && $this->WoundedCombatBonusApplied)
    {
        $combatEvent = EventFactory::createCharacterCombatModifiedEvent(
            $this->ControllerId, $this->Id,
            $this->ModifiedCombat, $this->ModifiedCombat - 1,
            $this->getInjectCode()
        );
        $theah->queueEvent($combatEvent);
        $this->WoundedCombatBonusApplied = false;
        $this->IsUpdated = true;
    }
}
```

WHY a flag + delta-event instead of recompute-from-base:

- Attachments and other cards also mutate `ModifiedCombat`. A naive "set `ModifiedCombat = Combat + (wounded ? 1 : 0)`" would clobber a Weapon attachment's +1 Combat that flowed in via its own `CombatModified` event. The delta-on-transition pattern plays nicely with the rest of the stat-modifier ecosystem: each modifier only adjusts what *it* contributed.
- Mirrors `_01089` Soline el Gato's `lowerFinesse`/`raiseFinesse` shape, which uses the same per-source-bookkeeping discipline.

WHY `parent::handleEvent` BEFORE checking `$this->Wounds`:

- `Character::handleEvent` (Character.php ~242) does `$this->Wounds += $event->wounds` (or `-=` for heal) inside its own `EventCharacterWounded`/`EventCharacterHealed` branches. Our recompute MUST run *after* that update — `parent::handleEvent($event)` first is non-negotiable.

WHY skip on `IsDying` / `characterIsInDiscardOrLocker`:

- If the wound event drove her to Wounds >= ModifiedResolve, `Character::handleEvent` sets `IsDying = true` and queues `EventCharacterDestroyed`. Queueing a combat bonus at that point is wasted work — her `ModifiedCombat` is irrelevant. When she re-instantiates (next game/recruit), `resetCard` re-derives `ModifiedCombat = Combat`, and the bonus flag is default-false on the fresh instance.

Adapting for other stats / conditions:
- `+N [Finesse]` → `createCharacterFinesseModifedEvent` (note framework typo: `Modifed`).
- `+N [Influence]` → `createCharacterInfluenceModifiedEvent`.
- `+N [Resolve]` → no factory exists. Use Joern's `$this->ModifiedResolve` direct-mutation pattern instead (Pattern A's "Phase-conditional Resolve modifier").
- `+N [Panache]` (Leader only) → `createCharacterPanacheModifiedEvent`.

For non-wound conditions (e.g., "while engaged"), swap the trigger event (`EventCardEngaged` / `EventCardEngarded`) and the `$shouldHaveBonus` predicate. Same flag discipline.

### "Opposing characters are considered <Trait>" — tag opposing characters, don't override hasTrait

For text like "While using your abilities, characters opposing <Owner> may be considered <Trait>" (Daniella Dietrich `_03013`): the trait must light up on *opposing* characters, not on the owner. The Uwe Zimmerman `_01043` `hasTrait` override pattern is the WRONG fit — that pattern lights up the *receiver* of `hasTrait`, so it only works when the card being considered is the card whose `hasTrait` was overridden. For the opposing-direction case, mirror the Wilhelm Dünst `Action_02013` pattern instead: **mutate the opposing characters' `ModifiedTraits` directly via `addTrait` / `removeTrait`**, keep a tracked set of the ids you tagged, and untag at the scope boundary.

Pattern (typically lives on a continuous Action; see the next subsection):

```php
private array $TaggedOpposingIds = [];  // ids we added the trait to

private function tagOpposingAs(string $trait, Theah $theah): void
{
    $owner = $this->getOwningCharacter($theah);
    if ($owner === null) return;
    $game = $theah->game;

    $opposing = array_filter(
        $theah->getCharactersAtLocation($owner->Location),
        fn($c) => $c->ControllerId !== $owner->ControllerId
            && ! in_array($c->Id, $this->TaggedOpposingIds, true)  // dedup — see WHY below
            && ! $c->hasTrait($trait)
    );
    foreach ($opposing as $c)
    {
        $c->addTrait($game, $trait);
        $this->TaggedOpposingIds[] = $c->Id;
    }
}

private function untagOpposing(string $trait, Theah $theah): void
{
    if (empty($this->TaggedOpposingIds)) return;
    $game = $theah->game;
    foreach ($this->TaggedOpposingIds as $cid)
    {
        $c = $theah->getCharacterById($cid);
        if ($c !== null) $c->removeTrait($game, $trait);
    }
    $this->TaggedOpposingIds = [];
}
```

WHY tracked-set + skip-already-tagged:

- `Card::addTrait` (in `modules/php/cards/Card.php`) appends to `$this->ModifiedTraits` **without** deduping. Two `addTrait("Sorcerer")` calls leave two `"Sorcerer"` entries in the array, and `removeTrait` removes only one (`array_search` returns the first match). Re-tagging on every ability-use event without a guard would pile up duplicates that never fully clear.
- `! $c->hasTrait($trait)` is the cheap "they already have it printed/granted" check; `! in_array($c->Id, ...)` is the cheap "we already granted it" check. Use both — a character could legitimately have the trait printed before our grant fires.

WHY "opposing" = controller-mismatch + location-match: this matches `Theah::getOpposingCharactersAtLocation` and the codebase-wide definition (see the memory note). Don't roll your own filter; just pull from the location and exclude same-controller.

Scope boundary for untagging: the scope is whatever the card text says. Daniella's "while using your abilities" reads as "for the duration of your turn" once you map ability-use to turn-scope — `EventPlayerTurnEnd` is the natural clear. Add `EventCardMoved` / `EventCharacterDestroyed` cleanups for the owner so an outstanding tag set doesn't get orphaned on a character that no longer opposes her.

### Continuous Action — passive ability that lives on an `Action` class but never appears in the UI

For passive abilities that the framework should treat as an ability but the player never directly activates (e.g., Daniella Dietrich `_03013`'s trait-tagging passive), mount the logic on a `CharacterAction` subclass attached via `IHasActions` / `ActionTrait`. Make `isAvailableToPlayer` return false so it never shows in the action menu — the Action is purely a `handleEvent` listener.

```php
class Action_NNNNN extends CharacterAction
{
    /** @var int[] running state for the passive (e.g. tagged character ids) */
    private array $TaggedOpposingIds = [];

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("(Continuous) <plain-English description of what it does>");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        // Passive — never offered from the action menu. Returning false hides
        // it but does not suppress handleEvent.
        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        // ... trait-tagging / passive work ...

        if ($event instanceof EventPlayerTurnEnd)
        {
            $this->untagOpposing("Sorcerer", $event->theah);

            // "Continuous" — clear Used at the same boundary so the parent
            // CardAction::handleEvent's EventDuskEndOfDay reset isn't the only
            // thing keeping the action alive across turns.
            $this->setUsed($event->theah, false);
        }
    }
}
```

Wiring on the card:

```php
class _NNNNN extends Leader implements IHasActions, IHasReactions, IHasTechniques
{
    use ActionTrait;
    use ReactionTrait;
    use TechniqueTrait;

    // ... constructor ...
    $this->Actions = [ new Action_NNNNN() ];
}
```

Where to place the passive's `handleEvent` — the Action or the card class? Either works mechanically, but **prefer the Action** when the passive is conceptually an ability that the card text *names* as an "Action / Forced / Maneuver / Passive / Technique / Reaction." That keeps the responsibility scoped to one file and lets the card class's `handleEvent` stay minimal (just `parent::handleEvent($event)` for the Leader-inherited renown/Panache logic). The card class's `handleEvent` is still where you put cross-ability bookkeeping that doesn't belong to any single ability.

WHY pre-commit doesn't complain about the missing `createActionResolvedEvent()`: the hook's regex matches `extends CardAction/RiskAction/RiskCityAction` literally — `CharacterAction` isn't on that list (see the Pre-Commit Hook section). A continuous Action that never goes through normal action resolution legitimately doesn't fire `createActionResolvedEvent`.

WHY `setUsed(false)` on a continuous Action: the parent `CardAction::handleEvent` already resets `Used` on `EventDuskEndOfDay`, which is fine for once-per-day actions. For a "continuous" Action that must survive multiple ability uses within the same turn, explicitly flip `Used` back to `false` at the same scope boundary you untag at (typically `EventPlayerTurnEnd`). The Reaction analogue is "do not call `setUsed(true)` at all" — see `Reaction_01196` "Continuous". Both forms work; the Action variant needs the explicit reset because `parent::handleEvent`'s once-per-day reset isn't frequent enough.

Reference: `Action_03013` (Daniella Dietrich) — Continuous Action that tags opposing characters with "Sorcerer" on ability-start events and untags at `EventPlayerTurnEnd`. `Action_01090` (Yuri Pyetrovich) — Continuous Action that pre-activates a paired Reaction; opposite shape (user-triggered, but immediately flips `Used` back to false).

### Phase / lifecycle events worth knowing

| Event | When it fires | Typical use |
|---|---|---|
| `EventNewDay` | Start of each Day | Reset per-day flags |
| `EventPhaseDawnBeginning` | Dawn begins | "At the beginning of Dawn …" |
| `EventPhaseDawnEnding` | Dawn ends (fired by `StatesTrait::stDawnEnding`) | "At the end of Dawn …" |
| `EventDuskPhaseBegin` | Dusk phase begins (fired by `StatesTrait::stDuskPhaseBegin`, BEFORE characters route home) | "At the beginning of Dusk …" / start of a phase-conditional Resolve penalty (Joern `_03015`). |
| `EventDuskPhaseEnd` | After cleanup/discard, before `EventDuskEndOfDay` | Less commonly used; `EventDuskEndOfDay` is usually the right "Dusk is over" hook |
| `EventDuskEndOfDay` | End of Day (Brute discards happen here) | Reset per-day Used flags (base classes handle this for Actions/Reactions automatically); restore phase-conditional Resolve penalties |
| `EventCharacterMustered` | A character was just mustered (recruit / brute / `Action_01024` / etc.) | "Forced after X musters …" — **always pair with `EventApproachCharacterPlayed`** (see Pattern A's "Forced muster/approach triggers" subsection) |
| `EventApproachCharacterPlayed` | A character entered play via an Approach card | Same triggers as `EventCharacterMustered`; hook the pair |
| `EventChallengeRejected` | A challenge was refused (`$event->challengerId` issued, `$event->targetId` refused) | "When <Owner>'s challenge is refused …" / "When a challenge to <Owner> is refused …". Reference: `_03015` Joern (self-heal), `_01119` Nazem (engage the refuser). |
| `EventPressureOccuring` | A pressure is happening at a location | "When pressuring …", `_01006` Don Constanzo |
| `EventDuelStarted` / `EventDuelEnd` | Duel boundaries | Passive duel stat modifiers, `_01089`. **`EventDuelEnd` fires BEFORE the dueling line is cleared** in `stDuelEnd` (the discard events are queued AFTER it), so a recount-based dueling-line effect must reset via direct inverse-event, not via re-reading the line. |
| `EventDuelEndOfRound` | A duel round just ended; both combat cards are in the dueling line; the next round hasn't begun | Recompute "for each X in my dueling line" running bonuses *before* the next round's gambling. `_03004` Elena. |
| `EventDuelCalculateCombatCardStats` | Combat card stats are being computed for a duel | "+X to combat card stats", `_01116` Yevgeni |
| `EventChallengerSwapped` / `EventDefenderSwapped` | A challenge had its participant changed | Re-evaluate any duel-time modifier you applied, `_01089` |
| `EventTableSetup` | Game setup | Initial decisions like "during setup, reveal X from your deck", `_01006` |
| `EventSchemeCardRevealed` | A scheme is revealed | Leaders react via the base `Leader::handleEvent`; only override if you have card-specific logic |
| `EventCharacterDestroyed` | A character is destroyed | Leaders have built-in renown-loss logic in `Leader::handleEvent` — don't reinvent |
| `EventSorcererAbilityPlayed` | A sorcerer ability resolved | "After <X> performs a Sorcerer ability …" reactions, Pattern D below |
| `EventActionResolved` | An action just resolved | "After an Action resolves …" reactions, `Reaction_01089` |
| `EventCardMoving` / `EventCardMoved` | Pre / past tense of a card-to-location move | `Moving` is cancelable (`$event->canceled = true`) — use for opt-out Reactions (Pattern D "Cancel-and-reissue"). `Moved` is the past-tense receiver — use for "after X moves to/from this location" triggers. The Dusk auto-move emits `Moving` with `$sourceId == 0`; ability-driven moves pass a non-zero sourceId. Reference: `Reaction_03016a` (cancel), `Reaction_03016b` (react to). |

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

**Exception: "issue a challenge" actions DO need a `<card>_2` entry.** See Pattern F — those actions cross from player-turn states into the challenge sub-state machine via `createTransitionEvent("<card>_2", ...)`, so the lookup table is actually consulted.

### Named transitions, and the `""` (empty) transition rule

A state's `transitions` array maps a transition name (the argument you pass to `nextState(...)`) to a destination state. **An empty-string transition `"" => ...` is only valid when it's the ONLY transition out of the state.** With multiple transitions, name each one:

```php
// CORRECT — multiple named transitions
transitions: [
    "zombie"       => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
    "targetChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
],

// WRONG — mixing "" with another named transition errors out
transitions: [
    ""       => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
    "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
],
```

When the zombie path is the only escape hatch besides the success path (typical for picker states), give both a name. Wilhelm's `State_highDramaPhase02013_2` gets away with the single-`""` form because it doesn't declare a separate zombie transition — its `zombie()` method calls `nextState()` (empty), which lands on `""`. If you want a distinct zombie path, you must name both.

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

## Pattern F — Issuing a Challenge from a City Action

For text like **"Engage <self> • Issue a <Stat> challenge to target opposing character"** (Aja, Wilhelm Dunst, Torvo Espada). The CharacterAction sets a handful of globals, then transitions into the standard challenge sub-state machine, which handles intervention, refusal, technique activation, and threat resolution. The hard part is wiring the new flow without re-implementing any of the challenge machinery.

References: `Action_02013` (Wilhelm Dünst), `Action_02034` (Torvo Espada), `Action_03002` (Aja).

### Action skeleton

```php
class Action_NNNNN extends CharacterAction implements IAbilityThatTargetsCharacters
{
    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) return false;

        $owner = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($owner)) return false;          // City Action
        if (! $owner->canChallenge() || $owner->Engaged) return false;  // engagement is the cost

        return count($theah->getOpposingCharactersAtLocation($owner->Location, $owner->ControllerId)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "NNNNN", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_NNNNN) {
            $target = $game->theah->getCharacterById($id);
            [$isValid, $err] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid) throw new UserException($err);

            $owner = $this->getOwningCharacter($game->theah);

            $game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);
            $game->globals->set(Game::CHOSEN_TARGET,    $target->Id);
            $game->globals->set(Game::CHALLENGE_STAT,   Game::STAT_COMBAT);  // or STAT_FINESSE / STAT_INFLUENCE
            $game->globals->set(Game::CHALLENGE_TYPE,   Game::NORMAL_CHALLENGE_TYPE);  // or your new type

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "NNNNN_2", $this->Id);
            $game->theah->queueEvent($transition);

            $game->gamestate->nextState("targetChosen");
        }
    }
}
```

### State + states.inc.php wiring

- State class `State_highDramaPhaseNNNNN` is a standard target-picker (`StateType::ACTIVE_PLAYER`). Both `"zombie"` and `"targetChosen"` (or any named transition you use) point to `HIGH_DRAMA_PLAYER_TURN_EVENTS`:
  ```php
  transitions: [
      "zombie"       => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
      "targetChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
  ],
  ```
- `states.inc.php` needs **two** entries (this is the exception to the "don't add `_2`" rule):
  ```php
  "NNNNN"   => States::HIGH_DRAMA_PLAYER_TURN_NNNNN,
  "NNNNN_2" => States::HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE,
  ```

WHY this flow shape:
- The action queues a `createTransitionEvent("NNNNN_2")` AND calls `nextState("targetChosen")` to `HIGH_DRAMA_PLAYER_TURN_EVENTS`.
- The events dispatcher in `EVENTS` flushes queued events; the transition event then routes via the `states.inc.php` lookup to `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE`.
- This is necessary because the challenge sub-state machine relies on queued events firing first (e.g., `EventCardEngaged` from `stIssueChallenge`'s auto-engage). Bypassing the EVENTS dispatch with a direct `nextState(...)` to TECHNIQUE_AVAILABLE would leave events stuck in the queue.

### No `createActionResolvedEvent` in the action

The challenge resolution flow fires `createActionResolvedEvent` itself — either in `stChallengeActionCheckCancelled` (cancelled path) or in the threat-resolution path. Don't call it from your action. Mirror the `// createActionResolvedEvent is queued by the challenge resolution flow.` comment from `Action_01083`.

### Engage-as-cost is automatic — when

`StatesTrait::stIssueChallenge` auto-engages the performer for challenges of type `NORMAL`, `SERVO_SCARPA`, `TORVO_ESPADA`, and `AJA_CHALLENGE_TYPE` (the auto-engage list). If your "Issue a Challenge" card's cost is "Engage <self>" and the printed effect doesn't add anything weird to the cost, register the new challenge type in that list and omit a manual engage event. If your card has a different cost shape (e.g., engage a Weapon attachment instead), register a separate handler — see `Action_02013`'s `doCost` for the "discard a card" variant.

**Do NOT add your challenge type to the auto-engage list if engaged characters are eligible performers.** If the card text doesn't print "Engage [self]" as a cost (e.g., Don Constanzo's "Your **Thug** at this location issues a **Combat** challenge"), an already-engaged Thug is a valid performer — the rules don't bar it from issuing. Auto-engaging an already-engaged card re-emits `EventCardEngaged`, and downstream reactions (e.g., Vittoria's `Reaction_01014` "instead of me" swap) treat that as a *fresh* engagement and misfire. Instead:

1. Skip the auto-engage list entirely for your new type.
2. In the action's terminal step, emit the engage event yourself, **conditionally**:
   ```php
   if (! $performer->Engaged)
   {
       $engageEvent = EventFactory::createCardEngagedEvent(
           $performer->ControllerId, $performer->Id, $owner->Id, $this->Id
       );
       $game->theah->queueEvent($engageEvent);
   }
   ```

The `eligibility filter` follows the same logic. Aja and Servo's actions check `! $self->Engaged` because their text *does* print "Engage [self]"; Don Constanzo's `getAvailableThugs` does NOT check `! Engaged` because his doesn't. Read the printed cost and match.

### Performer ≠ action owner

The default Pattern F assumes the action's owner is also the challenge performer. But some cards (e.g., Don Constanzo `_03003`: "Your **Thug** at this location issues a **Combat** challenge") have the owner *select* the performer separately. Adjust:

- **Two-step state machine.** Step 1 picks the performer (e.g., a Thug at the owner's location). Step 2 picks the target at the *performer's* location. State IDs follow the usual `4` + cardId scheme with `_2` suffix.
- **`CHOSEN_PERFORMER` is the picked performer's id, not the owner's.** Set it in step 1's act handler; reference it in step 2's `getArgsFromAction` so the target picker filters opposing characters at the performer's location (not the owner's — they're usually equal but stay correct if the performer was at a different valid location).
- **`isValidTargetForAbility(Game, Character)` reads `CHOSEN_PERFORMER` to find the controller and location** for the validity check, since `getOwningCharacter` returns the action owner (Don), not the performer (the Thug).
- **Conditional engage in step 2** (see previous section). The performer is the Thug; engage only if `! Engaged`.

Reference: `Action_03003` (Don Constanzo).

Note that `canChallenge()` on the base `Character` class only checks `isControlled()` — it does NOT check `Engaged`. If your eligibility filter needs both, add `! $c->Engaged` explicitly. Characters that override `canChallenge` (e.g., Sigurd Ulfsen `_01190` permanent ban, Carmella `_01178` "engaged once" rule) handle their own engagement logic.

### Adding a NEW challenge type

A new `*_CHALLENGE_TYPE` constant is justified only when the card imposes restrictions or behaviors that diverge from `NORMAL_CHALLENGE_TYPE` — e.g., Aja's "only Finesse ≥ 3 may intervene or refuse." Touch these files in lockstep:

| File | What goes there |
|---|---|
| `modules/php/Game.php` | `final const NEW_CHALLENGE_TYPE = N;` (next int after the highest existing). |
| `seventhseacityoffivesails.js` | `this.NEW_CHALLENGE_TYPE = N;` — same int. Client checks reference `this.NEW_CHALLENGE_TYPE`. |
| `modules/php/StatesTrait.php::stIssueChallenge` | Add the new type to the auto-engage `if` list (if cost is "Engage performer"). |
| `modules/php/theah/Theah.php::interventionCheck` | Add an `else if` branch that throws `UserException` when the would-be intervener fails the card's restriction. Server-side enforcement. |
| `modules/php/ArgumentsTrait.php::argsHighDramaChallengeActionAcceptChallenge` | Post-filter `$charactersCanIntervene` so disallowed characters never appear in the picker. Add any extra args (e.g., `defenderFinesse`) the client needs to gate UI. |
| `modules/php/FrameworkActionsTrait.php::actHighDramaChallengeActionReject` | Throw `UserException` if the card forbids refusal under its conditions. |
| `modules/js/OnUpdateActionButtons.js::highDramaChallengeActionAcceptChallenge` | Add a `dojo.addClass('btnRefuse', 'disabled')` branch for the new type — mirror the existing `EPEE_SANGLANTE` / `UNSANCTIONED_DUEL` block. Use the server-supplied args (e.g., `args.defenderFinesse`) to compute the condition. |

The intervention-restriction story specifically:
- The args function filters the *visible* intervener list (UX).
- `interventionCheck` enforces the same rule on the server (security).
- For refusal, `actHighDramaChallengeActionReject` enforces server-side; the JS disable is UX. Always both.

### IAbilityThatTargetsCharacters

Always implement this interface on a challenge-issuing action — challenge target *is* a targeted character, so other cards' "before being targeted" hooks need to see it. Implement `isValidTargetForAbility(Game $game, Character $character): array` returning `[bool, string]`.

### Examples

| File | Demonstrates |
|---|---|
| `Action_02013` (Wilhelm Dünst) | "Discard a Card. Issue a Challenge." — discard-as-cost, then standard issue-challenge transition. Two-step state machine; reference for `doCost`/`doEffect` separation. |
| `Action_02034` (Torvo Espada) | Three-step "offer challenge → accept/decline → issue" flow with the `TORVO_ESPADA_CHALLENGE_TYPE` (no interventions allowed). |
| `Action_03002` (Aja) | Single-step picker → standard challenge flow with `AJA_CHALLENGE_TYPE` (Finesse ≥ 3 to intervene/refuse). Canonical reference for a NEW challenge type with restrictions. |
| `Action_03003` (Don Constanzo) | Two-step "pick your Thug → pick target". Performer is the chosen Thug, not the owner. New challenge type `DON_CONSTANZO_CHALLENGE_TYPE` deliberately kept OUT of the auto-engage list; action emits a conditional engage event in step 2 so already-engaged Thugs remain eligible. |
| `Action_01083` (Legendary Reputation) | RiskCityAction variant — sets `LEGENDARY_REPUTATION_CHALLENGE_TYPE` (only Leaders may intervene). |

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

### "Put into play from hand or discard"

For Reactions whose effect is "put a card into play" (e.g., Don Constanzo's "Put a different **Thug** into play at your **Home** from your hand or discard pile"):

- **Source filtering.** For hand: `$theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $owner->ControllerId)`. For discard pile: `$theah->getCardObjectsAtLocation($game->getPlayerDiscardDeckName($owner->ControllerId), $owner->ControllerId)`. Both per-player.
- **The muster event does the move.** `EventFactory::createCharacterMusteredEvent($playerId, $cardId, $location)` is the only event needed for the actual location change — its handler calls `$deck->moveCard(...)` on the game deck which physically moves the card.
- **`createCardRemovedFromPlayerDiscardPileEvent` is notification-only** in the default code path — it sends a `cardRemovedFromPlayerDiscardPile` notification (and only physically moves the card if `permanentlyHide=true`). The actual remove-from-discard happens implicitly when `createCharacterMusteredEvent`'s `$deck->moveCard` runs. So:
  - Fire `createCardRemovedFromPlayerDiscardPileEvent` BEFORE the muster event so JS clients (which filter `player.discard` on that notification) update their state in the right order.
  - Don't expect it to move the card; that's the muster event's job.
  - Reference: `Action_01024` (Bravos) follows this exact ordering for Thug-from-discard mustering.

### After a character moves to this location

For "Reaction: After <enemy/X> character moves to this location • <effect>" (`_03016` Ise). Listen on `EventCardMoved` (past-tense — the move has already committed). Required gates:

```php
if (! ($event instanceof EventCardMoved)) return;
if (! $this->isAvailable()) return;

$owner = $this->getOwningCharacter($event->theah);
if ($owner === null) return;
if (! $event->theah->cardInCity($owner)) return;   // enemies can't enter your Home
if ($event->cardId == $owner->Id) return;          // skip the Owner's own moves
if ($event->toLocation != $owner->Location) return;

$character = $event->theah->getCardById($event->cardId);
if (! ($character instanceof Character)) return;   // attachments and other cards also move
if ($character->ControllerId == 0) return;          // uncontrolled / mercenary — skip
if ($character->ControllerId == $owner->ControllerId) return;   // "enemy" gate

// Valid-effect-target precondition: if no eligible action, don't prompt the player.
if (count($this->getEligibleMovers($event->theah, $owner)) == 0) return;
```

WHY the `cardInCity($owner)` gate: enemy characters can't enter your Home location (Home is per-controller scope), so the `toLocation == $owner->Location` check would silently never match for an Owner at Home. The explicit gate documents the intent and skips the per-event work entirely.

WHY `Character` instanceof check (not just `getCardById`): `EventCardMoved` fires for *any* card that moved — attachments equipping, schemes being placed, etc. Filter to Character explicitly.

WHY `ControllerId == 0` skip: uncontrolled characters (mercenaries in transit, cards being mustered with no controller yet) shouldn't trigger an "enemy" reaction. Skipping them is the consistent behavior across the codebase.

WHY the valid-target precondition: if no eligible mover exists, the player would get a useless prompt they could only Pass. The general Pattern D rule (skill section "Trigger gates") applies here verbatim.

For the *self-moves* analogue ("after this character moves to a new location, do X for nearby allies"), the receiver isn't a Reaction — it's a `handleEvent` on the card itself. See `_01067` Jean Urbain or `_02022` Stranahan.

### Cancel-and-reissue Reaction — opt out of an auto-emitted event

For text like "During Dusk, you may choose not to move <Owner> Home" (`_03016` Ise). The framework's `stDuskPhaseCleanup` emits a `createCardMovingEvent(..., LOCATION_PLAYER_HOME, $engage=false, $sourceId=0)` for every non-Home controlled character. The Reaction intercepts that event, asks the player, and either keeps it canceled (effect: stay) or re-queues it (effect: go home as normal).

Skeleton (mirror `Reaction_03016a` Ise, in-hand sibling `Reaction_01140`):

```php
class Reaction_NNNNN extends CardReaction
{
    private ?EventCardMoving $cardMovingEvent = null;
    private string $fromLocation = '';

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Keep in city'), 'stay');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoving)) return;
        if ($event->canceled || $event->unstoppable) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;

        if ($event->cardId != $owner->Id) return;
        if ($event->toLocation != Game::LOCATION_PLAYER_HOME) return;
        if ($event->sourceId != 0) return;                              // auto-emitter signal
        if (in_array($owner->Id, $event->cancelDeclinedByCardIds)) return;  // re-queue guard

        $turnPhase = (int) $event->theah->game->getGameStateValue(Game::TURN_PHASE);
        if ($turnPhase != Game::DUSK) return;

        $this->cardMovingEvent = clone $event;
        unset($this->cardMovingEvent->theah);
        $this->fromLocation = $event->fromLocation;
        $event->canceled = true;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->stackEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);
        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId == 'stay')
        {
            // Already canceled in handleEvent; just announce + setUsed.
            $this->setUsed($game->theah, true);
            $this->cardMovingEvent = null;
            $this->fromLocation = '';
            $owner->IsUpdated = true;
        }

        if ($reactionId == 'decline')
        {
            // Re-queue the move with a self-marker so handleEvent doesn't re-trigger.
            $this->cardMovingEvent->cancelDeclinedByCardIds[] = $owner->Id;
            $game->theah->queueEvent($this->cardMovingEvent);
            $this->cardMovingEvent = null;
            $this->fromLocation = '';
            $owner->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}
```

WHY `sourceId == 0` is the auto-emitter signal: grepping `createCardMovingEvent(...LOCATION_PLAYER_HOME...)` confirms every ability-driven move-home passes a non-zero sourceId (action id / reaction id / etc.); only `stDuskPhaseCleanup` and `_01126`'s own self-recall emit with the default `$sourceId=0`. For the Dusk opt-out, that's exactly the signal you want — abilities that *also* try to move the Owner home should not be intercepted, because the player already chose to play that ability.

WHY the redundant `TURN_PHASE == DUSK` gate: belt-and-suspenders. Cheap, authoritative, and protects against any future code path that emits a zero-source move-home outside Dusk.

WHY clone + `unset($this->cardMovingEvent->theah)`: storing the event for later re-queue. The `theah` reference holds the Theah/Game graph; unsetting prevents recursive serialization when the reaction-instance state is persisted via `IsUpdated`. This matches `Reaction_01140`'s shape.

WHY `cancelDeclinedByCardIds` instead of "just delete the stored event": when the player picks "Decline", the move MUST still happen — the framework's downstream logic (engardment cleanup at dusk, etc.) depends on every non-Home character routing home. Re-queueing the cloned event with `cancelDeclinedByCardIds[] = $owner->Id` lets the move proceed without `handleEvent` immediately re-catching the re-queued event. Same dance as `Reaction_01140`.

WHY `stackEvent` (not `queueEvent`) for the transition: stacking puts the reaction prompt ahead of other queued cleanup events so the player decision happens BEFORE subsequent dusk cleanup events fire for other characters. Matches `Reaction_01140`'s convention.

Reference: `Reaction_03016a` (Ise Dusk opt-out, on a Character in play), `Reaction_01140` (in-hand RiskReaction sibling — same dance for player-driven moves).

### Reactions that need to pay a wealth cost — click-to-pay

For Reactions where the effect costs Wealth (e.g., Don Constanzo's "at -1 cost"), the framework's `PAY_STATE_PLAY_BRUTE` / `actPayForBrute` is usually NOT a fit because:

- Its success transition is hard-coded to `HIGH_DRAMA_PLAYER_TURN_EVENTS`, but reactions can fire outside high drama (dawn cleanup, pressure, duel cleanup) and must return to whatever state cycle invoked them.
- It requires the paid-for card to be in `LOCATION_HAND`. Reactions like "from hand or discard pile" don't fit.

Instead, do the payment **inside the Reaction class** using the standard `playerReaction` loop. Pattern:

1. **Reaction-instance state** for the running payment:
   ```php
   private array $paidCardIds = [];       // cards selected so far
   private int $paidWealth = 0;           // running wealth sum
   private bool $paidHasWealthCard = false; // true if any selected card has the "Wealth" trait
   ```
   Plus a `$stage` field (e.g., `'pick'` → `'pay'`).
2. **`getReactionButtonProperties` during the `'pay'` stage** lists every card in hand as a button (`Pay with <name> (+N Wealth)`), excluding cards already in `$paidCardIds` and excluding the card being put into play (when it's the hand-source one). Always include `< Back` and `Decline`.
3. **Each click runs `handlePay`**: validate the card, append to `paidCardIds`, increment `paidWealth` by `$card->hasTrait("Wealth") ? 2 : 1`, set `paidHasWealthCard` if applicable.
4. **`isPaymentComplete($cost)` mirrors `UtilitiesTrait::isValidWealthPayment`** — exact match OR `paidWealth == cost + 1 && paidHasWealthCard` (the "overpay by 1 using a Wealth card" rule).
5. **Filter button list to valid-next-clicks** via a `wouldClickProduceValidPayment` helper. Suppress buttons that would put paid beyond `cost + 1` or beyond `cost` without using a Wealth card.
6. **Queue discards atomically at finalize**, not per-click. WHY: `Decline` becomes a clean rollback (no cards were ever queued for discard), AND downstream reactions to `EventCardDiscardedFromHand` don't see partial-payment intermediate states.
7. **Always set `$owner->IsUpdated = true`** on every reaction-instance state mutation so the framework persists the running totals across reaction-loop iterations.
8. **Skip the `'pay'` stage entirely when `cost == 0`** — go straight to finalize.

Reference: `Reaction_03003` (Don Constanzo) — the canonical implementation of this pattern.

### Reaction examples

| File | Demonstrates |
|---|---|
| `Reaction_01006` | `IRiskReaction`-shaped pre-end-of-day cleanup ("Reaction: Before the end of the Day"). |
| `Reaction_01008` | "Cesca Scarpa copies the Sorcerer ability just played" — listens on `EventSorcererAbilityPlayed`, branches on the ability instance to copy actions/cards/etc. The original kitchen-sink Sorcerer-after-Sorcerer reaction. |
| `Reaction_01013` | Canonical "after my Red Hand is destroyed" Reaction — `EventCharacterDestroyed` trigger + button-based draw choice. Reference for the trait/controller/location identity gates. |
| `Reaction_01014` (Vittoria) | "Instead of me" target swap on `EventCardEngaged`/`EventChallengeIssued`/etc. ⚠ Re-emitting `EventCardEngaged` on an already-engaged character will trip this. Pattern F users beware. |
| `Reaction_01089` | Soline el Gato's "after an Action resolves" — `EventActionResolved` + button-per-adjacent-location. |
| `Reaction_01116a`, `Reaction_01116b` | Yevgeni's paired Reactions on a single Leader. |
| `Reaction_01118` | Elina's "after a Sorcerer ability targets a character at her location, move Renown to her location" — `sourceId == owner` OR `performerId == owner` pattern. |
| `Reaction_02001` | Andriana — Sorcerer Reaction (so implements `ISorcererAbility`); button-prompts to wound a non-Sorcerer. |
| `Reaction_03001` | Cesca del Rosso's "after Cesca performs a Sorcerer ability, wound an opposing character" — button-per-opposing-character target picker, with a Pass button. |
| `Reaction_03003` (Don Constanzo) | Multi-stage Reaction with hand/discard source selection, **incremental click-to-pay wealth handling** rolled inside the reaction (no PAY_STATE_PLAY_BRUTE coupling), and muster-at-Home. Canonical reference for cost-bearing Reactions and "put into play from hand or discard pile." |
| `Reaction_03016a` (Schwester Ise — Dusk opt-out) | **Canonical cancel-and-reissue Reaction.** Listens on `EventCardMoving` for the Dusk auto-move home (`sourceId == 0`, `toLocation == LOCATION_PLAYER_HOME`, `TURN_PHASE == DUSK`). Cancels and prompts; "Keep in city" calls `setUsed`, "Decline" re-queues the cloned event with `cancelDeclinedByCardIds[] = owner.Id`. Uses `stackEvent` so the prompt jumps ahead of other queued dusk cleanup. In-hand sibling: `Reaction_01140`. |
| `Reaction_03016b` (Schwester Ise — pull a friendly) | **Canonical "after enemy moves to my location" reaction.** Listens on `EventCardMoved` with `cardId != owner.Id`, `toLocation == owner.Location`, `cardInCity(owner)`, enemy controller check; button per eligible mover (own characters not at owner's location); queues `createCardMovingEvent` for the chosen character to the owner's location. |

## Pattern E — Techniques and Maneuvers

The Character lineage already brings `TechniqueTrait`. Add `IHasManeuvers` + `ManeuverTrait` for maneuvers. Implement under `cards/<expansion>/techniques/` or `cards/<expansion>/maneuvers/`. The base `create-city-character` skill has the general shape; the notes below are duel-specific patterns that come up often.

### In-duel availability gate

Most Character techniques are duel-only — they're activated during a duel round by the actor. Gate `isAvailableToPlayer`:

```php
public function isAvailableToPlayer(int $playerId, Theah $theah): bool
{
    if (! parent::isAvailableToPlayer($playerId, $theah)) return false;
    if (! $theah->game->globals->get(Game::IN_DUEL, false)) return false;

    $owner = $this->getOwningCharacter($theah);
    $actor = $theah->getDuelRoundActor();
    if ($actor === null || $actor->Id !== $owner->Id) return false;

    // ... card-specific preconditions (adversary state, equipped weapons, etc.)
    return true;
}
```

Helpers worth knowing:
- `$theah->getDuelRoundActor(): ?Character` — the participant whose turn it is this round.
- `$theah->getDuelRoundOpponent(): ?Character` — the other participant. Returns the *last-known* state when the opponent is in discard/locker (e.g., already destroyed).
- `$theah->getDuelChallengerId() / getDuelDefenderId() / getDuelOpponentId($actorId)` — id-only accessors.
- `Game::IN_DUEL` global — true between duel start and end.
- `Game::DUEL_GAMBLED` global — true after the actor locks in a combat card via gamble; cleared at end of round.

### Gambling Technique gate

"**Gambling Technique:** …" — only available if the actor has gambled for their combat card this round. Add one extra check on top of the in-duel gate:

```php
if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false)) return false;
```

WHY use the global (and not query `duel_round.gambled` directly): the global is set in `FrameworkActionsTrait::actChooseGambleCard` at the moment the gambled combat card is locked in, and cleared in `stDoneRound`. It's the cheapest authoritative answer to "has the actor gambled this round." `isAvailableToPlayer` runs on a hot path (every time the action menu refreshes), so the SQL alternative is wasteful.

Reference: `Technique_03002` (Aja).

### Gain Lethal — in-duel vs city-challenge

There are two completely different "Gain Lethal" pipelines depending on context. Don't conflate them.

| Event | When it fires | Use case |
|---|---|---|
| `EventGenerateChallengeThreat` | City-action challenge resolution (no duel; single threat roll) | Techniques granting Lethal during a non-duel challenge. Set `$event->adversaryThreatIsLethal = true` directly on the event. |
| `EventDuelCalculateTechniqueValues` | Per-technique calculation phase during a duel round | Techniques granting Lethal during a duel. Queue `EventFactory::createGainLethalEvent($event->actorId, $event->theah)` — this internally creates a `ThreatModified` event that marks the adversary's threat lethal regardless of which side the actor is. |

```php
public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
    {
        $lethalEvent = EventFactory::createGainLethalEvent($event->actorId, $event->theah);
        $event->theah->queueEvent($lethalEvent);
    }
}
```

A technique can handle BOTH events if it's usable in both contexts (see `Technique_01049` and the generic `Technique_GainLethal` helper). A Gambling Technique is duel-only, so only `EventDuelCalculateTechniqueValues` matters — gambling is exclusively a duel-round mechanic.

`createGainLethalEvent($actorId, $theah)` reads as: "the actor's strike against the adversary is now lethal." The naming inside the produced event (`challengerThreatIsLethal` / `defenderThreatIsLethal`) describes whose threat is lethal — i.e., the threat dealt TO that role. The factory figures out the sign for you; just pass the actor's id.

References: `Technique_GainLethal` (generic two-pipeline helper), `Technique_01049` (in-duel + city-context), `Technique_03002` (Aja, in-duel only via Gambling Technique gate).

### `EventDuelCalculateTechniqueValues` field shape

Unlike `EventDuelCalculateCombatCardStats` (which exposes `addRiposte`/`addParry`/`addThrust`/`removeRiposte`/etc. methods and respects `dashedX` flags), `EventDuelCalculateTechniqueValues` has plain int fields `$riposte`/`$parry`/`$thrust` you mutate directly:

```php
if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
{
    $event->parry  += 1;
    $event->thrust -= 1;
    $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds +1 Parry."), $owner->getInjectCode(), $this->Name);
}
```

Reference: `Technique_01050` (–1 Thrust + wound), `Technique_03004` Elena (+1 Parry + wound). You can queue follow-on events (e.g., `createCharacterBeingWoundedEvent`, `createGainLethalEvent`) from inside the same calc handler — the queued events fire after the calc resolves.

### "If <owner>'s combat card is a <trait>" gate

For techniques gated on the actor's combat card having a particular trait (`_03004` Elena's "if combat card is a Sorcery"):

```php
$combatCards = $theah->getCombatCardsForCurrentRound();
foreach ($combatCards as $card)
{
    if ($card->ControllerId == $owner->ControllerId && $card->hasTrait("Sorcery"))
    {
        return true;
    }
}
return false;
```

`getCombatCardsForCurrentRound()` returns BOTH players' combat cards. Filter by `$card->ControllerId == $owner->ControllerId` to isolate the actor's own combat card. (Since the technique already gates on `actor->Id == owner->Id`, this is the actor's own combat card.) Cesca Scarpa's `Technique_02003` is similar but cares about *any* Sorcery played in the round, so it skips the ControllerId filter — match the card text literally.

### "If <Owner> is equipped with X **or** there is an X card in his dueling line" gate

For techniques gated on a trait being present on either the owner's attachments OR the owner's side of the dueling line (`_03014` Kaspar — "equipped with an Eisenfaust attachment or there is an Eisenfaust card in his dueling line"). Check BOTH sources, OR them, and gate `isAvailableToPlayer` on the OR:

```php
private function hasEisenfaust(Theah $theah, Character $owner): bool
{
    // Attachments: $owner->Attachments is an array of *ids*. Look each up.
    foreach ($owner->Attachments as $attachmentId)
    {
        $attachment = $theah->getCardById($attachmentId);
        if ($attachment !== null && $attachment->hasTrait("Eisenfaust"))
        {
            return true;
        }
    }

    // Dueling line: per-player, keyed on the owner's ControllerId.
    $cards = $theah->getCardObjectsAtLocation(Game::LOCATION_DUELING_LINE, $owner->ControllerId);
    foreach ($cards as $card)
    {
        if ($card->hasTrait("Eisenfaust"))
        {
            return true;
        }
    }
    return false;
}
```

WHY `getCardObjectsAtLocation(LOCATION_DUELING_LINE, $owner->ControllerId)` is safe inside an `IN_DUEL` gate: the dueling line is per-player and accumulates combat cards over the duel's rounds; outside a duel it's empty (the line is cleared at duel end). With the standard `isAvailableToPlayer` gate on `IN_DUEL` + `actor == owner`, the cards returned are the owner's combat cards from this duel's prior rounds (plus the current round once a combat card has been picked). If the card text said "his dueling line *this round*" you'd switch to `getCombatCardsForCurrentRound()` filtered by controller; "his dueling line" without qualifier means the cumulative line.

WHY iterate `$owner->Attachments` by id rather than calling `hasWeaponEquipped` / similar helper: there's no `hasAttachmentWithTrait($trait)` helper on `Character`. The id-list-then-`getCardById` pattern is the one in use across the codebase (e.g. `Maneuver_01054`'s `if ($attachment && $attachment->hasTrait("Eisenfaust"))`). Don't roll a new helper — match the existing shape.

### Wound-as-cost: queue the wound event at `EventResolveTechnique` BEFORE the transition

For techniques whose printed cost is "Wound <Owner> • <effect>" (Daniella Dietrich `_03013`), the wound is part of the cost — paid before the effect resolves. The natural place is the `EventResolveTechnique` handler, where you queue BOTH the wound event and the technique-transition event, in that order:

```php
if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
{
    $owner = $this->getOwningCharacter($event->theah);

    // Pay the cost: wound the owner. Cost-before-effect per the "Wound X •" split.
    $woundedEvent = EventFactory::createCharacterBeingWoundedEvent(
        $owner->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
    );
    $event->theah->queueEvent($woundedEvent);

    // Effect: transition into the target-picker state.
    $transition = EventFactory::createTechniqueTransitionEvent(
        $owner->ControllerId, $owner->Id, "NNNNN", $this->Id
    );
    $event->theah->queueEvent($transition);
}
```

WHY at resolve-time and not inside `actFromTechniqueWithId`: by the time the player picks a swap target in `actFromTechniqueWithId`, the cost has already been paid — the wound fired earlier when `EventResolveTechnique` flushed. Putting the wound in the act handler would invert the cost/effect order printed on the card and let a player back out of the cost by declining the picker. Queue at resolve and the wound is committed regardless of whether the player completes the effect.

The wound-event factory signature mirrors `Technique_01063`'s use: `($characterId, $sourceCharacterId, $wounds, $sourceDescription, $techniqueId)`.

### Swap mechanics inline in `actFromTechniqueWithId` — challenge vs duel context

For "swap <Owner> with another character" techniques (Daniella Dietrich `_03013` — Wound + swap with Hunter/Zealot at this location), don't defer the swap to event handlers. Do it inline in `actFromTechniqueWithId` so the player's commit unambiguously commits the swap. Branch on the state to handle the challenge-time and duel-time contexts differently:

```php
public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
{
    parent::actFromTechniqueWithId($game, $state, $stateName, $id);

    if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_NNNNN
        || $state == States::DUEL_CHOOSE_TECHNIQUE_NNNNN)
    {
        // ... target validation, notification ...

        $this->swapId = $target->Id;

        if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_NNNNN)
        {
            // Challenge context: duel not yet built. Redirect CHOSEN_PERFORMER
            // and move DUEL_CHALLENGER condition so the new challenger is the
            // one who actually enters the duel.
            $game->globals->set(Game::CHOSEN_PERFORMER, $target->Id);
            $owner->removeCondition(Game::DUEL_CHALLENGER);
            $target->addCondition(Game::DUEL_CHALLENGER);
            $owner->IsUpdated = true;
            $target->IsUpdated = true;
            $game->updateCardObjectInDb($owner);
            $game->updateCardObjectInDb($target);

            $challengerSwappedEvent = EventFactory::createChallengerSwappedEvent(
                $owner->ControllerId, $owner->Id, $target->Id
            );
            $game->theah->queueEvent($challengerSwappedEvent);
        }
        else  // DUEL_CHOOSE_TECHNIQUE_NNNNN — already inside a duel
        {
            // Duel context: rewrite the duel's stored participant list so the
            // target takes Daniella's seat for the rest of the duel.
            $duelId = $game->globals->get(Game::DUEL_ID);
            $round  = $game->globals->get(Game::DUEL_ROUND);
            $game->theah->swapParticipantsInDuel($duelId, $round, $owner->Id, $target->Id);
            $game->updateCardObjectInDb($owner);
            $game->updateCardObjectInDb($target);
        }

        $game->gamestate->nextState();
    }
}
```

Keep ONE thing in `handleEvent` — the `EventGenerateChallengeThreat` `actorId` redirect. That mutation can only happen at event-fire time:

```php
if ($event instanceof EventGenerateChallengeThreat
    && $event->techniqueId == $this->Id
    && $this->swapId != 0)
{
    // WHY: the event is in flight when threat is being calculated. Character
    // ::handleEvent (which adds the actor's stat to adversaryThreat when
    // actorId matches) and the EventHub threat notification both key on
    // $event->actorId. Without the redirect they still reference the original
    // challenger, even though DUEL_CHALLENGER condition has already moved.
    $event->actorId = $this->swapId;
}
```

WHY split the work this way (vs. mirroring Bastien's all-in-events approach in `Technique_01063Swap`): Bastien defers the condition swap into `EventGenerateChallengeThreat` (with a `CHALLENGE_ACCEPTED` guard) so the swap doesn't fire if the challenge is rejected. That's a stricter, more conservative shape. The in-`actFromTechniqueWithId` shape is cleaner to read and matches the user's preference (see project history), but if your card text says the swap is *conditional on the challenge being accepted*, prefer Bastien's pattern instead so a rejection doesn't leave a stuck DUEL_CHALLENGER condition on a character that never enters a duel.

### Technique usable in BOTH challenge and duel contexts — two states, two routings, two state classes

A technique that fires in either a challenge-resolve flow or a duel round needs entries in BOTH dispatcher routes:

- **Challenge-time:** state ID `455` + 5-digit cardId (e.g. `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_03013 = 45503013`). Routed from `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS`. State class: `State_highDramaChallengeActionResolveTechnique_NNNNN`.
- **Duel-time:** state ID `521` + 5-digit cardId (e.g. `DUEL_CHOOSE_TECHNIQUE_03013 = 52103013`). Routed from `DUEL_CHOOSE_TECHNIQUE_EVENTS`. State class: `State_duelChooseTechnique_NNNNN`.

Both states live under `modules/php/States/<expansion>/` and extend `GameState`. The technique's `createTechniqueTransitionEvent($controllerId, $ownerId, "NNNNN", $this->Id)` uses the SAME transition-name string (`"NNNNN"`) in both contexts — the dispatcher routes correctly because the lookup is per-dispatcher-state. Both routing maps need the entry:

```php
// states.inc.php — HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS.transitions
"NNNNN" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_NNNNN,

// states.inc.php — DUEL_CHOOSE_TECHNIQUE_EVENTS.transitions
"NNNNN" => States::DUEL_CHOOSE_TECHNIQUE_NNNNN,
```

Both state classes use the default-`""` transition back to their dispatcher EVENTS state (it's the only exit), and both expose `actFromCardWithId` as their `#[PossibleAction]`. Their `getArgsFromTechnique`/`actFromTechniqueWithId` can share a single `if ($state == HIGH_DRAMA... || $state == DUEL_CHOOSE...)` branch since the args shape and act validation are identical — the only divergence is the swap mechanics (see above).

JS handlers live in `modules/js/{OnEnteringState,OnUpdateActionButtons,OnLeavingState}.<expansion>.js`. Both states need their own keyed handler in each file — the args shape and Confirm button are identical to the existing `_01063` Bastien handlers; copy-paste and rename. The `_01063` versions live in the `*.7s5s.js` files; faf cards' versions live in `*.faf.js` files.

WHY `actFromCardWithId` and not `actFromTechniqueWithId` as the `#[PossibleAction]`: the GameState framework's `actFromCardWithId` delegates into `Game::actFromCardWithId`, which the technique framework routes back to the technique's own `actFromTechniqueWithId` via the per-state dispatch in `StatesTrait`. Don't expose `actFromTechniqueWithId` directly as the `#[PossibleAction]` — mirror the existing `_01063` state classes.

### Disambiguating same-name characters in state descriptions

Some characters share a name across expansions (e.g., `_01036` "Daniella Dietrich" and `_03013` "Daniella Dietrich, Witch / Hunter"). The state's `descriptionMyTurn` is the only place this is user-visible; disambiguate by appending the `Title` in parens:

```php
descriptionMyTurn: clienttranslate('Daniella Dietrich (Witch, Hunter)')
                   . clienttranslate(': Wound and Swap with a Hunter or Zealot: ${you} must choose a Hunter or Zealot:'),
```

The state classes' `name` field (used by JS) doesn't need disambiguation because state IDs already differ — `_01036`'s state is `duelChooseTechnique_01036`, `_03013`'s is `duelChooseTechnique_03013`.

### Duel-flow events worth knowing

| Event | When it fires |
|---|---|
| `EventDuelStarted` / `EventDuelEnd` | Duel boundaries. |
| `EventNewDuelRound` / `EventDuelEndOfRound` | Round boundaries. |
| `EventDuelAttemptGamble` | Pre-check fired when the actor clicks Gamble. Throw via `eventCheck` to block gambling (Mysta's Technique_02037 pattern). |
| `EventDuelGambleCardsRevealed` | After cards are revealed during gambling. Carries `revealedCardIds`. |
| `EventDuelPlayerGambled` | After the actor selects a card from the gambled reveal — combat card locked in, `DUEL_GAMBLED = true`. |
| `EventTechniqueActivated` | A technique was just activated (the base `Technique::handleEvent` flips `Used` on this for the matching technique). |
| `EventResolveTechnique` | Resolve-time event for a technique. Used to spawn the technique's "side effects" (queue further events, transition into a state). |
| `EventDuelCalculateTechniqueValues` | Per-technique value calculation. Use this to inject Lethal, modify riposte/parry/thrust, etc. |
| `EventDuelCalculateCombatCardStats` | Per-combat-card stat calculation (Yevgeni's pattern). |
| `EventGenerateChallengeThreat` | City-action challenge threat generation (no duel). |
| `EventChallengerSwapped` / `EventDefenderSwapped` | The challenge had its participant changed mid-stream. Re-evaluate any modifier you applied. |

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

Duel-specific (used in Pattern E and the in-duel branch of any ability):
- `$theah->getDuelRoundActor(): ?Character` / `getDuelRoundOpponent(): ?Character` — current round participants.
- `$theah->getDuelChallengerId(): ?int` / `getDuelDefenderId(): int` / `getDuelOpponentId(int $actorId): int` — id-only accessors.
- `$theah->getCombatCardsForCurrentRound(): array` — combat cards played in the current round.
- `$theah->getCurrentDuelThreat(int $characterId): int` — running threat against a participant.
- `EventFactory::createGainLethalEvent(int $actorId, Theah $theah)` — produces a `ThreatModified` event marking the adversary's threat lethal.
- `Game::IN_DUEL` / `Game::DUEL_GAMBLED` globals — round-scoped, see Pattern E.

## Reference Implementations

| File | What it demonstrates |
|---|---|
| `modules/php/cards/_7s5s/_01007.php` (Aldo Bussotti) | **Canonical regular Character.** `initializeFaction`, `handleEvent` listening on `EventCardMoved` / `EventRenownAddedToLocation` / `EventReknownRemovedFromLocation` to keep Influence in sync with current-location Renown, paired with a one-step City Action. |
| `modules/php/cards/_7s5s/_01006.php` (Don Constanzo Scarpa) | **Leader with setup-time reaction.** `EventTableSetup` flow (reveal a Red Hand Thug from your deck), `EventPressureOccuring` listener that flips a pressure-type global, paired with multi-step setup states. |
| `modules/php/cards/_7s5s/_01089.php` (Soline el Gato) | **Leader with passive duel hook + City Reaction.** `EventDuelStarted` / `EventDuelEnd` / `EventDefenderSwapped` / `EventChallengerSwapped` keep the affected character's Finesse modified; `Reaction_01089` adds a button-based "move to adjacent location after an Action resolves" prompt. |
| `modules/php/cards/_7s5s/_01116.php` (Yevgeni) | **Leader with passive duel-stat hook + paired Reactions.** Demonstrates `EventDuelCalculateCombatCardStats`, `actorId == $this->Id` checks, and multi-reaction wiring. |
| `modules/php/cards/_7s5s/_01035.php` (Kaspar Dietrich) | **Leader with parley discount + City Action.** Demonstrates `getParleyDiscount` override and the reveal-from-city-deck-until-trait pattern. |
| `modules/php/cards/faf/_03001.php` (Cesca del Rosso) | **Leader with End-of-Dawn draw + button-based City Reaction + two-step City Action.** `EventPhaseDawnEnding` + `characterIsInDiscardOrLocker` gate, `EventSorcererAbilityPlayed` reaction with source/performer identity check, two-step CharacterAction with the move-wound (heal + wound) recipe. |
| `modules/php/cards/faf/reactions/Reaction_03001.php` | Button-per-opposing-character target picker; `IAbilityThatTargetsCharacters`; `isNotControlledByPlayer` + location filter for "opposing"; `setUsed`/`isAvailable` discipline. |
| `modules/php/cards/faf/actions/Action_03001.php` | Two-step CharacterAction; `cardInCity` gate; `IAbilityThatTargetsCharacters` interface for target hooks; `isValidTargetForAbility` double-checked at step 2; heal+wound recipe; `createActionResolvedEvent` at terminal state. |
| `modules/php/cards/faf/_03002.php` (Aja) | **Character that issues a Combat challenge + Gambling Technique.** Pattern F (issue-a-challenge) with a new `AJA_CHALLENGE_TYPE` whose intervention/refusal are gated by Finesse ≥ 3 — touches all six challenge-type integration points. Pattern E "Gambling Technique" gate via `Game::DUEL_GAMBLED` + `getDuelRoundActor`. |
| `modules/php/cards/faf/actions/Action_03002.php` | Pattern F skeleton: opposing-target picker → set CHOSEN_PERFORMER/TARGET/CHALLENGE_STAT/CHALLENGE_TYPE → `createTransitionEvent("03002_2")` + `nextState("targetChosen")` to `HIGH_DRAMA_PLAYER_TURN_EVENTS`. |
| `modules/php/cards/faf/techniques/Technique_03002.php` | Gambling Technique with adversary-wounded precondition. `EventDuelCalculateTechniqueValues` + `createGainLethalEvent` in-duel pipeline. |
| `modules/php/cards/faf/_03003.php` (Don Constanzo Scarpa, Fearsome Father) | **Character with a Pattern F variant where performer ≠ owner + a cost-bearing City Reaction.** City Action picks one of the controller's Thugs at Don's location and has *that* Thug issue the challenge — new `DON_CONSTANZO_CHALLENGE_TYPE` deliberately omitted from auto-engage list so already-engaged Thugs are eligible. City Reaction triggers on `EventCharacterDestroyed` for Thugs and offers a multi-stage "pick from hand/discard → click-to-pay Wealth → muster at Home" flow. |
| `modules/php/cards/faf/actions/Action_03003.php` | Two-step Pattern F where the performer is selected by the player. Step 1 picks the Thug, sets `CHOSEN_PERFORMER` to the Thug's id. Step 2 picks the opposing target at the performer's location, conditionally engages the Thug (`if (! $performer->Engaged)`), then `createTransitionEvent("03003_2")` into the challenge sub-state. |
| `modules/php/cards/faf/reactions/Reaction_03003.php` | Multi-stage Reaction (`'pick'` → `'pay'` → finalize). Source filtering from hand AND discard with the destroyed-Thug exclusion. **In-reaction click-to-pay** with running `$paidWealth`/`$paidHasWealthCard` state, `wouldClickProduceValidPayment` button filter, atomic discards at finalize. Mirrors `UtilitiesTrait::isValidWealthPayment` semantics (exact match OR overpay-by-1-with-Wealth). |
| `modules/php/cards/faf/_03004.php` (Elena Agnelli) | **Character with a dynamic-recompute dueling-line Finesse bonus + a trait-gated Technique.** Pattern A passive with a `$FinesseBonus` running field recomputed at `EventDuelEndOfRound` from `getCardObjectsAtLocation(LOCATION_DUELING_LINE, controllerId)`, reset via inverse-delta at `EventDuelEnd` (which fires BEFORE the line is cleared). Gates on the owner being a duel participant (the dueling line is per-player, not per-character). |
| `modules/php/cards/faf/techniques/Technique_03004.php` | Trait-gated Technique: in-duel + actor-is-owner + actor's own combat card has the Sorcery trait (via `getCombatCardsForCurrentRound()` filtered by `ControllerId`). `EventDuelCalculateTechniqueValues` handler mutates `$event->parry` directly (plain int field — no `addParry` method on this event) AND queues a `createCharacterBeingWoundedEvent` for the adversary in the same calc handler. |
| `modules/php/cards/faf/_03013.php` (Daniella Dietrich, Witch/Hunter) | **Leader with a continuous-Action trait passive + cost-reduction Reaction + dual-context Wound+Swap Technique.** Three patterns on one card: opposing-character `addTrait`/`removeTrait` lifecycle via `Action_03013` (never-`Used` continuous Action), Faith/Sorcery cost-reduction reaction cloned from `Reaction_01116b`, and a Technique usable from both `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS` and `DUEL_CHOOSE_TECHNIQUE_EVENTS` with the swap mechanics inline in `actFromTechniqueWithId`. |
| `modules/php/cards/faf/actions/Action_03013.php` | **Canonical Continuous-Action passive.** `isAvailableToPlayer` returns `false` so it never appears in the action menu; `handleEvent` tags opposing characters with "Sorcerer" on `EventActionTriggered`/`EventReactionActivated`/`EventTechniqueActivated`/`EventManeuverActivated` for the owner's controller and untags at `EventPlayerTurnEnd`; tracks tagged-id set to dedup `addTrait` (which appends without dedup); explicitly resets `Used` to false at the turn-end boundary. |
| `modules/php/cards/faf/reactions/Reaction_03013.php` | Cost-reduction Reaction cloned from `Reaction_01116b`; filter swapped to `IWealthCost && (hasTrait("Faith") || hasTrait("Sorcery"))`. Standard four-discount-method shape. |
| `modules/php/cards/faf/techniques/Technique_03013.php` | **Dual-context Wound + Swap Technique.** Wound cost queued at `EventResolveTechnique` BEFORE the technique-transition event (cost-before-effect ordering). Swap mechanics inline in `actFromTechniqueWithId`, branched on state: challenge-context moves DUEL_CHALLENGER condition + queues ChallengerSwappedEvent + sets CHOSEN_PERFORMER; duel-context calls `swapParticipantsInDuel`. `EventGenerateChallengeThreat` handler kept slim — only the `actorId` redirect, which must happen at event-fire time. |
| `modules/php/cards/faf/_03014.php` (Kaspar Dietrich, Iron Reforged) | **Character with a wound-prevention passive + attachment-or-dueling-line-gated Technique.** Passive uses `eventCheck` (not `handleEvent`) on `EventCharacterBeingWounded` to zero `$event->wounds` — cleaner than Maxime's skip-`parent::handleEvent` shape because `EventHub` only emits the past-tense `EventCharacterWounded` when `wounds > 0`, so nothing downstream thinks the wound happened. Filters: `abilityId != ''` (lets threat conversion through, which emits with empty `abilityId`) AND `source.ControllerId != owner.ControllerId` (opponent's ability). Wound-movement (heal+wound recipe, `Action_02010`) is blocked by the same filter — no special handler needed. |
| `modules/php/cards/faf/_03016.php` (Schwester Ise, Moonlit Interrogator) | **Character with a self-condition stat bonus + a cancel-and-reissue Reaction + an enemy-moved-to-me Reaction.** (1) +1 Combat while wounded: private `$WoundedCombatBonusApplied` flag, hook `EventCharacterWounded`/`EventCharacterHealed` for `characterId == $this->Id` after `parent::handleEvent` updates `$this->Wounds`, queue `createCharacterCombatModifiedEvent(±1)` only on flag transition, skip on `IsDying`/discard. (2) Dusk "keep in city" via `Reaction_03016a` — listens on `EventCardMoving` (`sourceId == 0`, `toLocation == HOME`, `TURN_PHASE == DUSK`), cancels and prompts, uses `cancelDeclinedByCardIds` to gate the Decline re-queue. (3) "After enemy moves here" via `Reaction_03016b` — `EventCardMoved` with controller/location gates, button-per-eligible-friendly, `createCardMovingEvent` to the owner's location. |
| `modules/php/cards/faf/reactions/Reaction_03016a.php` | Canonical cancel-and-reissue Reaction. Clone + `unset($cloned->theah)` for storage; `stackEvent` for the transition; `cancelDeclinedByCardIds[] = owner.Id` on Decline so the re-queued event isn't re-caught. Same shape as in-hand `Reaction_01140` but lives on a Character in play. |
| `modules/php/cards/faf/reactions/Reaction_03016b.php` | "After enemy moves here → pull a friendly" Reaction. Demonstrates the full `EventCardMoved` gate set (`cardId != owner.Id`, `toLocation == owner.Location`, `cardInCity(owner)`, instanceof Character, `ControllerId != 0`, enemy controller), valid-target precondition (`getEligibleMovers` non-empty), and `createCardMovingEvent` for a non-self mover with `engage=false`. |
| `modules/php/cards/faf/_03015.php` (Joern Kietelsson, Fury's Edge) | **Character with three pure-passive abilities — no Action/Reaction/Technique files.** (1) Forced self-wound on muster: hooks BOTH `EventCharacterMustered` AND `EventApproachCharacterPlayed` OR'd in one conditional, gated on `characterId == $this->Id`. (2) Phase-conditional Resolve penalty ("During Dusk, -3 Resolve"): direct `$this->ModifiedResolve` mutation on `EventDuskPhaseBegin`, restore on `EventDuskEndOfDay`, gated by a private `$DuskResolvePenaltyApplied` bool — there is no `createCharacterResolveModifiedEvent` factory. Includes an explicit destruction check (mirroring `EventHub.php:251`) because `Character::handleEvent`'s threshold check only runs inside an `EventCharacterWounded` handler. (3) Challenge-refused self-heal on `EventChallengeRejected` with `challengerId == $this->Id` — symmetric to `_01119` Nazem's challenger-side engage. |
| `modules/php/cards/faf/techniques/Technique_03014.php` | **Attachment-OR-dueling-line trait-gated Technique that wounds the adversary.** `isAvailableToPlayer` ORs two checks: iterate `$owner->Attachments` (ids → `getCardById` → `hasTrait("Eisenfaust")`) and iterate `getCardObjectsAtLocation(LOCATION_DUELING_LINE, $owner->ControllerId)`. Effect mirrors `Technique_03004` — `EventDuelCalculateTechniqueValues` handler queues a `createCharacterBeingWoundedEvent` against `getDuelRoundOpponent()` and pushes an explanation. |
| `modules/php/cards/_7s5s/_01069.php` (Maxime de Lafayette) | **Wound-prevention passive — own-Sorcerer scope.** Overrides `handleEvent` on `EventCharacterWounded` and skips `parent::handleEvent` to drop the wound (alternative to Kaspar's `eventCheck`-on-`EventCharacterBeingWounded` shape). Distinguishes Sorcery-trait source (auto-targets performer) from `ISorcererAbility` + `CHOSEN_PERFORMER == Maxime`. Prefer Kaspar's shape for new wound-prevention passives — it doesn't propagate the past-tense event to other listeners. |
| `modules/php/cards/_7s5s/_01153.php` (Breastplate) | **Reduce-by-one wound prevention in `eventCheck`.** Canonical `eventCheck` on `EventCharacterBeingWounded` pattern. Tracks `$hasBlockedWound` to enforce "first time this duel." Mutates `$event->wounds` rather than zeroing — adapt this shape for partial-reduction passives. |
| `modules/php/cards/_7s5s/actions/Action_01090.php` (Yuri Pyetrovich) | **Continuous Action — user-triggered variant.** Player activates from the menu; the Action sets globals and immediately calls `$this->setUsed($event->theah, false)` so it's available again. Companion to `Action_03013`'s never-shown variant. |
| `modules/php/cards/tac/actions/Action_02013.php` (Wilhelm Dünst) | Pattern F with a discard-as-cost step plus the standard challenge transition. Reference for `doCost` / `doEffect` separation when the cost isn't just engagement. |
| `modules/php/cards/_7s5s/techniques/Technique_GainLethal.php` | Generic two-pipeline Gain Lethal helper — handles both `EventGenerateChallengeThreat` (city) and `EventDuelCalculateTechniqueValues` (duel). |
| `modules/php/cards/_7s5s/techniques/Technique_01049.php` | Engagement-as-cost Gain Lethal technique; handles both pipelines, demonstrates `IRangedAbility` integration. |
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
12. **For "issue a challenge" actions (Pattern F):** confirm all six challenge-integration files are touched if you minted a new challenge type — `Game.php`, `seventhseacityoffivesails.js`, `StatesTrait::stIssueChallenge` (auto-engage list), `Theah::interventionCheck`, `ArgumentsTrait::argsHighDramaChallengeActionAcceptChallenge`, `FrameworkActionsTrait::actHighDramaChallengeActionReject`, plus `OnUpdateActionButtons.js::highDramaChallengeActionAcceptChallenge` for the Refuse button UI. The PHP int and the JS int MUST match. Confirm `states.inc.php` has BOTH `"NNNNN"` (picker entry) and `"NNNNN_2"` (post-pick → `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE`). **Engagement audit:** if the card text doesn't print "Engage [self]" as a cost, keep the new type OUT of the auto-engage list, allow `Engaged` performers in `isAvailableToPlayer`, and emit `createCardEngagedEvent` conditionally in the action's terminal step (`if (! $performer->Engaged)`). Re-emitting the engage event on an already-engaged character will trip Vittoria-style `Reaction_01014` "instead of me" swaps.
13. **For picker states with multiple exits**, name every transition (`"targetChosen"`, `"zombie"`, etc.). The empty-string `""` transition is only legal when it's the SOLE transition out of the state.
14. **For in-duel techniques (Pattern E)**, confirm `Game::IN_DUEL` and (for Gambling Techniques) `Game::DUEL_GAMBLED` gates are in `isAvailableToPlayer`, plus the actor-identity check via `getDuelRoundActor()`. For Gain Lethal effects, use `EventDuelCalculateTechniqueValues` + `createGainLethalEvent` for the in-duel pipeline; only also handle `EventGenerateChallengeThreat` if the technique is meant to fire outside duels too.
15. **For cost-bearing Reactions (e.g., "at -N cost", "pay N Wealth"):** roll the payment tracking inside the Reaction class using running `$paidCardIds`/`$paidWealth`/`$paidHasWealthCard` state. Do NOT route through `PAY_STATE_PLAY_BRUTE` — it's tied to the player-turn state cycle and won't return correctly from reactions fired in dawn/dusk/duel contexts. See Pattern D's "Reactions that need to pay a wealth cost" subsection and `Reaction_03003`. Mirror `UtilitiesTrait::isValidWealthPayment` semantics (exact OR `cost+1`-with-Wealth-card). Queue discards atomically at finalize, not per-click, so `Decline` is a clean rollback.
16. **For "Put into play from hand or discard" Reactions:** `createCharacterMusteredEvent` does the actual move. `createCardRemovedFromPlayerDiscardPileEvent` is notification-only and exists so JS clients can sync their `player.discard` array — fire it BEFORE the muster event when the card is from discard. Pattern reference: `Action_01024` (Bravos), `Reaction_03003`.
17. **For dueling-line-derived running bonuses** ("+N[Stat] for each X in my dueling line"): there is no event fired when a card enters the dueling line (`cards->moveCard` is called directly, bypassing `EventCardMoved`). Recompute the running bonus at `EventDuelEndOfRound` from `getCardObjectsAtLocation(LOCATION_DUELING_LINE, controllerId)`. Reset at `EventDuelEnd` via direct inverse-delta (NOT a recount — `stDuelEnd` queues `EventDuelEnd` BEFORE the line-clearing discard events, so the line still contains the round's cards). Gate the recount on the owner being a duel participant (the line is per-player, not per-character). Pattern reference: `_03004` Elena and Pattern A's "Dynamic stat bonuses tied to the dueling line" subsection.
18. **For "opposing characters are considered <Trait>" passives:** mutate the opposing characters' `ModifiedTraits` via `addTrait` / `removeTrait` (Wilhelm `Action_02013` shape), NOT a `hasTrait` override on the owner (Uwe `_01043` shape only works when the receiver of the call is the modified card). Track a `TaggedOpposingIds` set on the listener — `Card::addTrait` appends without dedup, so untracked re-tagging on every ability-use event will pile up duplicate trait entries that `removeTrait` won't fully clear. Untag at the scope boundary named by the text (`EventPlayerTurnEnd` for "while using your abilities" → turn-scope) and add `EventCardMoved` / `EventCharacterDestroyed` cleanups for the owner. Pattern reference: `Action_03013` (Daniella Dietrich).
19. **For continuous Actions** (passive abilities mounted on a `CharacterAction` that the player never triggers from the menu): `isAvailableToPlayer` returns `false`; `handleEvent` does the work; explicitly call `$this->setUsed($event->theah, false)` at the scope boundary you want the Action to "reset" at (typically `EventPlayerTurnEnd`) — the parent `CardAction::handleEvent`'s `EventDuskEndOfDay` reset alone isn't frequent enough for an effect that needs to persist within a single turn but renew next turn. Mirror `Reaction_01196` "Continuous" for the never-`setUsed(true)` Reaction analogue. Pattern reference: `Action_03013` and Pattern A's "Continuous Action" subsection.
20. **For techniques with "Wound X • effect" cost-bearing text:** queue `createCharacterBeingWoundedEvent` at the `EventResolveTechnique` handler BEFORE the `createTechniqueTransitionEvent`, so the cost fires before the player picks a target. Queueing the wound from inside `actFromTechniqueWithId` would invert the printed cost/effect ordering and let a player decline the picker to dodge the cost. Pattern reference: `Technique_03013` and Pattern E's "Wound-as-cost" subsection.
21. **For swap techniques in challenge AND duel contexts:** mint TWO state classes under `modules/php/States/<expansion>/` — `State_highDramaChallengeActionResolveTechnique_NNNNN` (id `455` + cardId, routed from `HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS`) and `State_duelChooseTechnique_NNNNN` (id `521` + cardId, routed from `DUEL_CHOOSE_TECHNIQUE_EVENTS`). Both states use the same transition name (`"NNNNN"`) in `createTechniqueTransitionEvent`; the per-dispatcher lookup routes correctly. The technique's swap mechanics live inline in `actFromTechniqueWithId` branched on `$state` — challenge-context moves `DUEL_CHALLENGER` condition + queues `ChallengerSwappedEvent` + sets `CHOSEN_PERFORMER`; duel-context calls `swapParticipantsInDuel($duelId, $round, $owner->Id, $target->Id)`. Keep ONE thing in `handleEvent` — the `EventGenerateChallengeThreat` `actorId` redirect, which has to happen at event-fire time so `Character::handleEvent`'s threat-add and the EventHub notification reference the new challenger. Both states need JS handlers in `OnEnteringState.<expansion>.js`, `OnUpdateActionButtons.<expansion>.js`, `OnLeavingState.<expansion>.js`. Disambiguate `descriptionMyTurn` with `Name (Title)` when other cards share the character's name. Pattern reference: `Technique_03013` and Pattern E's "Technique usable in BOTH challenge and duel contexts" subsection.
22. **For wound-prevention passives** ("<X>'s abilities cannot wound <Owner>" / "<Owner> ignores wounds from <Y>"): override `eventCheck` on the card class (NOT `handleEvent`) on `EventCharacterBeingWounded` and zero `$event->wounds` when the gate trips. Reasons: (a) `EventHub` only emits the past-tense `EventCharacterWounded` when `wounds > 0`, so zeroing in the *Being*-tense event also suppresses every downstream listener that keys on the past-tense event — cleaner than Maxime's skip-`parent::handleEvent` shape; (b) `eventCheck` runs before any handler, so the mutation is visible to everyone. Use `$event->abilityId == ''` as the threat-conversion signal (`StatesTrait::stDuelEndOfRound` omits the ability id when emitting threat-to-wounds — every ability emitter passes it). Use `$source->ControllerId != $this->ControllerId` for "opponent's ability" scope; use Sorcery-trait / `ISorcererAbility` + `CHOSEN_PERFORMER` for "abilities he performs" scope (Maxime). Wound-movement (heal+wound recipe) is automatically covered by the wound-block — don't add a special handler. Pattern reference: `_03014` Kaspar (zero), `_01153` Breastplate (reduce-by-one), `_01069` Maxime (alternative `handleEvent` shape — only use for "abilities he performs" scope).
23. **For techniques gated on "equipped with X or X in dueling line":** OR two checks in `isAvailableToPlayer` — iterate `$owner->Attachments` (ids → `getCardById($id)` → `hasTrait(...)`) AND iterate `getCardObjectsAtLocation(LOCATION_DUELING_LINE, $owner->ControllerId)`. Both are inside the standard `IN_DUEL` + actor-is-owner gate. There is no `hasAttachmentWithTrait` helper — the id-then-lookup pattern is the codebase convention (`Maneuver_01054`). Pattern reference: `Technique_03014` and Pattern E's "equipped with X or X in dueling line" subsection.
24. **For "after X musters" triggers:** the conditional MUST OR `EventCharacterMustered` AND `EventApproachCharacterPlayed`. The Approach card path emits a distinct event, so a single-event hook silently misses Approach-driven entries. Pattern reference: `_01009` Cirilo (mercenary→Brute) and `_03015` Joern (Forced self-wound). See Pattern A's "Forced muster/approach triggers" subsection.
25. **For phase-conditional Resolve modifiers** ("During <Phase>, X has ±N Resolve"): mutate `$this->ModifiedResolve` directly — there is no `createCharacterResolveModifiedEvent` factory (unlike Combat/Finesse/Influence/Panache which all have one). Gate the apply with a private bool flag so attachment-driven `ModifiedResolve` churn doesn't desync. Manually emit `createCharacterDestroyedEvent` (mirroring `EventHub.php:251`'s unequip path) if the reduction crosses the wounds-equal-resolve threshold — `Character::handleEvent`'s destruction check only runs inside `EventCharacterWounded`. Restore at `EventDuskEndOfDay` (or whichever phase-end event matches the printed scope), unconditionally on the flag — destroyed objects in the Locker are fine, and the unconditional restore guards against any hypothetical return-from-Locker path that skips the constructor. Pattern reference: `_03015` Joern and Pattern A's "Phase-conditional Resolve modifier" subsection.
26. **For "+N [Stat] while <self-condition>" passives** (Combat/Finesse/Influence/Panache): use a flag-based recompute pattern, NOT a recompute-from-base. Hook the event(s) that toggle the condition (`EventCharacterWounded`/`EventCharacterHealed` for "while wounded", `EventCardEngaged`/`EventCardEngarded` for "while engaged", etc.) gated on `characterId == $this->Id`. Call `parent::handleEvent($event)` FIRST so the parent updates `$this->Wounds` / `Engaged` / etc.; then re-derive the boolean and queue `createCharacter<Stat>ModifiedEvent(±1)` only on flag transition. Skip if `IsDying` or `characterIsInDiscardOrLocker`. Pattern reference: `_03016` Ise (+1 Combat while wounded) and Pattern A's "Stat bonus while a self-condition holds" subsection. The flag avoids clobbering attachment-driven `ModifiedCombat` etc. **Resolve has no factory — use the `_03015` Joern direct-mutation pattern instead.**
27. **For "During <Phase>, you may choose not to <auto-action>" Reactions** (Dusk opt-out, Dawn cleanup opt-out, etc.): listen on the *pre*-event (e.g., `EventCardMoving`) and use the `cancelDeclinedByCardIds` re-queue dance. Cancel the event in `handleEvent` (`$event->canceled = true`), clone-and-store it (with `unset($cloned->theah)`), prompt the player; "Keep" path calls `setUsed(true)` and discards the clone; "Decline" path re-queues the clone with `cancelDeclinedByCardIds[] = $owner->Id` so `handleEvent` doesn't immediately re-catch it. Gate the trigger on the auto-emitter signal — for the Dusk move-home, that's `sourceId == 0` AND `TURN_PHASE == Game::DUSK`. Use `stackEvent` (not `queueEvent`) for the transition so the prompt fires before subsequent dusk cleanup events. Pattern reference: `Reaction_03016a` (Ise, on a Character in play) and `Reaction_01140` (in-hand sibling). See Pattern D's "Cancel-and-reissue Reaction" subsection.
28. **For "Reaction: After <enemy/X> character moves to this location" triggers:** listen on `EventCardMoved` (past-tense, the move has committed). Required gates in order: `isAvailable()`, `cardInCity($owner)` (enemies can't enter your Home), `event.cardId != $owner->Id` (skip the owner's self-moves), `event.toLocation == $owner->Location`, `getCardById` returns a Character, `ControllerId != 0`, and the enemy/friendly controller check that matches the text. ALWAYS include a valid-effect-target precondition (`count($eligibleEffectTargets) > 0`) before queuing the transition, or the player gets a useless prompt. To MOVE another character to the owner's location, queue `createCardMovingEvent($mover.ControllerId, $mover.Id, $mover.Location, $owner.Location, $engage=false, $owner->Id, $this->Id)` — there's no pull/teleport helper, the standard move event handles all bookkeeping. Pattern reference: `Reaction_03016b` (Ise). For the *self-moves* analogue ("after this character moves to a new location"), the receiver is a `handleEvent` on the card itself — see `_01067` Jean Urbain / `_02022` Stranahan.
29. Write a journal entry in `.cursor/journal/YYYY-MM-DD-NN-<card>.md`. Capture the **WHY** of any non-obvious decision — event-type choice, why the Reaction was not flagged `ISorcererAbility` (or why it was), what the identity-check field is on the event (`sourceId` vs `performerId` vs `cardId`), why a particular state-ID encoding, why a button-based Reaction was chosen over state classes, why a new challenge type was added vs. piggybacked on an existing one. Read the Cesca journal (`2026-05-13-01-cesca-del-rosso-03001-implementation.md`), the Aja journal (`2026-05-13-02-aja-03002-implementation.md`), the Don Constanzo journal (`2026-05-14-01-don-constanzo-03003-implementation.md`), the Elena journal (`2026-05-16-01-elena-agnelli-03004-implementation.md`), the Kaspar Iron Reforged journal (`2026-05-25-02-kaspar-dietrich-03014-implementation.md`), the Joern journal (`2026-05-29-03-joern-kietelsson-03015-implementation.md`), and the Ise journal (`2026-05-29-04-schwester-ise-03016-implementation.md`) — between them they cover the End-of-Dawn / Sorcerer-trigger / move-wound / state-ID-encoding / issue-a-challenge / Gambling-Technique / new-challenge-type / performer-≠-owner / click-to-pay-Wealth / muster-from-discard / dueling-line-recompute / wound-prevention-via-eventCheck / muster-includes-Approach / phase-conditional-Resolve / while-wounded-stat-bonus / cancel-and-reissue-Reaction / after-enemy-moves-here decisions in detail.
