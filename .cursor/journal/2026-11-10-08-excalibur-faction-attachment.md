# _05Excalibur Dietrich Blade

Finished CAD FactionAttachment from Text via create-faction-attachment skill.

## Rename

User corrected spelling: Excaliber → Excalibur (class, reaction, technique, test, journal, Image). Asset was already `05Excalibur.v4.jpg`.

## Text → shapes

1. **Reaction** — Pattern D AttachmentReaction. "When the equipped performer issues a challenge • Wound the performer and target opposing character with equal or greater [Combat]."
2. **Technique** — Pattern E free +1 Riposte (Sabre/Rapier shape).

No equip restriction, no engage cost, no GameState/JS.

## WHY Reaction gates

- Trigger = `EventChallengeIssued` where `challengerId == owningCharacter->Id`. Not "challenge at this location" (that's Stiletto `_01022`). Equipped host must be the one issuing.
- Wound target is **not** locked to the challenge defender. User clarification: pick **any** opposing character at the performer's location with `ModifiedCombat >= performer`. Offer only when ≥1 such character exists (weak defender + strong bystander still offers).
- Buttons recompute via `getOpposingCharactersAtLocation` + Combat gate (Sniper `wound-{id}` shape). Stash PerformerId only.
- No engage printed → no `Engaged` gate / no `createCardEngagedEvent`.
- Wound order: performer then chosen target.

## WHY Technique own class

Skill allows `Technique_PlusOneRiposte` + `setId` for plain free +1 Riposte. CAD prototype rule (wiring.md / user): no clienttranslate / translate() on this card — PlusOneRiposte wraps Name + explanations. Mirrored Robin: own `Technique_05Excalibur` with plain strings, same `IN_DUEL`-only availability as PlusOneRiposte (no challenge threat branch — Riposte isn't challenge threat).

## Refs mirrored

- Compose: Pompon `_04026` (Reaction + Technique on FactionAttachment).
- Reaction bones: AttachmentReaction + `ownerIsAttached` / `isAvailable` / `setUsed` (pre-commit); challenge stash from `Reaction_05Sniper`; wound queue from `Reaction_01022` without engage.
- Technique: `Technique_PlusOneRiposte` behavior + `_05Robin` plain-string CAD style.

## Tests

`tests/regression/cad/Card_05Excalibur_Test.php` — construct, offer/combat gate/wrong challenger/not attached, bystander wound, pass, technique duel-only +1 Riposte.

## Skill

Learnings folded into create-faction-attachment (see `2026-11-10-09-faction-attachment-skill-excalibur.md`).
