# 08 — Challenge Actions (Pattern F)

← [[07 — Techniques|CityCharacter Guide Techniques And Maneuvers]] · [[Index|Implementing a CityCharacter Card]] · Next: [[09 — Wiring|CityCharacter Guide Wiring States And JavaScript]]

Some Actions say **Issue a Combat / Finesse / Influence challenge**. You do **not** reimplement dueling. You set a few globals, then hand control to the existing challenge state machine.

CityCharacters use the **same** challenge hand-off as faction Characters. This page is the short map; for the full engagement trichotomy and file touch list, read [[Character Challenge Actions|Character Guide Challenge Actions]] and mirror a Character Action whose printed challenge text matches yours.

## High-level flow

1. Player activates the Action.
2. Picker state: choose the defending character (and sometimes the performer).
3. Your Action sets:
   - `Game::CHOSEN_PERFORMER`
   - `Game::CHOSEN_TARGET`
   - `Game::CHALLENGE_STAT` (`STAT_COMBAT` / `STAT_FINESSE` / `STAT_INFLUENCE`)
   - `Game::CHALLENGE_TYPE` (`NORMAL_CHALLENGE_TYPE` or a new type)
4. Queue transition `"03cdNN_2"` into `HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE` via the events dispatcher.
5. Framework handles intervene / refuse / techniques / threat.

## states.inc.php needs both keys

```php
"03cdNN"   => States::HIGH_DRAMA_PLAYER_TURN_03CDNN,              // picker
"03cdNN_2" => States::HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE,  // enter challenge machine
```

## Do not call `createActionResolvedEvent`

The challenge flow resolves the Action itself. Leave a comment mirroring other challenge Actions.

## Engagement trichotomy (one-line reminder)

| Printed shape | What to do |
|---|---|
| **Engage [performer]** printed | Require unengaged; let `stIssueChallenge` auto-engage your type |
| No Engage printed, but unengaged performers still engage | Conditional manual `createCardEngagedEvent` — keep type **out** of auto list |
| Never engages | Engaged performers OK; **no** engage event; keep type **out** of auto list |

Copying the wrong engage shape is a common bug. Ask: does this Action engage at all?

## When to mint a new `*_CHALLENGE_TYPE`

Only when behavior differs from `NORMAL` (no intervene, special refuse rules, avoid NORMAL auto-engage, …). Keep the PHP int and the JS int **identical**. Full file list → [[Character Challenge Actions|Character Guide Challenge Actions]].

## Next

Wire pickers and challenge UI → [[09|CityCharacter Guide Wiring States And JavaScript]]
