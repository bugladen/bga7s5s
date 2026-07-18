> Part of **create-faction-attachment**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

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
