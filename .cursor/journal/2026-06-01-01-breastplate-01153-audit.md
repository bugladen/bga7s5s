# Breastplate (_01153) Audit

## Card Text
> **Forced:** During a duel, the first time the equipped character would suffer any amount of wounds • Reduce the number by one.
> **Forced:** When the equipped character is wounded • Destroy this card. (0 wounds is not wounded.)

Castille FactionAttachment. 1 wealth, 0/3/1 R/P/T (Riposte dashed). Trait: Armor.

## Component Inventory
- `modules/php/cards/_7s5s/_01153.php` — the card class.
- Parents: `FactionAttachment` → `Attachment` → `Card`. Traits: `FactionCardTrait`, `WealthCostTrait`.
- No separate Action/Reaction/Maneuver/Technique files (just the two Forced abilities live on the card class itself).

## Things That Look Right
- **Forced #1 in `eventCheck`** correctly mutates `EventCharacterBeingWounded::$wounds` before the event runs. `Theah::queueEvent` calls `eventCheck` at queue time and serializes the modified event, so the EventHub's `wounds > 0` gate (EventHub.php:1958) correctly sees the reduced number. ✓
- **"0 wounds is not wounded"** is enforced at the hub level: if `wounds == 0` after reduction, no `EventCharacterWounded` is queued, so Forced #2 doesn't fire. ✓
- **Forced #2 in `handleEvent`** queues unequip-then-discard in the right order. ✓
- **Duel scope check** via `Game::IN_DUEL` global. ✓
- **`IsUpdated = true`** set whenever the private flag changes, so the serialized card state persists. ✓
- **Constructor sets stats and traits**; nothing odd. Riposte dashed / Parry 3 / Thrust 1 matches the published card. ✓

## Issues Found

### 1. (BUG, high) `$hasBlockedWound` never resets — "first time" becomes "first time ever"
The card text scopes "first time" to "during a duel" — standard 7s5s reading is per-duel (compare `Maneuver_01051` which clears its per-duel state on `EventDuelEnd`).

`$hasBlockedWound` is only set to `false` in `__construct()`. Once it flips to `true`, it stays true for the life of the serialized card object.

The bug only manifests in the "interesting" case:
- Wound of 1 → reduced to 0 → no `EventCharacterWounded` fires → card survives → flag stuck true.
- Wound of >1 → reduced but still >0 → `EventCharacterWounded` fires → card destroyed → flag value irrelevant.

So the exact case where Breastplate is most useful (saving the character from a 1-wound poke) is also the case where the card becomes permanently inert for every future duel.

**Fix:** Override `handleEvent` to reset `$hasBlockedWound = false` on `EventDuelEnd` (and probably also `EventDuelStarted` defensively). Set `IsUpdated = true` when resetting.

### 2. (NPE risk) `getDuelRoundActor()` can return null
Lines 56–58:
```php
$actor = $event->theah->getDuelRoundActor();          // ?Character
$adversaryId = $event->theah->getDuelOpponentId($actor->Id);
```
`getDuelRoundActor()` returns `?Character`; if `Game::IN_DUEL` is true but the `duel_round` row is missing/inconsistent, `$actor` is null and the next line is a fatal `->Id` on null. Realistically rare, but cheap to guard.

### 3. (redundant guard, code smell) Adversary/actor check is implied
Line 58: `$this->AttachedToId == $adversaryId || $this->AttachedToId == $actor->Id` is redundant with the outer `$event->characterId == $this->AttachedToId` (line 51). The compound effectively says "the wounded character is a duel participant."

What's really being filtered is: there could be `IN_DUEL == true` plus a wound event aimed at someone outside the duel (e.g., a side effect targeting a third character). The current guard *does* cover that, but the intent isn't obvious without a `// WHY:` comment.

Cleaner: pull the participant-check into a single line, e.g.
```php
$isDuelist = $event->characterId == $actor->Id || $event->characterId == $adversaryId;
if ($isDuelist && ! $this->hasBlockedWound) { … }
```

### 4. (redundant inner check) `handleEvent` re-checks `$event->wounds > 0`
EventHub gates `EventCharacterWounded` creation on `wounds > 0`, so by the time `handleEvent` sees it, wounds are already > 0. The check is defensive but not necessary. Harmless.

### 5. (style consistency) Mixed notify styles
- Line 67: `$event->theah->game->notifyAllPlayers("message", …)` — old style.
- Line 89: `$game->notify->all("message", …)` — new style.

Codebase has been migrating to `notify->all`. Trivial.

### 6. (text-vs-implementation gap, intentional?) "During a duel" only
The card text explicitly restricts to duels. The implementation correctly checks `Game::IN_DUEL`. But Forced #2 ("when the equipped character is wounded • destroy this card") **also** only fires in code paths where the wound came through `EventCharacterBeingWounded` → `EventCharacterWounded`. That's fine because that's the standard wound path; just noting that if any non-duel wound ever bypassed the BeingWounded → Wounded chain, both abilities would silently no-op.

## Risk Assessment
- **#1** is a real bug affecting actual gameplay. Should fix.
- **#2** is a defensive null guard; low frequency but real.
- **#3, #4, #5** are stylistic / clarity issues.
- **#6** is a note for future-me, not actionable now.

## Suggested Fix Sketch (not applied — audit only)
Add to `_01153::handleEvent`, before/after the existing wound-destroy block:
```php
if ($event instanceof EventDuelEnd && $this->hasBlockedWound)
{
    $this->hasBlockedWound = false;
    $this->IsUpdated = true;
}
```
WHY add this: card text reads "the first time the equipped character would suffer any amount of wounds [during a duel]" — per-duel reset matches the wording and matches the convention used by `Maneuver_01051::handleEvent` (which also clears per-duel state on `EventDuelEnd`).

## Fixes Applied (this session)

User asked to fix all found bugs. Applied #1 and #2. Left #3 (clarity), #4 (harmless redundancy), and #5 (notify-style nit) as-is.

- **#1 (per-duel reset):** Added an `EventDuelEnd` branch at the top of `handleEvent` that clears `$hasBlockedWound` and sets `IsUpdated = true`. Added a `// WHY:` comment in the code explaining why the reset is necessary (so a future agent doesn't "simplify" it away). Imported `EventDuelEnd`.
- **#2 (null actor guard):** Added an early `return` in `eventCheck` if `getDuelRoundActor()` returns null. This avoids the `null->Id` fatal if `IN_DUEL` is true but the `duel_round` row is missing/inconsistent.

Did not touch the redundant adversary/actor check (#3) — leaving that participant filter intact since reworking it risks regressions in the (rare) "wound during a duel aimed at a non-duelist" path, and it's not actually a bug.

## WHY for future-me
The `eventCheck`-vs-`handleEvent` split here is deliberate and correct:
- "Would suffer wounds" = pre-modifier = `eventCheck` on `EventCharacterBeingWounded` (runs at queue time, mutates the event before it's dispatched).
- "Is wounded" = post-trigger = `handleEvent` on `EventCharacterWounded` (runs when the event fires, after the hub has already gated on `wounds > 0`).

If a future agent "simplifies" this by collapsing both into `handleEvent`, the wound reduction will run too late — after the hub has already decided whether to create the `Wounded` event from the `BeingWounded` event. The split is load-bearing.
