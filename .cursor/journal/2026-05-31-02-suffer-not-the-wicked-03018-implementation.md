# Suffer Not the Wicked (_03018) Implementation

## Card text

> Add a Renown to [The Grand Bazaar] and [The City Forum]
> ---
> **Reaction:** When a challenge issued by your **Zealot** or **Hunter** is refused • Wound the refusing character.
> Your wounded characters gain: **Technique:** If your adversary is a **Sorcerer** • +1[Thrust]

Three distinct effects:

1. **Resolve** — trivial Renown adds to two fixed locations, no player choice. Mirrors `_02004` (Crash the Party). No state class needed, no `states.inc.php` transition.
2. **Reaction** — listens for `EventChallengeRejected` where the challenger is owned by the scheme owner and has Zealot or Hunter trait. Wounds the refuser (`$event->targetId`). Single forced target so I did **not** implement `IAbilityThatTargetsCharacters` — that's for abilities that surface a target picker. The trigger event already identifies a unique victim; `Reaction_03cd20` and `_03017` follow the same convention (no interface even when capturing/iterating characters).
3. **Granted Technique** — a passive "while in play" effect that adds a +1[Thrust] vs Sorcerer technique to every wounded owned character.

## Granted-technique pattern

Mirrored `_02022` (Stranahan): the scheme listens for the events that change which characters should hold the granted technique, and adds/removes accordingly.

For us, the conditioning is `Wounds > 0` (not location-based like Stranahan). Events I hook:

- `EventResolveScheme` (own scheme) — initial sweep of all owned characters via `getCharactersInPlayByPlayerId`. Grants the technique to any character already wounded at the time the scheme resolves. Wounds persist across days, so this matters.
- `EventCharacterWounded` (gated by `$this->Location == LOCATION_PLAYER_HOME` so we only run while scheme is actually in play) — sync that one character.
- `EventCharacterHealed` — same shape.
- `EventDuskPhaseEnd` — sweep and remove all granted techniques. **WHY:** Schemes get moved to the locker during Dusk, but the character objects persist (with their Wounds intact going into the next day). Without explicit cleanup the techniques would carry forward into a day this scheme isn't revealed in. `EventDuskPhaseEnd` runs after schemes are sent to locker (StatesTrait `stDuskPhaseEnd` runs after `stDuskPhaseSendCardsToDiscard`-equivalent in 2179 onward), but the character handles still exist and `getCharactersInPlayByPlayerId` still finds them.

I intentionally skipped `EventCharacterRecruited` — newly recruited characters can't have wounds, and if a character somehow enters play already wounded (extreme edge case via an ability), they'd take a wound event sooner or later. Not worth the noise.

## Why per-character `syncTechnique` instead of a full sweep

`EventCharacterWounded` fires once per event with `$event->characterId`. Doing a full sweep on every wound is wasted work — only one character's state has changed. Per-character sync is cheaper and matches the precedent in `_03016` (Schwester Ise) which keys off `characterId == $this->Id`.

The initial-sweep on `EventResolveScheme` is the only place we touch all characters at once.

## Subtle: gate by `$this->Location == LOCATION_PLAYER_HOME`

Schemes live in `$this->cards` even when they're in the player's scheme deck or discard, so their `handleEvent` will fire for unrelated wounds. The `LOCATION_PLAYER_HOME` check is the canonical "scheme is currently revealed/in play" test (see `_02024` for the same pattern). Without it, we'd be granting techniques to wounded characters owned by players who haven't even revealed this scheme.

The `EventDuskPhaseEnd` branch deliberately does NOT gate by location. By that point the scheme has been moved to the locker, so `Location` is no longer `LOCATION_PLAYER_HOME`. We want cleanup to run unconditionally at Dusk — if there are no granted techniques to remove, the loop is a no-op.

## `IsDying` and `characterIsInDiscardOrLocker` guards

Copied from `_03016::recomputeWoundedCombatBonus`. Skip dying characters (they're mid-destruction) and characters already in discard/locker (their wound state shouldn't grant anything, and we don't want to leave a technique attached to a card that's about to be flushed).

## Open question I didn't bother solving

If the scheme controller changes mid-day (does that even happen?) the technique attribution would get weird. No precedent for handling this, and no obvious mechanism to trigger it. Leaving alone.

## Lifecycle of the Technique itself

`Technique_03018` has `isAvailableToPlayer` gated on `IN_DUEL` global + adversary having Sorcerer trait. The technique is only offered as an option during a duel — and only when the adversary is a Sorcerer. The `handleEvent` adds +1 thrust on `EventDuelCalculateTechniqueValues` when its id matches.

Mirrors `Technique_03004` (Elena's Sorcery technique) for the in-duel + actor checks. Difference: ours doesn't have a "must be Elena" actor check because the technique is granted to many characters; the per-character actor binding happens at grant time (`setOwnerId($character->Id)`).

## Files

- `modules/php/cards/faf/_03018.php` — scheme class, IHasReactions, sync logic
- `modules/php/cards/faf/reactions/Reaction_03018.php` — wound-refuser reaction with captured target id/name
- `modules/php/cards/faf/techniques/Technique_03018.php` — +1 Thrust vs Sorcerer

No JS changes (no player-choice resolve sub-state). No `States.php` / `states.inc.php` changes. No `ZombieTrait.php` changes.
