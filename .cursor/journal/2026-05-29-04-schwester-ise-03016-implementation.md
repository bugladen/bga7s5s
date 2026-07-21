# Schwester Ise (03016) "Moonlit Interrogator" — Implementation

## Card Text

- During Dusk, you may choose not to move Ise **Home**.
- Ise has +1 [Combat] while wounded.
- **Reaction:** After an enemy character moves to this location • Move
  another character you control to this location.

Stats: Resolve 5 / Combat 1 / Finesse 2 / Influence 2. Eisen. Traits:
Academic, Hunter, Zealot, Eisen.

## Files

- `modules/php/cards/faf/_03016.php` — the Character class. Holds the
  +1 Combat passive (handleEvent on wound/heal events) and wires both
  Reactions in `Reactions[]`.
- `modules/php/cards/faf/reactions/Reaction_03016a.php` — the Dusk
  reaction (don't-go-home).
- `modules/php/cards/faf/reactions/Reaction_03016b.php` — the
  enemy-moves-here reaction.

## Ability 1 — Dusk "stay in city" reaction

User explicitly asked for this to be a Reaction. Implemented as
`Reaction_03016a` listening on `EventCardMoving` with these gates:

```
cardId == owner.Id
toLocation == LOCATION_PLAYER_HOME
sourceId == 0
turnPhase == Game::DUSK
! in_array(owner.Id, cancelDeclinedByCardIds)
```

### Why `sourceId == 0` as the Dusk signal

`StatesTrait::stDuskPhaseCleanup` (line 2144) emits the Dusk auto-move
home with an explicit `$sourceId=0`. Every ability-driven move-home in
the codebase passes a non-zero sourceId (action id / reaction id / etc.) —
grepped all 17 `createCardMovingEvent(...LOCATION_PLAYER_HOME...)` call
sites to confirm. `_01126` is the one other zero-source case, but it's
specific to that card's own logic and never moves Ise; safe.

The redundant `turnPhase == DUSK` gate is belt-and-suspenders: cheap,
authoritative, and protects against future code paths that might emit a
zero-source move-home outside Dusk.

### Why use the existing cancel mechanism (vs. some new opt-out flag)

`EventCardMoving::cancelDeclinedByCardIds` already exists for exactly
this case — see `Reaction_01140` (Cancel Your Character Movement) which
does the same dance for player-initiated moves. The "Decline" path
re-queues the event with `cancelDeclinedByCardIds[] = owner.Id` so the
reaction doesn't immediately re-catch the re-queued event. Used
`stackEvent` (not `queueEvent`) for the transition like Reaction_01140
does — stacking puts the reaction prompt ahead of other queued work so
it runs before subsequent dusk cleanup events for other characters fire.

### Why not a passive `eventCheck` cancel

Could have unconditionally canceled the move in `eventCheck` and let
some other player input drive it. But the card text says "you MAY choose
not to" — there has to be a player decision, and the Reaction pattern
is exactly how players make in-flight decisions in this codebase.

## Ability 2 — +1 Combat while wounded

Standard "track a flag, queue inverse events" pattern (Soline el Gato
`_01089` shape, simplified — Soline does it for an opponent's Finesse,
this is for self-Combat).

`handleEvent` listens on `EventCharacterWounded` and
`EventCharacterHealed` where `characterId == $this->Id`. After
`parent::handleEvent($event)` runs (which is when the parent
`Character::handleEvent` updates `$this->Wounds`), we recompute:

- `$this->Wounds > 0 && ! $WoundedCombatBonusApplied` → queue
  `CombatModified(Combat, Combat+1)`, set flag.
- `$this->Wounds == 0 && $WoundedCombatBonusApplied` → queue
  `CombatModified(Combat, Combat-1)`, clear flag.

### Why a flag instead of a recompute-from-base

Identical reasoning to Joern (`2026-05-29-03`): attachments and other
cards also mutate `ModifiedCombat`. A naive recompute that does
`base_combat + (wounded ? 1 : 0)` would clobber attachment bonuses
(like a Weapon adding +1 Combat). The delta-on-state-change pattern
plays nicely with the rest of the stat-modifier ecosystem because each
modifier only adjusts what *it* contributed.

### Why skip on IsDying / in discard

If the wound event that puts her at Wounds >= ModifiedResolve fires,
`Character::handleEvent` (line 256) sets IsDying and queues
destroy. At that point queueing a combat bonus is wasted work — her
ModifiedCombat is irrelevant for anything downstream. The
`characterIsInDiscardOrLocker` and `IsDying` gates skip that case.

### Edge cases considered

- **Healed during a duel.** If she goes Wounds 1 → 0 mid-duel, the
  bonus drops. That's correct per the text — bonus only applies "while
  wounded." Combat applies to her stat-pressure pool and her threat
  in challenges; she doesn't have a combat card stat that's locked in
  at the start of the round, so the live update is fine.
- **Multiple wound events stacked.** Each one calls recompute, but the
  flag-guard makes subsequent calls no-ops once the bonus is applied.
- **Re-instantiation.** `resetCard()` resets `ModifiedCombat` to Combat,
  and the flag is a default-false on the new instance. Clean.

## Ability 3 — Reaction: enemy moves here → move a controlled character here

`Reaction_03016b` listens on `EventCardMoved` (the past-tense event).
Gates:

```
isAvailable()
event.cardId != owner.Id          (don't trigger on Ise's own moves)
event.toLocation == owner.Location (moved card landed at our location)
cardInCity(owner)                  (Ise must be in city)
character is a Character
character.ControllerId != 0
character.ControllerId != owner.ControllerId  ("enemy")
at least one eligible mover exists (don't prompt for a useless choice)
```

### Eligible movers

"Another character you control to this location" — anyone the
controller owns, except Ise herself, except characters already at Ise's
location. Uses `getCharactersInPlayByPlayerId` which already covers
both city and Home — moving from Home to city is fine (it's an
implicit muster into play).

### Why `createCardMovingEvent` (not Moved)

`Moving` is the pre-event; the framework's hub handler does the actual
move and emits the `Moved` past-tense event. All player-initiated and
ability-driven movement uses the `Moving` event. The CardMoved trigger
we listen on is the *result* of someone else's Moving event.

### Why no IAbilityThatTargetsCharacters

The skill flags this for actions that target a character with their
ability. The reaction's effect is to *move* a character, not to apply
an effect to them. None of the existing move-character reactions
(Reaction_01039 Philip, Reaction_03cd18) implement that interface.
The interface is for hooks like "before this character is targeted by
an ability, do X" — moving isn't targeting in the rules sense.

### Edge case: the moving event is itself canceled or unstoppable

`EventCardMoved` only fires after the move actually happens — by then
the cancel question is moot. No additional guards needed.

## Pre-commit hook compliance

- **Reaction subclasses** need both `$this->setUsed(` and
  `$this->isAvailable(` as literal grep matches. Both reactions have
  both. Verified.
- **No Action / Sorcerer / Risk** patterns — N/A.
- **No new traits** — all four are already in `TraitNames::$TraitsJson`
  (Academic line 10, Eisen line 66, Hunter line 99, Zealot line 212).

## Things considered and rejected

- **Tying the Dusk reaction to `EventDuskPhaseBegin` instead of the
  move event.** Would need a separate flow to actually skip the dusk
  move-home for Ise specifically — too far from the existing cancel
  pattern, and the cancel-pattern variant is already mentally
  approved (Reaction_01140 is exactly this dance for player moves).
- **Implementing the +1 Combat as a passive in
  `EventDuelCalculateCombatCardStats`.** Wrong layer — the card text
  is about her Combat *stat*, not her combat *card*. The stat affects
  challenge threat and pressure, not just combat cards. Using the
  CombatModified event is the correct layer.
- **Making the "move another character" reaction unconditional
  (auto-pick).** No — the text says player chooses, and there's always
  the question of whether to move *which* character, so a button
  picker is needed.

## What I'd flag for future audit

- The Dusk reaction fires per move-home event, so if Ise is moved
  Home by some other ability that uses sourceId=0 (defensive: I've
  audited and none currently do this for Ise specifically), the
  reaction could mis-trigger. The DUSK phase gate is the secondary
  defense.
- The wounded-combat-bonus flag is in-memory state on the Character.
  If a save-load cycle drops `IsUpdated`, the flag could desync from
  ModifiedCombat (e.g., bonus event queued but flag-write lost). I
  followed the standard `IsUpdated = true` discipline so this matches
  every other stat-modifier pattern in the codebase; if there's a bug
  here it's not Ise-specific.
- "Another character you control" — read literally, this includes
  characters at Home. I allow this. If playtesting reveals that
  pulling a Home character into city via a Reaction is too strong,
  add a `cardInCity($character)` filter to `getEligibleMovers`.
