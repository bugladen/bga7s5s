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
                "01150" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01150,
                "01152" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152,
                "endOfEvents" => States::PLANNING_PHASE_DRAW
            ]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_01016 => [
            "name" => "planningPhaseResolveSchemes_01016",
            "description" => clienttranslate('Plans Within Plans: ${actplayer} must choose two city locations to place Reknown onto.'),
            "descriptionmyturn" => clienttranslate('Plans Within Plans: ${you} must choose two city locations to place Reknown onto:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actCityLocationsForReknownSelected", 
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_2]
        ],
            States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_2 => [
                "name" => "planningPhaseResolveSchemes_01016_2",
                "description" => clienttranslate('Plans Within Plans: ${actplayer} may search their deck for a Red Hand Thug.'),
                "descriptionmyturn" => clienttranslate('Plans Within Plans: ${you} may choose a Red Hand Thug from Your Deck:'),
                "type" => "activeplayer",
                "args" => "argsPlanningPhaseResolveSchemes_01016_2",
                "possibleactions" => [
                    "actPassWithPass",
                    "actPlanningPhase_01016_2", 
                ],
                "transitions" => [
                    "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                    "cardChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_3
                ]
            ],
            States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_3 => [
                "name" => "planningPhaseResolveSchemes_01016_3",
                "description" => clienttranslate('Plans Within Plans: Your opponent(s) must acknowlege revealed card.'),
                "descriptionmyturn" => clienttranslate('Plans Within Plans: ${you} must must acknowlege revealed card:'),
                "type" => "multipleactiveplayer",
                "args" => "argsPlanningPhaseResolveSchemes_01016_3",
                "action" => "stMultiPlayerInit",
                "possibleactions" => [
                    "actMultipleOk", 
                ],
                "transitions" => ["multipleOk" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
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
            States::PLANNING_PHASE_RESOLVE_SCHEMES_01071 => [
                "name" => "planningPhaseResolveSchemes_01071",
                "description" => clienttranslate('Épée Sanglante: ${actplayer} must choose a city location to place Reknown onto.'),
                "descriptionmyturn" => clienttranslate('Épée Sanglante: ${you} must choose a city location to place Reknown onto:'),
                "type" => "activeplayer",
                "args" => "argsEmpty",
                "possibleactions" => [
                    "actCityLocationsForReknownSelected", 
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

        States::PLANNING_PHASE_RESOLVE_SCHEMES_01143 => [
            "name" => "planningPhaseResolveSchemes_01143",
            "description" => clienttranslate('Contempt and Hatred: ${actplayer} may choose a city location to place Reknown onto.'),
            "descriptionmyturn" => clienttranslate('Contempt and Hatred: ${you} may choose a city location to place Reknown onto:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPass",
                "actPlanningPhase_01143", 
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
    
        States::PLANNING_PHASE_RESOLVE_SCHEMES_01150 => [
            "name" => "planningPhaseResolveSchemes_01150",
            "description" => clienttranslate('Parley Gone Wrong: ${actplayer} may choose a city location to move a Reknown from.'),
            "descriptionmyturn" => clienttranslate('Parley Gone Wrong: ${you} may choose a city location to move a Reknown from:'),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01150", 
                "actPass" 
            ],
            "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_01152 => [
            "name" => "planningPhaseResolveSchemes_01152",
            "description" => clienttranslate('Until Morale Improves: ${actplayer} may choose a City Location to place a Reknown onto.'),
            "descriptionmyturn" => clienttranslate('Until Morale Improves: ${you} may choose a City Location to place a Reknown onto: '),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actPlanningPhase_01152",
                "actPassWithPass"
            ],
            "transitions" => [
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2,
                "reknownPlaced" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS
                ]
            ],
            States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2 => [
                "name" => "planningPhaseResolveSchemes_01152_2",
                "description" => clienttranslate('Until Morale Improves: ${actplayer} must choose a City Location to move a Reknown FROM, if able.'),
                "descriptionmyturn" => clienttranslate('Until Morale Improves: ${you} must choose a City Location to move a Reknown FROM, if able: '),
                "type" => "activeplayer",
                "args" => "argsEmpty",
                "possibleactions" => [
                    "actPlanningPhase_01152_2",
                    "actPassWithPass"
                ],
                "transitions" => [
                    "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                    "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3
                    ]
                ],
                States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3 => [
                    "name" => "planningPhaseResolveSchemes_01152_3",
                    "description" => clienttranslate('Until Morale Improves: ${actplayer} must choose an adjacent City Location to move the Reknown TO.'),
                    "descriptionmyturn" => clienttranslate('Until Morale Improves: ${you} must choose an adjacent City Location to move the Reknown TO:'),
                    "type" => "activeplayer",
                    "args" => "argsPlanningPhaseResolveSchemes_01152_3",
                    "possibleactions" => ["actPlanningPhase_01152_3"],
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
        "possibleactions" => [
            "actHighDramaChallengeActionStart",
            "actHighDramaClaimActionStart",
            "actHighDramaEquipActionStart",
            "actHighDramaMoveActionStart", 
            "actHighDramaRecruitActionStart",
            "actPassWithPass",
        ],
        "transitions" => [
            "challengeActionStart" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_PERFORMER,
            "claimActionStart" => States::HIGH_DRAMA_CLAIM_ACTION_CHOOSE_PERFORMER,
            "equipActionStart" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_PERFORMER,
            "moveActionStart" => States::HIGH_DRAMA_MOVE_ACTION_CHOOSE_PERFORMER, 
            "recruitActionStart" => States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_PERFORMER,
            "pass" => States::NEXT_PLAYER
        ]
    ],
        States::HIGH_DRAMA_PLAYER_TURN_EVENTS => [
            "name" => "highDramaPlayerTurnEvents",
            "description" => clienttranslate("Resolving Events for High Drama Player Turn..."),
            "type" => "game",
            "action" => "stRunEvents",
            "transitions" => [
                "endOfEvents" => States::NEXT_PLAYER
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
            ],
            "transitions" => [
                "targetChosen" => States::HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE, 
                "back" => States::HIGH_DRAMA_CHALLENGE_ACTION_CHOOSE_PERFORMER
            ]
        ],
        States::HIGH_DRAMA_CHALLENGE_ACTION_TECHNIQUE_AVAILABLE => [
            "name" => "highDramaChallengeActionTechniqueAvailable",
            "type" => "game",
            "action" => "stTechniqueAvailable",
            "transitions" => [
                "hasTechique" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE,
                "noTechnique" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_CHALLENGE
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
                    "endOfEvents" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE
                    ]
            ],
    
        States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE => [
            "name" => "highDramaChallengeActionSetupChallenge",
            "type" => "game",
            "action" => "stSetupChallenge",
            "transitions" => [
                "challengeSetUp" => States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_EVENTS,
                "challengeFailed" => States::HIGH_DRAMA_PLAYER_TURN
            ]
        ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_SETUP_CHALLENGE_EVENTS => [
                "name" => "highDramaChallengeActionSetupChallengeEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "endOfEvents" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_CHALLENGE
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
                "" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE,
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
                    "01067b" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS_01067b,
                    "endOfEvents" => States::HIGH_DRAMA_CHALLENGE_ACTION_GENERATE_THREAT
                    ]
            ],
                States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS_01067b => [
                    "name" => "highDramaChallengeActionResolveTechnique_01067b",
                    "description" => clienttranslate('${actplayer} is choosing options to resolve Jean Urbain\'s Technique.'),
                    "descriptionmyturn" => clienttranslate('Jean Urbain: +1 Thrust or Riposte: ${you} may choose Thrust or Riposte:'),
                    "type" => "activeplayer",
                    "args" => "argsEmpty",
                    "possibleactions" => [
                        "actHighDramaChallengeActionResolveTechnique_01067b", 
                    ],
                    "transitions" => [
                        "" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS,
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
                    "endOfEvents" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLUTION
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
                    "endOfEvents" => States::HIGH_DRAMA_PLAYER_TURN
                    ]
            ],
            States::HIGH_DRAMA_CHALLENGE_ACTION_ACCEPT_EVENTS => [
                "name" => "highDramaChallengeActionAcceptEvents",
                "type" => "game",
                "action" => "stRunEvents",
                "transitions" => [
                    "endOfEvents" => States::DUEL_STARTED
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
                "performerChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS, 
                "back" => States::HIGH_DRAMA_PLAYER_TURN
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
            "descriptionmyturn" => clienttranslate('${you} are performing an Equip Action.  Choose an Attachment Location to equip from:'),
            "type" => "activeplayer",
            "args" => "argsHighDramaEquipActionChooseAttachmentLocation",
            "possibleactions" => [
                "actHighDramaEquipAttachment", 
                "actBack",
            ],
            "transitions" => [
                "attachmentEquipped" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS, 
                "back" => States::HIGH_DRAMA_EQUIP_ACTION_CHOOSE_PERFORMER
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
                "args" => "argsHighDramaMoveActionChooseDestination",
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
                "updateGameProgression" => false,
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
                    "mercenaryChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS, 
                    "back" => States::HIGH_DRAMA_RECRUIT_ACTION_CHOOSE_PERFORMER
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
                    "endOfEvents" => States::DUEL_NEW_ROUND
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
                    "endOfEvents" => States::DUEL_CHOOSE_ACTION
                    ]
            ],
        States::DUEL_CHOOSE_ACTION => [
            "name" => "duelChooseAction",
            "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
            "descriptionmyturn" => clienttranslate('${you} must choose a Duel Action:'),
            "type" => "activeplayer",
            "args" => "argsChooseDuelAction",
            "possibleactions" => [
                "actDuelActionChooseTechnique"
            ],
            "transitions" => [
                "chooseTechnique" => States::DUEL_CHOOSE_TECHNIQUE
            ]
        ],
            States::DUEL_CHOOSE_TECHNIQUE => [
                "name" => "duelChooseTechnique",
                "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
                "descriptionmyturn" => clienttranslate('${you} must choose a Technique for this Round:'),
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
                States::DUEL_CHOOSE_TECHNIQUE_EVENTS => [
                    "name" => "duelChooseTechniqueEvents",
                    "type" => "game",
                    "action" => "stRunEvents",
                    "transitions" => [
                        "01013" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS_01013,
                        "endOfEvents" => States::DUEL_CHOOSE_ACTION
                        ]
                ],
                States::DUEL_CHOOSE_TECHNIQUE_EVENTS_01013 => [
                    "name" => "duelActionResolveTechnique_01013",
                    "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
                    "descriptionmyturn" => clienttranslate('Vissenta Scarpa: Add Parry or Thrust: ${you} must choose Parry or Thrust:'),
                    "type" => "activeplayer",
                    "args" => "argsEmpty",
                    "possibleactions" => [
                        "actDuelActionResolveTechnique_01013", 
                    ],
                    "transitions" => [
                        "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
                    ]
                ],
            
    States::NEXT_PLAYER => [
        "name" => "nextPlayer",
        "description" => '',
        "type" => "game",
        "action" => "stNextPlayer",
        "updateGameProgression" => true,
        "transitions" => [
            "endGame" => States::END_GAME, 
            "nextPlayer" => States::HIGH_DRAMA_PLAYER_TURN
        ]
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



