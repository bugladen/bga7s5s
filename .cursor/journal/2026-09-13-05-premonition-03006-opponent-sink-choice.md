# Premonition Reaction_03006 — opponent must choose sink cards

## Bug / requirement
Eddie: opponent chooses two cards from their own hand to sink; Premonition's owning player does not make those choices.

## Follow-up (playtest)
After public-fields fix: owner Force Sink → **opponent saw Force Sink/Pass again**; opponent Force Sink → **Premonition owner had to sink two of their own cards**. Roles inverted on the pick step.

## Root cause (handoff race + persist)
1. **`parent::performReaction` on Force Sink** stacks `EventReactionActivated` at `HIGHEST_PRIORITY` before our opponent-pick transition (`REACTION_PRIORITY`). Other reactions can steal the next `playerReaction`; Premonition's pick transition never applies cleanly, so the opponent reloads still on stage `'offer'`.
2. A second Force Sink from the opponent then re-runs the offer/`advanceToNextPick` path in a confused active-player context — owner ends up on the pick UI with their own hand.
3. **Detached card copies**: `getCardById` can return a DB instance not registered in `theah->cards`. Mutating `$this->stage` on one copy then `updateCardObjectInDb` on another leaves DB at `'offer'`.

## Fix
1. Public stage fields (unchanged WHY — `_03068` / `_03044`).
2. **Skip `parent::performReaction`** on Force Sink and card-pick clicks (no mid-flow `ReactionActivated`).
3. **`persistReactionState`**: `addCardToWorld`, sync fields onto hosted reaction if `$this` is detached, then `updateCardObjectInDb`.
4. Opponent transition at **`Event::HIGH_PRIORITY`** (Torres Cloak pattern).
5. Owner-only gate on offer; if opponent is stuck on offer UI, treat sink as desync repair → `advanceToNextPick`.
6. Pick stages still require `activePlayer === opponentId`.

## Expected flow
Owner: Force Sink / Pass → Opponent: card / card (their hand only) → finalize.
