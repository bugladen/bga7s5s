# bga7s5s
Board Game Arena adaptation of 7th Sea: City of Five Sails

## Prerequisites
Knowledge of the BGA Framework is imperative. I recommend you complete the tutorials described in BGA Studio and learn the fundamentals. Once you have completed the Reversi and Hearts tutorials you will have a firm grasp of the framework and will have a great chance of understanding the code is this project.

PHP is used for backend development.

JavaScript is used for the front end. jQuery is rolled into the BGA Framework, so is available for you to use.

## Environment Setup
- VS Code
- SFTP Plugin
- Elephense Plugin (recommended)

Create your own game in BGA Studio. I recommend something like 7s5s_[username].

This creates your own sandbox for the game. You can run your own changes without restrictions.

git checkout the project into your local SFTP directory structure.

SFTP Upload the entire project directory up into the BGA server.

## State of the Project 

- The core game mechanics are complete.
- All core actions are implemented.
- The game handles duels to completion.
- The game can declare a winner at the end of the fifth day using all win conditions described in the Core Rulebook.
- The game can declare a winner if, in a two-player game, a Leader is sent to the Locker.

## Sets Implemented 
- Core (In Progress)

## Going Forward 
Since The core framework for The game is complete, only cards will need to be implemented. The wiki in this project will have more details on how to implement specific cards.

The BGA Framework is completely state-based. Therefore every single step of every card needs to be programmed in order to be integrated into the game 

The upside to this is that the game is completely automated.

The downside to this is that development will progress very slowly unless more developers contribute. That is where you come in!

