# Servo Technique_01011 availability gate

## Report
Servo's Technique should not be available if there are no other Red Hands controlled by the player at the location.

## Prior state
`Technique_01011` had no `isAvailableToPlayer` override — parent only blank-checks. Effect already counted only OTHER controlled Red Hands for Thrust/Threat, so alone at location the technique was offered as a +0 no-op (challenge ActivateTechnique and duel).

## Fix
Override `isAvailableToPlayer`: parent + owner present + `count(other controlled Red Hands at owner location) > 0`.

Extracted `getOtherControlledRedHandsAtLocation` used by availability + both event handlers (challenge threat / duel calc).

## WHY not gate on IN_DUEL
Servo's technique is also listed at `highDramaChallengeActionActivateTechnique` and applies via `EventGenerateChallengeThreat`. `Technique_02006` / `Technique_05Cooper` can require IN_DUEL because they are duel-picker-only; Servo cannot.

## Tests
`Technique_01011_Test`: available with ally RH; unavailable alone / enemy RH only / non-RH ally.
