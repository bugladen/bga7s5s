# Unyielding Loyalty — gate self-targeting

## Bug
UL (Reaction_01032) offered Play during a Recruit Action. Card text does not say
"opponent," but canceling your own recruit/self-engage is nonsense.

## Root cause
Cirilo (`Action_01009`) implements `IAbilityThatTargetsCards` and engages Cirilo
himself (`createCardEngagedEvent(..., $owner->Id, $owner->Id, $this->Id)`).

UL already required:
- targeted card controlled by UL's player
- ability is `IAbilityThatTargetsCharacters` or `IAbilityThatTargetsCards`

It did **not** require the *source* to be an opposing controller. Cirilo's engage
matched both checks → intercept + reaction prompt mid-recruit.

Basic Recruit parley engage uses `sourceId=0` / empty `abilityId`, so it would
not have hit `shouldReactToEvent`. Cirilo (and any other self-engage that
implements a targeting interface) would.

## Fix
In `shouldReactToEvent`, resolve initiator as source card's ControllerId, else
`$initiatingPlayerId` (playerId / initiatingPlayerId from the event). Refuse when
initiator is 0 or same as UL owner.

WHY initiator gate over "only react if target != source character": Cirilo is both
source and engage target; other self-effects may target a different friendly card
(heal/engage ally). Same-controller is the rule the user asked for.

WHY fall back to event playerId: BasicChallenge often has `sourceId=0`; Vittoria
uses the same `$source ? source.ControllerId : event.playerId` pattern for
challenges. Without the fallback, defending against a basic challenge would stop
working.

Call sites pass playerId / initiatingPlayerId where the event has one; wound/heal
rely on sourceId only (those events have no playerId).

## Don't regress
Pay-first cost flow (2026-09-13-13) is untouched. This is only the trigger gate.
