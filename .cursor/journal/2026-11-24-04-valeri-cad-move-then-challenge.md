# CAD Valeri (_05DabneyUS01) — move even with no challenge target

## User ruling
City Action: "Move Valeri to an adjacent City location. He issues a [Combat]
challenge to target opposing non-Leader."

Abilities resolve L→R; if a later clause has no legal target it fails and you
continue. Move can succeed with zero challenge targets at the destination.

## Current (wrong)
Availability + UI require a challengeable opposing non-Leader at an adjacent
city. Pick character → CharacterTargeted → move onto them → challenge.
Empty-adjacent / Leader-only / Envoy-banned destinations cannot be used as a
pure move.

## Plan (Yepikhodov / pattern-c L→R)
1. Availability: `cardInCity` + ≥1 adjacent city (drop challenge-target gate).
2. Step 1: location picker (Forsythe/Refuge UX).
3. Move `engage=false` (trichotomy c unchanged).
4. On `EventCardMoved`: if challengeable non-Leaders at `toLocation` → transition
   to post-move target pick; else `createActionResolvedEvent` (move-only done).
5. Step 2: pick challenge target at Valeri's new location → CharacterTargeted
   (after move — cancel must NOT undo the move / delete move batch).
6. On targeted survive → `"05DabneyUS01_2"` TECHNIQUE_AVAILABLE as today.
7. Keep `shouldIssueChallenge` for Soline/Envoy soft-lock after target pick.

## WHY not keep character-first
Printed first clause is a location move, not "target then move there". Character-
first makes empty destinations illegal and conflates the two clauses.

## Transition keys
- `"05DabneyUS01"` location pick (405001) — Forsythe UX
- `"05DabneyUS01_3"` GAME bridge (4050013) — stateFromAction after CardMoved reactions
- `"05DabneyUS01_4"` challenge target pick (4050014) when targets remain
- `"05DabneyUS01_2"` keep → TECHNIQUE_AVAILABLE (many deleteQueuedTransitionsNamed)

## WHY GAME bridge
Checking targets inside EventCardMoved races Soline/CardMoved reactions (pri 6 vs
transition pri 9). Bridge runs after those drain; empty → ActionResolved; else → _4.

## CharacterTargeted after move
Move no longer queued from targeting. Cancel must not deleteEventBatch the move
(already drained). Only challenge hub-exit `"05DabneyUS01_2"` on survive.

## Done
Action + states `_3`/`_4` + JS location/character wiring + tests 15/15.
pattern-f / references updated for location-first L→R.

## Skill note (user ask)
Added **Ability grammar (`•` / left-to-right)** to all seven card SKILL.md files
(character, risk, scheme, city-character, city-attachment, city-event-card,
faction-attachment) + create-character pattern-c reminder + checklist item 1.
WHY: same parse rule applies to every card type, not just Characters.
