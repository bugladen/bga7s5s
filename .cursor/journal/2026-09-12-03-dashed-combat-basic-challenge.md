# Dashed Combat blocks basic Challenge action

## Context

Penya (`_03cd01`) has `DashedCombat = true` (and dashed Influence). User: cards with dashed Combat cannot initiate a challenge with the combat action.

## Gap

Basic Challenge always sets `CHALLENGE_STAT = STAT_COMBAT`, but availability / performer lists only checked `canChallenge()` + Engaged. Claim already gates on `DashedInfluence`; Challenge had no parallel for Combat.

Pressures already use `canPressure()` → `!DashedCombat`. Challenges did not.

## Fix

Mirror Claim's DashedInfluence pattern onto basic Challenge:

1. `Theah::playerCanBasicChallenge` / `characterCanBasicChallenge` — skip / reject `DashedCombat`
2. `argsHighDramaChallengeActionChoosePerformer` — exclude from selectable ids
3. `actHighDramaChallengeActionPerformerChosen` — UserException (same wording shape as Claim)
4. `Character::eventCheck` on `EventChallengeIssued` when challenger has `DashedCombat` and `CHALLENGE_STAT == COMBAT` — catches ability-issued Combat challenges that bypass basic-action filters (Térence `_03028` sibling pattern)

## Why not override `canChallenge()` on Penya

Dashed Combat is a **stat rule**, not a card-specific "cannot challenge" ban. Sigurd overrides `canChallenge()` because he cannot issue *any* challenge. Penya (and any future dashed-Combat character) can still issue Finesse/Influence challenges from abilities. Putting the gate on Combat-stat paths keeps other challenge types open.

## Why central Character eventCheck, not only Penya

Any character with printed `—` Combat has the same rules restriction. Encoding it once on Character avoids per-card copies and matches how `canPressure` already treats dashed stats.

## Unrelated open

Penya city-deck recreate (today's `2026-09-12-02`) and duel-mid-Forced edge case still separate.
