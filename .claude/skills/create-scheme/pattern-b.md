> Part of **create-scheme**. Open from [SKILL.md](SKILL.md) only when the shape table routes here - keep WHYs intact; do not summarize away regression traps.

## Pattern B — When-Revealed effect

Override `hasWhenRevealedEffect()` so the framework knows to fire `EventCardWhenRevealedEffect` against this scheme *before* any scheme's resolve.

```php
public function hasWhenRevealedEffect(): bool
{
    return true;
}

public function handleEvent(Event $event)
{
    parent::handleEvent($event);

    if ($event instanceof EventCardWhenRevealedEffect && $event->cardId == $this->Id)
    {
        // pre-resolve cleanup or setup work
    }

    if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
    {
        // normal resolve
    }
}
```

Reference: `_01151` (Shifting Tides) — When Revealed discards all city cards from city locations *before* any other scheme resolves.

## Become a copy of an opponent's scheme (player pick)

Printed: **"When this scheme is revealed, if you control an Academic, this scheme becomes a copy of an opponent's scheme in play or in The Locker."** Often paired with **"If you do not control an Academic, add a Renown to any location."**

1. `hasWhenRevealedEffect(): true`. On `EventCardWhenRevealedEffect`: Academic gate; if eligible schemes exist, `createTransitionEvent(..., "NNNNN")` into a When-Revealed pick state.
2. **Transition map:** `"NNNNN"` on **both** `PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_EVENTS` and `…_CHOOSE_ORDER_EVENTS`. State id `24<NNNNN>` (e.g. `2405024`). Pick state returns to main When-Revealed EVENTS (`240`).
3. Eligible = opponent `Scheme` at `LOCATION_PLAYER_HOME` **or** in any opponent locker via `getCardObjectsAtLocation($game->getPlayerLockerName($id))` — do **not** scan `$theah->cards` for locker.
4. **Copy = Gary same-Id class swap** (`instantiateCard(sourceType, $this->Id)` + `addCardToWorld` + `updateCardObjectInDb`). RiskClone shells cannot host the source's resolve transition keys / `actFromCard*`.
5. **Assign `$copy->Initiative` / `$copy->PanacheModifier` from the live source** (not the shell's 0/0). Leader already absorbed the shell on `EventSchemeCardRevealed` — apply `source.PanacheModifier - shell.PanacheModifier`.
6. **Keep `card_type` as the shell id** (`05Hacked`) so Unique `getCardIdByType` for the real source stays unambiguous; serialize carries the copy class.
7. Stamp `Game::STOLEN_SCHEMATICS_COPY`. **EventHub** `EventCardSentToLocker` (hub-before-cards) reverts to the shell **after** the locker move — handlers no longer live on the shell class after the swap.
8. Renown else-branch is **Pattern A resolve** on the shell only when the player does **not** control an Academic. Academic + zero targets → notify; do **not** fall through to Renown.
9. **Home UI:** reuse `characterFlipped` with full `card` property array; client **recreates** the scheme at `{playerId}-scheme-anchor` (initiative/panache chips are not character combat/finesse nodes). Sync `gamedatas.homeCards`. chooseList uses `args.cards`. Prototype CAD: plain strings.

Reference: `_05Hacked` / `State_planningPhaseResolveWhenRevealed05Hacked` / Gary `_05Gary::flipToBSide`.
