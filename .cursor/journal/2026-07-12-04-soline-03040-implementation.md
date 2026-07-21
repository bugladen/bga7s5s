# Soline el Gato (_03040) — create-character implementation

## Card text
1. City Reaction: After a character moves to this location → Move Soline to any City location
2. City Action: Engage Soline → Pressure this location with Finesse. Succeed even if tied. If successful, claim it or engage an opposing character.

## Approach / WHY

### Reaction
Pattern D on `EventCardMoved` (past tense). Mirror Ise `Reaction_03016b` gates EXCEPT the enemy controller check — printed text says "a character", not "opposing/enemy". Still skip Soline's own moves, non-Characters, and ControllerId==0.

Effect UI: button list of all city locations except current (Reaction_01089 adjacent-button shape, but full city set like Reaction_01099b). Pass declines without setUsed.

Valid-destination precondition: always true while Soline is in city (4 other city locations exist). Still gate on `cardInCity`.

### Action
Composite of:
- Action_01075 Tabard — Engage + pressure + win ties via PRESSURE_TYPE flag + claim on success
- Action_01105 Drinking Games — post-pressure engage picker state
- Action_03cd20 — PRESSURE_STAT = FINESSE

New `SOLINE_PRESSURE_TYPE = 16384` added to win-ties OR list in `UtilitiesTrait::pressureLocation`. WHY new flag not reuse TABARD: each card owns its bit so stacked pressures / diagnostics stay distinguishable (LOYAL pattern).

Post-success is **mandatory choose one** when either option exists:
- Claim button → `actFromCardWithId({id: 0})`
- Character highlight + Confirm for unengaged opposing at location
- If neither claimable nor engageable → skip picker, just `createActionResolvedEvent`
- No Pass — text is "claim it or engage", not "you may"

No `IAbilityThatTargetsCharacters` — text lacks "target".

## Files touched
- `modules/php/cards/faf/_03040.php`
- `modules/php/cards/faf/reactions/Reaction_03040.php`
- `modules/php/cards/faf/actions/Action_03040.php`
- `modules/php/States/faf/State_highDramaPhase03040.php`
- `modules/php/States.php` — `HIGH_DRAMA_PLAYER_TURN_03040 = 403040`
- `states.inc.php` — `"03040"` transition
- `modules/php/Game.php` — `SOLINE_PRESSURE_TYPE = 16384`
- `modules/php/UtilitiesTrait.php` — win-ties OR
- JS: OnEntering/Update/Leaving `.faf.js`

## Not done / watch
- Not playtested in Studio
- Title in stub is `Gato el la Bolsa` (likely print typo for "en") — left as-is
- Line endings left alone (no CRLF post-pass) per 03039 lesson

## Skill update (2026-07-12)

Folded into `create-character` skill:
- Canonical `_03040` bullet
- Ability-shape rows for pressure win-ties / claim-or-engage / any-character move / move-to-any-city buttons
- Pattern C subsections "Pressure (win ties)" + "Claim or engage after pressure"
- Pattern D: enemy gate optional; move-self button UI
- Checklist items 50–52; item 28/35 updated

WHY pressure got a full Pattern C subsection: the skill previously barely covered pressure Actions despite many cards needing them; Soline is the first FAF Character compositing win-ties + claim-or-engage, so she's the right canonical anchor.
