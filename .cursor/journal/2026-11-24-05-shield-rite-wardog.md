# Shield Rite vs Smuggled Long Rifle — no offer

## Report
User: Shield Rite Reaction did not trigger off Smuggled Long Rifle abilities.

## What I checked
Rifle `Reaction_05Wardoga` queues `EventCharacterBeingWounded` with
`sourceId = attachment`, `abilityId = reaction Id` — same shape as Excalibur/Kaiser.
Shield Rite `isOpponentAbility` finds the attachment ControllerId and treats it as
opponent ability (empty-abilityId threat gate does NOT apply).

Reproduced in `Reaction_04058_Test`: en garde Sorcerer at Forum + opposing foe there
+ rifle wound → cancel + reaction transition. **Code path works.**

## WHY it often looks broken on rifle
Wardog is **After** move → wound. Two Shield Rite gates then fail in the common sniper
setup:

1. **En Garde** — basic Move engages (`createCardMovingEvent` default `engage=true`).
   Hub sets `Engaged` before the player clicks Wound. Wounded performer is no longer
   En Garde → Reaction heading rejects. (Blood Mark / other `engage=false` moves keep
   En Garde and *can* offer.)
2. **Opposing redirect pool** — "opposing" = same location. Sniper host is **adjacent**,
   not co-located. If nobody else is at the wounded site, offer is hidden (dead play).
   Cannot redirect onto the sniper next door under current print/pattern D.4.1.

Pressure half (`Reaction_05Wardogb`) never wounds — Shield Rite cannot fire on it.

## Resolution
User confirmed: no opposing characters at the wounded site to divert to. Expected
hide — not a bug. Kept only the positive regression
(`redirects a Smuggled Long Rifle reaction wound`); dropped engaged / adjacent-only
negatives. No production code change.
