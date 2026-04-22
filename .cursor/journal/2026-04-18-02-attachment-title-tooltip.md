# Attachment Title in Text Tooltips

## What
Added the `Title` field to the text-based tooltip for Attachments in `Utilities.js:createTextTooltipForAttachment()`.

## WHY
The `Title` property was added to `Attachment.php` and is already being sent to the frontend via `getPropertyArray()`, but the text tooltip renderer wasn't displaying it. The Character tooltip always shows Title (line 524), but for Attachments most cards won't have one, so I made it conditional — only shown when `card.title` is truthy (non-empty string).

## Details
- The Title row appears after Cost and before the stat modifiers, matching the logical position used in Character tooltips (after Cost, before stats).
- Had to split the initial array literal into an array + conditional push + `rows.push(...)` for the remaining rows. Clean pattern already used elsewhere in the codebase.
- Example card using Title: `_02055` (Dame of Swords) with Title "Famed Fortune".
