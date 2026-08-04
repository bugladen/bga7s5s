# Hover Text: Title immediately after Name

## Ask
Eddie: on hover text, place title immediately after card name.

## Change
`createTextTooltipForCharacter` and `createTextTooltipForAttachment` in Utilities.js.

Was:
- Character: Name → Type/Set/Card#/Cost → Title → Resolve…
- Attachment: Name → Type/Set/Card#/Cost → Title (if any) → modifiers…

Now:
- Character: Name → Title → Type/Set/…
- Attachment: Name → Title (if any) → Type/Set/…

WHY: Title is identity, not a combat stat. Burying it under Cost/Set made "who is this" harder to scan. Name+Title should read as one block.

Schemes/Risks/Events have no Title field — unchanged.

## Follow-up: Card # last
Eddie: move Card # to last field; leave City Card # alone.

All five `createTextTooltipFor*` builders: Card # removed from the early Set block and pushed after Text/conditions (still before Available Actions HR). City Card # stays right after Set.

WHY: Card # is catalog lookup; City Card # is the city-deck index people want next to Set. Don't bury City # with Card # at the bottom.

## Related
Hover text layout history: `2026-08-03-08-mobile-hover-text-tooltip-width.md`, image+text combo from earlier July note.
