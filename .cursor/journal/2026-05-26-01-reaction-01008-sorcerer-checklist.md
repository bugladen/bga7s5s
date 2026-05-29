# Reaction_01008 (Cesca Avara) — Sorcerer Ability Copy Checklist

## Rule for future sessions

**Whenever you create or modify an ability on a card that implements `ISorcererAbility`, check whether `Reaction_01008` needs to be updated to handle copying it.**

Reaction_01008 lets Cesca Avara copy a Sorcerer Ability that was just played, but only if one of these prerequisites is met:

- The Sorcerer Ability was performed by Cesca herself, OR
- The Sorcerer Ability targeted a character at Cesca's location

If the new ability can satisfy either prerequisite, `Reaction_01008::performReaction` must know how to copy it — otherwise the player clicks "Copy" and nothing useful happens.

## Why this matters

The reaction's `performReaction` currently dispatches via a long chain of `if ($ability instanceof Action_XXXX)` checks. Each new copyable Sorcerer Ability needs an entry there (or in whatever replaces that dispatch after the planned refactor). Without it, the ability is silently un-copyable even though Cesca's trigger fires.

## How to apply

When implementing a new card whose ability implements `ISorcererAbility`:
1. Check the prerequisites above — could a character at Cesca's location ever be targeted by it, or could Cesca ever be the performer?
2. If yes, add the ability to `Reaction_01008` so it can be copied.
3. After the planned refactor (class-name dispatch + Risk vs non-Risk owner check), this may just mean adding the action's class name to a registry rather than a new `instanceof` branch.
