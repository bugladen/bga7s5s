# Contempt and Hatred (01143) — aura must die with The Locker

## Bug
Eddie: Mercenary -1 Influence aura should not affect cards once Contempt and Hatred is in The Locker.

## Why the old code could keep affecting
- Recruit path gated on `Location == PLAYER_HOME`, which is correct in spirit, but the scheme can still sit in `$theah->cards` for the rest of the dusk cleanup request after EventHub has already moved it to `Locker-*`. Named `isSchemeInPlay()` makes that gate explicit.
- Locker cleanup restored **every** in-play Mercenary (`hasTrait`) rather than only characters stamped with `CONTEMPT_AND_HATRED_CONDITION`. Wrong correlator; also missed Spend-to-Locker corpses that keep serialized Conditions because `buildCity()` never loads Locker piles.

## Fix
Helpers: `applyMercenaryAura` / `removeMercenaryAura` / `clearAuraFromAllAffected`.
- Apply: skip if already stamped or in discard/locker; idempotent.
- Scheme → Locker: clear all in-play stamps (+1 via event) **and** scan each player's Locker for leftover stamps (direct ModifiedInfluence +1 + DB write — InfluenceModified IsUpdated flush only walks `$theah->cards`).
- Recruit: only while `isSchemeInPlay()`.

## Why not only Location check
Location gate stops *new* applies. Clearing by condition + locker scan stops *stale* applies that outlive the scheme — that was the observable "still affected after Locker" case.
