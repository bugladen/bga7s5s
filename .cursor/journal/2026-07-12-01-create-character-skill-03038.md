# create-character skill update — Damya (_03038) learnings

## Why

Eddie asked to capture Damya session learnings into create-character (same as Sanjay skill update). Future agents would otherwise invent `createAttachmentDestroyedEvent`, cram two City Actions into one class, draw-after-discard, or add `IAbilityThatTargetsCharacters` for "Your equipped character moves".

## What was added

1. **Canonical ref** `_03038` Damya (Sea Serpent)
2. **Ability-shape table** — dual Action a/b; draw-then-discard; move equipped + destroy attachment + draw cost+1; destroy-attachment recipe row
3. **Pattern C** — subsections: Draw-then-discard; Multiple Actions a/b; Destroy attachment (unequip + discard-from-play); Attachment button picker; Equipped-character move eligibility
4. **State ID encoding** — a/b digit scheme (`4030381` / `4030382` / `40303822`)
5. **states.inc.php `_2` guidance** — corrected outdated "only 03cd03" claim; Angeline/Damya/Pattern F all legitimately queue `createTransitionEvent` for later steps when returning through EVENTS
6. **Action examples** — `Action_03038a` / `Action_03038b`
7. **Reference table** — `_03038`, both actions, plus `Action_01174` / `Action_01194` as destroy + button-picker refs
8. **When You Finish** — items 5–6 rewritten; item 34 Damya example; item 35 Damya journal; items 41–44 (dual actions, draw-then-discard, destroy attachment, equipped move)

## Feelings

The `_2` transition-table note was actively misleading (said only Chance Meeting uses `_2` event names). Angeline already contradicted it; Damya made the EVENTS-queue-then-reenter pattern too clear to leave wrong. Biggest regression risk without this update: inventing a destroy-attachment factory that doesn't exist, or drawing after discard and breaking the hand picker.
