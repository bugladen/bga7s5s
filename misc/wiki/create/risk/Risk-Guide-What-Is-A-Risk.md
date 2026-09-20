# 01 — What is a Risk?

← [[Index|Implementing a Risk Card]] · Next: [[02 — The card class|Risk Guide The Card Class]]

## Risk vs Character vs Scheme vs Attachment

A **Risk** is a combat card from a player's **faction deck**. Players draw Risks into hand. From hand they can:

- Play **Action** / **City Action** abilities (pay Wealth, resolve effect)
- Play **Reaction** / **City Reaction** abilities (pay Wealth when a trigger fires)
- Play the card as a **combat card** during a duel — its Riposte / Parry / Thrust apply, and any printed **Maneuver** may activate

| Class | Lives in | When it matters |
|---|---|---|
| **`Risk`** | Player's faction deck → hand | Actions/Reactions from hand; combat card + Maneuver in duels |
| `Character` / `Leader` | Faction deck / setup | People you move and fight with |
| `Scheme` | Scheme deck | Chosen each day; Planning resolve |
| `FactionAttachment` | Faction deck → hand → equipped | Gear on a character |
| City cards | City deck | Mustered / equipped from the city |

**If your stub says `extends Risk`, you are in the right place.**

## What makes a Risk different

Risks add combat-card fields beyond a normal `Card`:

- **`$WealthCost`** — paid when playing from hand as Action / Reaction, and when paying to use the card as a combat-card Maneuver
- **`$Riposte` / `$Parry` / `$Thrust`** — duel combat-card stats
- **`$DashedRiposte` / `$DashedParry` / `$DashedThrust`** — when the print shows a dashed value (framework treats that stat specially)

They implement `IFactionCard` + `IWealthCost` via the `Risk` base class. You still must call `initializeFaction(...)`.

## Risk location lifecycle (read this first)

1. Risk sits in the player's **faction deck**, then is drawn into **hand**.
2. Playing an Action / Reaction from hand usually **discards** the Risk as the cost (after pay).
3. Playing it as a combat card moves it into the **dueling line** for that duel.
4. After the duel / day cleanup, discarded Risks end up in the player's discard (Locker rules vary by effect — follow the mirror card).

Implications beginners miss:

- **RiskReactions require the card to still be in hand** when the trigger fires. Pre-commit hooks enforce a hand-location guard.
- After you *pay* for a Reaction, the card is often already in discard — later stages of a multi-step Reaction must **not** re-check "still in hand."
- Maneuvers run while the Risk is the combat card in the dueling line — not from hand.

## What "City" means on ability text

Printed **City Action** / **City Reaction** does **not** change the card into a city-deck card.

| Printed heading | Base class | Extra meaning |
|---|---|---|
| **City Action:** | `RiskCityAction` | Performer must be in the city (base class helps) |
| **Action:** (no City) | `RiskAction` | Performers can be at **Home or city** — do not filter to city-only |
| **City Reaction:** | still `RiskReaction` | Offer only if you have a character in the city; Risk stays in hand until paid |
| **Reaction:** | `RiskReaction` | From hand; no city gate unless printed |

## Where files live

Card id `03008` in expansion `faf` (Fate & Faith):

```
modules/php/cards/faf/
  _03008.php                 ← Risk card class
  actions/
    Action_03008.php         ← City Action / Action
  maneuvers/
    Maneuver_03008.php       ← Maneuver
  reactions/                 ← if the card has Reactions
    Reaction_NNNNN.php

modules/php/States/faf/
  State_highDramaPhaseNNNNN.php   ← only if an Action needs a picker
  State_duelResolveManeuver_NNNNN.php  ← only if a Maneuver needs a picker

modules/js/
  OnEnteringState.faf.js
  OnUpdateActionButtons.faf.js
  OnLeavingState.faf.js
```

Arrogant (`_03008`) uses the **shared** challenge target chooser — no custom High Drama GameState for the challenge itself.

Expansion folders you will see:

| Folder | Expansion |
|---|---|
| `_7s5s` | Base game |
| `tac` | Tooth and Claw |
| `faf` | Fate & Faith |
| `bas` | Blood and Steel (and similar newer expansions) |

## How the game runs (very short)

1. The backend is a **state machine** defined in `states.inc.php`.
2. Cards react to **events** through `handleEvent`.
3. **City Actions / Actions** use High Drama states. **Maneuvers** use duel resolve / calc events. **Reactions** use the reaction / pay pipeline.
4. Multi-step choices use custom **states** + a little **JavaScript** to highlight cards/locations and enable Confirm.

You almost never write UI from scratch. You copy an existing state's JS hooks and change the state name.

## Base Risk behavior (do not reimplement)

`modules/php/cards/Risk.php` already owns faction + wealth + combat-card fields.

Always call `parent::handleEvent($event)` first in any override.

## Next

Fill in the card class skeleton → [[02 — The card class|Risk Guide The Card Class]]
