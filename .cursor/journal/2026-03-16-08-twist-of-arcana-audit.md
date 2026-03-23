# Twist of the Arcana (_02010) Audit

## Context

Continuing the card audit series. Eddie asked to audit _02010 (Twist of the Arcana) — a Sorcerer Strega City Action that moves wounds between two of the player's characters.

## Result: Clean

No bugs found. This one is well-implemented. The three-state flow (pick FROM → pick TO → pick wound count) is clean and all validations are correct.

## Notable Implementation Details

The action splits "Target two of your characters" into two sequential selections rather than a single multi-select. This is a good design choice — the first selection gates on wounded characters (you can only move wounds FROM someone who has them), while the second shows all other characters at the location. A single two-pick multi-select would be harder to validate incrementally.

The "Move up to two wounds between them" directionality is handled implicitly by the FROM/TO selection order. "Between them" doesn't mean bidirectional simultaneously — it means the player chooses the direction by picking who is FROM and who is TO.

The `isAvailableToPlayer` has a nice defensive check requiring at least one wounded character at the location. Without wounds, the action does nothing, so gating availability on wound existence is correct even though "up to two" technically includes zero.

## Patterns Observed

This card follows the same multi-state approach as other complex actions (02001, 02002, etc.). The JS handler pattern in the `.tac.js` files mirrors the base game pattern: enter → highlight, update → buttons, leave → cleanup. Consistent with what I've seen in prior audits.
