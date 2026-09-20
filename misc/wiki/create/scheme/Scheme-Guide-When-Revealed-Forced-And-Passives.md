# 07 — When Revealed, Forced, and Passives

← [[06 — Reactions|Scheme Guide Reactions]] · [[Index|Implementing a Scheme Card]] · Next: [[08 — Challenge Actions|Scheme Guide Challenge Actions]]

These effects live on the **scheme card class** (`_NNNNN.php`). They are not Actions and usually are not Reactions.

## When-Revealed

Printed: **"When this scheme is revealed, …"**

Fires **before** any scheme's resolve. You must:

1. Override `hasWhenRevealedEffect()` to return `true`.
2. Handle `EventCardWhenRevealedEffect` when `$event->cardId == $this->Id`.

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
        // pre-resolve work
    }

    if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
    {
        // normal resolve
    }
}
```

Reference: `_01151` (Shifting Tides).

## Forced at end of Planning

Printed: **`<b>Forced:</b> At the end of Planning • …`**

This is **not** scheme resolve. It fires from `EventPhasePlanningEnd`.

### Trigger gate

```php
if ($event instanceof EventPhasePlanningEnd && $this->Location == Game::LOCATION_PLAYER_HOME)
{
    // Forced effect — queue draws / transitions here
}
```

**Why Home?** Chosen schemes stay at Home until Dusk. Same gate as `_01098` and `_03041`.

### If the Forced needs a player pick

Use a **different** state family than resolve:

| Piece | Value |
|---|---|
| Constant | `States::PLANNING_PHASE_END_<NNNNN>` = `28<NNNNN>` |
| Transition map | `PLANNING_PHASE_END_EVENTS.transitions` (not resolve map) |
| State name | `planningPhaseEnd_<NNNNN>` |
| Transition back | `"" => States::PLANNING_PHASE_END_EVENTS` |

The same card-number key `"NNNNN"` may appear in **both** resolve and end maps — that is intentional (separate lookups). Reference: `_01098`.

### Draw-then-discard subtype

Canonical: Proper Study (`_03041`).

1. Compute how many to draw (e.g. 2, or 3 if you control an Academic).
2. "Control a trait" = loop `getCharactersInPlayByPlayerId` + `hasTrait(...)` (includes Home + Leaders).
3. Clamp to drawable cards (faction deck + discard). If **0** drawable: notify and **return** — do not open a discard state (equal number of 0 draws is 0 discards).
4. Persist `$cardsToDiscard` on the scheme; queue draws **before** the discard transition so the hand UI has the new cards.
5. Discard via `actFromCardWithIds`; JS multi-select + `EventHandlers.js` enable Confirm only when selection length equals needed.

## Forced at end of High Drama

Printed: **`<b>Forced:</b> At the end of High Drama, …`**

- Listen on `EventHighDramaPhaseEnd` with `$this->Location == LOCATION_PLAYER_HOME`.
- Player picks use `HIGH_DRAMA_END_<NNNNN>` (`60<NNNNN>`) under **`HIGH_DRAMA_END_EVENTS.transitions`**.
- Reference: `_03061` (Burn like Mice).

## Other Forced (event-driven, no menu)

Example: Forced when a character is destroyed at a location during a duel — override `handleEvent`, no Action/Reaction file. May still need a state if the Forced itself requires a pick.

Reference: `_02052`.

## Passives (ongoing modifiers)

Example: **"When an opponent equips a card to a character opposing your \<Trait\>, it gains +1 cost"**

Override `getEquipDiscount` on the scheme and return `$discount -= 1` (negative discount = cost increase).

Gates that matter:

1. Scheme at `LOCATION_PLAYER_HOME`.
2. Performer is an opponent.
3. **`$theah->cardInCity($performer)`** — Home shares one location string across players; without this, Home equips false-positive.
4. You control a traited character at `$performer->Location`.

Reference: `_03063` (Smuggling Run). Character parallel: `_01092` (Makepeace).

## Next

Challenge Actions → [[08 — Challenge Actions|Scheme Guide Challenge Actions]]  
Or jump back to [[05 — Actions|Scheme Guide Actions]]
