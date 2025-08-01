<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

$machinestates += [

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01044 => [
        "name" => "planningPhaseResolveSchemes_01044",
        "description" => clienttranslate('Armed and Marshaled: ${actplayer} must choose an Attachment from their discard pile if able.'),
        "descriptionmyturn" => clienttranslate('Armed and Marshaled: ${you} must choose an Attachment from your discard pile if able:'),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actFromCardWithId", 
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

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01147 => [
        "name" => "planningPhaseResolveSchemes_01147",
        "description" => clienttranslate('Let\'s Haggle: Your opponent(s) must acknowledge revealed cards.'),
        "descriptionmyturn" => clienttranslate('Let\'s Haggle: ${you} must acknowledge revealed cards:'),
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

    States::HIGH_DRAMA_PLAYER_TURN_01029 => [
        "name" => "highDramaPhase01029",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('The Pressure Is On: ${you} must choose a character to target:'),
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
        "descriptionmyturn" => clienttranslate('Kaspar: ${you} must acknowledge revealed cards:'),
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
        "descriptionmyturn" => clienttranslate('Kaspar: ${you} must choose whether to recruit the revealed mercenary:'),
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
        "descriptionmyturn" => clienttranslate('Kaspar: ${you} must choose whether to parley with the revealed mercenary:'),
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

    States::HIGH_DRAMA_PLAYER_TURN_01044 => [
        "name" => "highDramaPhase01044",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Armed and Marshaled: ${you} must choose an attachment to Engage on the selected performer: '),
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
        "descriptionmyturn" => clienttranslate('Armed and Marshaled: ${you} must choose an opposing character to manipulate: '),
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
        "descriptionmyturn" => clienttranslate('Armed and Marshaled: ${you} must choose how to manipulate the chosen character: '),
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

    States::HIGH_DRAMA_PLAYER_TURN_01068 => [
        "name" => "highDramaPhase01068",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Léontine Giroux: ${you} must choose a character to move: '),
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
        "descriptionmyturn" => clienttranslate('Léontine Giroux: ${you} must choose a location to move the chosen character to: '),
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
        "descriptionmyturn" => clienttranslate('Maxime De Lafayette: ${you} must discard a card from your Faction Hand: '),
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
        "descriptionmyturn" => clienttranslate('Maxime De Lafayette: ${you} must choose an attachment to recover from your discard pile: '),
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
        "descriptionmyturn" => clienttranslate('Réputation Méritée: ${you} must choose a City Card to discard: '),
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
        "descriptionmyturn" => clienttranslate('Réputation Méritée: ${you} must choose a card to Muster: '),
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
        "descriptionmyturn" => clienttranslate('Blood Mark: ${you} must choose a City location to move Performer: '),
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
        "descriptionmyturn" => clienttranslate('Blood Mark: ${you} may Wound Performer and choose Character to include: '),
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
        "descriptionmyturn" => clienttranslate('Gallant Deeds: ${you} must choose an Opposing Character to Engage:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actBack",
            "actFromCardWithId"
        ],
        "transitions" => [
            "targetChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01085 => [
        "name" => "highDramaPhase01085",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Porté Travel: ${you} must choose a character to move:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId"
        ],
        "transitions" => [
            "targetChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01086 => [
        "name" => "highDramaPhase01086",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Status Matters: ${you} must choose a location to make Uncontrolled:'),
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
        "descriptionmyturn" => clienttranslate('Let\'s Haggle: ${you} must choose an attachment to Equip from the Bazaar: '),
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

    States::HIGH_DRAMA_PLAYER_TURN_01149 => [
        "name" => "highDramaPhase01149",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Midnight Shipment: ${you} must choose a location to move chosen Performer: '),
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

    States::HIGH_DRAMA_PLAYER_TURN_01180 => [
        "name" => "highDramaPhase01180",
        "description" => clienttranslate('Your opponent(s) must acknowledge revealed cards.'),
        "descriptionmyturn" => clienttranslate('Kaj Kousei: ${you} must acknowledge revealed cards:'),
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
        "descriptionmyturn" => clienttranslate('${you} must discard two cards to activate Risky Undertaking:'),
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
        "descriptionmyturn" => clienttranslate('Gustavo: ${you} must acknowledge revealed cards:'),
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
        "descriptionmyturn" => clienttranslate('Using Kalla\'s action, ${you} must choose a character to move an attachment FROM:'),
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
        "descriptionmyturn" => clienttranslate('Using Kalla\'s action, ${you} must choose attachment to move:'),
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
        "descriptionmyturn" => clienttranslate('Using Kalla\'s action, ${you} must choose a character to move attachment TO:'),
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
        "descriptionmyturn" => clienttranslate('${you} must choose an opponent for Crystal Eye\'s Forced Ability:'),
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
        "descriptionmyturn" => clienttranslate('${you} must choose a card for Crystal Eye\'s Forced Ability:'),
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

    States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_01063 => [
        "name" => "highDramaChallengeActionActivateTechnique_01063",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('Bastien\'s Technique: Swap with a Musketeer: ${you} must choose a Musketeer:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_EVENTS,
        ]
    ],

    States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_01067 => [
        "name" => "highDramaChallengeActionActivateTechnique_01067",
        "description" => clienttranslate('${actplayer} is choosing options to resolve Jean Urbain\'s Technique.'),
        "descriptionmyturn" => clienttranslate('Jean Urbain: +1 Thrust or Riposte: ${you} must choose Thrust or Riposte:'),
        "type" => "activeplayer",
        "args" => "argsEmpty",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_EVENTS,
        ]
    ],

    States::DUEL_CHOOSE_TECHNIQUE_01063 => [
        "name" => "duelChooseTechnique_01063",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('Bastien\'s Technique: Swap with a Musketeer: ${you} must choose a Musketeer:'),
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
        "descriptionmyturn" => clienttranslate('Jean Urbain\'s Technique: ${you} must choose +1 Thrust or +1 Riposte:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId", 
        ],
        "transitions" => [
            "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
        ]
    ],

    States::DUEL_RESOLVE_MANEUVER_01077 => [
        "name" => "duelResolveManeuver_01077",
        "description" => clienttranslate('${actplayer} is choosing their Duel Action options.'),
        "descriptionmyturn" => clienttranslate('Broken Time: ${you} must choose a Combat Card to play:'),
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
        "descriptionmyturn" => clienttranslate('Disarm: ${you} must choose a Adversary\'s weapon to destroy:'),
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
        "descriptionmyturn" => clienttranslate('Disarm: ${you} must choose to destroy your weapon or take a wound:'),
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
        "descriptionmyturn" => clienttranslate('Copy Technique from Adversary: ${you} must choose a Technique to copy:'),
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
        "descriptionmyturn" => clienttranslate('Porté Travel\'s Forced Ability: ${you} must choose a Sorcerer:'),
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