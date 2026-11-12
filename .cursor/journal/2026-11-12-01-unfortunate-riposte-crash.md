# Unfortunate (_01102_Attachment) Riposte crash on gamble

## Report
`Undefined property: _01102_Attachment::$Riposte` in `stApplyCombatCardStats` after `actGambleCardChosen`.

## Root cause (two layers)

1. **Immediate fatal:** `_01102_Attachment` extended bare `Attachment` — no `FactionCardTrait`, so no `$Riposte`/`$Parry`/`$Thrust`. PHP 8.2+ throws on undeclared property access when combat stats are applied to the chosen gamble card.

2. **How the clone got into the faction deck:** Host death → `Character::unEquipAllAttachments` plain-discarded every attachment via `createAttachmentDiscardedFromPlayEvent`. For `IRiskAttachment` FakeAttachments that put the **clone** in the Risk owner's discard (OwnerId = player who played Unfortunate). Discard reshuffle → faction deck → gamble → crash.

   Proper teardown is `removeRiskAttachment` (unequip + discard clone + hide clone + restore original Risk to discard). Action_01102 already did that; host death did not.

## Fix
1. `_01102_Attachment` → `FactionAttachment` with copied Text + RPT 1/0/3 + WealthCost 0 (Silence `_04008_Silence` clone pattern). Defends tooltips and any leftover leaked clones in live tables.
2. `Character::unEquipAllAttachments` — if `IRiskAttachment`, call `removeRiskAttachment` and skip the plain discard path. Protects Burden / Boon / Silence / Unfortunate alike.

## WHY not only RPT on the clone
RPT alone stops the fatal but leaves FakeAttachments circulating as combat cards (wrong game object, original Risk stuck in Permanently Hidden). Host-death path is the leak; fix both.

## WHY not centralize in EventFactory / CardDiscardedFromPlay
`removeRiskAttachment` itself queues discard-then-hide. A discard-handler that also restores the original would race / double-move during the intentional teardown sequence. Call-site fix in `unEquipAllAttachments` is the right layer for host death; ability destroy paths that can hit FakeAttachments should keep calling `removeRiskAttachment` (most already skip FakeAttachment).

## Tests
`Card_01102_Test`: RPT/FactionAttachment assert; host `unEquipAllAttachments` → unequip + discard + hide + original in owner discard.
`Action_01102_Test` still green.

## Not done / follow-ups
- Burden `_01025_Burden` / Boon `_01161_Boon` still bare Attachment clones (tooltip gap from Silence journal). Host-death leak fixed for them via Character; RPT copy optional polish.
- Live tables that already shuffled a clone into a deck: RPT copy prevents crash; clone may still be playable until removed manually / game ends.
