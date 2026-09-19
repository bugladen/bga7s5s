# Technique_01096 Ratón — challenge + duel arming

## Context
Eddie: Technique works both in-duel and Challenge. Same sibling risk as `Technique_01186` / `02037` / `01193` fixed earlier today. Card Text already says "(There are no adversaries if the challenge is refused.)"

## Bug
Resolve set `AdversaryId` via `getDuelRoundOpponent()->Id`. Challenge Resolve runs before the duel — `getDuelRoundActor()` is null, so that call fatals (or would arm wrong).

Also: no `EventChallengeRejected` clear — Refuse after Resolve would leave `IsActive` / `AdversaryId` and leak into a later duel.

## Fix (mirror Technique_01193 arming; EndOfRound already keyed correctly)
1. Store `$AdversaryId` from Resolve (`$event->adversaryId`, fallback `CHOSEN_TARGET`) — no duel-round helpers on challenge.
2. `EventChallengeRejected` clears when owner is the challenger.
3. `clearDeferredState()` for cancel / reject / duel end / successful EndOfRound fire.
4. EndOfRound steal path uses `getCharacterById($this->AdversaryId)` while arming is still live; chooser args/act still use `getDuelRoundActor()` because AdversaryId is cleared when the transition is queued.

## WHY no AwaitingAdversaryRound
Unlike 01186/02037 (round-spanning blocks cleared on owner NewRound), this effect fires only when `EndOfRound.actorId == stored AdversaryId`. Challenger round-1 EndOfRound after Challenge hits the else branch (reset wound flag only) and leaves `IsActive` armed for the adversary's first EndOfRound. Awaiting would be redundant.

## Wound gate
Kept `IN_DUEL` on `EventCharacterWounded` so pre-duel challenge-threat wounds don't count as "during" the adversary's round. Non-adversary EndOfRound still resets the wound flag (covers challenger round-1 after Challenge).

## Not needed
No challenge/duel chooser states beyond existing `DUEL_END_OF_ROUND_01096` — steal UI only runs once the duel has started and the adversary round ended. No `EventGenerateChallengeThreat` — deferred EndOfRound effect, not threat.

## Follow-up (2026-09-19): availability requires stealable attachment

Eddie: Technique only available if adversary/challenge target has an attachment legal to equip onto Ratón.

### Fix
- `isAvailableToPlayer` → `adversaryHasStealableAttachment` via `getAdversary()` (duel opponent / `CHOSEN_TARGET`, same as `Technique_02026b`).
- Legal = `!hasEquipRestrictions(Ratón, att) && att->canAttachTo(Ratón)` — `Maneuver_01113` equip gate **without** wealth (Technique steal pays no cost).
- Same filter on EndOfRound transition, chooser `getArgs`, and `actFromTechniqueWithId` so mid-duel unequips / restriction changes cannot offer or commit an illegal steal.

### WHY not just `count(Attachments) > 0`
Many attachments override `canAttachTo` (Duelist-only, not Diplomat, etc.). Offering the Technique when every attachment fails that check would arm a no-op EndOfRound (or a broken chooser).
