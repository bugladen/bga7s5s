<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * states.inc.php
 *
 * SeventhSeaCityOfFiveSails game states description
 *
 */

/*
   Game state machine is a tool used to facilitate game developpement by doing common stuff that can be set up
   in a very easy way from this configuration file.

   Please check the BGA Studio presentation about game state to understand this, and associated documentation.

   Summary:

   States types:
   _ activeplayer: in this type of state, we expect some action from the active player.
   _ multipleactiveplayer: in this type of state, we expect some action from multiple players (the active players)
   _ game: this is an intermediary state where we don't expect any actions from players. Your game logic must decide what is the next game state.
   _ manager: special type for initial and final state

   Arguments of game states:
   _ name: the name of the GameState, in order you can recognize it on your own code.
   _ description: the description of the current game state is always displayed in the action status bar on
                  the top of the game. Most of the time this is useless for game state with "game" type.
   _ descriptionmyturn: the description of the current game state when it's your turn.
   _ type: defines the type of game states (activeplayer / multipleactiveplayer / game / manager)
   _ action: name of the method to call when this game state become the current game state. Usually, the
             action method is prefixed by "st" (ex: "stMyGameStateName").
   _ possibleactions: array that specify possible player actions on this step. It allows you to use "checkAction"
                      method on both client side (Javacript: this.checkAction) and server side (PHP: $this->checkAction).
   _ transitions: the transitions are the possible paths to go from a game state to another. You must name
                  transitions in order to use transition names in "nextState" PHP method, and use IDs to
                  specify the next game state for each transition.
   _ args: name of the method to call to retrieve arguments for this gamestate. Arguments are sent to the
           client side to be used on "onEnteringState" or to set arguments in the gamestate description.
   _ updateGameProgression: when specified, the game progression is updated (=> call to your getGameProgression
                            method).
*/

//    !! It is not a good idea to modify this file when a game is running !!

$machinestates = [

    // The initial state. Please do not modify.

    States::GAME_SETUP => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => States::PICK_DECKS]
    ),

    // Note: ID=2 => your first state

    States::PICK_DECKS => [
        "name" => "pickDecks",
        "description" => clienttranslate('Your opponent must pick a deck to play with.'),
        "descriptionmyturn" => clienttranslate('${you} must pick your deck to play with:'),
        "type" => "multipleactiveplayer",
        "args" => "argAvailableDecks",
        "action" => "stMultiPlayerInit",
        "possibleactions" => [
            // these actions are called from the front with bgaPerformAction, and matched to the function on the game.php file
            "actPickDeck", 
        ],
        "transitions" => ["deckPicked" => States::BUILD_TABLE]
    ],

    States::BUILD_TABLE => [
        "name" => "buildTable",
        "description" => "Creating the City...",
        "type" => "game",
        "action" => "stBuildDecks",
        "transitions" => ["" => States::DAWN_NEW_DAY]
    ],

    States::DAWN_NEW_DAY => [
        "name" => "dawnNewDay",
        "description" => "Preparing the City for the next full Day...",
        "type" => "game",
        "action" => "stDawnNewDay",
        "transitions" => ["" => States::PLANNING_PHASE]            
    ],

    States::PLANNING_PHASE => [
        "name" => "planningPhase",
        "description" => clienttranslate('Your opponent must choose their Scheme Character to muster for the day.'),
        "descriptionmyturn" => clienttranslate('${you} must choose ONE Scheme and ONE Character from your Approach Deck to muster for the day.'),
        "type" => "multipleactiveplayer",
        "args" => "argsEmpty",
        "action" => "stPlanningPhase",
        "possibleactions" => [
            "actDayPlanned", 
        ],
        "transitions" => ["dayPlanned" => States::HIGH_DRAMA_PHASE]
    ],

    States::HIGH_DRAMA_PHASE => [
        "name" => "highDramaPhase",
        "description" => "High Drama Phase",
        "type" => "game",
        "action" => "stHighDramaPhase",
        "transitions" => ["" => States::PLAYER_TURN]
    ],

    States::PLAYER_TURN => [
        "name" => "playerTurn",
        "description" => clienttranslate('${actplayer} must perform an action or pass'),
        "descriptionmyturn" => clienttranslate('${you} must perform an action or pass'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "possibleactions" => [
            "actPlayCard", 
            "actPass",
        ],
        "transitions" => [
            "playCard" => States::NEXT_PLAYER, 
            "pass" => States::NEXT_PLAYER
        ]
    ],

    States::NEXT_PLAYER => [
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => ["endGame" => States::END_GAME, "nextPlayer" => States::PLAYER_TURN]
    ],

    // Final state.
    // Please do not modify (and do not overload action/args methods).
    States::END_GAME => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "args" => "argGameEnd"
    ],

];



