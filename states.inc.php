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
        "transitions" => ["" => States::DECK_ASSIGNMENT]
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
        "transitions" => ["" => States::SETUP_TABLE]
    ],

    States::SETUP_TABLE => [
        "name" => "setupTable",
        "description" => clienttranslate("Setting up the Table..."),
        "type" => "game",
        "action" => "stSetupTable",
        "transitions" => ["" => States::SETUP_TABLE_EVENTS]
    ],
    States::SETUP_TABLE_EVENTS => [
        "name" => "setupTableEvents",
        "description" => clienttranslate("Resolving Events for the Table Setup..."),
        "type" => "game",
        "action" => "stRunEvents",
        "transitions" => [
            "01006" => States::SETUP_TABLE_01006,
            "reaction" => States::SETUP_TABLE_REACTIONS,
            "endOfEvents" => States::DAWN_NEW_DAY,
            "endOfGame" => States::END_GAME
        ]
    ],
    States::SETUP_TABLE_REACTIONS => [
        "name" => "playerReaction",
        "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
        "descriptionmyturn" => "",
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actReactionForState", 
        ],
        "transitions" => [
            "done" => States::SETUP_TABLE_EVENTS, 
            "pay" => States::SETUP_TABLE_PAY_FOR_REACTION,
        ]
    ],
    States::SETUP_TABLE_PAY_FOR_REACTION => [
        "name" => "playerPayForReaction",
        "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
        "descriptionmyturn" => "",
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actBack",
            "actPayForReaction", 
        ],
        "transitions" => [
            "back" => States::SETUP_TABLE_REACTIONS, 
            "paid" => States::SETUP_TABLE_EVENTS, 
        ]
    ],

    States::DAWN_NEW_DAY => [
        "name" => "dawnNewDay",
        "description" => clienttranslate("Preparing the City for the next full Day..."),
        "type" => "game",
        "action" => "stDawnNewDay",
        "updateGameProgression" => true,
        "transitions" => ["" => States::DAWN_NEW_DAY_EVENTS]
    ],
        States::DAWN_NEW_DAY_EVENTS => [
            "name" => "dawnNewDayEvents",
            "description" => clienttranslate("Resolving Events for the New Day..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::DAWN_NEW_DAY_REACTIONS,
                "endOfEvents" => States::DAWN_BEGINNING,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::DAWN_NEW_DAY_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::DAWN_NEW_DAY_EVENTS, 
                "pay" => States::DAWN_NEW_DAY_PAY_FOR_REACTION,
            ]
        ],
        States::DAWN_NEW_DAY_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::DAWN_NEW_DAY_REACTIONS, 
                "paid" => States::DAWN_NEW_DAY_EVENTS, 
            ]
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
            "transitions" => [
                "reaction" => States::DAWN_BEGINNING_REACTIONS,
                "endOfEvents" => States::DAWN_CITY_CARDS,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::DAWN_BEGINNING_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::DAWN_BEGINNING_EVENTS, 
                "pay" => States::DAWN_BEGINNING_PAY_FOR_REACTION,
            ]
        ],
        States::DAWN_BEGINNING_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::DAWN_BEGINNING_REACTIONS, 
                "paid" => States::DAWN_BEGINNING_EVENTS, 
            ]
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
                "reaction" => States::DAWN_CITY_CARDS_REACTIONS,
                "endOfEvents" => States::DAWN_ENDING,
                "endOfGame" => States::END_GAME]
        ],
        States::DAWN_CITY_CARDS_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::DAWN_CITY_CARDS_EVENTS, 
                "pay" => States::DAWN_CITY_CARDS_PAY_FOR_REACTION,
            ]
        ],
        States::DAWN_CITY_CARDS_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::DAWN_CITY_CARDS_REACTIONS, 
                "paid" => States::DAWN_CITY_CARDS_EVENTS, 
            ]
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
            "transitions" => [
                "reaction" => States::DAWN_ENDING_REACTIONS,
                "endOfEvents" => States::PLANNING_PHASE_BEGINNING,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::DAWN_ENDING_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::DAWN_ENDING_EVENTS, 
                "pay" => States::DAWN_ENDING_PAY_FOR_REACTION,
            ]
        ],
        States::DAWN_ENDING_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::DAWN_ENDING_REACTIONS, 
                "paid" => States::DAWN_ENDING_EVENTS, 
            ]
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
            "transitions" => [
                "reaction" => States::PLANNING_PHASE_BEGINNING_REACTIONS,
                "endOfEvents" => States::PLANNING_PHASE,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::PLANNING_PHASE_BEGINNING_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::PLANNING_PHASE_BEGINNING_EVENTS, 
                "pay" => States::PLANNING_PHASE_BEGINNING_PAY_FOR_REACTION,
            ]
        ],
        States::PLANNING_PHASE_BEGINNING_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::PLANNING_PHASE_BEGINNING_REACTIONS, 
                "paid" => States::PLANNING_PHASE_BEGINNING_EVENTS, 
            ]
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
        "description" => clienttranslate("Approach Cards Played..."),
        "type" => "game",
        "action" => "stPlanningPhaseApproachCardsPlayed",
        "transitions" => ["" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_EVENTS]
    ],
        States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_EVENTS => [
            "name" => "planningPhaseApproachCardsPlayedEvents",
            "description" => clienttranslate("Resolving Events for Approach Cards Played..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_REACTIONS,
                "endOfEvents" => States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_EVENTS, 
                "pay" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_PAY_FOR_REACTION,
            ]
        ],
        States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_REACTIONS, 
                "paid" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_EVENTS, 
            ]
        ],

    States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER => [
        "name" => "planningPhaseDetermineFirstPlayer",
        "description" => clienttranslate("Determining the First Player..."),
        "type" => "game",
        "action" => "stPlanningPhaseDetermineFirstPlayer",
        "transitions" => ["" => States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_EVENTS]
    ],
        States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_EVENTS => [
            "name" => "planningPhaseDetermineFirstPlayerEvents",
            "description" => clienttranslate("Resolving Events for Determining the First Player..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_REACTIONS,
                "endOfEvents" => States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_EVENTS, 
                "pay" => States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_PAY_FOR_REACTION,
            ]
        ],
        States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_REACTIONS, 
                "paid" => States::PLANNING_PHASE_DETERMINE_FIRST_PLAYER_EVENTS, 
            ]
        ],

    States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS => [
        "name" => "planningPhaseResolveWhenRevealedCards",
        "description" => clienttranslate("Resolving When Revealed Cards..."),
        "type" => "game",
        "action" => "stPlanningPhaseResolveWhenRevealedCards",
        "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_EVENTS]
    ],
        States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_EVENTS => [
            "name" => "planningPhaseResolveWhenRevealedCardsEvents",
            "description" => clienttranslate("Resolving Events for When Revealed Cards..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_REACTIONS,
                "endOfEvents" => States::PLANNING_PHASE_MUSTER,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_EVENTS, 
                "pay" => States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_PAY_FOR_REACTION,
            ]
        ],
        States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_REACTIONS, 
                "paid" => States::PLANNING_PHASE_RESOLVE_WHEN_REVEALED_CARDS_EVENTS, 
            ]
        ],

    States::PLANNING_PHASE_MUSTER 
    => [
        "name" => "planningPhaseMuster",
        "description" => clienttranslate('Characters Mustered...'),
        "type" => "game",
        "action" => "stplanningPhaseMuster",
        "transitions" => ["" => States::PLANNING_PHASE_MUSTER_EVENTS]
    ],
        States::PLANNING_PHASE_MUSTER_EVENTS => [
            "name" => "planningPhaseMusterEvents",
            "description" => clienttranslate("Resolving Events for Mustering Cards..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::PLANNING_PHASE_MUSTER_REACTIONS,
                "endOfEvents" => States::PLANNING_PHASE_RESOLVE_SCHEMES,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::PLANNING_PHASE_MUSTER_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::PLANNING_PHASE_MUSTER_EVENTS, 
                "pay" => States::PLANNING_PHASE_MUSTER_PAY_FOR_REACTION,
            ]
        ],
        States::PLANNING_PHASE_MUSTER_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::PLANNING_PHASE_MUSTER_REACTIONS, 
                "paid" => States::PLANNING_PHASE_MUSTER_EVENTS, 
            ]
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
                "01016" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01016,
                "01071" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01071,
                "01072" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01072,
                "01098" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01098,
                "01125" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01125,
                "01126" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01126,
                "01044" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01044,
                "01045" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01045,
                "01143" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01143,
                "01144" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01144,
                "01145" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01145,
                "01147" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01147,
                "01150" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01150,
                "01152" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152,
                "reaction" => States::PLANNING_PHASE_RESOLVE_SCHEMES_REACTIONS,
                "endOfEvents" => States::PLANNING_PHASE_DRAW,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS, 
                "pay" => States::PLANNING_PHASE_RESOLVE_SCHEMES_PAY_FOR_REACTION,
            ]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::PLANNING_PHASE_RESOLVE_SCHEMES_REACTIONS, 
                "paid" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS, 
            ]
        ],

            States::PLANNING_PHASE_RESOLVE_SCHEMES_01125 => [
            "name" => "planningPhaseResolveSchemes_01125",
            "description" => clienttranslate('The Boar\'s Guile: ${actplayer} may choose a City Location to place a Reknown onto.'),
            "descriptionmyturn" => clienttranslate('The Boar\'s Guile: ${you} may choose a City Location to place a Reknown onto: '),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01125",
                "actPlanningPhase_01125_Pass"
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
            "description" => clienttranslate('Leshiye of the Wood: ${actplayer} must choose options for Leshiye of the Wood.'),
            "descriptionmyturn" => clienttranslate('Leshiye of the Wood: ${you} must choose an outer City Location for Leshiye of the Wood:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01126_2",
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],

        States::PLANNING_PHASE_RESOLVE_SCHEMES_01144 => [
            "name" => "planningPhaseResolveSchemes_01144",
            "description" => clienttranslate('Filling The Ranks: ${actplayer} must choose a city location to place Reknown onto.'),
            "descriptionmyturn" => clienttranslate('Filling The Ranks: ${you} must choose a city location to place Reknown onto:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01144", 
            ],
            "transitions" => [
                "fewestReknown" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2,
                "notFewestReknown" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS
            ]
        ],
            States::PLANNING_PHASE_RESOLVE_SCHEMES_01144_2 => [
                "name" => "planningPhaseResolveSchemes_01144_2",
                "description" => clienttranslate('Filling The Ranks: ${actplayer} must choose a different city location to place Reknown onto.'),
                "descriptionmyturn" => clienttranslate('Filling The Ranks: ${you} must choose a different city location to place Reknown onto:'),
                "type" => "activeplayer",
                "args" => "argsPlanningPhaseResolveSchemes_01144_2",
                "possibleactions" => [
                    "actPlanningPhase_01144_2", 
                ],
                "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
            ],

        States::PLANNING_PHASE_RESOLVE_SCHEMES_01145 => [
            "name" => "planningPhaseResolveSchemes_01145",
            "description" => clienttranslate('Inspire Generosity: ${actplayer} is choosing options for Inspire Generosity.'),
            "descriptionmyturn" => clienttranslate('Inspire Generosity: ${you} must choose a city location to move a Reknown from:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01145",
                "actPlanningPhase_01145_Pass"
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],
    
    States::PLANNING_PHASE_DRAW => [
        "name" => "planningPhaseDraw",
        "description" => clienttranslate('Drawing Cards...'),
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
                "reaction" => States::PLANNING_PHASE_END_REACTIONS,
                "endOfEvents" => States::HIGH_DRAMA_BEGINNING,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::PLANNING_PHASE_END_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::PLANNING_PHASE_END_EVENTS, 
                "pay" => States::PLANNING_PHASE_END_PAY_FOR_REACTION,
            ]
        ],
        States::PLANNING_PHASE_END_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::PLANNING_PHASE_END_REACTIONS, 
                "paid" => States::PLANNING_PHASE_END_EVENTS, 
            ]
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
                "reaction" => States::HIGH_DRAMA_BEGINNING_REACTIONS,
                "endOfEvents" => States::HIGH_DRAMA_PHASE,
                "endOfGame" => States::END_GAME
                ]
        ],
        States::HIGH_DRAMA_BEGINNING_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::HIGH_DRAMA_BEGINNING_EVENTS, 
                "pay" => States::HIGH_DRAMA_BEGINNING_PAY_FOR_REACTION,
            ]
        ],
        States::HIGH_DRAMA_BEGINNING_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::HIGH_DRAMA_BEGINNING_REACTIONS, 
                "paid" => States::HIGH_DRAMA_BEGINNING_EVENTS, 
            ]
        ],
        States::HIGH_DRAMA_BEGINNING_01144 => [
            "name" => "highDramaBeginning_01144",
            "description" => clienttranslate('Filling The Ranks: ${actplayer} may choose a Mercenary from a City Location to recruit to their home'),
            "descriptionmyturn" => clienttranslate('Filling The Ranks: ${you} may choose a Mercenary from a City Location to recruit to your home:'),
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
        "transitions" => ["" => States::HIGH_DRAMA_PLAYER_TURN]
    ],

    States::HIGH_DRAMA_PLAYER_TURN => [
        "name" => "highDramaPlayerTurn",
        "description" => clienttranslate('${actplayer} must perform a High Drama Action, or Pass.'),
        "descriptionmyturn" => clienttranslate('${you} must perform a High Drama Action, or Pass:'),
        "type" => "activeplayer",
        "args" => "argPlayerTurn",
        "action" => "stHighDramaPlayerTurn",
        "possibleactions" => [
            "actHighDramaChallengeActionStart",
            "actHighDramaClaimActionStart",
            "actHighDramaEquipActionStart",
            "actHighDramaMoveActionStart", 
            "actHighDramaRecruitActionStart",
            "actHighDramaChooseInPlayActionStart",
            "actHighDramaChooseInHandActionStart",
            "actHighDramaChooseBruteStart",
            "actHighDramaPass",
        ],
        "transitions" => [
            "challengeActionStart" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_PERFORMER,
            "claimActionStart" => States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER,
            "equipActionStart" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_PERFORMER,
            "moveActionStart" => States::HIGH_DRAMA_MOVE_ACTION_CHOOSE_PERFORMER, 
            "recruitActionStart" => States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_PERFORMER,
            "inPlayActionStart" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "inHandActionStart" => States::HIGH_DRAMA_IN_HAND_ACTION_CHOOSE_ACTION,
            "bruteStart" => States::HIGH_DRAMA_BRUTE_ACTION_PLAY_BRUTE,
            "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            "end" => States::HIGH_DRAMA_END
        ]
    ],
        // Add action transitions here
        States::HIGH_DRAMA_PLAYER_TURN_EVENTS => [
            "name" => "highDramaPlayerTurnEvents",
            "description" => clienttranslate("Resolving Events for High Drama Player Turn..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "01008" => States::HIGH_DRAMA_PLAYER_TURN_01008,
                "01009" => States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_MERCENARY,
                "01011" => States::HIGH_DRAMA_PLAYER_TURN_01011,
                "01011_2" => States::HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE,
                "01012" => States::HIGH_DRAMA_PLAYER_TURN_01012,
                "01015" => States::HIGH_DRAMA_PLAYER_TURN_01015,
                "01017" => States::HIGH_DRAMA_PLAYER_TURN_01017,
                "01019" => States::HIGH_DRAMA_PLAYER_TURN_01019,
                "01020" => States::HIGH_DRAMA_PLAYER_TURN_01020,
                "01024" => States::HIGH_DRAMA_PLAYER_TURN_01024,
                "01025" => States::HIGH_DRAMA_PLAYER_TURN_01025,
                "01026" => States::HIGH_DRAMA_PLAYER_TURN_01026,
                "01028" => States::HIGH_DRAMA_PLAYER_TURN_01028,
                "01028_2" => States::HIGH_DRAMA_CLAIM_ACTION,
                "01029" => States::HIGH_DRAMA_PLAYER_TURN_01029,
                "01030" => States::HIGH_DRAMA_PLAYER_TURN_01030,
                "01030_2" => States::HIGH_DRAMA_CLAIM_ACTION,
                "01033" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET,
                "01034" => States::HIGH_DRAMA_PLAYER_TURN_01034,
                "01034_2" => States::HIGH_DRAMA_PLAYER_TURN_01034_2,
                "01035" => States::HIGH_DRAMA_PLAYER_TURN_01035,
                "01036" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET,
                "01038" => States::HIGH_DRAMA_PLAYER_TURN_01038,
                "01044" => States::HIGH_DRAMA_PLAYER_TURN_01044,
                "01046a" => States::HIGH_DRAMA_PLAYER_TURN_01046a,
                "01049" => States::HIGH_DRAMA_PLAYER_TURN_01049,
                "01049_2" => States::HIGH_DRAMA_PLAYER_TURN_01049_2,    
                "01055" => States::HIGH_DRAMA_PLAYER_TURN_01055,
                "01056" => States::HIGH_DRAMA_PLAYER_TURN_01056,
                "01056_2" => States::HIGH_DRAMA_PLAYER_TURN_01056_2,
                "01056_3" => States::HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE,
                "01058" => States::HIGH_DRAMA_PLAYER_TURN_01058,
                "01059" => States::HIGH_DRAMA_PLAYER_TURN_01059,
                "01060" => States::HIGH_DRAMA_PLAYER_TURN_01060,
                "01068" => States::HIGH_DRAMA_PLAYER_TURN_01068,
                "01069" => States::HIGH_DRAMA_PLAYER_TURN_01069,
                "01071" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET,
                "01072" => States::HIGH_DRAMA_PLAYER_TURN_01072,
                "01072_2" => States::HIGH_DRAMA_PLAYER_TURN_01072_2,
                "01073" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET,
                "01075" => States::HIGH_DRAMA_CLAIM_ACTION,
                "01076" => States::HIGH_DRAMA_PLAYER_TURN_01076,
                "01078" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET,
                "01081" => States::HIGH_DRAMA_PLAYER_TURN_01081,
                "01083" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET,
                "01085" => States::HIGH_DRAMA_PLAYER_TURN_01085,
                "01086" => States::HIGH_DRAMA_PLAYER_TURN_01086,
                "01091" => States::HIGH_DRAMA_PLAYER_TURN_01091,
                "01092" => States::HIGH_DRAMA_PLAYER_TURN_01092,
                "01093" => States::HIGH_DRAMA_PLAYER_TURN_01093,
                "01097" => States::HIGH_DRAMA_PLAYER_TURN_01097,
                "01102" => States::HIGH_DRAMA_PLAYER_TURN_01102,
                "01104" => States::HIGH_DRAMA_PLAYER_TURN_01104,
                "01143" => States::HIGH_DRAMA_CLAIM_ACTION,
                "01147" => States::HIGH_DRAMA_PLAYER_TURN_01147,
                "01148" => States::HIGH_DRAMA_PLAYER_TURN_01148,
                "01148_3" => States::HIGH_DRAMA_PLAYER_TURN_01148_4,
                "01148_4" => States::HIGH_DRAMA_PLAYER_TURN_01148_2,
                "01149" => States::HIGH_DRAMA_PLAYER_TURN_01149,
                "01152a" => States::HIGH_DRAMA_PLAYER_TURN_01152a,
                "01152b" => States::HIGH_DRAMA_PLAYER_TURN_01152b,
                "01156" => States::HIGH_DRAMA_PLAYER_TURN_01156,
                "01156_3" => States::HIGH_DRAMA_PLAYER_TURN_01156_3,
                "01160" => States::HIGH_DRAMA_PLAYER_TURN_01160,
                "01161" => States::HIGH_DRAMA_PLAYER_TURN_01161,
                "01167" => States::HIGH_DRAMA_PLAYER_TURN_01167,
                "01171" => States::HIGH_DRAMA_PLAYER_TURN_01171,
                "01172" => States::HIGH_DRAMA_PLAYER_TURN_01172,
                "01174" => States::HIGH_DRAMA_PLAYER_TURN_01174,
                "01180" => States::HIGH_DRAMA_PLAYER_TURN_01180,
                "01187" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_LOCATION,
                "01185" => States::HIGH_DRAMA_PLAYER_TURN_01185,
                "01189a" => States::HIGH_DRAMA_PLAYER_TURN_01189a,
                "01189b" => States::HIGH_DRAMA_PLAYER_TURN_01189b,
                "01192" => States::HIGH_DRAMA_PLAYER_TURN_01192,
                "01194" => States::HIGH_DRAMA_PLAYER_TURN_01194,
                "01197" => States::HIGH_DRAMA_PLAYER_TURN_01197,
                "01198" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET,
                "01200" => States::HIGH_DRAMA_PLAYER_TURN_01200,
                "01205" => States::HIGH_DRAMA_PLAYER_TURN_01205,
                "01206" => States::HIGH_DRAMA_CLAIM_ACTION,
                "inHandActionChoosePerformer" => States::HIGH_DRAMA_IN_HAND_ACTION_CHOOSE_PERFORMER,
                "inHandActionPay" => States::HIGH_DRAMA_IN_HAND_ACTION_PAY,
                "reaction" => States::HIGH_DRAMA_PLAYER_TURN_REACTIONS,
                "endOfEvents" => States::NEXT_PLAYER,
                "endOfGame" => States::END_GAME
                ]
        ],
        States::HIGH_DRAMA_PLAYER_TURN_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS, 
                "pay" => States::HIGH_DRAMA_PLAYER_TURN_PAY_FOR_REACTION,
            ]
        ],
        States::HIGH_DRAMA_PLAYER_TURN_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::HIGH_DRAMA_PLAYER_TURN_REACTIONS, 
                "paid" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS, 
            ]
        ],

        States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_PERFORMER => [
            "name" => "highDramaChallengeActionChoosePerformer",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing a Challenge Action.  Choose a Performer to Challenge with:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaChallengeActionChoosePerformer",
            "possibleactions" => [
                "actHighDramaChallengeActionPerformerChosen", 
                "actBack",
            ],
            "transitions" => [
                "performerChosen" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET, 
                "back" => States::HIGH_DRAMA_PLAYER_TURN
            ]
        ],
        States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET => [
            "name" => "highDramaChallengeActionChooseTarget",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing a Challenge Action.  Choose a Target Character to Challenge:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaChallengeActionChooseTarget",
            "possibleactions" => [
                "actHighDramaChallengeActionTargetChosen", 
                "actBack",
                "actBackWithTransition",
            ],
            "transitions" => [
                "targetChosen" => States::HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE, 
                "back" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_PERFORMER,
                "backTriskelion" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
                "backEpeeSanglante" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER,
                "backCavalierHat" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            ]
        ],
        States::HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE => [
            "name" => "highDramaChallengeActionTechniqueAvailable",
            "description" => clienttranslate('Determining if any techniques are available...'),
            "type" => "game",
            "action" => "stTechniqueAvailable",
            "transitions" => [
                "hasTechique" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE,
                "noTechnique" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE
            ]
        ],
        States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE => [
            "name" => "highDramaChallengeActionActivateTechnique",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing a Challenge Action.  You may activate a Technique for the Challenge:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaChallengeActionActivateTechnique",
            "possibleactions" => [
                "actHighDramaChallengeActionTechniqueActivated", 
                "actHighDramaChallengeActionActivateTechnique_Pass",
                "actBack",
            ],
            "transitions" => [
                "techniqueActivated" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_EVENTS,
                "pass" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE,
                "back" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_TARGET
            ]
        ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_EVENTS => [
                "name" => "highDramaChallengeActionActivateTechniqueEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "reaction" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_REACTIONS,
                    "endOfEvents" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE,
                    "endOfGame" => States::END_GAME
                    ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_REACTIONS => [
                "name" => "playerReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actReactionForState", 
                ],
                "transitions" => [
                    "done" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_EVENTS, 
                    "pay" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_PAY_FOR_REACTION,
                ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_PAY_FOR_REACTION => [
                "name" => "playerPayForReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actBack",
                    "actPayForReaction", 
                ],
                "transitions" => [
                    "back" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_REACTIONS, 
                    "paid" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_EVENTS, 
                ]
            ],
    
        States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE => [
            "name" => "highDramaChallengeActionSetupChallenge",
            "type" => "game",
            "action" => "stSetupChallenge",
            "transitions" => [
                "" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_EVENTS,
            ]
        ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_EVENTS => [
                "name" => "highDramaChallengeActionSetupChallengeEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "reaction" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_REACTIONS,
                    "endOfEvents" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE,
                    "endOfGame" => States::END_GAME
                ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_REACTIONS => [
                "name" => "playerReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actReactionForState", 
                ],
                "transitions" => [
                    "done" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_EVENTS, 
                    "pay" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_PAY_FOR_REACTION,
                ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_PAY_FOR_REACTION => [
                "name" => "playerPayForReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actBack",
                    "actPayForReaction", 
                ],
                "transitions" => [
                    "back" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_REACTIONS, 
                    "paid" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_EVENTS, 
                ]
            ],

        States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE => [
            "name" => "highDramaChallengeActionResolveTechnique",
            "type" => "game",
            "action" => "stHighDramaChallengeActionResolveTechnique",
            "transitions" => [
                "" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS
                ]
        ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS => [
                "name" => "highDramaChallengeActionResolveTechniqueEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "01063" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01063,
                    "reaction" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_REACTIONS,
                    "endOfEvents" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHECK_CANCELLED,
                    "endOfGame" => States::END_GAME
                    ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_REACTIONS => [
                "name" => "playerReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actReactionForState", 
                ],
                "transitions" => [
                    "done" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS, 
                    "pay" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_PAY_FOR_REACTION,
                ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_PAY_FOR_REACTION => [
                "name" => "playerPayForReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actBack",
                    "actPayForReaction", 
                ],
                "transitions" => [
                    "back" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_REACTIONS, 
                    "paid" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS, 
                ]
            ],

        States::HIGH_DRAMA_CHALLENGE_ACTION_CHECK_CANCELLED => [
            "name" => "highChallengeActionCheckCancelled",
            "type" => "game",
            "action" => "stChallengeActionCheckCancelled",
            "transitions" => [
                "notCancelled" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_CHALLENGE,
                "cancelled" => States::NEXT_PLAYER,
            ]
        ],
        
        States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_CHALLENGE => [
            "name" => "highDramaChallengeActionAcceptChallenge",
            "description" => clienttranslate('${actplayer} is choosing to accept Challenge.'),
            "descriptionmyturn" => clienttranslate('${you} must choose to accept Challenge, or Intervene:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaChallengeActionAcceptChallenge",
            "possibleactions" => [
                "actHighDramaChallengeActionAccept",
                "actHighDramaChallengeActionReject",
                "actHighDramaChallengeActionIntervene"
            ],
            "transitions" => [
                "" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT,
            ]
        ],

        States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT => [
            "name" => "highDramaChallengeActionGenerateThreat",
            "type" => "game",
            "action" => "stHighDramaChallengeActionGenerateThreat",
            "transitions" => [
                "" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS,
                ]
        ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS => [
                "name" => "highDramaChallengeActionGenerateThreatEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "reaction" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_REACTIONS,
                    "endOfEvents" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLUTION,
                    "endOfGame" => States::END_GAME
                    ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_REACTIONS => [
                "name" => "playerReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actReactionForState", 
                ],
                "transitions" => [
                    "done" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS, 
                    "pay" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_PAY_FOR_REACTION,
                ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_PAY_FOR_REACTION => [
                "name" => "playerPayForReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actBack",
                    "actPayForReaction", 
                ],
                "transitions" => [
                    "back" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_REACTIONS, 
                    "paid" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT_EVENTS, 
                ]
            ],
        States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLUTION => [
            "name" => "highDramaChallengeActionResolution",
            "type" => "game",
            "action" => "stHighDramaChallengeActionResolution",
            "transitions" => [
                "accepted" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_EVENTS,
                "rejected" => States::HIGH_DRAMA_CHALLENGE_ACTION_REJECT_EVENTS,
                ]
        ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_REJECT_EVENTS => [
                "name" => "highDramaChallengeActionRejectEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "reaction" => States::HIGH_DRAMA_CHALLENGE_ACTION_REJECT_REACTIONS,
                    "endOfEvents" => States::NEXT_PLAYER,
                    "endOfGame" => States::END_GAME
                    ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_REJECT_REACTIONS => [
                "name" => "playerReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actReactionForState", 
                ],
                "transitions" => [
                    "done" => States::HIGH_DRAMA_CHALLENGE_ACTION_REJECT_EVENTS, 
                    "pay" => States::HIGH_DRAMA_CHALLENGE_ACTION_REJECT_PAY_FOR_REACTION,
                ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_REJECT_PAY_FOR_REACTION => [
                "name" => "playerPayForReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actBack",
                    "actPayForReaction", 
                ],
                "transitions" => [
                    "back" => States::HIGH_DRAMA_CHALLENGE_ACTION_REJECT_REACTIONS, 
                    "paid" => States::HIGH_DRAMA_CHALLENGE_ACTION_REJECT_EVENTS, 
                ]
            ],
                States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_EVENTS => [
                "name" => "highDramaChallengeActionAcceptEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "reaction" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_REACTIONS,
                    "endOfEvents" => States::DUEL_STARTED,
                    "endOfGame" => States::END_GAME
                    ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_REACTIONS => [
                "name" => "playerReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actReactionForState", 
                ],
                "transitions" => [
                    "done" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_EVENTS, 
                    "pay" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_PAY_FOR_REACTION,
                ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_PAY_FOR_REACTION => [
                "name" => "playerPayForReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actBack",
                    "actPayForReaction", 
                ],
                "transitions" => [
                    "back" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_REACTIONS, 
                    "paid" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_EVENTS, 
                ]
            ],

        States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER => [
            "name" => "highDramaClaimActionChoosePerformer",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing a Claim Action.  Choose a Performer to Claim with:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaClaimActionChoosePerformer",
            "possibleactions" => [
                "actHighDramaClaimActionPerformerChosen", 
                "actBack",
            ],
            "transitions" => [
                "performerChosen" => States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER_EVENTS, 
                "back" => States::HIGH_DRAMA_PLAYER_TURN
            ]
        ],
        States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER_EVENTS => [
            "name" => "highDramaClaimActionChoosePerformerEvents",
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER_REACTIONS,
                "endOfEvents" => States::HIGH_DRAMA_CLAIM_ACTION,
                "endOfGame" => States::END_GAME
                ]
        ],
        States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER_EVENTS, 
                "pay" => States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER_PAY_FOR_REACTION,
            ]
        ],
        States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER_REACTIONS, 
                "paid" => States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER_EVENTS, 
            ]
        ],

        States::HIGH_DRAMA_CLAIM_ACTION => [
            "name" => "highDramaClaimAction",
            "type" => "game",
            "action" => "stHighDramaClaim",
            "transitions" => [
                "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
            ]
        ],

        States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_PERFORMER => [
            "name" => "highDramaEquipActionChoosePerformer",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing an Equip Action.  Choose a Performer to Equip:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaEquipActionChoosePerformer",
            "possibleactions" => [
                "actHighDramaEquipActionPerformerChosen", 
                "actBack",
            ],
            "transitions" => [
                "performerChosen" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_LOCATION, 
                "back" => States::HIGH_DRAMA_PLAYER_TURN
            ]
        ],
        States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_LOCATION => [
            "name" => "highDramaEquipActionChooseAttachmentLocation",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing an Equip Action.  Choose where to equip the Attachment from:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaEquipActionChooseAttachmentLocation",
            "possibleactions" => [
                "actSimpleTransition", 
                "actBack",
                "actBackWithTransition",
            ],
            "transitions" => [
                "equipFromHand" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_FROM_HAND, 
                "equipFromPlay" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_FROM_PLAY, 
                "back" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_PERFORMER,
                "backSmuggledItem" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION
            ]
        ],
        States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_FROM_HAND => [
            "name" => "highDramaEquipActionChooseAttachmentFromHand",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing an Equip Action.  Choose an Attachment to equip from Your Hand:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaEquipActionChooseAttachmentFromHand",
            "possibleactions" => [
                "actHighDramaEquipActionAttachmentFromHandSelected", 
                "actBack",
            ],
            "transitions" => [
                "attachmentSelected" => States::HIGH_DRAMA_EQUIP_ACTION_PAY_FOR_ATTACHMENT_FROM_HAND, 
                "back" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_LOCATION
            ]
        ],
        States::HIGH_DRAMA_EQUIP_ACTION_PAY_FOR_ATTACHMENT_FROM_HAND => [
            "name" => "highDramaEquipActionPayForAttachmentFromHand",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing an Equip Action.  Choose cards to pay for selected Attachment:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaEquipActionPayForAttachmentFromHand",
            "possibleactions" => [
                "actHighDramaEquipAttachment", 
                "actBack",
            ],
            "transitions" => [
                "attachmentEquipped" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS, 
                "back" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_FROM_HAND
            ]
        ],

        States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_FROM_PLAY => [
            "name" => "highDramaEquipActionChooseAttachmentFromPlay",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing an Equip Action.  Choose an Attachment to equip from play:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaEquipActionChooseAttachmentFromPlay",
            "possibleactions" => [
                "actHighDramaEquipActionAttachmentFromPlaySelected", 
                "actBack",
            ],
            "transitions" => [
                "attachmentSelected" => States::HIGH_DRAMA_EQUIP_ACTION_PAY_FOR_ATTACHMENT_FROM_PLAY, 
                "back" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_LOCATION
            ]
        ],
        States::HIGH_DRAMA_EQUIP_ACTION_PAY_FOR_ATTACHMENT_FROM_PLAY => [
            "name" => "highDramaEquipActionPayForAttachmentFromPlay",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing an Equip Action.  Choose cards to pay for selected Attachment:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaEquipActionPayForAttachmentFromPlay",
            "possibleactions" => [
                "actHighDramaEquipAttachment", 
                "actBack",
                "actBackWithTransition",
            ],
            "transitions" => [
                "attachmentEquipped" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS, 
                "back" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_ATTACHMENT_FROM_PLAY,
                "backLetsHaggle" => States::HIGH_DRAMA_PLAYER_TURN_01147
            ]
        ],

        States::HIGH_DRAMA_MOVE_ACTION_CHOOSE_PERFORMER => [
            "name" => "highDramaMoveActionChoosePerformer",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} are performing a Move Action.  Choose a Performer to Move:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaMoveActionChoosePerformer",
            "possibleactions" => [
                "actHighDramaMoveActionPerformerChosen", 
                "actBack",
            ],
            "transitions" => [
                "performerChosen" => States::HIGH_DRAMA_MOVE_ACTION_CHOOSE_DESTINATION, 
                "back" => States::HIGH_DRAMA_PLAYER_TURN
            ]
        ],
            States::HIGH_DRAMA_MOVE_ACTION_CHOOSE_DESTINATION => [
                "name" => "highDramaMoveActionChooseLocation",
                "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
                "descriptionmyturn" => clienttranslate('${you} are performing a Move Action.  Choose a destination location for your Perfomer:'),
                "type" => "activeplayer",
                "args" => "argsHighDramaMoveActionChooseLocation",
                "possibleactions" => [
                    "actHighDramaMoveActionDestinationChosen", 
                    "actBack",
                ],
                "transitions" => [
                    "destinationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS, 
                    "back" => States::HIGH_DRAMA_MOVE_ACTION_CHOOSE_PERFORMER
                ]
            ],

            States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_PERFORMER => [
                "name" => "highDramaRecruitActionChoosePerformer",
                "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
                "descriptionmyturn" => clienttranslate('${you} are performing a Recruit Action.  Choose a Performer that will recruit:'),
                "type" => "activeplayer",
                "args" => "argsHighDramaRecruitActionChoosePerformer",
                "possibleactions" => [
                    "actHighDramaRecruitActionPerformerChosen", 
                    "actBack",
                ],
                "transitions" => [
                    "performerChosen" => States::HIGH_DRAMA_RECRUIT_ACTION_PARLEYABLE, 
                    "back" => States::HIGH_DRAMA_PLAYER_TURN
                ]
            ],
            States::HIGH_DRAMA_RECRUIT_ACTION_PARLEYABLE => [
                "name" => "highDramaRecruitActionParleyable",
                "type" => "game",
                "action" => "stHighDramaRecruitActionParleyable",
                "transitions" => [
                    "parleyable" => States::HIGH_DRAMA_RECRUIT_ACTION_PARLEY, 
                    "notParleyable" => States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_MERCENARY
                ]
            ],
            States::HIGH_DRAMA_RECRUIT_ACTION_PARLEY => [
                "name" => "highDramaRecruitActionParley",
                "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
                "descriptionmyturn" => clienttranslate('${you} are performing a Recruit Action.  Choose if your Performer will Parley:'),
                "type" => "activeplayer",
                "args" => "argsHighDramaRecruitActionParley",
                "possibleactions" => [
                    "actHighDramaRecruitActionParleyYes", 
                    "actHighDramaRecruitActionParleyNo", 
                    "actBack",
                ],
                "transitions" => [
                    "parleyChosen" => States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_MERCENARY, 
                    "back" => States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_PERFORMER
                ]
            ],
            States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_MERCENARY => [
                "name" => "highDramaRecruitActionChooseMercenary",
                "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
                "descriptionmyturn" => clienttranslate('${you} are performing a Recruit Action.  Choose a Mercenary to recruit:'),
                "type" => "activeplayer",
                "args" => "argsHighDramaRecruitActionChooseMercenary",
                "possibleactions" => [
                    "actHighDramaRecruitActionMercenaryChosen", 
                    "actBack",
                ],
                "transitions" => [
                    "mercenaryChosen" => States::HIGH_DRAMA_RECRUIT_ACTION_PAY_FOR_MERCENARY, 
                    "back" => States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_PERFORMER
                ]
            ],
            States::HIGH_DRAMA_RECRUIT_ACTION_PAY_FOR_MERCENARY => [
                "name" => "highDramaRecruitActionPayForMercenary",
                "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
                "descriptionmyturn" => clienttranslate('${you} are performing a Recruit Action. Choose cards from your Faction Hand to pay for selected Mercenary: '),
                "type" => "activeplayer",
                "args" => "argsHighDramaRecruitActionPayForMercenary",
                "possibleactions" => [
                    "actHighDramaRecruitActionPayForMercenary", 
                    "actBack",
                    "actBackWithTransition",
                ],
                "transitions" => [
                    "mercenaryPaidFor" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS, 
                    "back" => States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_MERCENARY,
                    "backKaspar" => States::HIGH_DRAMA_PLAYER_TURN_01035_4
                ]
            ],

            States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION => [
                "name" => "highDramaInPlayActionChooseAction",
                "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
                "descriptionmyturn" => clienttranslate('${you} are choosing an In-Play Action.  Choose an Action:'),
                "type" => "activeplayer",
                "args" => "argsHighDramaInPlayActionChooseAction",
                "possibleactions" => [
                    "actHighDramaInPlayActionChosen", 
                    "actBack",
                ],
                "transitions" => [
                    "inPlayActionChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                    "requiresPerformerSelected" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER,
                    "back" => States::HIGH_DRAMA_PLAYER_TURN
                ]
            ],
            States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER => [
                "name" => "highDramaInPlayActionChoosePerformer",
                "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
                "descriptionmyturn" => clienttranslate('${you} must choose a performer for the chosen In-Play Action:'),
                "type" => "activeplayer",
                "args" => "argsHighDramaInPlayActionChoosePerformer",
                "possibleactions" => [
                    "actHighDramaInPlayActionPerformerChosen", 
                    "actBack",
                ],
                "transitions" => [
                    "inPlayActionPerformerChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                    "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION
                ]
            ],
            States::HIGH_DRAMA_IN_HAND_ACTION_CHOOSE_ACTION => [
                "name" => "highDramaInHandActionChooseAction",
                "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
                "descriptionmyturn" => clienttranslate('${you} must choose an In-Hand Action:'),
                "type" => "activeplayer",
                "args" => "argsHighDramaInHandActionChooseAction",
                "possibleactions" => [
                    "actHighDramaInHandActionChosen",
                    "actBack",
                ],
                "transitions" => [
                    "inHandActionChosen" => States::HIGH_DRAMA_IN_HAND_ACTION_PAY,
                    "requiresPerformerSelected" => States::HIGH_DRAMA_IN_HAND_ACTION_CHOOSE_PERFORMER,
                    "back" => States::HIGH_DRAMA_PLAYER_TURN
                ]
            ],
            States::HIGH_DRAMA_IN_HAND_ACTION_CHOOSE_PERFORMER => [
                "name" => "highDramaInHandActionChoosePerformer",
                "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
                "descriptionmyturn" => clienttranslate('${you} must choose a performer for chosen In-Hand Action:'),
                "type" => "activeplayer",
                "args" => "argsHighDramaInHandActionChoosePerformer",
                "possibleactions" => [
                    "actHighDramaInHandActionPerformerChosen", 
                    "actBack",
                ],
                "transitions" => [
                    "inHandActionPerformerChosen" => States::HIGH_DRAMA_IN_HAND_ACTION_PAY,
                    "back" => States::HIGH_DRAMA_IN_HAND_ACTION_CHOOSE_ACTION
                ]
            ],
            States::HIGH_DRAMA_IN_HAND_ACTION_PAY => [
                "name" => "highDramaInHandActionPay",
                "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
                "descriptionmyturn" => clienttranslate('${you} must pay for the In-Hand Action:'),
                "type" => "activeplayer",
                "args" => "argsHighDramaInHandActionPay",
                "possibleactions" => [
                    "actPayForInHandAction", 
                    "actBackWithTransition",
                ],
                "transitions" => [
                    "actionPaidFor" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                    "backPerformer" => States::HIGH_DRAMA_IN_HAND_ACTION_CHOOSE_PERFORMER,
                    "backChooseAction" => States::HIGH_DRAMA_IN_HAND_ACTION_CHOOSE_ACTION
                ]
            ],
        States::HIGH_DRAMA_BRUTE_ACTION_PLAY_BRUTE => [
            "name" => "highDramaBruteActionChooseBrute",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} must choose a Brute from your Hand to play:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaBruteActionChooseBrute",
            "possibleactions" => [
                "actHighDramaBruteActionBruteChosen",
                "actBack",
            ],
            "transitions" => [
                "bruteChosen" => States::HIGH_DRAMA_BRUTE_ACTION_PAY_FOR_BRUTE,
                "back" => States::HIGH_DRAMA_PLAYER_TURN
            ]
        ],
        States::HIGH_DRAMA_BRUTE_ACTION_PAY_FOR_BRUTE => [
            "name" => "highDramaBruteActionPayForBrute",
            "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
            "descriptionmyturn" => clienttranslate('${you} must pay for the chosen Brute:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaBruteActionPayForBrute",
            "possibleactions" => [
                "actPayForBrute",
                "actBack",
            ],
            "transitions" => [
                "brutePaidFor" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "back" => States::HIGH_DRAMA_BRUTE_ACTION_PLAY_BRUTE
            ]
        ],

        States::DUEL_STARTED => [
            "name" => "duelStarted",
            "type" => "game",
            "action" => "stDuelStarted",
            "transitions" => [
                "" => States::DUEL_STARTED_EVENTS
                ]
        ],
            States::DUEL_STARTED_EVENTS => [
                "name" => "duelStartedEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "reaction" => States::DUEL_STARTED_REACTIONS,
                    "endOfEvents" => States::DUEL_NEW_ROUND,
                    "endOfGame" => States::END_GAME
                    ]
            ],
            States::DUEL_STARTED_REACTIONS => [
                "name" => "playerReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actReactionForState", 
                ],
                "transitions" => [
                    "done" => States::DUEL_STARTED_EVENTS, 
                    "pay" => States::DUEL_STARTED_PAY_FOR_REACTION,
                ]
            ],
            States::DUEL_STARTED_PAY_FOR_REACTION => [
                "name" => "playerPayForReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actBack",
                    "actPayForReaction", 
                ],
                "transitions" => [
                    "back" => States::DUEL_STARTED_REACTIONS, 
                    "paid" => States::DUEL_STARTED_EVENTS, 
                ]
            ],
        States::DUEL_NEW_ROUND => [
            "name" => "duelNewRound",
            "type" => "game",
            "action" => "stDuelNewRound",
            "transitions" => [
                "" => States::DUEL_NEW_ROUND_EVENTS
                ]
        ],
            States::DUEL_NEW_ROUND_EVENTS => [
                "name" => "duelNewRoundEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "reaction" => States::DUEL_NEW_ROUND_REACTIONS,
                    "endOfEvents" => States::DUEL_CHOOSE_ACTION,
                    "endOfGame" => States::END_GAME
                    ]
            ],
            States::DUEL_NEW_ROUND_REACTIONS => [
                "name" => "playerReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actReactionForState", 
                ],
                "transitions" => [
                    "done" => States::DUEL_NEW_ROUND_EVENTS, 
                    "pay" => States::DUEL_NEW_ROUND_PAY_FOR_REACTION,
                ]
            ],
            States::DUEL_NEW_ROUND_PAY_FOR_REACTION => [
                "name" => "playerPayForReaction",
                "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                "descriptionmyturn" => "",
                "type" => "activeplayer",
                "args" => "argsForStatePrivate",
                "possibleactions" => [
                    "actBack",
                    "actPayForReaction", 
                ],
                "transitions" => [
                    "back" => States::DUEL_NEW_ROUND_REACTIONS, 
                    "paid" => States::DUEL_NEW_ROUND_EVENTS, 
                ]
            ],
        States::DUEL_CHOOSE_ACTION => [
            "name" => "duelChooseAction",
            "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
            "descriptionmyturn" => clienttranslate('${you} must choose a Duel Action:'),
            "type" => "activeplayer",
            "args" => "argsChooseDuelAction",
            "action" => "stResetDuelAction",
            "possibleactions" => [
                "actDuelActionGamble",
                "actDuelActionChooseCombatCard",
                "actDuelActionChooseTechnique",
                "actDuelDoneRound",
                "actDuelEndDuel"
            ],
            "transitions" => [
                "chooseTechnique" => States::DUEL_CHOOSE_TECHNIQUE,
                "useManeuver" => States::DUEL_USE_MANEUVER_FROM_COMBAT_CARD,
                "chooseGambleCard" => States::DUEL_CHOOSE_GAMBLE_CARD,
                "applyCombatCardStats" => States::DUEL_APPLY_COMBAT_CARD_STATS,
                "doneWithRound" => States::DUEL_END_OF_ROUND,
            ]
        ],
            States::DUEL_CHOOSE_TECHNIQUE => [
                "name" => "duelChooseTechnique",
                "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
                "descriptionmyturn" => clienttranslate('${you} may choose a Technique to use this Round:'),
                "type" => "activeplayer",
                "args" => "argsChooseDuelTechnique",
                "possibleactions" => [
                    "actDuelTechniqueChosen",
                    "actBack"
                ],
                "transitions" => [
                    "techniqueChosen" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
                    "back" => States::DUEL_CHOOSE_ACTION
                ]
            ],
                //Most custom transitions for techniques will be here
                States::DUEL_CHOOSE_TECHNIQUE_EVENTS => [
                    "name" => "duelChooseTechniqueEvents",
                    "type" => "game",
                    "action" => "stRunEvents",
                    "transitions" => [
                        "reaction" => States::DUEL_CHOOSE_TECHNIQUE_REACTIONS,
                        "01013" => States::DUEL_CHOOSE_TECHNIQUE_01013,
                        "01036" => States::DUEL_CHOOSE_TECHNIQUE_01036,
                        "01063" => States::DUEL_CHOOSE_TECHNIQUE_01063,
                        "01067" => States::DUEL_CHOOSE_TECHNIQUE_01067,
                        "01093" => States::DUEL_CHOOSE_TECHNIQUE_01093,
                        "endOfEvents" => States::DUEL_CHOOSE_ACTION,
                        "endOfGame" => States::END_GAME
                        ]
                ],
                States::DUEL_CHOOSE_TECHNIQUE_REACTIONS => [
                    "name" => "playerReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actReactionForState", 
                    ],
                    "transitions" => [
                        "done" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS, 
                        "pay" => States::DUEL_CHOOSE_TECHNIQUE_PAY_FOR_REACTION,
                    ]
                ],
                States::DUEL_CHOOSE_TECHNIQUE_PAY_FOR_REACTION => [
                    "name" => "playerPayForReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actBack",
                        "actPayForReaction", 
                    ],
                    "transitions" => [
                        "back" => States::DUEL_CHOOSE_TECHNIQUE_REACTIONS, 
                        "paid" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS, 
                    ]
                ],
            States::DUEL_USE_MANEUVER_FROM_COMBAT_CARD => [
                "name" => "duelUseManeuverFromCombatCard",
                "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
                "descriptionmyturn" => clienttranslate('${you} may choose to use any Maneuvers from the chosen combat card:'),
                "type" => "activeplayer",
                "args" => "argsDuelUseManeuverFromCombatCard",
                "possibleactions" => [
                    "actDuelUseManeuverFromCombatCard",
                    "actBack",
                    "actDuelUseManeuverFromCombatCardDeclined"
                ],
                "transitions" => [
                    "maneuverChosen" => States::DUEL_GET_MANEUVER_FROM_COMBAT_CARD_COST,
                    "maneuverDeclined" => States::DUEL_APPLY_COMBAT_CARD_STATS,
                    "back" => States::DUEL_CHOOSE_ACTION
                ]
            ],
            States::DUEL_GET_MANEUVER_FROM_COMBAT_CARD_COST => [
                "name" => "duelGetManeuverFromCombatCardCost",
                "type" => "game",
                "action" => "stDuelGetManeuverFromCombatCardCost",
                "transitions" => [
                    "" => States::DUEL_GET_MANEUVER_FROM_COMBAT_CARD_COST_EVENTS
                    ]
            ],
                States::DUEL_GET_MANEUVER_FROM_COMBAT_CARD_COST_EVENTS => [
                    "name" => "duelGetManeuverFromCombatCardCostEvents",
                    "type" => "game",
                    "action" => "stRunEvents",
                    "transitions" => [
                        "endOfEvents" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD,
                        "endOfGame" => States::END_GAME
                        ]
                ],
            States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD => [
                "name" => "duelPayForManeuverFromCombatCard",
                "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
                "descriptionmyturn" => clienttranslate('${you} must pay for Maneuver from chosen combat card by selecting cards in your hand:'),
                "type" => "activeplayer",
                "args" => "argsDuelPayForManeuverFromCombatCard",
                "possibleactions" => [
                    "actDuelPayForManeuverFromCombatCard",
                    "actBack",
                    "actBackWithTransition"
                ],
                "transitions" => [
                    "maneuverPaidFor" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_EVENTS,
                    "back" => States::DUEL_CHOOSE_ACTION,
                    "backAbnormalFlow" => States::DUEL_USE_MANEUVER_FROM_COMBAT_CARD
                ]
            ],
                // Add custom maneuver transitions here
                States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_EVENTS => [
                    "name" => "duelUseManeuverFromCombatCardEvents",
                    "type" => "game",
                    "action" => "stRunEvents",
                    "transitions" => [
                        "01051" => States::DUEL_RESOLVE_MANEUVER_01051,
                        "01059" => States::DUEL_RESOLVE_MANEUVER_01059,
                        "01077" => States::DUEL_RESOLVE_MANEUVER_01077,
                        "01079" => States::DUEL_RESOLVE_MANEUVER_01079,
                        "01079_2" => States::DUEL_RESOLVE_MANEUVER_01079_2,
                        "01103" => States::DUEL_RESOLVE_MANEUVER_01103,
                        "01165" => States::DUEL_RESOLVE_MANEUVER_01165,
                        "reaction" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_REACTIONS,
                        "endOfEvents" => States::DUEL_APPLY_COMBAT_CARD_STATS,
                        "endOfGame" => States::END_GAME
                        ]
                ],
                States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_REACTIONS => [
                    "name" => "playerReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actReactionForState", 
                    ],
                    "transitions" => [
                        "done" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_EVENTS, 
                        "pay" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_PAY_FOR_REACTION,
                    ]
                ],
                States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_PAY_FOR_REACTION => [
                    "name" => "playerPayForReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actBack",
                        "actPayForReaction", 
                    ],
                    "transitions" => [
                        "back" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_REACTIONS, 
                        "paid" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_EVENTS, 
                    ]
                ],
            States::DUEL_APPLY_COMBAT_CARD_STATS => [
                "name" => "duelApplyCombatCardStats",
                "type" => "game",
                "action" => "stApplyCombatCardStats",
                "transitions" => [
                    "" => States::DUEL_APPLY_COMBAT_CARD_STATS_EVENTS
                ]
            ],
                States::DUEL_APPLY_COMBAT_CARD_STATS_EVENTS => [
                    "name" => "duelApplyCombatCardStatsEvents",
                    "type" => "game",
                    "action" => "stRunEvents",
                    "transitions" => [
                        "01085" => States::DUEL_APPLY_COMBAT_CARD_STATS_01085,
                        "reaction" => States::DUEL_APPLY_COMBAT_CARD_STATS_REACTIONS,
                        "endOfEvents" => States::DUEL_SET_NEXT_COMBAT_CARD,
                        "endOfGame" => States::END_GAME
                    ]
                ],
                States::DUEL_APPLY_COMBAT_CARD_STATS_REACTIONS => [
                    "name" => "playerReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actReactionForState", 
                    ],
                    "transitions" => [
                        "done" => States::DUEL_APPLY_COMBAT_CARD_STATS_EVENTS, 
                        "pay" => States::DUEL_APPLY_COMBAT_CARD_STATS_PAY_FOR_REACTION,
                    ]
                ],
                States::DUEL_APPLY_COMBAT_CARD_STATS_PAY_FOR_REACTION => [
                    "name" => "playerPayForReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actBack",
                        "actPayForReaction", 
                    ],
                    "transitions" => [
                        "back" => States::DUEL_APPLY_COMBAT_CARD_STATS_REACTIONS, 
                        "paid" => States::DUEL_APPLY_COMBAT_CARD_STATS_EVENTS, 
                    ]
                ],
            States::DUEL_SET_NEXT_COMBAT_CARD => [
                "name" => "duelSetNextCombatCard",
                "type" => "game",
                "action" => "stSetNextCombatCard",
                "transitions" => [
                    "useManeuver" => States::DUEL_USE_MANEUVER_FROM_COMBAT_CARD,
                    "applyCombatCardStats" => States::DUEL_APPLY_COMBAT_CARD_STATS,
                    "noMoreCombatCards" => States::DUEL_CHOOSE_ACTION
                ]
            ],

            States::DUEL_CHOOSE_GAMBLE_CARD =>[
                "name" => "duelChooseGambleCard",
                "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
                "descriptionmyturn" => clienttranslate('${you} must choose to a card to Gamble with:'),
                "type" => "activeplayer",
                "args" => "argsDuelChooseGambleCard",
                "possibleactions" => [
                    "actBack",
                    "actGambleCardChosen"
                ],
                "transitions" => [
                    "back" => States::DUEL_CHOOSE_ACTION,
                    "useManeuver" => States::DUEL_USE_MANEUVER_FROM_COMBAT_CARD,
                    "noManeuver" => States::DUEL_CHOOSE_GAMBLE_CARD_EVENTS
                ]
            ],
                States::DUEL_CHOOSE_GAMBLE_CARD_EVENTS => [
                    "name" => "duelChooseGambleCardEvents",
                    "type" => "game",
                    "action" => "stRunEvents",
                    "transitions" => [
                        "reaction" => States::DUEL_CHOOSE_GAMBLE_CARD_REACTIONS,
                        "endOfEvents" => States::DUEL_APPLY_COMBAT_CARD_STATS,
                        "endOfGame" => States::END_GAME
                    ]
                ],
                States::DUEL_CHOOSE_GAMBLE_CARD_REACTIONS => [
                    "name" => "playerReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actReactionForState", 
                    ],
                    "transitions" => [
                        "done" => States::DUEL_CHOOSE_GAMBLE_CARD_EVENTS, 
                        "pay" => States::DUEL_CHOOSE_GAMBLE_CARD_PAY_FOR_REACTION,
                    ]
                ],
                States::DUEL_CHOOSE_GAMBLE_CARD_PAY_FOR_REACTION => [
                    "name" => "playerPayForReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actBack",
                        "actPayForReaction", 
                    ],
                    "transitions" => [
                        "back" => States::DUEL_CHOOSE_GAMBLE_CARD_REACTIONS, 
                        "paid" => States::DUEL_CHOOSE_GAMBLE_CARD_EVENTS, 
                    ]
                ],
            States::DUEL_END_OF_ROUND => [
                "name" => "duelEndOfRound",
                "type" => "game",
                "action" => "stDuelEndOfRound",
                "transitions" => [
                    "" => States::DUEL_END_OF_ROUND_EVENTS
                ]
            ],
                States::DUEL_END_OF_ROUND_EVENTS => [
                    "name" => "duelEndOfRoundEvents",
                    "type" => "game",
                    "action" => "stRunEvents",
                    "transitions" => [
                        "01031" => States::DUEL_END_OF_ROUND_01031,
                        "reaction" => States::DUEL_END_OF_ROUND_REACTIONS,
                        "endOfEvents" => States::DUEL_NEXT_PLAYER,
                        "endOfGame" => States::END_GAME
                    ]
                ],
                States::DUEL_END_OF_ROUND_REACTIONS => [
                    "name" => "playerReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actReactionForState", 
                    ],
                    "transitions" => [
                        "done" => States::DUEL_END_OF_ROUND_EVENTS, 
                        "pay" => States::DUEL_END_OF_ROUND_PAY_FOR_REACTION,
                    ]
                ],
                States::DUEL_END_OF_ROUND_PAY_FOR_REACTION => [
                    "name" => "playerPayForReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actBack",
                        "actPayForReaction", 
                    ],
                    "transitions" => [
                        "back" => States::DUEL_END_OF_ROUND_REACTIONS, 
                        "paid" => States::DUEL_END_OF_ROUND_EVENTS, 
                    ]
                ],
            States::DUEL_NEXT_PLAYER => [
                "name" => "duelNextPlayer",
                "type" => "game",
                "action" => "stDuelNextPlayer",
                "transitions" => [
                    "endOfGame" => States::END_GAME, 
                    "endOfDuel" => States::DUEL_END, 
                    "newRound" => States::DUEL_NEW_ROUND
                ]
            ],
            States::DUEL_END => [
                "name" => "duelEnd",
                "type" => "game",
                "action" => "stDuelEnd",
                "transitions" => [
                    "" => States::DUEL_END_EVENTS
                ]
            ],
                States::DUEL_END_EVENTS => [
                    "name" => "duelEnd",
                    "type" => "game",
                    "action" => "stRunEvents",
                    "transitions" => [
                        "reaction" => States::DUEL_END_REACTIONS,
                        "endOfEvents" => States::NEXT_PLAYER,
                        "endOfGame" => States::END_GAME
                    ]
                ],
                States::DUEL_END_REACTIONS => [
                    "name" => "playerReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actReactionForState", 
                    ],
                    "transitions" => [
                        "done" => States::DUEL_END_EVENTS, 
                        "pay" => States::DUEL_END_PAY_FOR_REACTION,
                    ]
                ],
                States::DUEL_END_PAY_FOR_REACTION => [
                    "name" => "playerPayForReaction",
                    "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
                    "descriptionmyturn" => "",
                    "type" => "activeplayer",
                    "args" => "argsForStatePrivate",
                    "possibleactions" => [
                        "actBack",
                        "actPayForReaction", 
                    ],
                    "transitions" => [
                        "back" => States::DUEL_END_REACTIONS, 
                        "paid" => States::DUEL_END_EVENTS, 
                        "01080" => States::DUEL_END_01080,
                    ]
                ],
                
    States::NEXT_PLAYER => [
        "name" => "nextPlayer",
        "type" => "game",
        "action" => "stNextPlayer",
        "transitions" => [
            "nextPlayer" => States::NEXT_PLAYER_EVENTS,
            "endOfGame" => States::END_GAME
            ]
        ],
        States::NEXT_PLAYER_EVENTS => [
            "name" => "nextPlayerEvents",
            "description" => clienttranslate("Resolving Events for end of Player Turn..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::NEXT_PLAYER_REACTIONS,
                "endOfEvents" => States::NEXT_PLAYER_SET_CURRENT_PLAYER,
                "endOfGame" => States::END_GAME
                ]
        ],
        States::NEXT_PLAYER_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::NEXT_PLAYER_EVENTS, 
                "pay" => States::NEXT_PLAYER_PAY_FOR_REACTION,
            ]
        ],
        States::NEXT_PLAYER_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::NEXT_PLAYER_REACTIONS, 
                "paid" => States::NEXT_PLAYER_EVENTS, 
            ]
        ],
    States::NEXT_PLAYER_SET_CURRENT_PLAYER => [
        "name" => "nextPlayerSetCurrentPlayer",
        "type" => "game",
        "action" => "stSetCurrentPlayer",
        "transitions" => ["" => States::HIGH_DRAMA_PLAYER_TURN]
    ],
            
    States::HIGH_DRAMA_END => [
        "name" => "highDramaEnd",
        "type" => "game",
        "action" => "stHighDramaEnd",
        "transitions" => ["" => States::HIGH_DRAMA_END_EVENTS]
    ],
        States::HIGH_DRAMA_END_EVENTS => [
            "name" => "highDramaEndEvents",
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::HIGH_DRAMA_END_REACTIONS,
                "endOfEvents" => States::PLUNDER_PHASE_BEGIN,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::HIGH_DRAMA_END_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::HIGH_DRAMA_END_EVENTS, 
                "pay" => States::HIGH_DRAMA_END_PAY_FOR_REACTION,
            ]
        ],
        States::HIGH_DRAMA_END_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::HIGH_DRAMA_END_REACTIONS, 
                "paid" => States::HIGH_DRAMA_END_EVENTS, 
            ]
        ],

    States::PLUNDER_PHASE_BEGIN => [
        "name" => "plunderPhaseBegin",
        "type" => "game",
        "action" => "stPlunderPhaseBegin",
        "transitions" => ["" => States::PLUNDER_PHASE_BEGIN_EVENTS]
    ],
        States::PLUNDER_PHASE_BEGIN_EVENTS => [
            "name" => "plunderPhaseBeginEvents",
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::PLUNDER_PHASE_BEGIN_REACTIONS,
                "endOfEvents" => States::PLUNDER_CHECK_DOMINANCE_VICTORY,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::PLUNDER_PHASE_BEGIN_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::PLUNDER_PHASE_BEGIN_EVENTS, 
                "pay" => States::PLUNDER_PHASE_BEGIN_PAY_FOR_REACTION,
            ]
        ],
        States::PLUNDER_PHASE_BEGIN_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::PLUNDER_PHASE_BEGIN_REACTIONS, 
                "paid" => States::PLUNDER_PHASE_BEGIN_EVENTS, 
            ]
        ],

    States::PLUNDER_CHECK_DOMINANCE_VICTORY => [
        "name" => "plunderCheckDominanceVictory",
        "type" => "game",
        "action" => "stPlunderCheckDominanceVictory",
        "transitions" => [
            "next" => States::PLUNDER_GAIN_REKNOWN,
            "endOfGame" => States::END_GAME
        ]
    ],
    States::PLUNDER_GAIN_REKNOWN => [
        "name" => "plunderGainReknown",
        "type" => "game",
        "action" => "stPlunderGainReknown",
        "transitions" => ["" => States::PLUNDER_GAIN_REKNOWN_EVENTS]
    ],
        States::PLUNDER_GAIN_REKNOWN_EVENTS => [
            "name" => "plunderGainReknownEvents",
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::PLUNDER_GAIN_REKNOWN_REACTIONS,
                "endOfEvents" => States::PLUNDER_CHECK_ECONOMIC_VICTORY,
                "endOfGame" => States::END_GAME]
        ],
        States::PLUNDER_GAIN_REKNOWN_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::PLUNDER_GAIN_REKNOWN_EVENTS, 
                "pay" => States::PLUNDER_GAIN_REKNOWN_PAY_FOR_REACTION,
            ]
        ],
        States::PLUNDER_GAIN_REKNOWN_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::PLUNDER_GAIN_REKNOWN_REACTIONS, 
                "paid" => States::PLUNDER_GAIN_REKNOWN_EVENTS, 
            ]
        ],

    States::PLUNDER_CHECK_ECONOMIC_VICTORY => [
        "name" => "plunderCheckEconomicVictory",
        "type" => "game",
        "action" => "stPlunderCheckEconomicVictory",
        "transitions" => [
            "next" => States::PLUNDER_CHECK_FIFTH_DAY_VICTORY,
            "endOfGame" => States::END_GAME
        ]
    ],
    States::PLUNDER_CHECK_FIFTH_DAY_VICTORY => [
        "name" => "plunderCheckFifthDayVictory",
        "type" => "game",
        "action" => "stPlunderCheckFifthDayVictory",
        "transitions" => [
            "next" => States::PLUNDER_PHASE_END,
            "endOfGame" => States::END_GAME
        ]
    ],
    States::PLUNDER_PHASE_END => [
        "name" => "plunderPhaseEnd",
        "type" => "game",
        "action" => "stPlunderPhaseEnd",
        "transitions" => ["" => States::PLUNDER_PHASE_END_EVENTS]
    ],
        States::PLUNDER_PHASE_END_EVENTS => [
            "name" => "plunderPhaseEndEvents",
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::PLUNDER_PHASE_END_REACTIONS,
                "endOfEvents" => States::DUSK_PHASE_BEGIN,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::PLUNDER_PHASE_END_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::PLUNDER_PHASE_END_EVENTS, 
                "pay" => States::PLUNDER_PHASE_END_PAY_FOR_REACTION,
            ]
        ],
        States::PLUNDER_PHASE_END_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::PLUNDER_PHASE_END_REACTIONS, 
                "paid" => States::PLUNDER_PHASE_END_EVENTS, 
            ]
        ],

    States::DUSK_PHASE_BEGIN => [
        "name" => "duskPhaseBegin",
        "type" => "game",
        "action" => "stDuskPhaseBegin",
        "transitions" => ["" => States::DUSK_PHASE_BEGIN_EVENTS]
    ],
        States::DUSK_PHASE_BEGIN_EVENTS => [
            "name" => "duskPhaseBeginEvents",
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "01177" => States::DUSK_PHASE_BEGIN_01177,
                "reaction" => States::DUSK_PHASE_BEGIN_REACTIONS,
                "endOfEvents" => States::DUSK_PHASE_CLEANUP,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::DUSK_PHASE_BEGIN_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::DUSK_PHASE_BEGIN_EVENTS, 
                "pay" => States::DUSK_PHASE_BEGIN_PAY_FOR_REACTION,
            ]
        ],
        States::DUSK_PHASE_BEGIN_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::DUSK_PHASE_BEGIN_REACTIONS, 
                "paid" => States::DUSK_PHASE_BEGIN_EVENTS, 
            ]
        ],

    States::DUSK_PHASE_CLEANUP => [
        "name" => "duskPhaseCleanup",
        "type" => "game",
        "action" => "stDuskPhaseCleanup",
        "transitions" => ["" => States::DUSK_PHASE_CLEANUP_EVENTS]
    ],
        States::DUSK_PHASE_CLEANUP_EVENTS => [
                "name" => "duskPhaseCleanupEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "reaction" => States::DUSK_PHASE_CLEANUP_REACTIONS,
                    "endOfEvents" => States::DUSK_PHASE_DISCARD,
                    "endOfGame" => States::END_GAME
                ]
            ],
        States::DUSK_PHASE_CLEANUP_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::DUSK_PHASE_CLEANUP_EVENTS, 
                "pay" => States::DUSK_PHASE_CLEANUP_PAY_FOR_REACTION,
            ]
        ],
        States::DUSK_PHASE_CLEANUP_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::DUSK_PHASE_CLEANUP_REACTIONS, 
                "paid" => States::DUSK_PHASE_CLEANUP_EVENTS, 
            ]
        ],

    States::DUSK_PHASE_DISCARD => [
        "name" => "duskPhaseDiscard",
        "description" => clienttranslate('Your opponent(s) must discard cards down to their Leader Panache value.'),
        "descriptionmyturn" => clienttranslate('${you} must discard cards down to your unmodified Leader Panache value:'),
        "type" => "multipleactiveplayer",
        "args" => "argsEmpty",
        "action" => "stDuskPhaseDiscard",
        "possibleactions" => [
            "actDuskPhaseCardsDiscarded", 
        ],
        "transitions" => ["cardsDiscarded" => States::DUSK_PHASE_DISCARD_EVENTS]
    ],
        States::DUSK_PHASE_DISCARD_EVENTS => [
            "name" => "duskPhaseDiscardEvents",
            "type" => "game",
            "action" => "stDuskPhaseDiscardEvents",
            "transitions" => [
                "reaction" => States::DUSK_PHASE_DISCARD_REACTIONS,
                "endOfEvents" => States::DUSK_PHASE_END,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::DUSK_PHASE_DISCARD_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::DUSK_PHASE_DISCARD_EVENTS, 
                "pay" => States::DUSK_PHASE_DISCARD_PAY_FOR_REACTION,
            ]
        ],
        States::DUSK_PHASE_DISCARD_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::DUSK_PHASE_DISCARD_REACTIONS, 
                "paid" => States::DUSK_PHASE_DISCARD_EVENTS, 
            ]
        ],

    States::DUSK_PHASE_END => [
        "name" => "duskPhaseEnd",
        "type" => "game",
        "action" => "stDuskPhaseEnd",
        "transitions" => ["" => States::DUSK_PHASE_END_EVENTS]
    ],
        States::DUSK_PHASE_END_EVENTS => [
            "name" => "duskPhaseEndEvents",
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::DUSK_PHASE_END_REACTIONS,
                "endOfEvents" => States::DUSK_END_OF_DAY,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::DUSK_PHASE_END_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::DUSK_PHASE_END_EVENTS, 
                "pay" => States::DUSK_PHASE_END_PAY_FOR_REACTION,
            ]
        ],
        States::DUSK_PHASE_END_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::DUSK_PHASE_END_REACTIONS, 
                "paid" => States::DUSK_PHASE_END_EVENTS, 
            ]
        ],

    States::DUSK_END_OF_DAY => [
        "name" => "duskEndOfDay",
        "type" => "game",
        "action" => "stDuskEndOfDay",
        "transitions" => ["" => States::DUSK_END_OF_DAY_EVENTS]
    ],
        States::DUSK_END_OF_DAY_EVENTS => [
            "name" => "duskEndOfDayEvents",
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "reaction" => States::DUSK_END_OF_DAY_REACTIONS,
                "endOfEvents" => States::DAWN_NEW_DAY,
                "endOfGame" => States::END_GAME
            ]
        ],
        States::DUSK_END_OF_DAY_REACTIONS => [
            "name" => "playerReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actReactionForState", 
            ],
            "transitions" => [
                "done" => States::DUSK_END_OF_DAY_EVENTS, 
                "pay" => States::DUSK_END_OF_DAY_PAY_FOR_REACTION,
            ]
        ],
        States::DUSK_END_OF_DAY_PAY_FOR_REACTION => [
            "name" => "playerPayForReaction",
            "description" => clienttranslate('${actplayer} is choosing Reaction options.'),
            "descriptionmyturn" => "",
            "type" => "activeplayer",
            "args" => "argsForStatePrivate",
            "possibleactions" => [
                "actBack",
                "actPayForReaction", 
            ],
            "transitions" => [
                "back" => States::DUSK_END_OF_DAY_REACTIONS, 
                "paid" => States::DUSK_END_OF_DAY_EVENTS, 
            ]
        ],
                                        
    // Final state.
    // Please do not modify (and do not overload action/args methods).
    States::END_GAME => [
        "name" => "gameEnd",
        "description" => clienttranslate("End of game"),
        "type" => "manager",
        "action" => "stGameEnd",
        "updateGameProgression" => true,
        "args" => "argGameEnd"
    ],

];

include("states.7s5s.php");
