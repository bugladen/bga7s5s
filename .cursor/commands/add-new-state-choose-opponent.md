# add-new-state-choose-opponent

## Overview

Will create a new GameState class and add supporting entries to other files. The purpose of which is to enable the ability to choose an opponent.

## Steps

1. Reject command if the name of the new State is not given.
2. Create a GameState class.
3. Update the OnUpdateActionButtons file
4. Update the States file

## Steps to create a GameState class

1. Use the pattern of the entire class from `modules\php\States\State_highDramaPhase01111_2.php`.
2. Place the file in the `modules\php\States` folder
3. Name the file `State_*` and append the name provided in the parameters.
4. In the `name` parameter of the constructor use the name provided in the parameters.
5. In the `descriptionMyTurn` parameter of the constructor there are two calls to clientTranslate().  Update the parameter in the second clientTranslate() call to `: ${you} must choose an opponent `
6. In the `id` parameter of the constructor use the pattern from State_highDramaPhase01097.php but use the correct name from the command parameters.

## Steps to update the OnUpdateActionButtons file
1. Update the file `modules\js\OnUpdateActionButtons.7s5s.jss`.
2. Create a new entry in the file using the name provided in the parameters
3. Copy the exact pattern used for the entry `highDramaPhase01111_2`.
4. Place the new entry in the file in alphabetical order in the area where similar named entries are located.

## Steps to update the States file
1. Update the file `modules\php\States.php`
2. Add a new state entry with the id of the new State.
3. Place the entry in the appropriate location of the file.