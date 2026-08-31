## Reaction_02037 — equipped-only gate

User correction: Mysta's reaction must only fire when Mysta is equipped, not when in hand.

### WHY the gate exists
`Theah::runEvents` line 316 comment: "Run the event for all cards in play, including hands". Every card in `$this->cards` gets `handleEvent`, so attachment reactions on cards in hand/deck still see `EventChallengeIssued` unless gated.

The default attachment-reaction rule: **`ownerIsAttached()`** — reactions on attachments only trigger when the attachment is in play (equipped).

### What changed
- `Reaction_02037` now extends `AttachmentReaction` (was `CardReaction`).
- Removed `LOCATION_HAND` gate; added `$this->ownerIsAttached($event->theah)` in `handleEvent`.
- Effect is now re-equip: move Mysta to a different controller-owned character at the challenger's location. Current host excluded from eligible targets (no-op re-equip filtered out).
- `performReaction` validates `isAttached()` instead of hand.

### Supersedes
April audit (`2026-04-05-02-mysta-02037-full-audit.md`) said "Mysta must be in hand" — that was wrong for attachment-reaction gating. Card text "Equip this card to your character at this location" reads as re-equip when already on the board.

### Note for future agents
Documented in `create-faction-attachment/pattern-d.md` under "Default gate: attachment must be in play (equipped)". Exception path remains for reactions that equip *from* hand/dueling line — those gate on zone, not `ownerIsAttached`.
