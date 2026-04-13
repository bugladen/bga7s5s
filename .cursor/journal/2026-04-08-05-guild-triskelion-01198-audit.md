# Guild Triskelion (01198) Audit

## Card Text
> The equipped character gains Duelist.
> **City Action:** Engage the equipped performer · Issue a [Finesse] challenge to target opposing character. Only Leaders can refuse the challenge. (Intervening accepts the challenge.)

## Bug Found: Missing opposing-character check in isValidTargetForAbility

`isValidTargetForAbility` only validated that the target was at the same location as the performer, but did NOT check that the target was an opposing character. The UI-side args method (`argsHighDramaChallengeActionChooseTarget`) already filters for opposing characters, so a player couldn't select a friendly target through normal UI interaction — but the server-side validation had a hole.

### Fix
Added `$character->ControllerId == $performer->ControllerId` check, matching the identical pattern in `Action_01073` (Cavalier Hat) and `Action_01071` (Épée Sanglante), which are the closest analogs.

### Everything else checked out
- Duelist trait: added on equip, removed on unequip ✓
- City Action prereqs: controlled, in city, unengaged, opposing characters present ✓
- Engage performer: handled in `stSetupChallenge` for `TRISKELION_CHALLENGE_TYPE` ✓
- Finesse stat: `CHALLENGE_STAT` = `STAT_FINESSE` ✓
- `setUsed`, `announceAction`, `resetPlayerPassCount`: all in `stSetupChallenge` ✓
- Only Leaders can refuse: `eventCheck` blocks `EventChallengeRejected` for non-Leader targets ✓
- Intervening accepts: generic behavior in `actHighDramaChallengeActionIntervene` sets `CHALLENGE_ACCEPTED = true` ✓
- Back transition: `backTriskelion` goes to `HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION` ✓

## Files Changed
- `modules/php/cards/_7s5s/actions/Action_01198.php` — added opposing character validation
