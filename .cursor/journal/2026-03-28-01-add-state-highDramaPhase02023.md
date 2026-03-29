# Added States for Sir Jack Harding (02023)

Card is "Sir Jack Harding" — City Action: Move target opposing non-Leader to an adjacent City location with less Renown. Technique: If adversary is Thug or Mercenary, +1 thrust.

## highDramaPhase02023 (choose-in-play-card)

Ran `add-new-state-choose-in-play-card` command.

1. **GameState class**: `modules/php/States/tac/State_highDramaPhase02023.php` — based on 01097 pattern, `actBack` + `actFromCardWithId`, transitions back/characterChosen
2. **OnEnteringState.tac.js**: Highlights performer, makes target cards selectable
3. **OnLeavingState.tac.js**: Unhighlights performer/cards, clears clientStateArgs
4. **OnUpdateActionButtons.tac.js**: Back + disabled Confirm with `onChooseInPlayCardConfirmed`
5. **States.php**: `HIGH_DRAMA_PLAYER_TURN_02023 = 402023`

## highDramaPhase02023_2 (choose-location)

Ran `add-new-state-choose-location` command. This is the second step — after choosing the target character, the player chooses a location to move them to.

1. **GameState class**: `modules/php/States/tac/State_highDramaPhase02023_2.php` — based on 01093 pattern, `actBack` + `actFromCardWithLocations`, transitions back/locationChosen
2. **OnEnteringState.tac.js**: Makes city locations selectable (supports home endcap), highlights performer via cardProperties/image
3. **OnLeavingState.tac.js**: Resets city locations, removes chosen class from performer image
4. **OnUpdateActionButtons.tac.js**: Back + disabled Confirm Location with `onCityLocationsSelected`
5. **States.php**: `HIGH_DRAMA_PLAYER_TURN_02023_2 = 4020232`
