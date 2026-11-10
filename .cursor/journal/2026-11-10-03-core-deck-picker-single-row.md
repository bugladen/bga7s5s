# Core deck-picker: single row + blank

## Change
User: when Core cards are displayed, put all faction buttons on one row and leave a blank row underneath.

Reverted the earlier 3+2 Core layout (Castille/Eisen/Montaigne | Ussura/Vodacce) back to a single row of five, with an empty second `_7sfs-deck-picker-tab-buttons` marked `_7sfs-deck-picker-blank-row`.

## WHY blank row
Balanced stays 5+5. Without a second Core row, switching Core ↔ Balanced jumps the button-stack height and the modal content reflows. Empty row keeps the stack at two-row height. `min-height: 30px` approximates a stack button (pad + font + border); row still inherits the stack's 5+5 vertical padding.

Highlight logic unchanged — `querySelectorAll('.deck-picker-button')` on the faction wrapper only sees the five real buttons.

## Blank row exact height
`min-height: 30px` was an approximation and still short. Replaced with a `visibility:hidden` spacer `<button>` that shares the same font/pad/border box as stack `.deck-picker-button` (class `_7sfs-deck-picker-row-spacer`, deliberately NOT `deck-picker-button` so the highlight query doesn't count a 6th "choice").

## Removed blank row
User: remove the blank line. Dropped the spacer row + CSS; Core is just one row of five again. Height will jump vs Balanced — accepted.
