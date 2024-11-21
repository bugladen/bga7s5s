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

    States::GAME_SETUP => array(
        "name" => "gameSetup",
        "description" => "",
        "type" => "manager",
        "action" => "stGameSetup",
        "transitions" => ["" => States::PICK_DECKS]
    ),

    States::PICK_DECKS => [
        "name" => "pickDecks",
        "description" => clienttranslate('Your opponent must pick a deck to play with.'),
        "descriptionmyturn" => clienttranslate('${you} must pick your deck to play with:'),
        "type" => "multipleactiveplayer",
        "args" => "argAvailableDecks",
        "action" => "stMultiPlayerInit",
        "possibleactions" => [
            "actPickDeck", 
        ],
        "transitions" => ["deckPicked" => States::BUILD_TABLE]
    ],

    States::BUILD_TABLE => [
        "name" => "buildTable",
        "description" => clienttranslate("Creating the City..."),
        "type" => "game",
        "action" => "stBuildDecks",
        "transitions" => ["" => States::DAWN_NEW_DAY]
    ],

    States::DAWN_NEW_DAY => [
        "name" => "dawnNewDay",
        "description" => clienttranslate("Preparing the City for the next full Day..."),
        "type" => "game",
        "action" => "stDawnNewDay",
        "transitions" => ["" => States::DAWN_NEW_DAY_EVENTS]
    ],
        States::DAWN_NEW_DAY_EVENTS => [
            "name" => "dawnNewDayEvents",
            "description" => clienttranslate("Resolving Events for the New Day..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => ["endOfEvents" => States::DAWN_BEGINNING]
        ],

    States::DAWN_BEGINNING => [
        "name" => "dawnBeginning",
        "description" => clienttranslate("The Beginning of Dawn..."),
        "type" => "game",
        "action" => "stDawnBeginning",
        "transitions" => ["" => States::DAWN_BEGINNING_EVENTS]
    ],
        States::DAWN_BEGINNING_EVENTS => [
            "name" => "dawnBeginningEvents",
            "description" => clienttranslate("Resolving Events for the Beginning of the Dawn..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => ["endOfEvents" => States::DAWN_CITY_CARDS]
        ],

    States::DAWN_CITY_CARDS => [
        "name" => "dawnCityCards",
        "description" => clienttranslate("Adding City Cards to the City Locations..."),
        "type" => "game",
        "action" => "stDawnCityCards",
        "transitions" => ["" => States::DAWN_CITY_CARDS_EVENTS]
    ],
        States::DAWN_CITY_CARDS_EVENTS => [
            "name" => "dawnCityCardsEvents",
            "description" => clienttranslate("Resolving Events for the City Cards..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "endOfEvents" => States::DAWN_ENDING]
        ],

    States::DAWN_ENDING => [
        "name" => "dawnEnding",
        "description" => clienttranslate("Ending the Dawn Phase..."),
        "type" => "game",
        "action" => "stDawnEnding",
        "transitions" => ["" => States::DAWN_ENDING_EVENTS]
    ],
        States::DAWN_ENDING_EVENTS => [
            "name" => "dawnEndingEvents",
            "description" => clienttranslate("Resolving Events for the End of the Dawn..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => ["endOfEvents" => States::PLANNING_PHASE_BEGINNING]
        ],

    States::PLANNING_PHASE_BEGINNING => [
        "name" => "planningPhaseBeginning",
        "description" => clienttranslate("Beginning the Planning Phase..."),
        "type" => "game",
        "action" => "stPlanningPhaseBeginning",
        "transitions" => ["" => States::PLANNING_PHASE_BEGINNING_EVENTS]
    ],
        States::PLANNING_PHASE_BEGINNING_EVENTS => [
            "name" => "planningPhaseBeginningEvents",
            "description" => clienttranslate("Resolving Events for the Beginning of the Planning Phase..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => ["endOfEvents" => States::PLANNING_PHASE]
        ],

    States::PLANNING_PHASE => [
        "name" => "planningPhase",
        "description" => clienttranslate('Your opponent must choose their Scheme Character to muster for the day.'),
        "descriptionmyturn" => clienttranslate('${you} must choose ONE Scheme and ONE Character from your Approach Deck to muster for the day.'),
        "type" => "multipleactiveplayer",
        "args" => "argEmpty",
        "action" => "stPlanningPhase",
        "possibleactions" => [
            "actDayPlanned", 
        ],
        "transitions" => ["dayPlanned" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED]
    ],

    States::PLANNING_PHASE_APPROACH_CARDS_PLAYED => [
        "name" => "planningPhaseApproachCardsPlayed",
        "description" => clienttranslate("Approach Cards Played"),
        "type" => "game",
        "action" => "stPlanningPhaseApproachCardsPlayed",
        "transitions" => ["" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_EVENTS]
    ],
        States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_EVENTS => [
            "name" => "planningPhaseApproachCardsPlayedEvents",
            "description" => clienttranslate("Resolving Events for Approach Cards Played..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => ["endOfEvents" => States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER]
        ],

    States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER => [
        "name" => "planningPhaseDetermineFirstPlayer",
        "description" => clienttranslate("Determining the First Player"),
        "type" => "game",
        "action" => "stPlanningPhaseDetermineFirstPlayer",
        "transitions" => ["" => States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_EVENTS]
    ],
        States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_EVENTS => [
            "name" => "planningPhaseDetermineFirstPlayerEvents",
            "description" => clienttranslate("Resolving Events for Determining the First Player..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => ["endOfEvents" => States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS]
        ],

    States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS => [
        "name" => "planningPhaseResolveWhenRevealedCards",
        "description" => clienttranslate("Resolving When Revealed Cards"),
        "type" => "game",
        "action" => "stPlanningPhaseResolveWhenRevealedCards",
        "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_EVENTS]
    ],
        States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_EVENTS => [
            "name" => "planningPhaseResolveWhenRevealedCardsEvents",
            "description" => clienttranslate("Resolving Events for When Revealed Cards..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => ["endOfEvents" => States::PLANNING_PHASE_MUSTER]
        ],

    States::PLANNING_PHASE_MUSTER 
    => [
        "name" => "planningPhaseMuster",
        "description" => clienttranslate('Characters Mustered.'),
        "type" => "game",
        "action" => "stplanningPhaseMuster",
        "transitions" => ["" => States::PLANNING_PHASE_MUSTER_EVENTS]
    ],
        States::PLANNING_PHASE_MUSTER_EVENTS => [
            "name" => "planningPhaseMusterEvents",
            "description" => '',
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => ["endOfEvents" => States::PLANNING_PHASE_RESOLVE_SCHEMES]
        ],

    States::PLANNING_PHASE_RESOLVE_SCHEMES => [
        "name" => "planningPhaseResolveSchemes",
        "description" => '',
        "type" => "game",
        "action" => "stPlanningPhaseResolveSchemes",
        "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
    ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS => [
            "name" => "planningPhaseResolveSchemesEvents",
            "description" => clienttranslate("Resolving events for the Played Schemes..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "pickTwoLocations" => States::PLANNING_PHASE_RESOLVE_SCHEMES_PICK_TWO_LOCATIONS,
                "endOfEvents" => States::PLANNING_PHASE_END
            ]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_PICK_TWO_LOCATIONS => [
            "name" => "planningPhaseResolveSchemesPickTwoLocations",
            "description" => clienttranslate('${actplayer} must choose two city locations to place Reknown onto.'),
            "descriptionmyturn" => clienttranslate('${you} must choose two city locations to place Reknown onto.'),
            "type" => "activeplayer",
            "args" => "argEmpty",
            "possibleactions" => [
                "actCityLocationsForReknownSelected", 
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],

    States::PLANNING_PHASE_END => [
        "name" => "planningPhaseEnd",
        "description" => clienttranslate("End of the Planning Phase..."),
        "type" => "game",
        "action" => "stPlanningPhaseEnd",
        "transitions" => ["" => States::PLANNING_PHASE_END_EVENTS]
    ],
        States::PLANNING_PHASE_END_EVENTS => [
            "name" => "planningPhaseEndEvents",
            "description" => clienttranslate("Resolving Events for the End of the Planning Phase..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => ["endOfEvents" => States::HIGH_DRAMA_BEGINNING]
        ],

    States::HIGH_DRAMA_BEGINNING => [
        "name" => "highDramaBeginning",
        "description" => clienttranslate("Beginning of High Drama..."),
        "type" => "game",
        "action" => "stHighDramaBeginning",
        "transitions" => ["" => States::HIGH_DRAMA_BEGINNING_EVENTS]
    ],
        States::HIGH_DRAMA_BEGINNING_EVENTS => [
            "name" => "highDramaBeginningEvents",
            "description" => clienttranslate("Resolving Events for the Beginning of High Drama..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => ["endOfEvents" => States::HIGH_DRAMA_PHASE]
        ],

    States::HIGH_DRAMA_PHASE => [
        "name" => "highDramaPhase",
        "description" => clienttranslate("High Drama Phase"),
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



