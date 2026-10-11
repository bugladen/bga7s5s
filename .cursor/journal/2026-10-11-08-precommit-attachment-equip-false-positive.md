# Pre-commit false positive on Action_01180_Test

## What happened
User commit aborted: hook said `Action_01180_Test.php` calls `createAttachmentEquippedEvent()` without `getRequiredAttachTargetId()`.

## WHY it fired
Check #5 greps the whole file for the string. The test only had it in a WHY comment documenting that the factory returns `EventAttachmentEquipping` (not Equipped). No actual call.

## Fix
1. Reworded the comment to "EventFactory equip helper" so it doesn't contain the trigger string.
2. Updated `.githooks/pre-commit` to skip `tests/*` for that check (same reason EventFactory is skipped — non-card files legitimately mention the API without the attach-target helper).

## Unfinished
None — user can re-commit. Hook change only helps if `.githooks` is what git is using (`core.hooksPath`).
