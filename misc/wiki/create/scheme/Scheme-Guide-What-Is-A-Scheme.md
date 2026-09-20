# 01 — What is a Scheme?

← [[Index|Implementing a Scheme Card]] · Next: [[02 — The card class|Scheme Guide The Card Class]]

## Scheme vs Character vs City card

A **Scheme** is the card a player chooses each turn and reveals during the Planning Phase. It is **not** a city-deck card and **not** a Character.

| Class | Lives in | When it matters |
|---|---|---|
| **`Scheme`** | Player's scheme deck | Chosen each day; revealed / resolved in Planning; stays at Home until Dusk |
| `Character` / `Leader` | Faction deck / setup | In play as people you move and fight with |
| City cards | City deck | Mustered / equipped from the city |

**If your stub says `extends Scheme`, you are in the right place.**

## What makes a Scheme different

Schemes add two fields beyond a normal `Card`:

- **`$Initiative`** — lower number resolves earlier during Planning (tie-breaks with Leader Panache). Never leave this at `0`.
- **`$PanacheModifier`** — added to (or subtracted from) the controller's Leader Panache while the scheme is the chosen one (often `0`, `+1`, or `-1`).

They also have an optional hook: `hasWhenRevealedEffect()` (default `false`) for text that starts **"When this scheme is revealed…"**.

## Scheme location lifecycle (read this first)

1. Player selects a scheme during Planning.
2. Schemes are revealed (When-Revealed effects fire first, if any).
3. Schemes resolve in Initiative order (`EventResolveScheme`).
4. The chosen scheme stays at **`Game::LOCATION_PLAYER_HOME` for the rest of the day**.
5. At Dusk it goes to the **Locker** (not the discard pile).

Implications beginners miss:

- Forced / Action / Reaction gates that mean "is this my chosen scheme?" check `$this->Location == Game::LOCATION_PLAYER_HOME`.
- Scheme Reactions **do** fire during High Drama — the card is still in `$theah->cards` at Home. Do **not** add "is the scheme still in play?" guards that look for discard.
- `SchemeCityAction` availability already expects Home. Do not "fix" that to discard.

## What "City" means on ability text

Printed **City Action** / **City Reaction** does **not** change the base class.

- On a Scheme, **City** usually means the *performer* (or relevant character) must be at a city location — not that the scheme file should `extends CityCharacter`.
- The scheme card itself sits at Home. The Action still `extends SchemeCityAction`.

## Where files live

Card id `03005` in expansion `faf` (Fate & Faith):

```
modules/php/cards/faf/
  _03005.php                 ← Scheme card class (resolve + ownership)
  actions/                   ← if the card has Actions
  reactions/
    Reaction_03005.php       ← Reaction

modules/php/States/faf/
  State_planningPhaseResolveSchemes03005.php   ← only if resolve needs a picker
  State_highDramaPhaseNNNNN.php                ← only if an Action needs a picker
  State_planningPhaseEnd_NNNNN.php             ← only if Forced-at-Planning-End needs a picker

modules/js/
  OnEnteringState.faf.js
  OnUpdateActionButtons.faf.js
  OnLeavingState.faf.js
```

Crash the Party (`_02004`) has a Reaction and **no** resolve GameState (Renown is automatic).

Expansion folders you will see:

| Folder | Expansion |
|---|---|
| `_7s5s` | Base game |
| `tac` | Tooth and Claw |
| `faf` | Fate & Faith |
| `bas` | Blood and Steel (and similar newer expansions) |

## How the game runs (very short)

1. The backend is a **state machine** defined in `states.inc.php`.
2. Cards react to **events** (`EventResolveScheme`, `EventPhasePlanningEnd`, …) through `handleEvent`.
3. Scheme **resolve picks** use Planning resolve states. **City Actions** use High Drama states. **Forced at end of Planning** uses a *third* map — see [[09 — Wiring|Scheme Guide Wiring States And JavaScript]].
4. Multi-step choices use custom **states** + a little **JavaScript** to highlight cards/locations and enable Confirm.

You almost never write UI from scratch. You copy an existing state's JS hooks and change the state name.

## Base Scheme behavior (do not reimplement)

`modules/php/cards/Scheme.php` already owns Initiative / PanacheModifier and the When-Revealed hook default.

Always call `parent::handleEvent($event)` first in any override.

## Next

Fill in the card class skeleton → [[02 — The card class|Scheme Guide The Card Class]]
