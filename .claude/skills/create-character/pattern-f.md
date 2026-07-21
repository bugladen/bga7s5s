> Part of **create-character**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

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

### Engage-as-cost is automatic — when (engagement trichotomy)

`StatesTrait::stIssueChallenge` auto-engages the performer for challenges of type `NORMAL`, `SERVO_SCARPA`, `TORVO_ESPADA`, and `AJA_CHALLENGE_TYPE` (the auto-engage list). Engagement for Pattern F actions is a **trichotomy** — read the printed cost and pick exactly one:

| Printed cost / shape | Eligibility | Auto-engage list | Manual `createCardEngagedEvent` |
|---|---|---|---|
| **"Engage [self/performer]"** (Aja, basic challenge) | Require `! Engaged` | Add the new type to the list | None (auto) |
| **No Engage printed, but unengaged performers still engage** (Don Constanzo's Thug) | Engaged performers eligible | Keep type **OUT** | `if (! $performer->Engaged) { … }` |
| **Not a basic challenge — never engages** (Sanjay `_03037`) | Engaged performers eligible | Keep type **OUT** | **None** — do not emit engage at all |

If your card has a different cost shape (e.g., engage a Weapon attachment instead of the performer), register a separate handler — see `Action_02013`'s `doCost` for the "discard a card" variant.

**Regression trap:** "No Engage printed" is **not** one pattern. Copying Don Constanzo's conditional-engage onto every such card is wrong. Ask: does this action engage the performer at all? Sanjay's City Action issues an Influence challenge without engaging — dedicated `SANJAY_CHALLENGE_TYPE` exists solely so `NORMAL` (which is on the auto-engage list) is not reused. No intervention/refuse restrictions → only `Game.php` + matching JS int; skip `interventionCheck` / Refuse-button wiring.

WHY keep a type out of the auto-engage list when engaged performers are eligible (Don Constanzo case): Auto-engaging an already-engaged card re-emits `EventCardEngaged`, and downstream reactions (e.g., Vittoria's `Reaction_01014` "instead of me" swap) treat that as a *fresh* engagement and misfire. Conditional engage:

```php
if (! $performer->Engaged)
{
    $engageEvent = EventFactory::createCardEngagedEvent(
        $performer->ControllerId, $performer->Id, $owner->Id, $this->Id
    );
    $game->theah->queueEvent($engageEvent);
}
```

The `eligibility filter` follows the same table. Aja checks `! $self->Engaged` because Engage is printed; Don Constanzo and Sanjay do NOT check `! Engaged`. Read the printed cost and match.

### Performer ≠ action owner

The default Pattern F assumes the action's owner is also the challenge performer. But some cards (e.g., Don Constanzo `_03003`: "Your **Thug** at this location issues a **Combat** challenge") have the owner *select* the performer separately. Adjust:

- **Two-step state machine.** Step 1 picks the performer (e.g., a Thug at the owner's location). Step 2 picks the target at the *performer's* location. State IDs follow the usual `4` + cardId scheme with `_2` suffix.
- **`CHOSEN_PERFORMER` is the picked performer's id, not the owner's.** Set it in step 1's act handler; reference it in step 2's `getArgsFromAction` so the target picker filters opposing characters at the performer's location (not the owner's — they're usually equal but stay correct if the performer was at a different valid location).
- **`isValidTargetForAbility(Game, Character)` reads `CHOSEN_PERFORMER` to find the controller and location** for the validity check, since `getOwningCharacter` returns the action owner (Don), not the performer (the Thug).
- **Engagement follows the trichotomy** (see previous section). Don Constanzo uses conditional engage in step 2; a never-engages variant would emit no engage event.

Reference: `Action_03003` (Don Constanzo).

Note that `canChallenge()` on the base `Character` class only checks `isControlled()` — it does NOT check `Engaged`. If your eligibility filter needs both, add `! $c->Engaged` explicitly. Characters that override `canChallenge` (e.g., Sigurd Ulfsen `_01190` permanent ban, Carmella `_01178` "engaged once" rule) handle their own engagement logic.

### Adding a NEW challenge type

A new `*_CHALLENGE_TYPE` constant is justified when the card imposes restrictions or behaviors that diverge from `NORMAL_CHALLENGE_TYPE` — e.g., Aja's "only Finesse ≥ 3 may intervene or refuse," **or** when you must avoid `NORMAL`'s auto-engage (Sanjay never engages; Don Constanzo needs conditional engage). Touch these files in lockstep when there **are** intervene/refuse restrictions:

| File | What goes there |
|---|---|
| `modules/php/Game.php` | `final const NEW_CHALLENGE_TYPE = N;` (next int after the highest existing). |
| `seventhseacityoffivesails.js` | `this.NEW_CHALLENGE_TYPE = N;` — same int. Client checks reference `this.NEW_CHALLENGE_TYPE`. |
| `modules/php/StatesTrait.php::stIssueChallenge` | Add the new type to the auto-engage `if` list (**only** if cost is "Engage performer" — trichotomy case a). |
| `modules/php/theah/Theah.php::interventionCheck` | Add an `else if` branch that throws `UserException` when the would-be intervener fails the card's restriction. Server-side enforcement. |
| `modules/php/ArgumentsTrait.php::argsHighDramaChallengeActionAcceptChallenge` | Post-filter `$charactersCanIntervene` so disallowed characters never appear in the picker. Add any extra args (e.g., `defenderFinesse`) the client needs to gate UI. |
| `modules/php/FrameworkActionsTrait.php::actHighDramaChallengeActionReject` | Throw `UserException` if the card forbids refusal under its conditions. |
| `modules/js/OnUpdateActionButtons.js::highDramaChallengeActionAcceptChallenge` | Add a `dojo.addClass('btnRefuse', 'disabled')` branch for the new type — mirror the existing `EPEE_SANGLANTE` / `UNSANCTIONED_DUEL` block. Use the server-supplied args (e.g., `args.defenderFinesse`) to compute the condition. |

The intervention-restriction story specifically:
- The args function filters the *visible* intervener list (UX).
- `interventionCheck` enforces the same rule on the server (security).
- For refusal, `actHighDramaChallengeActionReject` enforces server-side; the JS disable is UX. Always both.

### Character-scoped refuse restriction (NOT a new challenge type)

For text like Daichi `_03050`:

- "Daichi cannot refuse challenges issued by characters with greater [Combat]."
- "When Daichi issues a challenge, characters with greater [Combat] cannot refuse."

Ask first: does the restriction apply only to challenges **this card's Action issues**, or to **any** challenge involving this character (including NORMAL challenges issued *to* them)?

| Ownership of the refuse rule | Approach |
|---|---|
| Action-owned (Musketeer "cannot be refused", Aja Finesse ≥ 3, Torvo no intervene) | Mint / reuse a `*_CHALLENGE_TYPE` and wire the six type-integration points above. |
| Character-owned / relative-stat (Daichi) | **Do not** mint a challenge type. A type only covers challenges the Action sets — it misses "when someone challenges Daichi with NORMAL." |

Put a static (or instance) helper on the card class that encodes both printed lines. Daichi's unified rule: if either participant `instanceof _03050` and the **other** has strictly greater `ModifiedCombat`, refuse is blocked. "Greater" is strict `>` — equals may still refuse. Use `ModifiedCombat` / `ModifiedFinesse` / etc., not printed base stats.

```php
public static function challengeRefusalBlocked(Character $challenger, Character $defender): bool
{
    if ($challenger instanceof self) {
        return $defender->ModifiedCombat > $challenger->ModifiedCombat;
    }
    if ($defender instanceof self) {
        return $challenger->ModifiedCombat > $defender->ModifiedCombat;
    }
    return false;
}
```

Wire these files in lockstep (no `Game.php` challenge-type constant):

| File | What goes there |
|---|---|
| Card class (`_NNNNN.php`) | Helper above (and WHY comment: character-identity, not type). |
| `FrameworkActionsTrait::actHighDramaChallengeActionReject` | Throw `UserException` when the helper returns true — before When-Least-Expected discard routing. |
| `ArgumentsTrait::argsHighDramaChallengeActionAcceptChallenge` | Bool arg (e.g. `cannotRefuseDueToDaichi`) from the same helper. |
| `OnUpdateActionButtons.js::highDramaChallengeActionAcceptChallenge` | `dojo.addClass('btnRefuse', 'disabled')` when the arg is true. |
| `ZombieTrait` AcceptChallenge case | Default Accept when the arg is true — zombie otherwise calls Reject and would throw. |

WHY not `eventCheck` on `EventChallengeRejected` alone: the established refuse-block pattern (Aja / Épée) is throw-in-reject + JS disable. The card helper keeps the Combat comparison out of the framework traits' duplicated logic.

Reference: `_03050` Daichi. Contrast: `Action_03002` Aja / `Action_01071` Épée (type-owned).

### IAbilityThatTargetsCharacters

Always implement this interface on a challenge-issuing action — challenge target *is* a targeted character, so other cards' "before being targeted" hooks need to see it. Implement `isValidTargetForAbility(Game $game, Character $character): array` returning `[bool, string]`.

### Examples

| File | Demonstrates |
|---|---|
| `Action_02013` (Wilhelm Dünst) | "Discard a Card. Issue a Challenge." — discard-as-cost, then standard issue-challenge transition. Two-step state machine; reference for `doCost`/`doEffect` separation. |
| `Action_02034` (Torvo Espada) | Three-step "offer challenge → accept/decline → issue" flow with the `TORVO_ESPADA_CHALLENGE_TYPE` (no interventions allowed). |
| `Action_03002` (Aja) | Single-step picker → standard challenge flow with `AJA_CHALLENGE_TYPE` (Finesse ≥ 3 to intervene/refuse). Canonical reference for a NEW challenge type with restrictions. |
| `Action_03003` (Don Constanzo) | Two-step "pick your Thug → pick target". Performer is the chosen Thug, not the owner. New challenge type `DON_CONSTANZO_CHALLENGE_TYPE` deliberately kept OUT of the auto-engage list; action emits a conditional engage event in step 2 so already-engaged Thugs remain eligible. |
| `Action_03037` (Sanjay) | Single-step Influence challenge with **no engage at all**. `SANJAY_CHALLENGE_TYPE` out of auto-engage AND no `createCardEngagedEvent`. Hand-size target filter (`opponent hand < your hand`). Exemplar for "not a basic challenge — never engages." |
| `Action_01083` (Legendary Reputation) | RiskCityAction variant — sets `LEGENDARY_REPUTATION_CHALLENGE_TYPE` (only Leaders may intervene). |
| `_03050` (Mōri Daichi) | **Character-scoped refuse** via relative Combat — no new challenge type; helper + reject/args/JS/zombie. |

