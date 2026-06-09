# Reaction_NameGate

New game reaction mirroring `Reaction_CrewCapLimit`. Triggers when a player brings a character into play (Approach or Recruit) whose Name matches an existing in-play character with the same controller. Player picks which of the conflicting characters to destroy.

## Decisions / WHY

- **Events listened to:** `EventApproachCharacterPlayed`, `EventCharacterRecruited`. Skipped `EventCharacterMustered` because muster moves a character from home into the city — it doesn't bring a *new* character into play, so it can't create a fresh name conflict. Skipped `EventCharacterLostBrute` for the same reason (and Brute status is name-irrelevant).
- **Conflict detection:** group `getCharactersInPlayByPlayerId($playerId)` by `Name`; any group with size ≥ 2 yields conflict candidates. Doing it via "in play" filter (which includes city + home, and thus Leaders) means a Leader-vs-Approach collision works naturally without special-casing.
- **Button labeling:** `Leader %s` when `instanceof Leader`, `City %s` when `instanceof CityCharacter`, plain `Name` otherwise. Checked `Leader` first because `Leader extends Character` (not CityCharacter), so the order doesn't actually matter — but ordering Leader → CityCharacter → fallback reads more clearly than the reverse.
- **UserException:** used `\Bga\GameFramework\UserException` rather than the deprecated `\BgaUserException` that the CrewCapLimit class still uses (per memory `feedback_deprecated_BgaUserException`). Did not retrofit CrewCapLimit — out of scope.
- **`getNameConflicts` is also called from `getReactionButtonProperties`** after the reaction transition fires. We re-derive conflicts from current state rather than persisting them on the event, matching how CrewCapLimit re-derives via `getCharactersInPlayByPlayerId` at button-build time. If state mutates between trigger and button display, the buttons reflect current reality.

## Files

- `modules/php/theah/reactions/Reaction_NameGate.php` — new class extending `GameReaction`.
- `modules/php/theah/Theah.php` — added import and registered `new Reaction_NameGate()` in `$this->Reactions`.

## Leader buttons are shown but disabled

User asked for the Leader's button to render (so the player can see *why* the reaction fired) but be unclickable.

- PHP: `getReactionButtonProperties` sets `$button['disabled'] = true` on Leader entries. `performReaction` also throws a `UserException` if a Leader id arrives — defense-in-depth in case a client bypasses the disabled class.
- JS: `OnUpdateActionButtons.js` `playerReaction` branch now checks `button.disabled` after `addActionButton` and applies `dojo.addClass(buttonId, 'disabled')`. This piggybacks on the standard BGA `disabled` class that other states in this codebase already use to grey out and block buttons (see `actPass` / `actChooseCardSelected` usages).
- WHY this shape (not omitting the button entirely): the rule fires from a Leader-vs-Approach name collision — without the Leader entry visible, the player sees a single "Destroy {Name}" button and has no way to understand *why* their newly-played character is being threatened.

## Open questions / potential follow-ups

- **Multiple conflicting copies (≥ 3 of a name):** Each invocation destroys one. There's no re-trigger after `EventCharacterDestroyed`, so destroying one of three same-named characters leaves two still in conflict. CrewCapLimit has the same shape (it doesn't re-fire either), so I followed that pattern. If the design wants exhaustive resolution, the trigger set would need to include `EventCharacterDestroyed` (with the conflict check still finding a match) — but that's a separate decision.
- **Non-character cards sharing a Name:** the user's phrasing said "same name as an existing card in play" but then "buttons for both characters" — I interpreted as character-vs-character only. If attachments/schemes ever need to participate, the conflict-finder would need broadening.
