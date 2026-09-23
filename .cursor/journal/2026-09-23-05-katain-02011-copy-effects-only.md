# Katain DeWinter (Reaction_02011) — copy effects only, not costs

## Bug

Katain's Reaction text: "discard a card • Copy the effects." Hosting a fresh instance of the source ability and re-resolving it was also re-paying that ability's printed costs.

Worst offender: Polished Flintlock Technique (`Technique_01049`) — "Engage this card • Gain Lethal". `Reaction_02011` set `originalAttachmentId` so the copy re-engaged the flintlock. That was deliberate per 2026-09-04 journal, but wrong: Engage is the cost; Lethal is the effect.

Matchlock (`Action_01156`) — "Discard a card • …" — same shape: copy re-entered the discard picker.

## WHY effects ≠ full re-resolve

Contrast Cesca (`Reaction_01008`): "performs a copy … paying all costs." Katain explicitly says effects only. Dame/Yepikhodov already treat Technique copies as effects-only via `IsTemporaryCopy` (lifecycle flag) but Technique_01049 never skipped engage for those either — Dame would have engaged the Character host.

## Fix

1. `CardAbilityTrait::$IsEffectsOnlyCopy` — shared flag for Action/Maneuver/Technique/Reaction copies that must skip costs.
2. `Reaction_02011` sets it on every hosted copy. Technique_01049 no longer gets `originalAttachmentId` (no engage to redirect).
3. `Technique_01049`: skip engage when `IsTemporaryCopy || IsEffectsOnlyCopy` (covers Dame/Yepikhodov + Katain).
4. `Action_01156`: if effects-only, transition to `01156_2` (target) and skip discard; registered `"01156_2"` in `states.inc.php` transition map.
5. **Keep** `originalAttachmentId` on `Action_01049` copies — City Action text is "wound them **and engage this card**" (engage is part of the wound *effect*, not a cost before •).

## Also fixed in the same pass

Concealed Flintlock branch set `$copyManeuver = true` while creating a Technique — would null-deref `$maneuver` on resolve. Corrected to `$copyTechnique = true`.

## Do not regress

- Do not strip Action_01049's `originalAttachmentId` engage-on-wound — that is effect text.
- Do not treat Cesca copies as effects-only — she pays costs by design.
- Destroy-as-cost (Duckfoot / Throwing Knife) already no-ops when owner is Character; Character-hosted copy remains correct.
