# Corpse Speak (_01154) — Influence not restored on unequip

## Report

Bug report: "When Corpse Speak attachment is removed via Ivy the Resolve is not restored."

Ivy (_02042) has nothing that removes attachments — she's just a Sorcerer host for Corpse Speak. Reporter almost certainly meant **Influence** (the only stat Corpse Speak changes: "equipped character's Influence is set to 0"), not Resolve.

## Root cause

`Character::setLockedValues` **overwrites** `ModifiedInfluence` with `InfluenceLockedValue` (0). It does not store a delta.

`Character::removeAttachment` only **subtracts** `InfluenceModifier`. Corpse Speak's modifier is 0, so unequip left Influence stuck at 0. `setLockedValues` afterward finds no remaining InfluenceLocked attachment and no-ops.

Same class of bug Sigurd (_01190) hit with capped combat — running tally breaks when the attach path is not a pure delta. Journal `2026-04-09-02-sigurd-ulfsen-01190-audit.md`.

Corpse Speak is currently the only card setting any `*Locked` flag.

## Fix

In `Character::removeAttachment`: when the removed attachment had a lock flag for a stat, rebuild that stat from printed base + remaining attachments' modifiers, then call `setLockedValues` for any remaining locks. Non-locked stats keep the incremental subtract path.

WHY recalculate from scratch (not store pre-lock): attachments can be added while the lock is active; a saved pre-lock snapshot would miss those modifiers. Same reasoning as Sigurd's `recalculateCappedCombat`.

## Caveat (accepted)

Full recalculate from base+attachments does not re-apply event-based Influence mods (e.g. Contempt and Hatred) that existed *before* the lock. Those were already wiped when the lock overwrote ModifiedInfluence — so unequip was already unable to restore them via the old subtract path either. Rare edge case; not introduced by this fix.

## Files

- `modules/php/cards/Character.php` — `removeAttachment`
