# FakeAttachment / showStatModifiers stat display

## What Eddie actually wanted
Gate stays `!attachment.showStatModifiers` only — do NOT key off `fakeAttachment`. Burden already sets `ShowStatModifiers = false`.

## Fix (take 2 / 3)
Original block stripped box classes but **not** nested icon/value divs — those carry the backgrounds and dash text.

Now `removeClass` + clear value `innerHTML` for combat, finesse, and influence children inside the `!showStatModifiers` block.

## Failed take 1 (reverted)
Tried `fakeAttachment || !showStatModifiers` + `dojo.addClass(..., 'hidden')`. Eddie: don't add fakeAttachment. Also `hidden` likely lost to `._7sfs-card-influence { display: flex }` same-specificity override — influence especially kept showing.

## Cards
Burden (`_01025_Burden`) is the showStatModifiers=false case. Boon/Unfortunate keep showing their modifier chips (FakeAttachment alone is not a display gate).
