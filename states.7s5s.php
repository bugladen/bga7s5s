<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

$machinestates += [

    States::SETUP_TABLE_01006 => [
        "name" => "setupTable_01006",
        "description" => clienttranslate('Don Contanzo Scarpa') . clienttranslate(': ${actplayer} is choosing a Red Hand Thug from their Faction Deck to reveal and place in their Hand.'),
        "descriptionmyturn" => clienttranslate('Don Contanzo Scarpa') . clienttranslate(': ${you} must choose a Red Hand Thug from your Faction Deck to reveal and place in your Hand:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass"
        ],
        "transitions" => [
            "cardChosen" => States::SETUP_TABLE_01006_2,
            "pass" => States::SETUP_TABLE_EVENTS
        ]
    ],

    States::SETUP_TABLE_01006_2 => [
        "name" => "setupTable_01006_2",
        "description" => clienttranslate('Don Contanzo Scarpa') . clienttranslate(': Your opponent(s) must acknowlege revealed card.'),
        "descriptionmyturn" => clienttranslate('Don Contanzo Scarpa') . clienttranslate(': ${you} must must acknowlege revealed card:'),
        "type" => "multipleactiveplayer",
        "args" => "argsForState",
        "action" => "stMultiPlayerInitSansInitiatingPlayer",
        "possibleactions" => [
            "actMultipleOk", 
        ],
        "transitions" => ["multipleOk" => States::SETUP_TABLE_EVENTS]
    ],

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01016 => [
        "name" => "planningPhaseResolveSchemes_01016",
        "description" => clienttranslate('Plans Within Plans') . clienttranslate(': ${actplayer} must choose two city locations to place Reknown onto.'),
        "descriptionmyturn" => clienttranslate('Plans Within Plans') . clienttranslate(': ${you} must choose two city locations to place Reknown onto:'),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actCityLocationsForReknownSelected", 
        ],
        "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_2]
    ],    
    States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_2 => [
        "name" => "planningPhaseResolveSchemes_01016_2",
        "description" => clienttranslate('Plans Within Plans') . clienttranslate(': ${actplayer} may search their deck for a Red Hand Thug.'),
        "descriptionmyturn" => clienttranslate('Plans Within Plans') . clienttranslate(': ${you} may choose a Red Hand Thug from Your Deck:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardPass",
            "actFromCardWithId", 
        ],
        "transitions" => [
            "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
            "cardChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_3
        ]
    ],
    States::PLANNING_PHASE_RESOLVE_SCHEMES_01016_3 => [
        "name" => "planningPhaseResolveSchemes_01016_3",
        "description" => clienttranslate('Plans Within Plans') . clienttranslate(': Your opponent(s) must acknowlege revealed card.'),
        "descriptionmyturn" => clienttranslate('Plans Within Plans') . clienttranslate(': ${you} must must acknowlege revealed card:'),
        "type" => "multipleactiveplayer",
        "args" => "argsPlanningPhaseResolveSchemes_01016_3",
        "action" => "stMultiPlayerInitSansInitiatingPlayer",
        "possibleactions" => [
            "actMultipleOk", 
        ],
        "transitions" => ["multipleOk" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
    ],

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01044 => [
        "name" => "planningPhaseResolveSchemes_01044",
        "description" => clienttranslate('Armed and Marshaled') . clienttranslate(': ${actplayer} must choose an Attachment from their discard pile if able.'),
        "descriptionmyturn" => clienttranslate('Armed and Marshaled') . clienttranslate(': ${you} must choose an Attachment from your discard pile if able:'),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actFromCardWithId", 
            "actFromCardPass"
        ],
        "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
    ],

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01045 => [
        "name" => "planningPhaseResolveSchemes_01045",
        "description" => clienttranslate('The Song of Eisen') . clienttranslate(': ${actplayer} must choose a Mercenary from the City Deck discard pile if able.'),
        "descriptionmyturn" => clienttranslate('The Song of Eisen') . clienttranslate(': ${you} must choose a Mercenary from the City Deck discard pile if able:'),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actFromCardWithId", 
            "actFromCardPass"
        ],
        "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
    ],
    
    States::PLANNING_PHASE_RESOLVE_SCHEMES_01071 => [
        "name" => "planningPhaseResolveSchemes_01071",
        "description" => clienttranslate('Épée Sanglante') . clienttranslate(': ${actplayer} must choose a city location to place Reknown onto.'),
        "descriptionmyturn" => clienttranslate('Épée Sanglante') . clienttranslate(': ${you} must choose a city location to place Reknown onto:'),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actCityLocationsForReknownSelected", 
        ],
        "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
    ],

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01072 => [
        "name" => "planningPhaseResolveSchemes_01072",
        "description" => clienttranslate('Réputation Méritée') . clienttranslate(': ${actplayer} must choose a city location to place Reknown onto that doesn\'t have any.'),
        "descriptionmyturn" => clienttranslate('Réputation Méritée') . clienttranslate(': ${you} must choose a city location to place Reknown onto that doesn\'t have any:'),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actFromCardWithLocations", 
            "actFromCardPass"
        ],
        "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
    ],

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01143 => [
        "name" => "planningPhaseResolveSchemes_01143",
        "description" => clienttranslate('Contempt and Hatred') . clienttranslate(': ${actplayer} may choose a city location to place Reknown onto.'),
        "descriptionmyturn" => clienttranslate('Contempt and Hatred') . clienttranslate(': ${you} may choose a city location to place Reknown onto:'),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actPass",
            "actFromCardWithLocations", 
        ],
        "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
    ],

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01147 => [
        "name" => "planningPhaseResolveSchemes_01147",
        "description" => clienttranslate('Let\'s Haggle') . clienttranslate(': Your opponent(s) must acknowledge revealed cards.'),
        "descriptionmyturn" => clienttranslate('Let\'s Haggle') . clienttranslate(': ${you} must acknowledge revealed cards:'),
        "type" => "multipleactiveplayer",
        "args" => "argsForState",
        "action" => "stMultiPlayerInit",
        "possibleactions" => [
            "actMultipleOk", 
        ],
        "transitions" => ["multipleOk" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
    ],
    States::PLANNING_PHASE_RESOLVE_SCHEMES_01147_2 => [
        "name" => "planningPhaseResolveSchemes_01147_2",
        "type" => "game",
        "action" => "stFromCard",
        "transitions" => [
            "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS
        ]
    ],

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01150 => [
        "name" => "planningPhaseResolveSchemes_01150",
        "description" => clienttranslate('Parley Gone Wrong') . clienttranslate(': ${actplayer} may choose a city location to move a Reknown from.'),
        "descriptionmyturn" => clienttranslate('Parley Gone Wrong') . clienttranslate(': ${you} may choose a city location to move a Reknown from:'),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actFromCardWithLocations", 
            "actFromCardPass" 
        ],
        "transitions" => ["" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS]
    ],

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01152 => [
        "name" => "planningPhaseResolveSchemes_01152",
        "description" => clienttranslate('Until Morale Improves') . clienttranslate(': ${actplayer} may choose a City Location to place a Reknown onto.'),
        "descriptionmyturn" => clienttranslate('Until Morale Improves') . clienttranslate(': ${you} may choose a City Location to place a Reknown onto: '),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actFromCardWithLocations",
            "actPassWithPass"
        ],
        "transitions" => [
            "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2,
            "reknownPlaced" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS
            ]
        ],
        States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2 => [
            "name" => "planningPhaseResolveSchemes_01152_2",
            "description" => clienttranslate('Until Morale Improves') . clienttranslate(': ${actplayer} must choose a City Location to move a Reknown FROM, if able.'),
            "descriptionmyturn" => clienttranslate('Until Morale Improves') . clienttranslate(': ${you} must choose a City Location to move a Reknown FROM, if able: '),
            "type" => "activeplayer",
            "args" => "argsEmpty",
            "possibleactions" => [
                "actFromCardWithLocations",
                "actPassWithPass"
            ],
            "transitions" => [
                "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
                "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3
                ]
            ],
            States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_3 => [
                "name" => "planningPhaseResolveSchemes_01152_3",
                "description" => clienttranslate('Until Morale Improves') . clienttranslate(': ${actplayer} must choose an adjacent City Location to move the Reknown TO.'),
                "descriptionmyturn" => clienttranslate('Until Morale Improves') . clienttranslate(': ${you} must choose an adjacent City Location to move the Reknown TO:'),
                "type" => "activeplayer",
                "args" => "argsForState",
                "possibleactions" => [
                    "actBack",
                    "actFromCardWithLocations"
                ],
                "transitions" => [
                    "back" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01152_2,
                    "locationChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS
                ]
            ],

    States::HIGH_DRAMA_PLAYER_TURN_01008 => [
        "name" => "highDramaPhase01008",
        "description" => clienttranslate('Cesca Del Rosso') . clienttranslate(': Your opponent(s) must acknowlege revealed card.'),
        "descriptionmyturn" => clienttranslate('Cesca Del Rosso') . clienttranslate(': ${you} must must acknowlege revealed card:'),
        "type" => "multipleactiveplayer",
        "args" => "argsForState",
        "action" => "stMultiPlayerInitSansInitiatingPlayer",
        "possibleactions" => [
            "actMultipleOk", 
        ],
        "transitions" => ["multipleOk" => States::HIGH_DRAMA_PLAYER_TURN_01008_2]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01008_2 => [
        "name" => "highDramaPhase01008_2",
        "type" => "game",
        "action" => "stSetCurrentPlayer",
        "transitions" => ["" => States::HIGH_DRAMA_PLAYER_TURN_01008_3]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01008_3 => [
        "name" => "highDramaPhase01008_3",
        "type" => "game",
        "action" => "stFromCard",
        "transitions" => [
            "cardDrawn" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            "choose" => States::HIGH_DRAMA_PLAYER_TURN_01008_4]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01008_4 => [
        "name" => "highDramaPhase01008_4",
        "description" => clienttranslate('Cesca Del Rosso') . clienttranslate(': ${actplayer} may choose to sink revealed card.'),
        "descriptionmyturn" => clienttranslate('Cesca Del Rosso') . clienttranslate(': ${you} may choose to sink revealed card:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actPass",
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01011 => [
        "name" => "highDramaPhase01011",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Servo Scarpa') . clienttranslate(': ${you} must choose an adjacent opposing character to challenge: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER,
            "opposingCharacterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01012 => [
        "name" => "highDramaPhase01012",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Sibella Scarpa') . clienttranslate(': ${you} must choose an opposing character to wound: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [  
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "opposingCharacterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01015 => [
        "name" => "highDramaPhase01015",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('The Great Game') . clienttranslate(': ${you} must choose a character to wound:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01029 => [
        "name" => "highDramaPhase01029",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('The Pressure Is On') . clienttranslate(': ${you} must choose a character to target:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass"
        ],
        "transitions" => [
            "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01035 => [
        "name" => "highDramaPhase01035",
        "description" => clienttranslate('Your opponent(s) must acknowledge revealed cards.'),
        "descriptionmyturn" => clienttranslate('Kaspar') . clienttranslate(': ${you} must acknowledge revealed cards:'),
        "type" => "multipleactiveplayer",
        "args" => "argsForState",
        "action" => "stMultiPlayerInit",
        "possibleactions" => [
            "actMultipleOk", 
        ],
        "transitions" => ["multipleOk" => States::HIGH_DRAMA_PLAYER_TURN_01035_2]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01035_2 => [
        "name" => "highDramaPhase01035_2",
        "type" => "game",
        "action" => "stFromCard",
        "transitions" => [
            "found" => States::HIGH_DRAMA_PLAYER_TURN_01035_3, 
            "notFound" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01035_3 => [
        "name" => "highDramaPhase01035_3",
        "description" => clienttranslate('${actplayer} is choosing whether to recruit the revealed mercenary.'),
        "descriptionmyturn" => clienttranslate('Kaspar') . clienttranslate(': ${you} must choose whether to recruit the revealed mercenary:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass"
        ],
        "transitions" => [
            "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            "recruit" => States::HIGH_DRAMA_PLAYER_TURN_01035_4
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01035_4 => [
        "name" => "highDramaPhase01035_4",
        "description" => clienttranslate('${actplayer} is choosing whether to parley with the revealed mercenary.'),
        "descriptionmyturn" => clienttranslate('Kaspar') . clienttranslate(': ${you} must choose whether to parley with the revealed mercenary:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01035_3,
            "parleyChosen" => States::HIGH_DRAMA_RECRUIT_ACTION_PAY_FOR_MERCENARY
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01038 => [
        "name" => "highDramaPhase01038",
        "description" => clienttranslate('Your opponent(s) must acknowledge revealed cards.'),
        "descriptionmyturn" => clienttranslate('Otto Streit') . clienttranslate(': ${you} must acknowledge revealed cards:'),
        "type" => "multipleactiveplayer",
        "args" => "argsForState",
        "action" => "stMultiPlayerInit",
        "possibleactions" => [
            "actMultipleOk", 
        ],
        "transitions" => ["multipleOk" => States::HIGH_DRAMA_PLAYER_TURN_01038_2]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01038_2 => [
        "name" => "highDramaPhase01038_2",
        "type" => "game",
        "action" => "stSetCurrentPlayer",
        "transitions" => ["" => States::HIGH_DRAMA_PLAYER_TURN_01038_3]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01038_3 => [
        "name" => "highDramaPhase01038_3",
        "description" => clienttranslate('${actplayer} may choose an Attachment to put into their Faction Hand.'),
        "descriptionmyturn" => clienttranslate('${you} may choose an Attachment to put into your Faction Hand:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass"   
        ],
        "transitions" => [
            "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],



    States::HIGH_DRAMA_PLAYER_TURN_01044 => [
        "name" => "highDramaPhase01044",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Armed and Marshaled') . clienttranslate(': ${you} must choose an attachment to Engage on the selected performer: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER,
            "attachmentChosen" => States::HIGH_DRAMA_PLAYER_TURN_01044_2
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01044_2 => [
        "name" => "highDramaPhase01044_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Armed and Marshaled') . clienttranslate(': ${you} must choose an opposing character to manipulate: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01044,
            "opposingCharacterChosen" => States::HIGH_DRAMA_PLAYER_TURN_01044_3
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01044_3 => [
        "name" => "highDramaPhase01044_3",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Armed and Marshaled') . clienttranslate(': ${you} must choose how to manipulate the chosen character: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01044_2,
            "manipulationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01046a => [
        "name" => "highDramaPhase01046a",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Dark Gift') . clienttranslate(': ${you} must choose a location to move to: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithLocations"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01049 => [
        "name" => "highDramaPhase01049",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Polished Flintlock') . clienttranslate(': ${you} must choose a character to manipulate: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01049_2 => [
        "name" => "highDramaPhase01049_2",
        "description" => clienttranslate('${actplayer} is choosing whether to engage the target character.'),
        "descriptionmyturn" => clienttranslate('Polished Flintlock') . clienttranslate(': ${you} must choose to engage target character: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01055 => [
        "name" => "highDramaPhase01055",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Last Word') . clienttranslate(': ${you} must choose a character to move: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_01055_2
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01055_2 => [
        "name" => "highDramaPhase01055_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Last Word') . clienttranslate(': ${you} must choose a location to move the target character to: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithLocations"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01055,
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01056 => [
        "name" => "highDramaPhase01056",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Move Along') . clienttranslate(': ${you} must choose a character to confront: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01056_2 => [
        "name" => "highDramaPhase01056_2",
        "description" => clienttranslate('${actplayer} is choosing whether to have their target character Move Home or continue with Challenge.'),
        "descriptionmyturn" => clienttranslate('Move Along') . clienttranslate(': ${you} must choose whether to have your target character Move Home or continue with Challenge: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01058 => [
        "name" => "highDramaPhase01058",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Press the Advantage') . clienttranslate(': ${you} must choose a character to move HOME: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01059 => [
        "name" => "highDramaPhase01059",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Regroup') . clienttranslate(': ${you} must choose a location to move to: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithLocations"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01060 => [
        "name" => "highDramaPhase01060",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Stratege') . clienttranslate(': ${you} must choose a location with Performers to move: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithLocations"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_01060_2
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01060_2 => [
        "name" => "highDramaPhase01060_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Stratege') . clienttranslate(': ${you} must choose up to two Performers to move: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithIds"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01060,
            "performersChosen" => States::HIGH_DRAMA_PLAYER_TURN_01060_3
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01060_3 => [
        "name" => "highDramaPhase01060_3",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Stratege') . clienttranslate(': ${you} must choose a new location for your Performers: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithLocations"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01060_2,
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01068 => [
        "name" => "highDramaPhase01068",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Léontine Giroux') . clienttranslate(': ${you} must choose a character to move: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_01068_2
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01068_2 => [
        "name" => "highDramaPhase01068_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Léontine Giroux') . clienttranslate(': ${you} must choose a location to move the target character to: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithLocations"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01068,
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01069 => [
        "name" => "highDramaPhase01069",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Maxime De Lafayette') . clienttranslate(': ${you} must discard a card from your Faction Hand: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_01069_2
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01069_2 => [
        "name" => "highDramaPhase01069_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Maxime De Lafayette') . clienttranslate(': ${you} must choose an attachment to recover from your discard pile: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01069,
            "attachmentChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01072 => [
        "name" => "highDramaPhase01072",
        "type" => "game",
        "action" => "stFromCard",
        "transitions" => [
            "success" => States::HIGH_DRAMA_PLAYER_TURN_01072_2,
            "failure" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01072_2 => [
        "name" => "highDramaPhase01072_2",
        "description" => clienttranslate('${actplayer} is choosing a City Card to discard.'),
        "descriptionmyturn" => clienttranslate('Réputation Méritée') . clienttranslate(': ${you} must choose a City Card to discard: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_01072_3
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01072_3 => [
        "name" => "highDramaPhase01072_3",
        "description" => clienttranslate('${actplayer} is choosing a card to Muster.'),
        "descriptionmyturn" => clienttranslate('Réputation Méritée') . clienttranslate(': ${you} must choose a card to Muster: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01072_2,
            "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
        
    States::HIGH_DRAMA_PLAYER_TURN_01076 => [
        "name" => "highDramaPhase01076",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Blood Mark') . clienttranslate(': ${you} must choose a City location to move Performer: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithLocations"
        ],
        "transitions" => [
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_01076_2
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01076_2 => [
        "name" => "highDramaPhase01076_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Blood Mark') . clienttranslate(': ${you} may Wound Performer and choose Character to include: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01076,
            "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01081 => [
        "name" => "highDramaPhase01081",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Gallant Deeds') . clienttranslate(': ${you} must choose an Opposing Character to Engage:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01085 => [
        "name" => "highDramaPhase01085",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Porté Travel') . clienttranslate(': ${you} must choose a character to move:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01086 => [
        "name" => "highDramaPhase01086",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Status Matters') . clienttranslate(': ${you} must choose a location to make Uncontrolled:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithLocations"
        ],
        "transitions" => [
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
        ]
    ],
    
    States::HIGH_DRAMA_PLAYER_TURN_01147 => [
        "name" => "highDramaPhase01147",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Let\'s Haggle') . clienttranslate(': ${you} must choose an attachment to Equip from the Bazaar: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER,
            "attachmentSelected" => States::HIGH_DRAMA_EQUIP_ACTION_PAY_FOR_ATTACHMENT_FROM_PLAY
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01148 => [
        "name" => "highDramaPhase01148",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a Character to Engage or Wound: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER,
            "mercenaryChosen" => States::HIGH_DRAMA_PLAYER_TURN_01148_2
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01148_2 => [
        "name" => "highDramaPhase01148_2",
        "type" => "game",
        "action" => "stFromCard",
        "transitions" => [
            "proceed" => States::HIGH_DRAMA_PLAYER_TURN_01148_3,
            "cancel" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01148_3 => [
        "name" => "highDramaPhase01148_3",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a card to discard or click Finished: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actFinished"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01148_4 => [
        "name" => "highDramaPhase01148_4",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must must choose an option to manipulate the target Character: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01149 => [
        "name" => "highDramaPhase01149",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Midnight Shipment') . clienttranslate(': ${you} must choose a location to move chosen Performer: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithLocations",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER,
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01152a => [
        "name" => "highDramaPhase01152a",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Until Morale Improves') . clienttranslate(': ${you} must choose a character to En Garde: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER,
            "targetChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01152b => [
        "name" => "highDramaPhase01152b",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Until Morale Improves') . clienttranslate(': ${you} must choose a character to Engage: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER,
            "targetChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],


    States::HIGH_DRAMA_PLAYER_TURN_01156 => [
        "name" => "highDramaPhase01156",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Matchlock Musket') . clienttranslate(': ${you} must choose a card to discard: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_PERFORMER,
            "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_01156_2
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01156_2 => [
        "name" => "highDramaPhase01156_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Matchlock Musket') . clienttranslate(': ${you} must choose a character to target: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01156_3 => [
        "name" => "highDramaPhase01156_3",
        "description" => clienttranslate('${actplayer} is choosing whether to Engage targeted Character or Wound them.'),
        "descriptionmyturn" => clienttranslate('Matchlock Musket') . clienttranslate(': ${you} must choose whether to Engage or Wound your targeted Character: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01180 => [
        "name" => "highDramaPhase01180",
        "description" => clienttranslate('Your opponent(s) must acknowledge revealed cards.'),
        "descriptionmyturn" => clienttranslate('Kaj Kousei') . clienttranslate(': ${you} must acknowledge revealed cards:'),
        "type" => "multipleactiveplayer",
        "args" => "argsForState",
        "action" => "stMultiPlayerInit",    
        "possibleactions" => [
            "actMultipleOk", 
        ],
        "transitions" => ["multipleOk" => States::HIGH_DRAMA_PLAYER_TURN_01180_2]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01180_2 => [
        "name" => "highDramaPhase01180_2",
        "type" => "game",
        "action" => "stSetCurrentPlayer",
        "transitions" => ["" => States::HIGH_DRAMA_PLAYER_TURN_01180_3]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01180_3 => [
        "name" => "highDramaPhase01180_3",
        "description" => clienttranslate('${actplayer} may choose to Equip an Artifact from the top 4 cards of the City Deck.'),
        "descriptionmyturn" => clienttranslate('${you} may choose to Equip an Artifact from the top 4 cards of the City Deck:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass"
        ],
        "transitions" => [
            "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_01180_4,
            "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01180_4 => [
        "name" => "highDramaPhase01180_4",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a Performer to Equip Artifact:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01180,
            "performerChosen" => States::HIGH_DRAMA_PLAYER_TURN_01180_5
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01180_5 => [
        "name" => "highDramaPhase01180_5",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose cards from Your Hand to pay for chosen Artifact:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithIds",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01180_4,
            "artifactEquipped" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01185 => [
        "name" => "highDramaPhase01185",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Risky Undertaking') . clienttranslate(': ${you} must discard two cards to activate:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithIds",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "cardsDiscarded" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01189a => [
        "name" => "highDramaPhase01189a",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a location to move a Reknown FROM:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithLocations",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01189b => [
        "name" => "highDramaPhase01189b",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a location to move a Reknown TO:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithLocations",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01192 => [
        "name" => "highDramaPhase01192",
        "description" => clienttranslate('Your opponent(s) must acknowledge revealed cards.'),
        "descriptionmyturn" => clienttranslate('Gustavo') . clienttranslate(': ${you} must acknowledge revealed cards:'),
        "type" => "multipleactiveplayer",
        "args" => "argsForState",
        "action" => "stMultiPlayerInit",
        "possibleactions" => [
            "actMultipleOk", 
        ],
        "transitions" => ["multipleOk" => States::HIGH_DRAMA_PLAYER_TURN_01192_2]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01192_2 => [
        "name" => "highDramaPhase01192_2",
        "type" => "game",
        "action" => "stSetCurrentPlayer",
        "transitions" => ["" => States::HIGH_DRAMA_PLAYER_TURN_01192_3]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01192_3 => [
        "name" => "highDramaPhase01192_3",
        "description" => clienttranslate('${actplayer} may choose a Risk to put into their Faction Hand.'),
        "descriptionmyturn" => clienttranslate('${you} may choose a Risk to put into your Faction Hand:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass"   
        ],
        "transitions" => [
            "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

States::HIGH_DRAMA_PLAYER_TURN_01194 => [
        "name" => "highDramaPhase01194",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose an attachment on Adelheide to discard:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "attachmentChosen" => States::HIGH_DRAMA_PLAYER_TURN_01194_2
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01194_2 => [
        "name" => "highDramaPhase01194_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a character for Adelheide to wound:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01194,
            "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01197 => [
        "name" => "highDramaPhase01197",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Kalla Forsberg') . clienttranslate(': ${you} must choose a character to move an attachment FROM:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_01197_2
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01197_2 => [
        "name" => "highDramaPhase01197_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Kalla Forsberg') . clienttranslate(': ${you} must choose attachment to move:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01197,
            "attachmentChosen" => States::HIGH_DRAMA_PLAYER_TURN_01197_3
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01197_3 => [
        "name" => "highDramaPhase01197_3",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Kalla Forsberg') . clienttranslate(': ${you} must choose a character to move attachment TO:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01197_2,
            "characterChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01200 => [
        "name" => "highDramaPhase01200",
        "description" => clienttranslate('${actplayer} is choosing an opponent for Crystal Eye\'s Forced Ability.'),
        "descriptionmyturn" => clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose an opponent:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_01200_2
        ]
    ],
    
    States::HIGH_DRAMA_PLAYER_TURN_01200_2 => [
        "name" => "highDramaPhase01200_2",
        "description" => clienttranslate('${actplayer} is choosing a card for Crystal Eye\'s Forced Ability.'),
        "descriptionmyturn" => clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose a card:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithId",
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            
        ]
    ],
    
    States::HIGH_DRAMA_PLAYER_TURN_01205 => [
        "name" => "highDramaPhase01205",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a character to kidnap: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_IN_PLAY_ACTION_CHOOSE_ACTION,
            "victimChosen" => States::HIGH_DRAMA_PLAYER_TURN_01205_2            
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01205_2 => [
        "name" => "highDramaPhase01205_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a location to move to: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithLocations",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01205,
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01063 => [
        "name" => "highDramaChallengeActionResolveTechnique_01063",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Bastien Girard') . clienttranslate(': Swap with a Musketeer: ${you} must choose a Musketeer:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_EVENTS,
        ]
    ],

    States::DUEL_CHOOSE_TECHNIQUE_01013 => [
        "name" => "duelChooseTechnique_01013",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('Vissenta Scarpa') . clienttranslate(': Add Parry or Thrust: ${you} must choose Parry or Thrust:'),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
        ]
    ],

    States::DUEL_CHOOSE_TECHNIQUE_01036 => [
        "name" => "duelChooseTechnique_01036",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('Daniella Dietrich') . clienttranslate(': Move to Adjacent Location: ${you} must choose a location to move to:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithLocations", 
        ],
        "transitions" => [
            "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
        ]
    ],

    States::DUEL_CHOOSE_TECHNIQUE_01063 => [
        "name" => "duelChooseTechnique_01063",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('Bastien Girard') . clienttranslate(': Swap with a Musketeer: ${you} must choose a Musketeer:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
        ]
    ],

    States::DUEL_CHOOSE_TECHNIQUE_01067 => [
        "name" => "duelChooseTechnique_01067",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('Jean Urbain') . clienttranslate(': ${you} must choose +1 Thrust or +1 Riposte:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
        ]
    ],

    States::DUEL_RESOLVE_MANEUVER_01051 => [
        "name" => "duelResolveManeuver_01051",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('Answering the Call') . clienttranslate(': ${you} must choose a Mercenary to take a wound:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_EVENTS,
        ]
    ],

    States::DUEL_RESOLVE_MANEUVER_01059 => [
        "name" => "duelResolveManeuver_01059",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('Regroup') . clienttranslate(': ${you} must choose a location to move to:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithLocations", 
        ],
        "transitions" => [
            "" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_EVENTS,
        ]
    ],

    States::DUEL_RESOLVE_MANEUVER_01077 => [
        "name" => "duelResolveManeuver_01077",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('Broken Time') . clienttranslate(': ${you} must choose a Combat Card to play:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_EVENTS,
        ]
    ],

    States::DUEL_RESOLVE_MANEUVER_01079 => [
        "name" => "duelResolveManeuver_01079",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('Disarm') . clienttranslate(': ${you} must choose a Adversary\'s weapon to destroy:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_EVENTS,
        ]
    ],
    States::DUEL_RESOLVE_MANEUVER_01079_2 => [
        "name" => "duelResolveManeuver_01079_2",
        "description" => clienttranslate('${actplayer} is choosing to destroy their weapon or take a wound.'),
        "descriptionmyturn" => clienttranslate('Disarm') . clienttranslate(': ${you} must choose to destroy your weapon or take a wound:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::DUEL_PAY_FOR_MANEUVER_FROM_COMBAT_CARD_EVENTS,
        ]
    ],

    States::DUEL_RESOLVE_MANEUVER_01165 => [
        "name" => "duelResolveManeuver_01165",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('I Know That Trick!') . clienttranslate(': ${you} must choose a Technique to copy:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithIds", 
        ],
        "transitions" => [
            "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
        ]
    ],

    States::DUEL_APPLY_COMBAT_CARD_STATS_01085 => [
        "name" => "duelApplyCombatCardStats_01085",
        "description" => clienttranslate('Porté Travel\'s Forced Ability: ${actplayer} is choosing their Sorcerer.'),
        "descriptionmyturn" => clienttranslate('Porté Travel') . clienttranslate(': ${you} must choose a Sorcerer:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::DUEL_APPLY_COMBAT_CARD_STATS_EVENTS,
        ]
    ],

    States::DUEL_END_01080 => [
        "name" => "duelEnd_01080",
        "type" => "game",
        "action" => "stFromCard",
        "transitions" => [
            "" => States::DUEL_END_PAY_FOR_REACTION
        ]
    ],

    States::DUSK_PHASE_BEGIN_01177 => [
        "name" => "duskPhaseBegin01177",
        "description" => clienttranslate('${actplayer} is choosing options for Penya.'),
        "descriptionmyturn" => clienttranslate('${you} may choose a character to follow Penya:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actPassWithPass"
        ],
        "transitions" => [
            "pickCards" => States::DUSK_PHASE_BEGIN_01177_2,
            "pass" => States::DUSK_PHASE_BEGIN_EVENTS]
    ],
    States::DUSK_PHASE_BEGIN_01177_2 => [
        "name" => "duskPhaseBegin01177_2",
        "description" => clienttranslate('${actplayer} is choosing options for Penya.'),
        "descriptionmyturn" => clienttranslate('${you} must choose the order to replace the top 3 cards of the City Deck:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithIds",
            "actPass"
        ],
        "transitions" => ["" => States::DUSK_PHASE_BEGIN_EVENTS]
    ],
];