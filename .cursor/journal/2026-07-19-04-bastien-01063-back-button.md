# Bastien 01063 — duelChooseTechnique back button

## Context
Follow-on from Harpoon (_03064). Eddie: `duelChooseTechnique_01063` needs a back button when a condition prevents swapping.

## WHY custom actBack (not bare FrameworkActionsTrait::actBack alone)
Bare `nextState("back")` without cleanup would strand queued `EventDuelCalculateTechniqueValues` and leave `Used=true`. Cancel mirrors 01146b (`deleteTechniqueEvents` + clear CHOSEN_TECHNIQUE + TechniqueCanceled), then `runEvents(skipTransitions=true)` so cancel/used notifs flush without `endOfEvents`, then `"back"` → `DUEL_CHOOSE_TECHNIQUE` (Eddie: return to technique picker, not action menu).

## WHY setUsed(false) on player abort
Opponent cancel (01146b) leaves Used — you committed, they canceled the effect. Player abort before completing the Musketeer choice is a take-back; without reopening Used the technique disappears from the picker for the rest of the round.

## WHY also keep confirm throw + show technique when Harpooned
Eddie: do **not** hide the swap button via `isAvailableToPlayer` — player should attempt it and learn why it failed. Fail on `EventTechniqueActivated` via `eventCheck` (immediate UserException on button click). Confirm-time UserException remains as belt-and-suspenders if they somehow reach the Musketeer picker.

## Files
- `State_duelChooseTechnique_01063.php` (new; removed inline from states.7s5s.php)
- `OnUpdateActionButtons.7s5s.js` — back button
- `Technique_01063Swap.php` — Harpoon availability + confirm gate
- ZombieTrait: dropped 01063 case (State::zombie → actBack)

## Not done
- Same back button for `duelChooseTechnique_03013` (Daniella) — same trap shape
- Challenge-time `highDramaChallengeActionResolveTechnique_01063` — Harpoon is duel-only so less urgent

## Follow-up (same day)
`Technique_01036` (Daniella move) got the same Harpoon `eventCheck` on `EventTechniqueActivated` — move is EndOfRound-deferred so activation-time fail is required.
