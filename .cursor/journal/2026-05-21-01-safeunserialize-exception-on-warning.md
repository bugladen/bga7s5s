# safeUnserialize broken by exception_on_warning

## The bug

User hit `Fatal error: Uncaught ErrorException: unserialize(): Error at offset 832 of 1343 bytes` thrown from `UtilitiesTrait.php:71`. Line 71 was `$result = @unserialize($data);` — the first attempt inside `safeUnserialize`.

## Root cause

Commit 319bd80c added `'exception_on_warning' => true` to `gameinfos.inc.php`. BGA's framework installs an error handler that converts warnings into `ErrorException` regardless of `@`. So the `@` operator no longer suppresses — the warning becomes a thrown exception, bypassing the entire repair pipeline (length-fix regex, null-byte-fix regex, second unserialize attempt, and the carefully-built debug error message).

The whole point of `safeUnserialize` is to *attempt repair* when normal unserialize fails. With `exception_on_warning` on, the function effectively degraded to a plain `unserialize` + uncaught exception.

## Fix

Wrap both `@unserialize` calls in `try/catch (\ErrorException)`. Keep the `@` too in case `exception_on_warning` is ever turned off — belt and suspenders. Capture the message from either `error_get_last()` (suppressed-warning path) or the exception (warning-as-exception path).

WHY this approach over alternatives:
- Could disable `exception_on_warning` — no, the user added it intentionally to catch hidden bugs.
- Could use `set_error_handler` locally — fragile, fights BGA's handler.
- Try/catch is the clean PHP 8 idiom and works under both modes.

## Note

The underlying corruption at offset 832 may or may not match the patterns `safeUnserialize` knows how to repair (length-mismatch, missing `\0*\0` around protected props). At minimum the user will now get the rich diagnostic message instead of a bare ErrorException. If the repair doesn't fix it, the diagnostic output will tell us what kind of corruption we're dealing with.
