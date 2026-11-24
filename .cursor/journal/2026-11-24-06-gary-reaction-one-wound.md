# Reaction_05Gary: only one wound to Rena

## Context
User: Reaction_05Gary should only apply one wound. If a wound event comes in > 1, apply only 1 wound to Rena and let the rest go through.

Printed text: "When your character at this location would suffer **a wound** • Wound Rena instead." Singular.

## What was wrong
Accept path retargeted the whole saved `EventCharacterBeingWounded` onto Rena (`characterId = Rena`, wounds unchanged). A 3-wound leftover-threat event made Rena eat all 3; the ally got nothing.

## Fix
Keep cancel-first clone of the full event (offer / pass unchanged). On `woundRena`:
1. Queue clone onto Rena with `wounds = 1`
2. If original wounds > 1, queue a fresh BeingWounded onto the original victim for `wounds - 1`

WHY split on accept (not in handleEvent): Pass must still re-stack the full amount. Reducing the in-flight event before the choice would land remainder on the victim before Pass/Accept, and Pass would need a different re-release shape. Split-on-accept keeps Pass = full clone + skipNextEvent.

Used reaction already blocks re-offer of the remainder (`isAvailable()` false after `setUsed`).

## Tests
`Reaction_05Gary_Test`: multi-wound accept → Rena 1 + ally remainder; existing single-wound accept asserts wounds==1.
