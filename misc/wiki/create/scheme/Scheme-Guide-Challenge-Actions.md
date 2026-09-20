# 08 — Challenge Actions

← [[07 — When Revealed, Forced, and Passives|Scheme Guide When Revealed Forced And Passives]] · [[Index|Implementing a Scheme Card]] · Next: [[09 — Wiring|Scheme Guide Wiring States And JavaScript]]

Some Scheme City Actions **issue a challenge**. The Action still lives in `actions/Action_NNNNN.php` and extends `SchemeCityAction`, but you hand control to the challenge framework after picks.

This page is a beginner map. Mirror a real Scheme Action — do not invent a new challenge pipeline.

## When you need this page

Printed text looks like:

- "… issues a [Combat / Finesse / …] challenge"
- "Engage your performer • Your \<Trait\> at this location issues a challenge"
- "If your performer is a Duelist, it can only be refused by discarding a card"
- "Only \<Trait\>s may intervene"
- "If the challenge is accepted, add a threat to your participant"

## Engagement trichotomy

Before coding, decide which printed shape you have:

| Shape | Meaning |
|---|---|
| **Engage as cost** | Queue `createCardEngagedEvent` on the printed engager, then challenge |
| **Conditional Engage** | Only some branches engage |
| **Never Engage** | Challenge without an engage cost |

Copy the closest exemplar; do not mix Don Constanzo / Sanjay / scheme patterns blindly.

## Split performer and challenger

Printed: **"Engage your Diplomat • Your Duelist at this location issues a Combat challenge"**

1. Framework performer pick = Diplomat (trait-gated).
2. Engage that performer.
3. HD sub-state: pick Duelist challenger at that location.
4. HD sub-state: pick opposing target → challenge.

`CHOSEN_CARD` often preserves the original performer id; `CHOSEN_PERFORMER` becomes the challenger for the challenge framework.

Reference: `_03030` / `Action_03030` (Sworn Swords).

## Custom challenge types

When text restricts intervene / refuse (e.g. "Only Duelists may intervene"), add a `Game::…_CHALLENGE_TYPE` constant and wire **all three**:

1. `Theah::interventionCheck`
2. `ArgumentsTrait` (intervene-picker ids)
3. `Reaction_02058` (adjacent external intervene), if that path exists for your expansion

Also add a matching JS int in `seventhseacityoffivesails.js` when the client must disable Refuse / Intervene.

Reference: `_03030` (`SWORN_SWORDS_CHALLENGE_TYPE`).

## Accept-time threat

Printed: **"If the challenge is accepted, add a threat to your participant"**

Listen on `EventGenerateChallengeThreat` in the Action; bump `$event->actorThreat` only (unless the card says both sides). Fires on accept/intervene when threat is generated, not on refuse.

Reference: `Action_03030`.

## Discard-to-refuse

Printed: **"… can only be refused by discarding a card"**

1. Set a correlating `CHALLENGE_TYPE` so refuse is **out of** the auto-engage path.
2. Route refuse through a card-keyed HD discard state.
3. ACCEPT_CHALLENGE transition key is the card number string `"NNNNN"`.
4. Discard GameState uses a **named** success transition (e.g. `"cardDiscarded"`) plus `"back"` — never pair `""` with `"back"`.
5. JS disables Refuse using `mustDiscardToRefuse` + hand count when needed.

Reference: `_03042` / `Action_03042` (When Least Expected).

## Files you typically touch

| Piece | Where |
|---|---|
| Action class | `actions/Action_NNNNN.php` |
| HD state(s) | `States/<expansion>/State_highDramaPhaseNNNNN.php` |
| Constants | `States.php` (`40<NNNNN>…`) |
| Transitions | `HIGH_DRAMA_PLAYER_TURN_EVENTS` (+ accept-challenge map when refuse is custom) |
| JS triple | OnEntering / OnUpdate / OnLeaving |
| Challenge type | `Game.php` const + maybe `seventhseacityoffivesails.js` |
| Intervene trio | `Theah`, `ArgumentsTrait`, `Reaction_02058` |

## Pre-commit note

Challenge-flow Actions must still satisfy the `createActionResolvedEvent()` literal check — often via a comment explaining the challenge framework owns resolution. Mirror `_03042`.

## Next

Wire states and JS → [[09 — Wiring states and JavaScript|Scheme Guide Wiring States And JavaScript]]
