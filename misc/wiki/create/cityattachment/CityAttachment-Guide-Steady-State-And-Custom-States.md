# 07 — Steady-state and custom states

← [[06 — Reactions|CityAttachment Guide Reactions]] · [[Index|Implementing a CityAttachment Card]] · Next: [[08 — Wiring|CityAttachment Guide Wiring States And JavaScript]]

These two patterns are the CityAttachment specialty. Devil Jonah's Bones (`_03cd05`) uses **both**. Read that file before inventing anything new.

## Steady-state override (NOT event mutation)

**For properties that are read at fixed points in the game flow, override the matching `Card::get*` method. Do not mutate globals from `handleEvent`.**

Bones' "+1 gamble reveal" was first drafted as `handleEvent(EventGambleSetup)` bumping a count global. That was rejected. The correct pattern:

```php
public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, array &$explanations): int
{
    $count = parent::getNumberOfGambleCardsToReveal($theah, $actor, $explanations);

    if ($this->isAttached() && $actor->Id == $this->AttachedToId)
    {
        $count += 1;
        $explanations[] = sprintf(
            $theah->game->translate('%s reveals +1 card when Gambling.'),
            $this->getInjectCode()
        );
    }

    return $count;
}
```

**Why:** `Theah::getNumberOfGambleCardsToReveal` iterates every card in play and sums contributions. The count is a *steady-state property* of the play area — recomputed from scratch each gamble setup. Same idea as Sarafina `_01010`, Ivy `_02042`, Roll the Bones `_01114`.

**Rule of thumb:** if `Theah` greps cards and sums a `get*` call, override that method. Do not store the bonus in a transient global.

Other lasting play-area properties (pressure tallies, cost discounts, wounds capacity hooks) follow the same principle when a `get*` hook already exists.

## Custom state inserted into a core (auto) flow

If the attachment must prompt the equipped character's controller **during** an existing core state (mid-duel, mid-pressure, …) and that core state is type `game` (auto-running), you cannot put interactive logic inside it. The framework forbids player choices in game states.

### The Bones pattern — split setup from execution

1. Add a new `<thing>Setup` **game** state *before* the original auto state.
2. Add an immediately-following `<thing>SetupEvents` state that runs queued events (transitions, reactions, pay-for-reaction).
3. Reroute **all** existing transitions that used to enter the original auto state so they enter the new setup state instead.
4. Let the original auto state run as before after the setup window ends.

For `_03cd05` this looks like:

```
* → DUEL_GAMBLE_SETUP (game: queues EventGambleSetup)
  → DUEL_GAMBLE_SETUP_EVENTS (game: stRunEvents)
       ↳ "03cd05" → DUEL_GAMBLE_SETUP_03CD05 (activeplayer: top/bottom choice)
       ↳ "reaction" / "pay" → reaction side paths
       ↳ "endOfEvents" → DUEL_GAMBLE_REVEALED  ← original entry point
```

Several prior transitions into `DUEL_GAMBLE_REVEALED` were rerouted to `DUEL_GAMBLE_SETUP`. When you insert a setup window, **hunt every transition** that used to jump to the old entry — missing one leaves a silent bypass of your prompt.

### Mint a setup event

Bones listens for `EventGambleSetup`. New setup events need:

- Constant in `Events.php`
- No-op (or minimal) handler in `EventHub.php`
- Factory method in `EventFactory.php`

Carry `actorId` (participating character) and `playerId` (controller). Keep fields minimal.

### Globals for per-trigger choices

Bones uses `Game::GAMBLE_REVEAL_FROM_BOTTOM`. Clear duel-scoped globals in the matching cleanup state (`stDuelEndOfRound` for duel flags).

**Defensively reset the default branch.** If the player picks the default (top), explicitly set the global to `false` — do not "leave it alone." A previous round may have left it `true`.

### Player-choice state on the card

The activeplayer state calls into the card via `actFromCardWithId`. Bones interprets `$id` (`1` = top, `2` = bottom) and writes the global, then `$game->gamestate->nextState()`.

Zombie players fall through without setting the global — defaults to the safe baseline.

State files live in `modules/php/States/<expansion>/`. See [[08|CityAttachment Guide Wiring States And JavaScript]] for the state class + JS checklist.

### Deck helpers Bones uses

When revealing from bottom / sinking to top, you will meet:

- `getCardsOnBottomOfPlayerFactionDeck($playerId, $nbr)` — sorts `card_location_arg` ASC (lower = bottom)
- `insertCardOnExtremePosition($card, $location, $bOnTop)` — `$bOnTop = true` means place on top

**Landmine:** a local `$fromBottom` flag may numerically match `$bOnTop` but the *meanings* are unrelated. Comment the call site so a future edit does not invert sink direction.

## When you do *not* need this page

- Pure Forced wound / trait grant → [[04|CityAttachment Guide Passives And Forced]]
- High Drama Action that only needs its own picker state (not inserting into a shared auto flow) → [[05|CityAttachment Guide Actions]] + [[08|CityAttachment Guide Wiring States And JavaScript]]
- Immediate Action with no picks → [[05|CityAttachment Guide Actions]] only

## Checklist for this pattern

- [ ] Steady bonuses use `get*` overrides, not global mutation in `handleEvent`
- [ ] Custom mid-flow prompts insert Setup + SetupEvents before the auto state
- [ ] Every old transition into the original entry was rerouted
- [ ] New events registered in Events / EventHub / EventFactory
- [ ] Globals cleared in the right cleanup state; default branch resets explicitly
- [ ] JS buttons exist for the new activeplayer state

## Next

Wire states and JavaScript → [[08 — Wiring states and JavaScript|CityAttachment Guide Wiring States And JavaScript]]
