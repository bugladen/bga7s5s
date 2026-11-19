# A Fine Addition (_04029) — equip host chooser

## Report
User: stolen attachments defaulted to Guillen (Merchant performer). Printed text is "equip it to your character at this location" — should choose among your characters at the location. Asked if it only offered to the performing Merchant; yes it did.

## What was wrong
`Action_04029` stealability + equip used only `CHOSEN_PERFORMER` (the En Garde Merchant). Flow was attachment → pay → equip to merchant. No host chooser.

## Fix
Three-step flow (mirrors B.13 / `Action_05Teens` host re-point, not Teens' optional Pass):

1. `04029` — pick opposing equipped attachment (unchanged steal-to-hand staging)
2. `04029_2` — pick your character at the Merchant's location who can attach/afford
3. `04029_3` — wealth pay (old `_2`)

On host confirm: re-point `CHOSEN_PERFORMER` to the receiving character before `PAY_STATE_EQUIP_ATTACHMENT` so discount + equip target match. Stealability now: attachment legal if **any** ally at location can equip it (not only Merchant).

## WHY re-point CHOSEN_PERFORMER
Same as Teens: `calculateInHandPayDiscount` / pay UI for `PAY_STATE_EQUIP_ATTACHMENT` and the equip event both read `CHOSEN_PERFORMER`. Merchant En Garde gate already spent; safe to overwrite.

## WHY not equip to performer by default when alone
Always show `_2` chooser (even one candidate) so multi-character boards never silently default. Pass only when host list empty (soft-lock escape after steal).

## Docs
Updated create-risk B.9 (pattern-b, checklist #61, references, wiring) to three-step + ally stealability.

## Tests
`Action_04029_Test` — pay moved to `_3`; new case equips to non-Merchant ally at location. All 5 pass.
