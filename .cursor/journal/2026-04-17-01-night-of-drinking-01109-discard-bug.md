# Night of Drinking (01109) Discard Bug Fix

## Context from past sessions
Last sessions focused on card audits (Inspire Generosity, Let the Sword Decide, Let's Haggle, Marooned, etc.) and fixing when-revealed card ordering. Clean branch, no unfinished work.

## The Bug
User reported: "when paying for Night of Drinking, a Risk Reaction, Night of Drinking does not get discarded from hand."

## Root Cause: PHP 8 Loose Comparison + Wildcard SQL

The bug is a two-part chain:

**Part 1: PHP 8 type juggling change.**
In `Reaction_01109.php`, `ManeuverId` is `private string $ManeuverId = '';`. The check on line 130 was `$this->ManeuverId != 0`. In PHP 7, `'' == 0` was true, so `'' != 0` was false — the block would NOT execute. But in PHP 8, `'' != 0` is TRUE (PHP 8 fixed the inconsistent string/int comparison). So the block now executes when it shouldn't.

**Part 2: Wildcard SQL DELETE.**
`DB::deleteManeuverEvents('')` builds: `DELETE FROM events WHERE event_serialized LIKE '%%'`. The `%%` pattern matches EVERY row. This nukes all queued events, including the `EventCardDiscardedFromHand` that would discard Night of Drinking from the player's hand.

This also likely deletes the payment card discard events too — so payment cards probably weren't being discarded either. The user may not have noticed because the visible symptom was Night of Drinking staying in hand.

## The Fix
Changed `Reaction_01109.php`:
- `$this->ManeuverId != 0` → `$this->ManeuverId !== ''` (strict string comparison)
- `$this->ManeuverId = 0` → `$this->ManeuverId = '';` (consistent type for reset)

## WHY this approach over alternatives
- Could have fixed `deleteManeuverEvents` to guard against empty strings, but that would mask the real bug (the caller shouldn't be calling it with empty string in the first place)
- Could have used `strlen($this->ManeuverId) > 0` but `!== ''` is idiomatic PHP for this check
- The strict comparison is future-proof against any further PHP type juggling changes

## Potential wider issue
Other reactions or cards might have similar loose comparisons with string properties vs integer 0. Worth auditing if more bugs surface. The `deleteManeuverEvents`, `deleteTechniqueEvents`, etc. functions are all vulnerable to empty-string injection through their LIKE queries.
