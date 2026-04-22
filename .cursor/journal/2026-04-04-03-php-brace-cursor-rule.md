## Cursor rule: PHP braces on next line

Eddie asked for a project rule via `/create-rule`: when creating PHP blocks, start `{` on the next line (Allman / BSD style).

**Why a dedicated rule:** Matches Eddie’s preference and steers the agent away from K&R same-line braces that are common in other PHP codebases. Scoped with `globs: ["**/*.php"]` so JS/other files are unaffected.

**File:** `.cursor/rules/php-brace-style.mdc` — concise BAD/GOOD examples for classes, methods, control flow, try/catch.

**Magnus journal path** `C:\repos\magnus\journal\7s5s` did not exist this session; context came from this repo’s `.cursor/journal` only.
