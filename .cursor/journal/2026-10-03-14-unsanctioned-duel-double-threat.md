# Unsanctioned Duel (02061) double threat

## Bug
Opponent played Unsanctioned Duel; at first-round threat seed, both participants got the bonus threat twice.

## Root cause
`Action_02061` listened for `EventGenerateChallengeThreat` and gated only on `CHALLENGE_TYPE == UNSANCTIONED_DUEL`. That type is table-global. `Theah::runEvents` delivers the event to every card in `$theah->cards` (city + hands + discard), so every loaded `_02061` Action fired and each did `actorThreat += 1; adversaryThreat += 1`.

Starter decks ship 2 copies (`StarterDecks.php`). Played copy (discard) + spare (hand) both fire. Opponent copies in hand/discard also fire. N loaded copies → N×(+1/+1).

No `EventDuelNewRound` second path — card text says "start of the first round" but implementation correctly rides GenerateThreat (seeds round 1).

## Fix
Gate on `CHOSEN_ACTION == $this->Id` in addition to challenge type.

WHY CHOSEN_ACTION over TRANSITION_* :
- Technique activation overwrites `TRANSITION_INTERNAL_ID` with the technique id before `stIssueChallenge` (Premonition journal 2026-09-05-07 / Reaction_01014).
- Technique Resolve chooser transitions can overwrite `TRANSITION_SOURCE_ID` before GenerateThreat.
- `CHOSEN_ACTION` is set when the Risk action is chosen, unique per card instance (`{cardId}_Action_02061`), cleared only in `stNextPlayer`.

WHY not a persisted Action flag: same as Action_04045 — `EventActionResolved` can fire with `!IN_DUEL` before GenerateThreat and wipe it.

## Related smell (not fixed)
`Action_03030` (Sworn Swords) has the same type-only GenerateThreat gate. Schemes are usually 1/player so controller+type (04045 pattern) would suffice for mirror matches; left alone unless asked.

## Context from earlier today
UL cancel speed, Blood in Water / Amour UL, Rosa/Soline chooseNext, Mourad symbols, home deck count, accept-challenge technique threat feasibility (not implemented).
