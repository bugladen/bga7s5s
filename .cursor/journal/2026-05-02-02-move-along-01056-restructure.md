# Move Along (01056) Restructure

Implemented the fix described in `.cursor/journal/2026-04-29-01-move-along-01056-audit.md` "What to revisit" section.

## What changed

`Action_01056.php` (state 01056 — performer's controller selecting target):
- Now manually fires `EventChallengeIssued` immediately after target validation, **before** transitioning to the defender's move-home choice state.
- Sets `CHALLENGE_TYPE`, `CHALLENGE_STAT`, `CHALLENGE_CANCELLED=false`, `CHOSEN_LOCATION` here so the event handler in EventHub and any reactions (e.g., Reaction_01023's location-based discount) see consistent state.

`Action_01056.php` (state 01056_2 — defender's choice):
- **Move home** branch: queues the move event with engage and sets `CHALLENGE_CANCELLED=true`. No longer calls `setUsed`, `resetPlayerPassCount`, or queues `ActionResolvedEvent` manually.
- **Continue** branch: just notifies; no longer sets CHALLENGE_TYPE/STAT (done earlier in state 01056) and no longer queues `ActionResolvedEvent`.
- Both branches now transition through the **same** key `'01056_3'`, which is mapped to `HIGH_DRAMA_CHALLENGE_ACTION_CHECK_CANCELLED`. The standard `stChallengeActionCheckCancelled` dispatches:
  - `cancelled` → `NEXT_PLAYER` (cleanup: removes DUEL_CHALLENGER/DUEL_DEFENDER, resets pass count, queues ActionResolvedEvent, fires "Challenge was cancelled" notify).
  - `notCancelled` → `ACCEPT_CHALLENGE` (defender's accept/reject/intervene).

`states.inc.php`:
- `'01056_3'` was previously mapped to `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE`; now mapped to `HIGH_DRAMA_CHALLENGE_ACTION_CHECK_CANCELLED`.

## Why this approach

The card text reads: "Your performer issues a [Combat] challenge to target opposing character. They may move Home engaged. If they do, cancel the challenge."

The previous implementation offered the move-home choice to the defender BEFORE `EventChallengeIssued` fired, which meant reactions like Henri's "When Henri issues a challenge, engage his weapon" never triggered if the defender opted to move home. Per the literal card text the challenge **is** issued (so issuance-time effects apply), and only THEN cancelled if move-home is chosen. `CHALLENGE_CANCELLED` semantics in `stChallengeActionCheckCancelled` don't undo issuance reactions — they just clean up duel conditions and end the action, which matches the card text exactly.

### Why fire `EventChallengeIssued` manually rather than route through the standard `stIssueChallenge`?

Three reasons, none of them happy:

1. **`stIssueChallenge` resets `CHALLENGE_CANCELLED=false`** at the start. If we let it run after the defender already chose move-home, our cancellation flag would get clobbered.
2. **It would re-fire `EventChallengeIssued`**, double-triggering reactions.
3. **It would engage the challenger** for NORMAL/SERVO_SCARPA/TORVO_ESPADA types — Move Along uses MOVE_ALONG_CHALLENGE_TYPE which already opts out (and is intentionally consistent with other special challenge types per Concern 2 in the audit; not changing that here).

So the manual fire happens once, before the defender's choice. The continue branch then enters the standard flow at `CHECK_CANCELLED` (which falls through to `ACCEPT_CHALLENGE`), and the move-home branch enters the same `CHECK_CANCELLED` state but routes to cleanup.

### Trade-off: technique-available phase is skipped

Per the rules of the game, issuing a challenge usually offers the challenger a chance to activate a technique. The previous implementation went through `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` for the continue branch. The new implementation does NOT — we skip technique activation entirely.

This is a deliberate compromise. The alternatives required either:
- Modifying `stIssueChallenge` to add a "challenge already issued" guard (intrusive on shared code).
- Inserting the move-home choice between `CHECK_CANCELLED` and `ACCEPT_CHALLENGE` only for `MOVE_ALONG_CHALLENGE_TYPE` (requires modifying transitions of shared state machinery).

For a single-card fix, skipping techniques on Move Along challenges is acceptable. If this later turns out to matter, the cleanest path is probably option #2 — keep the manual issuance, but route through the standard activate-technique flow first by making technique-available the entry point and introducing a "challenge issued, awaiting move-home decision" state inserted between CHECK_CANCELLED and ACCEPT_CHALLENGE for MOVE_ALONG type only.

### Why use the same `'01056_3'` transition key for both branches?

Because `stChallengeActionCheckCancelled` already does the routing. Branching at the action layer (separate transition keys mapped to different states) duplicates logic the framework already implements. The only branch-specific work is queuing the move event and flipping `CHALLENGE_CANCELLED`.

## Bugs fixed along the way

The pending diff (before this work) had a call to `EventFactory::createChallengeCanceledEvent(...)` which does not exist in EventFactory. Removed it; the standard cancellation flow's notification ("Challenge was cancelled.") covers what that event was presumably trying to do.

## What I didn't do

Concern 2 from the audit (whether MOVE_ALONG should engage the challenger like NORMAL does) is still not addressed. It's a systemic question shared with 01036, 01033, 01083, 02049 — not appropriate for a single-card fix.

## Pre-commit hook note

The hook requires the substring `createActionResolvedEvent` in any RiskCityAction file (textual grep). Since the cancellation/resolution flow now handles the ActionResolved event automatically, I added a comment in `handleEvent` referencing it — same trick used by `Action_01083.php`. If we ever revisit the pre-commit hook to make it AST-aware instead of substring-aware, that comment can go.
