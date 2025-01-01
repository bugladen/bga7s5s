<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
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
        "description" => clienttranslate('Your opponent(s) must pick a deck to play with.'),
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
        "description" => clienttranslate('Your opponent(s) must choose their Scheme Character to muster for the day.'),
        "descriptionmyturn" => clienttranslate('${you} must choose ONE Scheme and ONE Character from your Approach Deck to muster for the day: '),
        "type" => "multipleactiveplayer",
        "args" => "argsEmpty",
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
                "01072" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01072,
                "pickOneLocationForReknown" => States::PLANNING_PHASE_RESOLVE_SCHEMES_PICK_ONE_LOCATION_FOR_REKNOWN,
                "01098" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01098,
                "01125" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_1,
                "01126" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01126,
                "01044" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01044,
                "01045" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01045,
                "01144" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_1,
                "01145" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01145,
                "01150" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01150,
                "endOfEvents" => States::PLANNING_PHASE_DRAW
            ]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_1 => [
            "name" => "planningPhaseResolveSchemes_01125_1",
            "description" => clienttranslate('The Boar\'s Guile: ${actplayer} may choose a City Location to place a Reknown onto.'),
            "descriptionmyturn" => clienttranslate('The Boar\'s Guile: ${you} may choose a City Location to place a Reknown onto: '),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01125_1",
                "actPlanningPhase_01125_1_Pass"
            ],
            "transitions" => [
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_2,
                "reknownPlaced" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_4
                ]
        ],
            States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_2 => [
                "name" => "planningPhaseResolveSchemes_01125_2",
                "description" => clienttranslate('The Boar\'s Guile: ${actplayer} must choose a City Location to move a Reknown FROM, if able.'),
                "descriptionmyturn" => clienttranslate('The Boar\'s Guile: ${you} must choose a City Location to move a Reknown FROM, if able: '),
                "type" => "activeplayer",
                "args" => "argsEmpty",
                "possibleactions" => [
                    "actPlanningPhase_01125_2",
                    "actPlanningPhase_01125_2_Pass"
                ],
                "transitions" => [
                    "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_4,
                    "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_3
                    ]
                ],
                States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_3 => [
                    "name" => "planningPhaseResolveSchemes_01125_3",
                    "description" => clienttranslate('The Boar\'s Guile: ${actplayer} must choose an adjacent City Location to move the Reknown TO.'),
                    "descriptionmyturn" => clienttranslate('The Boar\'s Guile: ${you} must choose an adjacent City Location to move the Reknown TO:'),
                    "type" => "activeplayer",
                    "args" => "argsPlanningPhaseResolveSchemes_01125_3",
                    "possibleactions" => ["actPlanningPhase_01125_3"],
                    "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_4]
                ],
                States::PLANNING_PHASE_RESOLVE_SCHEMES_01125_4 => [
                    "name" => "planningPhaseResolveSchemes_01125_4",
                    "description" => clienttranslate('The Boar\'s Guile: ${actplayer} must choose an enemy Character.'),
                    "descriptionmyturn" => clienttranslate('The Boar\'s Guile: ${you} must choose an enemy Character:'),
                    "type" => "activeplayer",
                    "args" => "argsEmpty",
                    "possibleactions" => [
                        "actPlanningPhase_01125_4",
                        "actPlanningPhase_01125_4_Pass"
                    ],
                    "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
                ],

        States::PLANNING_PHASE_RESOLVE_SCHEMES_01126 => [
            "name" => "planningPhaseResolveSchemes_01126",
            "description" => clienttranslate('Leshiye of the Woods: ${actplayer} must choose options for Leshiye of the Wood.'),
            "descriptionmyturn" => clienttranslate('Leshiye of the Woods: ${you} must choose an outer City Location for Leshiye of the Wood:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01126_2",
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],

        States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_1 => [
            "name" => "planningPhaseResolveSchemes_01144_1",
            "description" => clienttranslate('${actplayer} must choose a city location to place Reknown onto.'),
            "descriptionmyturn" => clienttranslate('${you} must choose a city location to place Reknown onto:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01144_1", 
            ],
            "transitions" => [
                "fewestReknown" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2,
                "notFewestReknown" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS
            ]
        ],
            States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2 => [
                "name" => "planningPhaseResolveSchemes_01144_2",
                "description" => clienttranslate('${actplayer} must choose a different city location to place Reknown onto.'),
                "descriptionmyturn" => clienttranslate('${you} must choose a different city location to place Reknown onto:'),
                "type" => "activeplayer",
                "args" => "argsPlanningPhaseResolveSchemes_01144_2",
                "possibleactions" => [
                    "actPlanningPhase_01144_2", 
                ],
                "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
            ],

        States::PLANNING_PHASE_RESOLVE_SCHEMES_01145 => [
            "name" => "planningPhaseResolveSchemes_01145",
            "description" => clienttranslate('${actplayer} is choosing options for Inspire Generosity.'),
            "descriptionmyturn" => clienttranslate('${you} must choose a city location to move a Reknown from:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01145",
                "actPlanningPhase_01145_Pass"
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],
    
        States::PLANNING_PHASE_RESOLVE_SCHEMES_01150 => [
            "name" => "planningPhaseResolveSchemes_01150",
            "description" => clienttranslate('Parlay Gone Wrong: ${actplayer} may choose a city location to move a Reknown from.'),
            "descriptionmyturn" => clienttranslate('Parlay Gone Wrong: ${you} may choose a city location to move a Reknown from:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01150", 
                "actPass" 
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],

        States::PLANNING_PHASE_RESOLVE_SCHEMES_01072 => [
            "name" => "planningPhaseResolveSchemes_01072",
            "description" => clienttranslate('Réputation Méritée: ${actplayer} must choose a city location to place Reknown onto that doesn\'t have any.'),
            "descriptionmyturn" => clienttranslate('Réputation Méritée: ${you} must choose a city location to place Reknown onto that doesn\'t have any:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actCityLocationsForReknownSelected", 
                "actPass"
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_PICK_ONE_LOCATION_FOR_REKNOWN => [
            "name" => "planningPhaseResolveSchemes_PickOneLocationForReknown",
            "description" => clienttranslate('${actplayer} must choose a city location to place Reknown onto.'),
            "descriptionmyturn" => clienttranslate('${you} must choose a city location to place Reknown onto:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actCityLocationsForReknownSelected", 
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_01098 => [
            "name" => "planningPhaseResolveSchemes_01098",
            "description" => clienttranslate('The Cat\'s Embargo: ${actplayer} must choose two city locations to place Reknown onto.'),
            "descriptionmyturn" => clienttranslate('The Cat\'s Embargo: ${you} must choose two city locations to place Reknown onto:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actCityLocationsForReknownSelected", 
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_01044 => [
            "name" => "planningPhaseResolveSchemes_01044",
            "description" => clienttranslate('Armed and Marshaled: ${actplayer} must choose an Attachment from your discard pile if able.'),
            "descriptionmyturn" => clienttranslate('Armed and Marshaled: ${you} must choose an Attachment from your discard pile if able:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01044", 
                "actPass"
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_01045 => [
            "name" => "planningPhaseResolveSchemes_01045",
            "description" => clienttranslate('The Song of Eisen: ${actplayer} must choose a Mercenary from the City Deck discard pile if able.'),
            "descriptionmyturn" => clienttranslate('The Song of Eisen: ${you} must choose a Mercenary from the City Deck discard pile if able:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01045", 
                "actPass"
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],
    States::PLANNING_PHASE_DRAW => [
        "name" => "planningPhaseDraw",
        "description" => '',
        "type" => "game",
        "action" => "stPlanningPhaseDraw",
        "transitions" => ["" => States::PLANNING_PHASE_END]
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
            "transitions" => [
                "01098" => States::PLANNING_PHASE_END_01098,
                "endOfEvents" => States::HIGH_DRAMA_BEGINNING
            ]
        ],
        States::PLANNING_PHASE_END_01098 => [
            "name" => "planningPhaseEnd_01098",
            "description" => clienttranslate('The Cat\'s Embargo: ${actplayer} must choose an opponent to reveal a card from hand.'),
            "descriptionmyturn" => clienttranslate('The Cat\'s Embargo: ${you} must choose an opponent to reveal a card from hand:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPass",
                "actPlanningPhaseEnd_01098", 
            ],
            "transitions" => ["" => States::PLANNING_PHASE_END_01098_2]
        ],
        States::PLANNING_PHASE_END_01098_2 => [
            "name" => "planningPhaseEnd_01098_2",
            "description" => clienttranslate('The Cat\'s Embargo: Your opponent(s) must acknowlege revealed card.'),
            "descriptionmyturn" => clienttranslate('The Cat\'s Embargo: ${you} must must acknowlege revealed card:'),
            "type" => "multipleactiveplayer",
            "args" => "argsPlanningPhaseEnd_01098_2",
            "action" => "stMultiPlayerInit",
            "possibleactions" => [
                "actMultipleOk", 
            ],
            "transitions" => ["multipleOk" => States::PLANNING_PHASE_END_EVENTS]
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
            "transitions" => [
                "01144" => States::HIGH_DRAMA_BEGINNING_01144,
                "endOfEvents" => States::HIGH_DRAMA_PHASE
                ]
        ],

        States::HIGH_DRAMA_BEGINNING_01144 => [
            "name" => "highDramaBeginning_01144",
            "description" => clienttranslate('${actplayer} may choose a Mercenary from a City Location to recruit to their home'),
            "descriptionmyturn" => clienttranslate('${you} may choose a Mercenary from a City Location to recruit to your home:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaBeginning_01144",
            "possibleactions" => [
                "actHighDramaBeginning_01144", 
                "actPass"
            ],
            "transitions" => ["" => States::HIGH_DRAMA_BEGINNING_EVENTS]
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
        "descriptionmyturn" => clienttranslate('${you} must perform an action or pass:'),
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



