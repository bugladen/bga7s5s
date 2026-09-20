# 08 — Challenge Actions (Pattern F)

← [[07 — Techniques|Character Guide Techniques And Maneuvers]] · [[Index|Implementing a Character Card]] · Next: [[09 — Wiring|Character Guide Wiring States And JavaScript]]

Some Actions say **Issue a Combat / Finesse / Influence challenge**. You do **not** reimplement dueling. You set a few globals, then hand control to the existing challenge state machine.

References to keep open (these are Characters / Character Actions unless noted):

- Aja `Action_03002` — Engage self • Combat challenge with special intervene/refuse rules
- Don Constanzo `Action_03003` — your Thug issues the challenge (performer ≠ owner)
- Raven `Action_04012` — no intervention allowed
- Valeri `Action_01123` — move adjacent + Combat + no intervene
- Sanjay `Action_03037` — Leader, but the **never engages** Pattern F exemplar
- Danilo `Action_04002` — intervene follow-up (wound or draw)

## High-level flow

1. Player activates the Action.
2. Picker state: choose the defending character (and sometimes the performer).
3. Your Action sets:
   - `Game::CHOSEN_PERFORMER`
   - `Game::CHOSEN_TARGET`
   - `Game::CHALLENGE_STAT` (`STAT_COMBAT` / `STAT_FINESSE` / `STAT_INFLUENCE`)
   - `Game::CHALLENGE_TYPE` (`NORMAL_CHALLENGE_TYPE` or a new type)
4. Queue transition `"NNNNN_2"` into `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` via the events dispatcher.
5. Framework handles intervene / refuse / techniques / threat.

## states.inc.php needs both keys

```php
"NNNNN"   => States::HIGH_DRAMA_PLAYER_TURN_NNNNN,              // picker
"NNNNN_2" => States::HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE,  // enter challenge machine
```

After the player confirms the target, call `nextState("targetChosen")` back to `HIGH_DRAMA_PLAYER_TURN_EVENTS` **and** queue `createTransitionEvent(..., "NNNNN_2", ...)`. The events queue must flush (engage events, etc.) before the challenge machine continues.

## Do not call `createActionResolvedEvent`

The challenge flow resolves the Action itself. Leave a comment mirroring other challenge Actions.

## Engagement trichotomy (read carefully)

"Engage" on the card is **not** one pattern.

| Printed shape | Eligibility | Auto-engage list in `stIssueChallenge` | Manual `createCardEngagedEvent` |
|---|---|---|---|
| **Engage [performer]** printed | Require `!Engaged` | Add your new type to the list | None (auto) |
| No Engage printed, but unengaged performers still engage (Don Constanzo Thug) | Engaged performers OK | Keep type **out** of list | `if (! $performer->Engaged) { … }` |
| Never engages (Sanjay) | Engaged performers OK | Keep type **out** | **None** |

Copying Don Constanzo's conditional engage onto every "no Engage printed" card is a common bug. Ask: does this Action engage at all?

Why avoid double-engage: re-emitting `EventCardEngaged` on an already-engaged character can fire reactions that think a *new* engage happened.

## When to mint a new `*_CHALLENGE_TYPE`

Create a new constant when you need behavior different from `NORMAL`, for example:

- special intervene / refuse rules (Aja)
- must not use NORMAL's auto-engage (Sanjay)
- "other characters cannot intervene" (Raven / Valeri / Torvo)
- intervene follow-up choice keyed off the type (Danilo)

### Files to touch when the type has intervene/refuse rules

Keep the PHP int and the JS int **identical**:

| File | Change |
|---|---|
| `modules/php/Game.php` | `final const FOO_CHALLENGE_TYPE = N;` |
| `seventhseacityoffivesails.js` | `this.FOO_CHALLENGE_TYPE = N;` |
| `StatesTrait::stIssueChallenge` | Auto-engage list **only** if Engage is printed |
| `Theah::interventionCheck` | Server-side intervene ban |
| `ArgumentsTrait::argsHighDramaChallengeActionAcceptChallenge` | Filter visible interveners |
| `FrameworkActionsTrait::actHighDramaChallengeActionReject` | Server-side refuse ban |
| `OnUpdateActionButtons.js` | Disable Refuse button in UI when needed |
| `Reaction_02058` | Skip Jump In for full no-intervene types |

If the type only avoids auto-engage and has **no** intervene/refuse restrictions, you often only need `Game.php` + matching JS int.

## Character-scoped refuse (no new type)

Some Characters say they (or their challenges) cannot be refused when a relative stat check fails. That is **not** a new challenge type — put a helper on the card class and wire reject / args / JS Refuse disable / zombie Accept. Reference: Daichi `_03050`.

## Performer ≠ Action owner

"Your Thug at this location issues a challenge" is two steps: pick performer, then pick target at the **performer's** location. Engagement still follows the trichotomy. Reference: `Action_03003`.

## Next

Wire pickers and challenge UI → [[09|Character Guide Wiring States And JavaScript]]
