# Risky Undertaking (01185) Audit

## Important: `\BgaUserException` is deprecated
When writing `throw` statements for user-facing errors, use `Bga\GameFramework\UserException` (add `use Bga\GameFramework\UserException;` at the top). Do NOT use the old `\BgaUserException` — it is deprecated.

## Card Text
> Traits: Discovery, Explorer's Society, Fortune
> **City Action:** Discard two cards • Add a Renown to this location. Discard this card.

## Verdict: Fix Applied — Missing Server-Side Validation

### Fix: Added card ID validation in `actFromActionWithIds`

The original code accepted player-submitted card IDs for the "discard two cards" cost without any validation. It didn't check:
1. Whether the card exists (null check)
2. Whether the card belongs to the active player
3. Whether the card is actually in the player's hand

Compared with Action_01175 (Tending the Wounded), which performs all three checks before discarding. Added a validation loop before the discard loop, matching the 01175 pattern.

Also switched the discard event to use `$playerId` instead of `$card->OwnerId` — they're equivalent after validation, but using `$playerId` makes the intent clearer.

WHY the validate-then-execute two-loop approach: If we validated and discarded in one loop and the second card failed validation, the first card would already be queued for discard. Separating validation from execution ensures atomicity — either both cards pass or neither gets discarded.

### Everything else checks out

**Availability chain:**
- `Action` → always true ✓
- `CardAction` → owner belongs to player, not used ✓
- `EventCityAction` → player has character at the card's location ✓
- `Action_01185` → player has ≥2 cards in hand ✓

**Event ordering:**
1. Announce → Discard card 1 → Discard card 2 → Add Renown → City discard (self) → Action Resolved ✓

**State machine:**
- `HIGH_DRAMA_PLAYER_TURN_01185`: activeplayer, `actFromCardWithIds`/`actBack`, transitions to events or back ✓

**`setUsed` not called:** Correct — the card is discarded after use, so marking it used is unnecessary ✓

**Renown destination:** `$riskyUndertaking->Location` — the city location where the event card sits ✓
