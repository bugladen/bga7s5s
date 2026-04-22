# Text Property Bulk Formatting Fixes

## What Was Done

Eddie asked for a bulk pass across all card files in both `modules/php/cards/_7s5s/` (210 files) and `modules/php/cards/tac/` (47 files) to fix two categories of Text property issues:

1. **Bold ability announcements**: Ensure all ability keywords (Forced, Action, Maneuver, Technique, Reaction and their prefixed variants) are wrapped in `<b></b>` tags.
2. **Expand abbreviated bracket tags**: `[com]` → `[Combat]`, `[fin]` → `[Finesse]`, `[inf]` → `[Influence]`, etc.

Results:
- _7s5s: 192 files modified
- tac: 18 files modified

## Approach

Wrote Python scripts (now deleted) that:
- Read each file in binary mode to detect and preserve original line endings (CRLF)
- Applied bracket abbreviation fixes (14+ patterns)
- Stripped existing `<b></b>` tags from ability announcements, then re-added them uniformly
- Only wrote files back if actual changes were detected

WHY Python instead of PHP: PHP wasn't in PATH on this machine.

WHY binary read/write: First attempt used Python's text mode which silently converted CRLF to LF. The user's rule says "Leave the line endings to a file intact." Had to revert and re-run with binary I/O.

## WHY the two-step bold approach

Instead of trying to match only un-bolded abilities (which would need negative lookbehind for variable-length prefixes), the script:
1. Strips existing `<b></b>` tags from ability patterns
2. Re-adds `<b></b>` to all ability patterns

This is idempotent and handles all edge cases including abilities inside quoted text.

## TAC-specific issues

The tac files had additional formatting problems not seen in _7s5s:
- Colons placed OUTSIDE bold tags: `<b>Forced</b>:` instead of `<b>Forced:</b>` (_02037, _02026)
- Missing colons entirely: `<b>Maneuver</b> +1[riposte]` (_02029)
- New prefix words: Zealot, Berserker, Spy, Thug, Diplomat
- `[influence]` (lowercase full word) as an additional abbreviation form

WHY the `(?<=<p>)` lookahead fix: The initial regex for "missing colon" treated any `<b>ABILITY_KEYWORD</b>` without a colon as an ability announcement. This caused a false positive in _02043.php where `perform a <b>Maneuver</b>` was a noun reference. Fixed by requiring `<p>` immediately before the `<b>` tag — ability announcements always start paragraphs.

## Things NOT Fixed (not in scope)

- `[+1 Parr]y` in _01013.php - formatting error but not abbreviation
- `[combat]challenge` (missing space) in _01078.php
- `[Thrust.` (missing closing bracket) in _01084.php
- `CIty` capitalization typo in _01008.php
- `Sorcerer CIty` instead of `Sorcerer City` - same typo

## Caveat

Eddie had already manually fixed 3 _7s5s files (_01089, _01090, _01098) before this session. The `git checkout` to revert a line-ending mistake also reverted those manual changes. The script then re-applied the same fixes, so the end result is identical.
