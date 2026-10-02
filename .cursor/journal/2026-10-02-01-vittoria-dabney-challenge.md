# Vittoria missing on CAD Valeri (05DabneyUS01) challenge

## Bug
Valeri used Action_05DabneyUS01 (move + unrefusable Combat challenge) targeting
Vittoria. Player had Alcee in hand. No Vittoria reaction prompt; only Accept
(unrefusable, no other characters to Intervene). Engaged state of Vittoria is
irrelevant to her Reaction text.

## Root causes (stacked)

1. **No `EventCharacterTargeted` on Dabney.** Action queued move + `"05DabneyUS01_2"`
   directly from `actFromActionWithId`. Vittoria's "when an opponent targets"
   path for printed Target never saw a targeting event at pick time. Relied on
   later `EventChallengeIssued`, which is the wrong moment for move-then-challenge
   (and is broken by (2)/(3)).

2. **Technique overwrites `TRANSITION_INTERNAL_ID`.**
   `actHighDramaChallengeActionTechniqueActivated` sets it to the Technique id
   before `stIssueChallenge`. `EventChallengeIssued.abilityId` becomes
   `Technique_05DabneyUS01`, which does **not** implement
   `IAbilityThatTargetsCharacters`. Vittoria's `EventChallengeIssued` handler
   gated on `shouldReactToEvent` → silent no-prompt. Same bug Premonition fixed
   in journal `2026-09-05-07` (skip interface gate for challenges).

3. **Even if (2) were fixed alone:** post-move, Dabney's
   `isValidTargetForAbility` still requires **adjacent** (pre-move picker rule).
   Redirecting to a Thug at the shared location would fail validation and
   `cancelEvents()` the challenge. Challenge redirects must not use that check.

## Fixes

### `Action_05DabneyUS01` — Come Hither pattern
- `actFromActionWithId`: validate + fire `EventCharacterTargeted` only; nextState
  to EVENTS.
- `handleEvent`: on non-canceled `EventCharacterTargeted` for this ability, sync
  `CHOSEN_TARGET` (Vittoria/DI redirect), queue move (engage=false) + `"05DabneyUS01_2"`.
- WHY gate on surviving targeting: UL/Maryam cancel must stop move+challenge
  (journal `2026-09-13-12`). WHY fire before technique: "when targeted" with
  Thug still in hand, and abilityId still the Action for other listeners.

### `Reaction_01014` — challenge path
- `EventChallengeIssued`: no longer call/gate on `shouldReactToEvent`. WHY:
  challenges always choose a defender; technique mutates abilityId (Premonition
  WHY). Bare call to capture ability ids was pointless — challenge redirects
  skip `isValidTargetForAbility` anyway (removed after Eddie asked).
- `performReaction` inPlayThug: if `$this->challengeIssuedEvent`, release to Thug
  without `isValidTargetForAbility`. WHY: see root cause (3); buttons already
  filter Thugs at Vittoria's location.

## Not changed
- `Action_01123` (base Valeri) — same missing `EventCharacterTargeted`. Same
  class of bug if Technique activated. Left alone; this report was CAD Valeri.
  Fix the same way when it bites.
- Framework technique overwrite of `TRANSITION_INTERNAL_ID` — broader change;
  reactions already work around it (Premonition, now Vittoria).

## Test plan
1. Vittoria in city, Alcee in hand, Valeri CAD Action targets Vittoria → reaction
   prompt **before** technique/accept; muster Alcee → challenge Alcee; optional
   move Home.
2. Same with Technique activated after redirect — challenge still goes to Thug.
3. Decline Vittoria → challenge still issues to Vittoria (unrefusable Accept).
4. Basic challenge + Technique vs Vittoria with Thug in hand → reaction now
   prompts (EventChallengeIssued gate fix).
