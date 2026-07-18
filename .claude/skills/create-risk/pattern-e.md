> Part of **create-risk**. Open from [SKILL.md](SKILL.md) only when the shape table routes here — keep WHYs intact; do not summarize away regression traps.

## Pattern E — Passive on the Risk class

For "While [condition] …" or other always-on effects of the Risk itself (typically combat-card cost discounts or in-duel stat modifiers), override `eventCheck` / `handleEvent` directly on the Risk class. Don't create an Action/Reaction file for passives.

```php
class _NNNNN extends Risk
{
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelCalculateCombatCardStats && $event->cardId == $this->Id)
        {
            // ... modify the event
        }
    }
}
```

Combat-card cost discounts ("this card has -1 cost when …") live on the **Maneuver** via `getManeuverFromCombatCardDiscount`, not on the Risk class — the discount applies only when this card is being played as a combat-card maneuver. Gate on `$owner->Id == $combatCard->Id` so copied/other cards do not inherit it. Common predicates:

| Printed condition | Gate |
|---|---|
| "While the adversary is engaged" | `$adversary->Engaged` — `Maneuver_01084` |
| "If your participant has more [Finesse] than the adversary" | `$actor->ModifiedFinesse > $adversary->ModifiedFinesse` — `Maneuver_03036` |
| "If this card was gambled" | `$theah->game->globals->get(Game::DUEL_GAMBLED, false)` — `Maneuver_03048` |

Use Modified stats and parse the comparison literally (`>` vs `>=`). For the gambled predicate, `DUEL_GAMBLED` is set in `actChooseGambleCard` before `PAY_STATE_USE_MANEUVER_FROM_COMBAT_CARD`, so it is live at discount time. Push a translated explanation into `$explanations` when the discount applies.

### Pattern E.1 — Forced on the Risk class (no player choice)

`<b>Forced:</b>` abilities with no chooser belong on the Risk's `handleEvent`, not a separate ability class. There is no Forced base class and no sub-state. Common shape for duel-line Forced effects:

**"After your adversary is destroyed, if this card is in your dueling line • …"**

Gate chain (all required):

1. `EventCharacterDestroyed`
2. `$this->Location == Game::LOCATION_DUELING_LINE`
3. `$game->globals->get(Game::IN_DUEL)` is truthy
4. Destroyed character is the **adversary of this card's controller** — not merely "someone in the duel"

Participant / winner lookup (same shape as `_02052` Gutter Full of Roses, scoped to `$this->ControllerId`):

```php
$challengerId = $theah->getDuelChallengerId();
$defenderId = $theah->getDuelDefenderId();
$destroyedId = $event->characterId;

$participantId = null;
if ($destroyedId == $defenderId
    && $theah->getCharacterById($challengerId)->ControllerId == $this->ControllerId)
{
    $participantId = $challengerId;
}
elseif ($destroyedId == $challengerId
    && $theah->getCharacterById($defenderId)->ControllerId == $this->ControllerId)
{
    $participantId = $defenderId;
}
```

**WHY not use `getDuelRoundActor()` / `getDuelRoundOpponent()` here:** those are round-relative. At destroy time the current round's actor may not be the surviving participant you need. Challenger/defender ids are stable for the whole duel.

**Heal discipline** (when the Forced effect heals): only queue `createCharacterBeingHealedEvent` when the participant is not in discard/locker **and** `$participant->Wounds > 0`. Mirror `Maneuver_01052`. Notify before queueing.

**WHY on the Risk class:** Forced with no player input is a passive — `_01102` (Unfortunate: Forced equip from dueling line at end of round) is the same shape. Do not invent a `Forced_NNNNN` ability file.

References: `_03033` (Glorious — heal on adversary destroyed), `_01102` (Unfortunate — equip from dueling line), `_02052` (scheme Forced for "any player's adversary destroyed" — same challenger/defender lookup, different scope).

