<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

$machinestates += [

    States::HIGH_DRAMA_PLAYER_TURN_01029 => [
        "name" => "highDramaPhase01029",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('The Pressure Is On: ${you} must choose a character to target:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithIds",
            "actFromCardPass"
        ],
        "transitions" => [
            "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01180 => [
        "name" => "highDramaPhase01180",
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
            "actFromCardWithIds",
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
            "actFromCardWithIds",
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
            "actFromCardWithIds",
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
            "actFromCardWithIds",
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
            "actFromCardWithIds",
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

    States::DUSK_PHASE_BEGIN_01177 => [
        "name" => "duskPhaseBegin01177",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} may choose a character to follow Penya:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithIds",
            "actPassWithPass"
        ],
        "transitions" => [
            "pickCards" => States::DUSK_PHASE_BEGIN_01177_2,
            "pass" => States::DUSK_PHASE_BEGIN_EVENTS]
    ],
    States::DUSK_PHASE_BEGIN_01177_2 => [
        "name" => "duskPhaseBegin01177_2",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
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