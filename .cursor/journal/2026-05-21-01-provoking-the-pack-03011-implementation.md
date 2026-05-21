# Provoking the Pack (_03011) — Implementation

Risk card with two distinct abilities:

- **City Action:** If your performer is opposed • Move your adjacent **Thug** or **Bodyguard** to this location.
- **Gambling Maneuver:** If you control a **Thug** or **Bodyguard** at this location • +1[Riposte].

Stats: 1/1/1. Wealth 0. Traits: Flourish, Camaraderie, Gang. Vodacce faction. All traits already in `TraitNames::$TraitsJson`.

## Files touched

- `modules/php/cards/faf/_03011.php` — wired `IHasActions, IHasManeuvers, IRiskThatTargetsCharacters`, ActionTrait + ManeuverTrait, Actions/Maneuvers arrays.
- `modules/php/cards/faf/actions/Action_03011.php` — new (RiskCityAction + IAbilityThatTargetsCharacters).
- `modules/php/cards/faf/maneuvers/Maneuver_03011.php` — new.
- `modules/php/States/faf/State_highDramaPhase03011.php` — new GameState class.
- `modules/php/States.php` — `HIGH_DRAMA_PLAYER_TURN_03011 = 403011`.
- `states.inc.php` — `"03011" => States::HIGH_DRAMA_PLAYER_TURN_03011` transition.
- `modules/js/OnEnteringState.faf.js`, `OnUpdateActionButtons.faf.js`, `OnLeavingState.faf.js` — `highDramaPhase03011` handlers.

## City Action — Action_03011

Modeled closely on `Action_01115` (Taunt), since both are RiskCityActions that move a single character via the chooser. Differences:

- Target is **friendly** (controlled by performer's player) rather than enemy — `isValidTargetForAbility` checks `ControllerId == performer->ControllerId`.
- Performer must be **opposed** (an opposing character at the performer's location). Implemented via `getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId) > 0`. Memory note: "opposing" requires both same-location AND different controller — `getOpposingCharactersAtLocation` already filters on `isNotControlledByPlayer` which excludes uncontrolled, so this is correct.
- Target must have **Thug** or **Bodyguard** trait, AND be at a city location adjacent to the performer (`includeHome = true` — a friendly Thug at the player's home is "adjacent" to a city character at the home-adjacent city location and qualifies for the move).

State wiring uses a new GameState class `State_highDramaPhase03011` with named transition `"targetChosen"` (the GameState class format that's the current convention; `""` only works for legacy array-format state defs in `states.7s5s.php`).

**WHY `IRiskThatTargetsCharacters`:** the City Action presents the player a friendly character chooser (via `IAbilityThatTargetsCharacters` on the Action). The skill checklist says to mark `IRiskThatTargetsCharacters` on the Risk class whenever any of its abilities targets a character, even friendly ones, so that any "before-being-targeted" framework hooks consult this card. Matches `_01115` (Taunt) which also targets a character (enemy in that case) and marks the interface.

## Gambling Maneuver — Maneuver_03011

Modeled on `Maneuver_03008` for the Gambling gate (`Game::DUEL_GAMBLED`), simpler since this maneuver only adds Riposte (no draw, no transition, no comparison).

Gates:
1. `parent::isAvailableToPlayer` (Used flag etc).
2. `Game::DUEL_GAMBLED` is true.
3. `getDuelRoundActor()` exists and the player controls a Thug or Bodyguard at the actor's location.

Resolution: `EventDuelCalculateManeuverValues` → `$event->riposte += 1` plus explanation. No `EventResolveManeuver` handler — there are no one-shot side effects.

`// EventManeuverCanceled handler not needed` comment in place (no state to undo).

**WHY scan the actor's location rather than passing in a separate target location:** the duel always takes place at the actor's location (which equals the adversary's location). There's no separate "duel location" global — `$actor->Location` is canonical. Matches the convention used elsewhere in maneuvers that reference "this location."

**WHY use `getCharactersAtLocation` + manual filter instead of a dedicated helper:** there isn't a `getCharactersAtLocationByPlayerIdWithTrait` helper, and the manual loop is short and clear. The character must be controlled (no helper needed beyond `ControllerId == playerId` since that excludes uncontrolled `ControllerId == 0`).

## Things considered and ruled out

- **Filter performers to those with a specific trait.** The card text just says "your performer" — no trait gate. Anyone of yours in the city who is opposed AND has an adjacent friendly Thug/Bodyguard qualifies.
- **`canChallenge()` filter on performer.** No challenge is issued — this is just a movement effect. Engaged or pressure-locked performers should still be able to call in the gang. Skipped the filter.
- **`includeHome = false` on `getAdjacentCityLocations`.** Friendly Thugs/Bodyguards sitting at your own home should be eligible to be drawn into the city, mirroring `_01115`'s `$includeHome = true`. Used `true`.
- **Engaged target restriction.** The card text doesn't restrict the target by engaged state. Engaged characters move all the time via card effects (e.g., Taunt moves enemies regardless). Skipped any engage check on the target.
- **`Game::CHALLENGE_TYPE` / `CHALLENGE_STAT` setup.** This is not a challenge — no globals to set.
- **Performer-Gambling stack on the Maneuver — checking the actor's controller equals `playerId`.** The maneuver runs in the actor's context (it's the actor's combat card being used), so the `playerId` passed to `isAvailableToPlayer` should equal the actor's controller. Followed the simpler "is there a Thug/Bodyguard I control at the actor's location" check; if the framework ever calls this for a non-actor player, the gate just returns false because they won't have characters there in practice.

## Pre-commit hook compliance

- `Action_03011` extends RiskCityAction → calls `createActionResolvedEvent` ✓.
- `Maneuver_03011` extends Maneuver → `EventManeuverCanceled handler not needed` comment ✓.
- No `ISorcererAbility`, no `IAbilityThatTargetsCards` on the same class as `IAbilityThatTargetsCharacters`.
- Hook ran clean against the full staged file set.

All PHP files lint clean.

## Open questions / risks

- **Performer being targeted by another card while in the chooser sub-state.** Standard risk for any character-chooser pattern; the framework's targeted/redirected-targeting machinery should handle it.
- **Adjacent home eligibility.** The skill says "performer in city" but the home-adjacent semantics for "your adjacent Thug" might be debatable. I read the intent as "any of the player's Thugs/Bodyguards within one move of the performer", which includes home. If QA decides home doesn't count, drop `$includeHome` to `false`.
