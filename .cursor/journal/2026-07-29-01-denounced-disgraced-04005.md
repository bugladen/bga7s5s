# Denounced, Disgraced (_04005)

## Orientation

BAS scheme. Scaffold has Text; Initiative 35 / Panache 0 match art. Traits Villainous + Purge — **Purge missing from TraitNames** (same class of bug as Assassination on _04004).

## Classification

1. **Pattern A trivial:** Add Renown to City Docks. No planning sub-state.
2. **Red Hand City Action:** trait gate (NOT Sorcerer). Destroy another controlled character at performer's location → Claim → Each player discards a card.

## Design decisions (WHY)

- **Destroy pick = HD state 04005** (activeplayer), not Pattern H immediate-resolve — need a character pick after framework performer select. Mirror Action_01015 shape (IAbilityThatTargetsCharacters + ids highlight) but filter **you control** and destroy the **target** (01015 destroys the performer as cost).
- **Claimability gates performers** (Pattern H discipline) — Claim is a printed payoff; don't offer a dead spend of your own character. Recheck at resolve; trailing discard still fires if claim blocked.
- **"Each player"** includes the acting player → MULTIPLE_ACTIVE_PLAYER state `04005_2`, NOT `stMultiPlayerInitSansInitiatingPlayer` (that's opponents-only like Patricia 01095). Custom onEnteringState activates only players with ≥1 hand card (empty hands can't pay "discards a card").
- **ActionResolved before discard transition** — same as Action_01095b (ActionResolved priority 3 runs before Transition priority 8). HD action wraps; discard is trailing multi-state returning to HD_EVENTS.
- Skip discard transition entirely when nobody has a hand card at resolve time.

## Shipped

- Pattern A: Docks Renown on EventResolveScheme
- Action_04005: Red Hand gate + destroy pick + claim + multi discard
- States 404005 / 4040052; HD_EVENTS keys `04005` / `04005_2`
- Purge added to TraitNames after Punctual
- JS bas triple + EventHandlers enable for 04005_2
- php -l clean; doubleCR=0 on new PHP

## WHY reminders for next agent

- Do NOT switch discard to sans-initiating-player — card says each player
- Do NOT move ActionResolved after discard multi-state — 01095b priority ordering is intentional
- Claimability in getPerformersForAction is deliberate (heavy cost = destroy your own char)
- unequip before destroy on direct destroy path (EventCharacterDestroyed recreates card)

## Follow-up 2026-07-30 — back on 04005

Eddie asked for back on `highDramaPhase04005` (destroy pick).

**First attempt (WRONG):** `"back" => HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER` mirroring legacy `01015` in states.7s5s.php.

**Bug Eddie hit:** back → choose performer → pick another → does NOT return to 04005.

**WHY it breaks:** `stHighDramaInPlayActionDispatch` queues `EventActionTriggered` *before* branching to CHOOSE_PERFORMER. First performer pick runs EVENTS, consumes Triggered, Action_04005 queues Transition("04005"). Bare back to CHOOSE_PERFORMER does not re-queue Triggered; second pick's EVENTS are empty → endOfEvents leaves the action.

**May 2026 context:** journal `2026-05-03-02` removed initial-state backs after CONFIRM precisely because announcement/Used already committed; performer-backs were flagged broken. Eddie still wants this UX for 04005.

**Fix:** `"back" => HIGH_DRAMA_IN_PLAY_ACTION_DISPATCH`. type=game; re-queues Triggered then immediately `requiresPerformerSelected` → `highDramaInPlayActionChoosePerformer`. Second pick gets a fresh Triggered → 04005 again.

Do NOT "simplify" back to bare CHOOSE_PERFORMER — looks right, breaks re-entry.

Not on `04005_2` — multiplayer discard after commit.

## Skill update 2026-07-30

Fed _04005 learnings into create-scheme:

- **Pattern L** in actions.md + SKILL shape table (destroy controlled → claim → each discards)
- Back → **DISPATCH** pitfall (not bare CHOOSE_PERFORMER)
- Each player vs each opponent / Pattern C contrast
- unequip-before-destroy; getGameDeckObject from State; Red Hand + Purge TraitNames
- references, checklist 28–30, walkthrough _04005

WHY document DISPATCH so hard: first draft used CHOOSE_PERFORMER looking "correct" from legacy 01015 — Eddie hit the silent-end bug. Future agents will "simplify" back to CHOOSE_PERFORMER without the WHY.
