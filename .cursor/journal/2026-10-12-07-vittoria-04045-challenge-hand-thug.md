# Vittoria Reaction_01014 did not react to Stand Your Ground Action_04045

## Report
Vittoria alone at location, **Dante (_01020)** in hand. Opponent used Action_04045. Reaction never offered.

## Root cause (revised — Dante is not a Recruit Brute)

First theory (Recruit Brute / `IN_DUEL` at ChallengeIssued) was wrong for this board. Dante is a normal Brute; `canBePlayedAsBruteFromHand()` defaults true.

Real gap: **framework challenge target picker never fired `EventCharacterTargeted`.**

Abilities that implement `IAbilityThatTargetsCharacters` and use a custom pick (Dabney, Amour, Giacinto) emit CharacterTargeted so Vittoria/UL/Premonition can redirect at the printed “when targeted” moment.

Stand Your Ground / Unsanctioned / When Least Expected / basic challenge go through `actHighDramaChallengeActionTargetChosen` → technique → `stIssueChallenge` → `EventChallengeIssued` only. No CharacterTargeted.

ChallengeIssued *should* still open Vittoria for hand Thugs (unit tests pass). User still saw a miss on Dante+04045 — CharacterTargeted at pick time is the correct targeting signal and matches Dabney’s working path. Wire it for all framework `IAbilityThatTargetsCharacters` challenges.

## Fix
1. `actHighDramaChallengeActionTargetChosen` — after CHOSEN_TARGET, if action is `IAbilityThatTargetsCharacters` and not Defending Honor, queue batched `EventCharacterTargeted` (source = TRANSITION_SOURCE_ID or performer).
2. New hub `TARGET_CHOSEN_EVENTS` (4510–4513) between choose-target and TECHNIQUE_AVAILABLE.
3. EventHub — on CharacterTargeted surviving, sync `CHOSEN_TARGET` when abilityId == CHOSEN_ACTION (redirect to Thug).
4. Reaction_01014 `releaseEvent` — also set CHOSEN_TARGET on CharacterTargeted redirect.

## Recruit Brute timing (user correction — revert CHALLENGE_PIPELINE)

Printed text: only enter play from hand **during a duel**. Duel starts on **accept** (`IN_DUEL` in `stDuelStarted`). Vittoria's challenge window is **when targeted** (CharacterTargeted / ChallengeIssued) — **before** accept. So Recruit Brutes are illegal for that redirect; alone+Bruno correctly offers nothing.

Removed `CHALLENGE_PIPELINE` entirely. `canEnterPlayFromHand` = Play Brute OR `IN_DUEL` only.

## Files
- `States.php`, `states.inc.php` — TARGET_CHOSEN_* hub
- `FrameworkActionsTrait.php` — CharacterTargeted emit
- `EventHub.php` — CHOSEN_TARGET sync
- `Reaction_01014.php` — releaseEvent CHOSEN_TARGET; eligibility comment
- `Character.php` / CAD Brutes / FakeGame / StatesTrait — no pipeline
- Tests: Dante+04045 CharacterTargeted; challenge+hand Dante; challenge+Bruno does not offer; Bruno in duel still ok

## Do not regress
- Defending Honor — do not CharacterTargeted the challenged ally
- Cancel/redirect must update CHOSEN_TARGET before ChallengeIssued
- Recruit Brute: only `IN_DUEL` (not challenge targeting)
- Normal hand Thugs (Dante) still redirect on challenge target

## Done
CharacterTargeted framework path for Dante; Recruit Brute timing fixed to post-accept only.
