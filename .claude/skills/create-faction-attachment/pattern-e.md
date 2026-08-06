> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern E — Technique / Maneuver

`Technique` and `Maneuver` cards fire during duels — Techniques during a normal duel turn, Maneuvers when a maneuver event is queued (typically from another card).

Both need:
- `implements IHasTechniques` / `implements IHasManeuvers` on the attachment.
- `use TechniqueTrait` / `use ManeuverTrait`.
- A `$this->Techniques = [new Technique_NNNNN()]` / `$this->Maneuvers = [...]` registration in the constructor **after** `$this->resetCard()` (same ordering as Actions/Reactions).

**Pre-commit hook on Maneuver:** must handle `EventManeuverCanceled` (either branch it in `handleEvent` or add a comment `// EventManeuverCanceled handler not needed`).

**Pre-commit hook on Technique:** same — must handle `EventTechniqueCanceled` or add the equivalent comment.

References: `Technique_01050` (Unsavory Salve — -1 Thrust + wound), `Maneuver_01133` (Matushka's Efficiency), `Technique_03043` (El Gato's Mask — Gambling + reveal/discard), `Technique_03064` (Harpoon — Gambling + remainder-of-duel condition), `Technique_04016` (Drachenblut — Gambling + EndOfRound +1/+1 threat).

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

When the cost is **"Engage this card"** (the attachment), also gate `! $attachment->Engaged` and queue `createCardEngagedEvent($playerId, $attachment->Id, $attachment->Id, $this->Id)` on `EventResolveTechnique` — mirror `Technique_01049` / `Technique_03064`.

`Technique` base sets `Used` on `EventTechniqueActivated` and resets on `EventDuelEnd` when `ResetOnDuelEnd` (default true) — do not double-`setUsed` unless a multi-step resolve needs it (`Technique_01096`).

### Deferred EndOfRound ("At the end of your round, …")

When the Technique effect fires **after the round ends** (not during CalculateValues / Resolve), use a public `$IsActive` flag — **not** a `Game::*_CONDITION` (that is for remainder-of-duel restrictions like Harpoon).

```php
// EventResolveTechnique → arm
$this->IsActive = true;
$attachment->IsUpdated = true;

// EventDuelEndOfRound → fire only on "your" round
if ($this->IsActive && $owner !== null && $event->actorId == $owner->Id)
{
    // effect…
    $this->IsActive = false;
    $attachment->IsUpdated = true;
}

// EventTechniqueCanceled / EventDuelEnd → clear stranded IsActive
```

**WHY `actorId == owningCharacter->Id`:** "your round" means the equipped character's round as duel actor. Do **not** clear `IsActive` on every EndOfRound — a stray non-owner EndOfRound would eat the pending effect. Clear on fire, cancel, and duel end.

**"Each participant gains a threat":** `EventFactory::createThreatModifiedEvent(1, 1)` — challenger delta + defender delta. Sibling: `Reaction_02039`. No GameState when there is no picker.

Reference: `Technique_04016` (Drachenblut). EndOfRound siblings: `Technique_03039` (MoveHome flag), `Technique_01096` / `Maneuver_01031` (IsActive + picker).

### Remainder-of-duel lasting effects (condition)

When printed text says the adversary (or participant) **"has -N [Stat], cannot be swapped, and cannot move for the remainder of the duel"** (or similar lasting duel-scoped restrictions), do **not** rely on a Technique `$Active` bool alone. Stamp a **`Game::*_CONDITION`** on the affected character.

**Not the same as while-equipped restrictions** (Pattern B'', Lodestone `_03065`) — those stamp on equip / clear on unequip and are not duel-scoped. Use Pattern E only when the printed duration is the duel (or "remainder of the duel").

Canonical: **`Technique_03064` (Harpoon)** + `Game::HARPOON_CONDITION`. Mirror Soline `_01089` for Finesse ±1 via `createCharacterFinesseModifedEvent` + condition Started/Ended notifies.

**Apply** (on `EventResolveTechnique`):
1. Pay engage cost if printed.
2. Queue stat-mod event (`createCharacterFinesseModifedEvent` / Combat / Influence siblings) with attachment inject code as reason.
3. `$character->addCondition(Game::YOUR_CONDITION)` + `$game->updateCardObjectInDb($character)` (IsUpdated alone is not always flushed before the next rebuild — same lesson as 03062 muster stamps).
4. Notify `yourConditionStarted` with `cardId` so JS can push the condition onto `card.conditions` and refresh the tooltip.
5. Persist `$AffectedCharacterId` on the Technique + `$attachment->IsUpdated = true`.

**Clear** (on `EventDuelEnd` and `EventTechniqueCanceled` / `EventManeuverCanceled`):
1. If `AffectedCharacterId > 0` and character still has the condition and is not in discard/locker → reverse the stat mod, `removeCondition`, `updateCardObjectInDb`, notify `*Ended`.
2. Reset `$AffectedCharacterId = 0`.

**Enforce "cannot move"** — two layers:
1. **Central:** `Character::eventCheck` on `EventCardMoving` when `$this->hasCondition(...)` and `! $event->unstoppable` → `UserException`. Condition is the source of truth even if the attachment leaves `$theah->cards`.
2. **Activate-time on deferred movers:** Techniques/Maneuvers that *choose now, move later* (`EventDuelEndOfRound`) must also `eventCheck` `EventTechniqueActivated` / `EventManeuverActivated` when the character who would move is conditioned. WHY: keep the button visible so the player gets an explanatory exception, instead of locking in a location/target and only failing at EndOfRound / `swapParticipantsInDuel`. See `Technique_01036`, `Maneuver_01164`, `Maneuver_01059` (actor), `Maneuver_01033` (adversary — "Move Adversary Home"; also checks Lodestone `_03065` for Home-specific blocks).

**Enforce "cannot be swapped"** — must gate **before** duel rows mutate:
1. **Central:** `Theah::swapParticipantsInDuel` — if `$oldParticipant->hasCondition(...)` throw. WHY: `EventChallengerSwapped` / `EventDefenderSwapped` only queue *after* the swap is already applied; `eventCheck` on those events is too late.
2. **Activate-time on swap techniques:** `EventTechniqueActivated` check when the owning/swap-out character is conditioned — especially when Resolve pays a wound *before* the picker (`Technique_03013` Daniella). Also `Technique_01063Swap` (Bastien). Optional UX: Back button on the Musketeer picker so a Harpooned participant is not stuck (`State_duelChooseTechnique_01063`).

**Do not conflate:**
- **"Cannot be swapped"** ≠ intervene. Intervene is challenge-time; a mid-duel Gambling Technique never needs an intervene gate.
- Challenge-time `ChallengerSwapped` (pre-duel Daniella path) does not go through `swapParticipantsInDuel` — usually no condition exists yet.

**JS for the condition** (Soline / Épée Sanglante shape — tooltip only, no chip required):
1. Constant on `seventhseacityoffivesails.js` matching the PHP string exactly.
2. Register `*ConditionStarted` / `*ConditionEnded` in `Notifications.js` durations + handlers that push/filter `card.conditions` and `refreshTooltipForCard(card)`.

**Finesse floor footgun:** `EventHub` clamps ModifiedFinesse with `max(0, …)`. Clearing with +1 after a 0-floor can overshoot printed 0. Soline has the same footgun — mirror deliberately unless Rules ask otherwise.

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
