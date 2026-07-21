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

### Pattern E.2 — Action-only "-1 cost if your Leader is …" (no Maneuver printed)

When printed text discounts the card's Wealth cost based on Leader traits (or similar out-of-duel conditions) **and the Risk has an Action/City Action but no Maneuver**, put the discount on the **Action** via `getActionFromHandDiscount` — not on the Risk class, and **not** on a fabricated Maneuver.

```php
public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, array &$explanations): int
{
    $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

    // WHY: Action-only Risk — combat-card pay has no Maneuver discount channel.
    // Do not invent a Maneuver solely to carry getManeuverFromCombatCardDiscount.
    if ($action->Id == $this->Id)
    {
        $owner = $this->getOwningCard($theah);
        $leader = $theah->getLeaderByPlayerId($owner->ControllerId);
        if ($leader !== null && ($leader->hasTrait('Villain') || $leader->hasTrait('Pirate')))
        {
            $discount += 1;
            $explanations[] = sprintf(
                $theah->game->translate("%s: -1 because Leader is a Villain or Pirate."),
                $owner->getInjectCode()
            );
        }
    }

    return $discount;
}
```

**WHY not invent a discount-only Maneuver:** `_01159` (Appealing), `_01160` (Bleed Out), and `_03071` (Leverage) all print a general "-1 cost if Leader …" clause with only an Action. Combat-card play of those Risks pays full `WealthCost`. Inventing a Maneuver invents a printed ability. If Eddie later asks for combat-card parity, *then* add `getManeuverFromCombatCardDiscount` on a real Maneuver (or a dedicated discount Maneuver with Eddie's OK).

**Gates:** `$action->Id == $this->Id` (sticky discount must not leak to other hand Actions); null-check `getLeaderByPlayerId` (Leader can be destroyed mid-game — `_01160` historically skipped this; new cards should not).

**Contrast Pattern E Maneuver discounts:** duel-relative predicates (engaged adversary, Finesse comparison, `DUEL_GAMBLED`) belong on Maneuvers because they only make sense at combat-card pay time. Leader-trait discounts on Action-only cards belong on the Action.

References: `Action_03071`, `Action_01160`, `Action_01159`.

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

