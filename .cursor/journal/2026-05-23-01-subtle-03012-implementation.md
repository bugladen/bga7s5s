# Subtle (_03012) — Implementation

Risk card with a single Sorcerer Strega Reaction:

- **Sorcerer Strega Reaction:** When your performer intervenes • The challenge becomes an [Influence] challenge.

Stats: 1 (dashed) / 3 / 1. Wealth 0. Traits: Sorcery, Sorte, Virtue, Cunning. Vodacce faction. All four traits already in `TraitNames::$TraitsJson`.

## Files touched

- `modules/php/cards/faf/_03012.php` — added `IHasReactions`, `ReactionTrait`, Reactions array. No `IRiskThatTargetsCharacters` needed (the reaction doesn't target a character; it just mutates the `CHALLENGE_STAT` global).
- `modules/php/cards/faf/reactions/Reaction_03012.php` — new; extends `RiskReaction implements ISorcererAbility`.

## Reaction wiring

Modeled on `Reaction_03010` (Manipulative, the other faf RiskReaction) for the pay-state chain, and on `Reaction_02001` (Andriana) for the `EventCharacterIntervened` trigger shape.

**Trigger** (`handleEvent` on `EventCharacterIntervened`):
1. `isAvailable()`.
2. Owner is in hand (`Location == Game::LOCATION_HAND` — pre-commit hook requires the literal `==` form).
3. `$event->playerId == $owner->ControllerId` — the player who intervened is the Risk's owner.
4. `$intervener->hasTrait("Strega")` — the Strega Reaction gate. The "performer" of the Reaction is the intervening character, so the Strega gate applies to it.

When all four hold, store `intervenerId` and queue `createReactionTransitionEvent` to offer the Reaction.

**`performReaction`:**
- `'use'` → queue `createEnteringPayStateEvent(PAY_STATE_IN_HAND_REACTION)` + `createReactionPayTransitionEvent`. The framework discards the Risk from hand and emits `EventRiskReactionTriggered`.
- `'pass'` → reset `intervenerId`, don't `setUsed` (Risk stays in hand for future triggers).

**`EventRiskReactionTriggered && internalId == $this->Id`** is where the effect actually applies (after the Risk is paid):
1. Queue `createSorcererAbilityStartEvent(performerId = intervenerId)`.
2. `globals->set(Game::CHALLENGE_STAT, Game::STAT_INFLUENCE)` — mutates the challenge stat global. Threat is calculated later (in `StatesTrait::stGenerateChallengeThreat` reading `CHALLENGE_STAT`), so this lands before threat is computed.
3. Queue `createSorcererAbilityPlayedEvent`.
4. Notify and `setUsed`.

## WHY mutate `CHALLENGE_STAT` instead of designing a new challenge type

The existing flow uses `Game::CHALLENGE_STAT` as the single source of truth for the active challenge's stat. `StatesTrait::stGenerateChallengeThreat` reads it at threat-calc time. Several Actions set it during their challenge-issuing `handleEvent` (e.g., `Action_03008` Arrogant → `CHALLENGE_STAT = STAT_COMBAT`). Mutating the same global mid-flow (after intervention, before threat) is the minimal-surface change.

The card text "The challenge becomes an [Influence] challenge" is *just* a stat swap — refusal/intervention rules don't change. So `Game::CHALLENGE_TYPE` stays as whatever it was; only `CHALLENGE_STAT` flips. No new `CHALLENGE_TYPE` constant needed (matches the skill guidance: "Custom challenge type only when intervention/refusal differ").

## WHY apply the effect in `EventRiskReactionTriggered` and not in `performReaction('use')`

If I set `CHALLENGE_STAT` directly inside `performReaction('use')`, the Risk hasn't been paid yet — a subsequent cancel-reaction (e.g., Hexenjagd) could cancel the Sorcerer ability after we've already mutated the global. By deferring the mutation to `EventRiskReactionTriggered`, the pay step has completed and the framework's reaction-cancel machinery has had its chance. This matches the pattern in `Reaction_03010` where the actual effect logic lives in the `EventRiskReactionTriggered` handler, not in `performReaction`.

## WHY no `IRiskThatTargetsCharacters`

The Reaction doesn't fire `EventCharacterTargeted` and doesn't present a character chooser — it changes a global. The interface is for Risks whose abilities target a character. Skipped.

## Pre-commit hook compliance

- `extends RiskReaction` → `Location == Game::LOCATION_HAND` literal ✓, `$this->setUsed(` ✓, `$this->isAvailable(` ✓.
- `implements ISorcererAbility` → `createSorcererAbilityStartEvent()` ✓, `createSorcererAbilityPlayedEvent()` ✓.
- No `IAbilityThatTargetsCards` + `IAbilityThatTargetsCharacters` co-implementation.

Hook ran clean against the staged set. Both files lint clean.

## Things considered and ruled out

- **Setting `CHALLENGE_STAT` inside `performReaction('use')` before the pay.** Rejected — a cancel-reaction post-pay could leave the global mutated even though the Sorcerer ability was canceled. Deferring to `EventRiskReactionTriggered` keeps the state change paired with the resolved sorcery.
- **Requiring the owner to control any Strega character (Manipulative-style gate).** The text says "your performer intervenes" — the performer IS the intervener. So the Strega check goes on the intervener (`newTargetId`), not on a separate search of `getCharactersInPlayByPlayerId`. Cleaner and faithful to the card.
- **Marking `IRiskThatTargetsCharacters` on the Risk.** No character is targeted; only the challenge stat changes. Skipped.
- **A custom `CHALLENGE_TYPE`.** Refusal/intervention rules are unchanged. Only the stat flips. No new constant.
- **Engaging the intervener / additional cost.** The card text only requires playing the Risk (which is discarded by the pay state). No extra cost.

## Open questions / risks

- **Threat already partially computed?** The flow is: actHighDramaChallengeActionIntervene → EventCharacterIntervened → reactionTransition → playerReaction → performReaction → pay → EventRiskReactionTriggered → state continues to refuse/threat calc. `stGenerateChallengeThreat` runs after this whole chain unwinds, so the mutation should be picked up. Worth playtesting to confirm no early threat snapshot.
- **Multiple interventions in one challenge.** If a second character intervenes (replacing the Strega), our Reaction's saved `intervenerId` would point at the old Strega. But since the Risk is already discarded by then, `isAvailable()` is false and no second trigger fires. Safe.
