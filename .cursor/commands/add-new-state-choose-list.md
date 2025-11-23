# add-new-state-choose-list

## Overview

Will create a new GameState class and add supporting entries to other files. The purpose of which is to choose a card from the chooseList element.

## Steps

1. Reject command if the name of the new State is not given.
2. Create a GameState class.
3. Update the OnEnteringState file
4. Update the OnLeavingState file
5. Update the OnUpdateActionButtons file
6. Update the States file

## Steps to create a GameState class

1. Use the pattern of the entire class from `modules\php\States\State_highDramaPhase01106_2.php`.
2. Place the file in the `modules\php\States` folder
3. Name the file `State_*` and append the name provided in the parameters.
4. In the `name` parameter of the constructor use the name provided in the parameters.
5. In the `descriptionMyTurn` parameter update the second clientTranslate parameter to `: ${you} must choose a location `
6. In the `id` parameter of the constructor use the pattern from State_highDramaPhase01097.php but use the correct name from the command parameters.

## Steps to update the OnEnteringState file
1. Update the file `modules\js\OnEnteringState.7s5s.js`.
2. Create a new entry in the file using the name provided in the parameters
3. Copy the exact pattern used for the entry `highDramaPhase01106_2`.
4. Place the new entry in the file in alphabetical order in the area where similar named entries are located

## Steps to update the OnLeavingState file
1. Update the file `modules\js\OnLeavingState.7s5s.js`.
2. Create a new entry in the file using the name provided in the parameters
3. Copy the exact pattern used for the entry `highDramaPhhighDramaPhase01106_2ase01093`.
4. Place the new entry in the file in alphabetical order in the area where similar named entries are located.

## Steps to update the OnUpdateActionButtons file
1. Update the file `modules\js\OnUpdateActionButtons.7s5s.jss`.
2. Create a new entry in the file using the name provided in the parameters
3. Copy the exact pattern used for the entry `highDramaPhase01106_2`.
4. Place the new entry in the file in alphabetical order in the area where similar named entries are located.

## Steps to update the States file
1. Update the file `modules\php\States.php`
2. Add a new state entry with the id of the new State.
3. Place the entry in the appropriate location of the file.