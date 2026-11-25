# Cancel-card sibling / REACTION_ID audit (post-Hexenjagd)

User asked whether other cancel cards need the Hexenjagd two-copy fix.

## Hexenjagd failure mode (must match both)

1. Offer without intercept → every hand copy stacks a reaction transition.
2. `EventRiskReactionTriggered` gates cancel cleanup on `reactionId` matching the cancel choice.
3. Sibling Pass/Decline overwrites `Game::REACTION_ID` before `actPayForReaction` → Triggered sees `decline`/`pass` → cancel skipped.

HoldingEffects + Decline re-queue made Hexenjagd especially ugly; (1)+(2) alone is enough for a silent miss.

## Same functional gap

| Card | Why |
|------|-----|
| **Objection! `_01027`** | Starter count 2. Offer-only on `EventLocationPressured`. Triggered gates on `failPressure`. No sibling clear / type dedupe. |
| **You're Embarrassing Yourself `_01088`** | Starter count 2. Offer-only on `EventChallengeIssued`. Triggered gates on `cancelChallenge`. No sibling clear / type dedupe. |

Fix shape if/when: sticky chosen id + clear sibling transitions on cancel choice (and Triggered), same as Hexenjagd / Mireli.

## Already OK or different

- **Mireli `_01135`**: sibling clear on Triggered; Triggered does not gate on reactionId. No sticky needed. Clear-on-performReaction would only tighten UX during pay.
- **Night of Drinking `_01109`**: Action/RiskPlayed use `areTransitionEventsOfTypeForPlayerQueued`. Triggered always cancels (no reactionId gate). Maneuver + Not Today paths lack the type dedupe → double prompt UX only.
- **Stubborn `_01140`**: intercepts (cancels event); Decline re-queues with `cancelDeclinedByCardIds`. Designed for multi-copy.
- **UL `_01032`**: intercept — first copy eats the event.
- **Torsten `_01122`**: character reaction, unique owner.
- **`_03020`**: starter count 2, no sibling clear, but Triggered always cancels → UX double prompt only.
- **`_04056a/b`**: count 1 in starters; Triggered always applies.

## Related non-cancel

Ambush `_01023` (count 2) is not `ICancelReaction` but same offer + Triggered-gates-on-`preventIntervention` pattern — same pollution class if two in hand.

## Fixed (user said yes)

Ported Hexenjagd shape to both:

1. **Objection `_01027` / `Reaction_01027`**
   - Sticky `chosenCancelReactionId` on `failPressure` choice
   - `clearSiblingObjectionReactionTransitions` on choice + Triggered
   - Triggered remaps polluted `pass`/`decline` via sticky
   - Tests: sibling clear + polluted pass still fails. 8/8 green.

2. **You're Embarrassing Yourself `_01088` / `Reaction_01088`**
   - Same sticky + `clearSiblingEmbarrassingReactionTransitions`
   - Triggered remaps polluted `pass`/`decline` via sticky before `cancelChallenge` gate
   - Tests: sibling clear + polluted pass still cancels. 18/18 green.

WHY not type-dedupe-at-offer like Night of Drinking: sticky + sibling clear matches the proven Hexenjagd/Mireli pattern and preserves the existing "both may offer until one commits" UX until cancel is chosen.
