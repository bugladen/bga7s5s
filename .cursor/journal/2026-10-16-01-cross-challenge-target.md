# Cross of the Martyrs — challenge targets the defender

## Bug
Opponent challenges your other character at the Cross location. Reaction_02016 does not offer.

## Why it missed
Card text is "when an opponent targets your character." A challenge targets the **defender**. Two signals, both gated wrong:

1. Framework target pick emits `EventCharacterTargeted` with `abilityId = BasicChallenge` and `sourceId` = the challenger. `shouldReactToEvent` only looked up the ability **on that character**. Basic Challenge lives on Theah (`BasicChallengeAction`), so the lookup failed.
2. `EventChallengeIssued` called the same gate. `stIssueChallenge` copies `TRANSITION_SOURCE_ID` (often 0 — basic challenge never sets it) and `TRANSITION_INTERNAL_ID` (overwritten with the technique id in `actHighDramaChallengeActionTechniqueActivated`). Same miss Premonition and Vittoria already special-cased.

Old ChallengeIssued branch also treated "your participant" as the target, preferring the challenger when you controlled them. That is the wrong character. The challenged character is the one targeted.

## Fix
- `shouldReactToEvent`: if the ability is not on the source card, resolve `getInPlayActionById` (Unyielding Loyalty shape). Basic Challenge `EventCharacterTargeted` now opens Cross at target pick, before the technique.
- `EventChallengeIssued`: defender only. Same controller, same location, not already the equipped performer, challenger is an opponent. No `IAbilityThatTargetsCharacters` gate (sourceId 0 / technique overwrite). Covers Defending Honor, which deliberately does not emit CharacterTargeted for the challenged ally.
- Decline of the target-pick offer sets `skipChallengeIssuedAfterTarget` when `abilityId === CHOSEN_ACTION`, so issuing the challenge does not prompt a second time. `skipNextEvent` still eats the re-queued CharacterTargeted.

Redirect still wounds the equipped character and rewrites `defenderId` + `CHOSEN_TARGET`. `isChallenger` stays false on this path; the old challenger-swap branch in `releaseEvent` is unused for new challenges. Left it — no reason to delete a release path that serialized in-flight reactions might still hold.

## Gaps closed

Eddie said close them.

- Removed `isChallenger`. Challenge release always rewrites `defenderId` and `CHOSEN_TARGET`. The branch that swapped the challenger was the old "your participant" model and could not run (`isChallenger` was hardcoded false).
- `EventCharacterTargeted` release now writes `CHOSEN_TARGET` when that global still names the intercepted character, or when `abilityId` is the in-flight `CHOSEN_ACTION`. Otherwise an unrelated global stays. WHY the guard: EventHub only copies the id later for the chosen action; a Risk redirect must not stomp a challenge target that belongs to a different pick.
- `shouldReactToEvent` no longer requires a source card. Opponent is source controller, else the event's player id (engage / engarde / move / CharacterTargeted), else the resolved action's owning card (wound / heal have no player id). Still refuses when that id is 0 or is the Cross controller.
- Intervention decline no longer `setUsed`. Same rule as wound decline: declining is not using the reaction. Still re-queues the event unchanged so `releaseEvent` does not move `oldTargetId` onto the wearer.

## Audit: wearer-as-original-target leftovers

Eddie asked whether any branch still only opens when the equipped character is the one targeted (the original Vittoria-shaped bug).

Offer gates do not. Engage, engarde, move, wound, heal, and CharacterTargeted all go through `shouldReactToEvent`, which requires a different friendly character at the wearer's location and returns false when that character already is the wearer. ChallengeIssued (this session) and CharacterIntervened (`newTargetId`) do the same. Excluding the wearer is correct: "targets your performer instead" has nowhere to go.

Leftovers that still *act* as if the wearer was the original target:

- `releaseEvent` `isChallenger` branch rewrites `challengerId` / `CHOSEN_PERFORMER`. New ChallengeIssued handling always sets `isChallenger = false`, so it is dead. Left from "your participant might be the challenger."
- CharacterTargeted release does not set `CHOSEN_TARGET`. Vittoria does (`Reaction_01014`) so the global matches before the re-queue. Cross only rewrites `targetId`. EventHub copies it onto `CHOSEN_TARGET` later, and only when `abilityId == CHOSEN_ACTION`. Any other targeting ability keeps the global on the ally.
- Intervention decline still `setUsed(true)` and the comment still describes Vittoria (condition already on the intervener). Accept path correctly strips `DUEL_DEFENDER` from `targetCharacterId` (the ally). Decline-marks-used is a separate bug; do not "fix" it by removing the condition from the wearer.

Also: `shouldReactToEvent` still returns false when the source card is missing, even after `getInPlayActionById` succeeds. ChallengeIssued bypasses that. Any other ally-targeting event with `sourceId = 0` still will not open. That misses everyone, not only the wearer.

## Audit: other "when targeted" cards vs basic challenge

Eddie asked if any other card reacts to a character being targeted and still misses Basic Challenge the way Cross did (ability lookup only on the source character).

No. The printed "when X is targeted" reactions that are not Risk-only or Sorcerer-only already resolve `BasicChallenge` off Theah:

- Vittoria `Reaction_01014` — `getInPlayActionById` fallback on `EventCharacterTargeted`. `EventChallengeIssued` does not use that gate at all.
- Unyielding Loyalty `Reaction_01032` — same fallback, and ChallengeIssued passes `playerId` when `sourceId` is 0. Issue-time still dies if a technique overwrote `abilityId`, but target-pick `EventCharacterTargeted` already opened it with `abilityId = BasicChallenge`.
- Premonition `Reaction_03006` — same fallback. ChallengeIssued skips the ability gate (Defending Honor included). Pass does not `setUsed`, so a declined target-pick offer can ask again at issue. Cross added `skipChallengeIssuedAfterTarget` for that. Premonition does not. Not a missing trigger.

Correctly ignore basic challenge (text is narrower):

- Torsten `01122`, Hexenjagd `01053`, Cesca `01008` — Sorcery / Sorcerer only. Lookup stays on the source card.
- Maryam `01186`, Blood Like Winter `02048`, Silver Spine `03cd21` — opponent's Risk only. Basic Challenge is not a Risk. Spine still imports `EventCharacterTargeted` and never handles it; move/engage/wound/ChallengeIssued cover Risk effects. Not this bug.

Altruistic `03031` is "would wound, move, or engage", not "targeted". A basic challenge does none of those to the defender. Leave it.

## Do not regress
- Already-the-performer: no offer (nowhere to redirect).
- Your own challenge (you are the challenger): no offer. You are not the target.
- Accept still sets Used, so ChallengeIssued `isAvailable()` is false.
- Altruistic / Vittoria have their own challenge gates. Do not copy this defender-only rule onto them.
