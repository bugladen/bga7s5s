# 04 — City Actions

← [[03 — Classify|Risk Guide Classify The Printed Text]] · [[Index|Implementing a Risk Card]] · Next: [[05 — Actions|Risk Guide Actions]]

Use this page when the printed text has **`<b>City Action:</b>`** (or **Leader City Action**, **Duelist City Action**, etc.).

## Base class

```php
class Action_NNNNN extends RiskCityAction
```

File: `modules/php/cards/<expansion>/actions/Action_NNNNN.php`

`RiskCityAction` already requires that you have at least one character in the city and filters performers to city characters. Layer your own gates on top.

## Minimal City Action skeleton

```php
<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\<expansion>\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_NNNNN extends RiskCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Example City Action');
        $this->RequiresPerformerSelected = true; // "Your performer …"
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) {
            return false;
        }
        // Card-specific: trait gates, valid targets exist, etc.
        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            // Resolve immediately, OR queue a transition to a chooser state.
            // Always include createActionResolvedEvent() somewhere in this file
            // (real call, or a comment if the challenge flow fires it later).
            $resolvedEvent = EventFactory::createActionResolvedEvent($event->playerId, $event->actionId);
            $event->theah->queueEvent($resolvedEvent);
        }
    }
}
```

## "Your performer" semantics

When text says "Your performer …", set `$this->RequiresPerformerSelected = true`. The chosen id is in `Game::CHOSEN_PERFORMER` by the time target validation runs.

Override `getPerformersForAction` to filter candidates (must still start from `parent::…`). Common filters:

- Trait: `$p->hasTrait('Duelist')`
- Can challenge: `$p->canChallenge()` — note: this only checks `isControlled()`, **not** Engaged
- Engage cost on the Action: also require `! $p->Engaged`
- Influence challenge: also require `! $p->DashedInfluence`
- At least one valid target for this performer

## Challenge City Actions (common)

Many Risk City Actions issue a challenge. Typical shape:

1. Set `Game::CHALLENGE_TYPE` (usually `NORMAL_CHALLENGE_TYPE`)
2. Set `Game::CHALLENGE_STAT` (`STAT_COMBAT` / `STAT_FINESSE` / `STAT_INFLUENCE`)
3. Transition to the **shared** chooser: `"NNNNN" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET`
4. Implement `IAbilityThatTargetsCharacters` when the text says **Target**

Reference: `Action_03008` (Arrogant), `Action_01083` (Legendary Reputation — custom intervention rules).

### When you need a custom `CHALLENGE_TYPE`

Mint a new constant in `Game.php` when:

1. Intervention / refusal **gates** differ from normal ("Only Leaders may intervene"), **or**
2. Refusal / intervention carries a **side effect** tied to this card ("If they refuse, claim" / "Wound anyone who intervenes")

**Why:** `EventChallengeRejected` and `EventCharacterIntervened` have **no** `actionId`. You cannot correlate with `$event->actionId == $this->Id`. Gate the Risk's handlers on `globals->get(CHALLENGE_TYPE) == YOUR_TYPE`.

References: `_03021` (Cornered), `_03057` (Censure — claim on refuse).

### Engage cost + challenge

If the Action pays **"Engage your performer"** itself, keep your custom `CHALLENGE_TYPE` **off** the short list that `stIssueChallenge` auto-engages (`NORMAL`, `SERVO_SCARPA`, `TORVO_ESPADA`, `AJA`). Otherwise the performer is engaged twice.

### Do not grey the Action on claimability

When claim is a *consequence* of refuse or pressure success, check `canLocationBeClaimedBy` at **emit** time only. The challenge / pressure should still be playable if claim is currently illegal.

## Leader City Action

- Fetch Leader with `getLeaderByPlayerId`
- Do **not** set `RequiresPerformerSelected`
- Set `CHOSEN_PERFORMER` yourself if pressure / challenge pipelines need it later

Reference: `Action_03067` (Ambitious — wound + choose-stat pressure + claim + locker).

## Bullet "If …" clauses are often availability filters

Printed **If** lines like "If this location is controlled by an opponent" or "If their controller has more characters here than you" usually mean: **only offer performers / targets that satisfy the If**. Do not silently no-op after the player confirms.

## What you do *not* call from the Action subclass

Central confirmation already handles announce / pass-count. Do **not** call `$this->setUsed(true)`, `$this->resetPlayerPassCount()`, or `$this->announceAction()` from `RiskCityAction` subclasses.

## Pre-commit

Somewhere in the Action file: literal `createActionResolvedEvent` (real call **or** a comment that the challenge flow fires it).

## Common shapes (pick a mirror)

| Shape | Mirror |
|---|---|
| Target + Combat challenge | `_03008` Arrogant |
| Leader-only intervention challenge | `_01083` Legendary Reputation |
| Engage + challenge + side effect on refuse | `_03021` Cornered, `_03057` Censure |
| Engage + En garde friendly + may heal/draw | `_03034` La Voix… |
| Opponent claims + you move Renown | `_03056` Astute |
| Duelist + headcount If → Combat challenge | `_03058` Courageous |
| May engage / ignore costs + heal another | `_03060` Matushka's Song |
| Leader wound + pressure + claim + locker | `_03067` Ambitious |
| Opponent-controlled location → engage opposing | `_03071` Leverage |
| Target destroy engaged attachments + engage rest | `_03072` Sabotage |

## Next

Plain **Action:** (no City) → [[05 — Actions|Risk Guide Actions]]  
Otherwise → [[06 — Maneuvers|Risk Guide Maneuvers]]
