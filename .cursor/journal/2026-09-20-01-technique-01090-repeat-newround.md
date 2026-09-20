# Technique_01090 — effect re-fires every adversary round

## Context
Eddie: once Technique_01090 selected in a duel, the play-revealed-card-or-take-wound prompt keeps firing for the adversary every round.

Yesterday (2026-09-18-04) fixed challenge-path adversary pull / deferred reveal. That work left the NewRound deferred effect alone — and that's the bug.

## Bug
`EventDuelNewRound` gates on `$this->CardPlayerId > 0` and matching actor controller. `CardPlayerId` / `RevealedCardId` were only cleared on `EventTechniqueCanceled` and `EventDuelEnd`. After the adversary resolved `DUEL_NEW_ROUND_01090` (play or wound), flags stayed set → every subsequent adversary NewRound requeued `"01090"`.

Card text: "When their **next** round begins" — once.

## Fix
Clear `RevealedCardId` / `CardPlayerId` and mark owner updated at end of `actFromTechniqueWithId` for `DUEL_NEW_ROUND_01090`, after either branch (wound or play). WHY clear on act not on queue: `getArgs` / play path still need `RevealedCardId` until the choice finishes.

Re-using the technique later in the same duel still works — reveal sets the flags again for the next adversary round only.

## Was this from the Sep 18 challenge fix?
No. `git log` on Technique_01090: original Dec 2025 impl already gated NewRound on `CardPlayerId > 0` and never cleared after `DUEL_NEW_ROUND_01090` act. Cancel clear Apr 2, DuelEnd clear May 6 — still no post-choice clear. Sep 18 `6c31a976` only touched challenge reveal / getArgs; journal even said NewRound path "Unchanged". Latent since original, not a regression from challenge work.
