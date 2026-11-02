<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

$machinestates += [

    // planningPhaseResolveSchemes_01016 / _2 / _3 live in State_planningPhaseResolveSchemes_01016*

    // planningPhaseResolveSchemes_01044 / 01045 / 01071 / 01072 / 01125 live in their State_* classes
    // planningPhaseResolveSchemes_01126 / _2 / 01143 / 01144 / _2 / 01145 / _2 / _3 / 01147 / 01150 / 01151 / _2 / 01152 / _2 / _3 live in their State_* classes

    // highDramaBeginning_01144 / _2 live in their State_* classes

    // highDramaPhase01011 / 01012 / 01015 / 01017 / 01019 / 01020 live in their State_* classes
    // highDramaPhase01024–01030 / 01034–01035(v2) / 01038 / 01044 / 01046a / 01049 live in their State_* classes
    // highDramaPhase01055 / 01056 / 01058 / 01059 / 01060 / 01068 / 01069 / 01072 live in their State_* classes
    // highDramaPhase01076 / _2 / 01081 / 01085 / 01086 live in their State_* classes
    // highDramaPhase01147 / 01148 / _2 / _3 / _4 / 01149 / 01152a / 01152b / 01154 / _2 / 01156 / _2 / _3 / 01158 / 01160 / 01161 / _2 live in their State_* classes
    // highDramaPhase01162 / _2 / 01163 / _2 / _3 / 01164 / 01167 / _2 / _3 / _4 / 01171 / 01172 / 01174 / 01175 live in their State_* classes

    States::HIGH_DRAMA_PLAYER_TURN_01180 => [
        "name" => "highDramaPhase01180",
        "description" => clienttranslate('Your opponent(s) must acknowledge revealed cards.'),
        "descriptionmyturn" => clienttranslate('Kaj Kousei') . clienttranslate(': ${you} must acknowledge revealed cards:'),
        "type" => "multipleactiveplayer",
        "args" => "argsForState",
        "action" => "stMultiPlayerInitCardRevealAcknowledge",    
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
    // States::HIGH_DRAMA_PLAYER_TURN_01180_3 migrated to State_highDramaPhase01180_3.php
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
            "performerChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01180_5 => [
        "name" => "highDramaPhase01180_5",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose cards from Your Hand to pay #{cost} Wealth for chosen Artifact:'),
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
            "actFromCardWithIds"
        ],
        "transitions" => [
            "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            "cardsDiscarded" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01189a => [
        "name" => "highDramaPhase01189a",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a location to move a Renown FROM:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithLocations"
        ],
        "transitions" => [
            "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01189b => [
        "name" => "highDramaPhase01189b",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a location to move a Renown TO:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithLocations"
        ],
        "transitions" => [
            "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            "locationChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::HIGH_DRAMA_PLAYER_TURN_01192 => [
        "name" => "highDramaPhase01192",
        "description" => clienttranslate('Your opponent(s) must acknowledge revealed cards.'),
        "descriptionmyturn" => clienttranslate('Gustavo') . clienttranslate(': ${you} must acknowledge revealed cards:'),
        "type" => "multipleactiveplayer",
        "args" => "argsForState",
        "action" => "stMultiPlayerInitCardRevealAcknowledgeSansInitiatingPlayer",
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
            "actFromCardWithId"
        ],
        "transitions" => [
            "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
            "actFromCardWithId"
        ],
        "transitions" => [
            "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
            // WHY: Pass only legal when every opponent Approach Deck is empty (Day 5 soft-lock).
            "actFromCardPass",
        ],
        // WHY: Pass must return to EVENTS — bare "" would be the only legal empty key and
        // would send Pass into _2 (another soft-lock). Named keys for both exits.
        "transitions" => [
            "opponentChosen" => States::HIGH_DRAMA_PLAYER_TURN_01200_2,
            "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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
            // WHY: Pass when the chosen Approach Deck is empty — unsticks Day-5 equip.
            "actFromCardPass",
        ],
        "transitions" => [
            "" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            
        ]
    ],

    // WHY: Syrneth Secrets (_05William) can equip Crystal Eye during scheme resolve (state 260).
    // Approach/HD-only "01200" transitions crashed there; return to resolve-schemes EVENTS.
    States::PLANNING_PHASE_RESOLVE_SCHEMES_01200 => [
        "name" => "planningPhaseResolveSchemes_01200",
        "description" => clienttranslate('${actplayer} is choosing an opponent for Crystal Eye\'s Forced Ability.'),
        "descriptionmyturn" => clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose an opponent:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass",
        ],
        "transitions" => [
            "opponentChosen" => States::PLANNING_PHASE_RESOLVE_SCHEMES_01200_2,
            "pass" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
        ]
    ],

    States::PLANNING_PHASE_RESOLVE_SCHEMES_01200_2 => [
        "name" => "planningPhaseResolveSchemes_01200_2",
        "description" => clienttranslate('${actplayer} is choosing a card for Crystal Eye\'s Forced Ability.'),
        "descriptionmyturn" => clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose a card:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass",
        ],
        "transitions" => [
            "" => States::PLANNING_PHASE_RESOLVE_SCHEMES_EVENTS,
        ]
    ],

    // WHY: Kaj Reaction_04042 can equip Crystal Eye during Approach (state 220). HD-only
    // "01200" transition crashed there; these return to Approach EVENTS, not HD EVENTS.
    States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_01200 => [
        "name" => "planningPhaseApproach01200",
        "description" => clienttranslate('${actplayer} is choosing an opponent for Crystal Eye\'s Forced Ability.'),
        "descriptionmyturn" => clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose an opponent:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass",
        ],
        "transitions" => [
            "opponentChosen" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_01200_2,
            "pass" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_EVENTS,
        ]
    ],

    States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_01200_2 => [
        "name" => "planningPhaseApproach01200_2",
        "description" => clienttranslate('${actplayer} is choosing a card for Crystal Eye\'s Forced Ability.'),
        "descriptionmyturn" => clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose a card:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass",
        ],
        "transitions" => [
            "" => States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_EVENTS,
        ]
    ],
    
    States::HIGH_DRAMA_PLAYER_TURN_01205 => [
        "name" => "highDramaPhase01205",
        "description" => clienttranslate('${actplayer} is choosing options to perform an Action.'),
        "descriptionmyturn" => clienttranslate('${you} must choose a character to kidnap: '),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId"
        ],
        "transitions" => [
            "zombie" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
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

    // highDramaChallengeActionResolveTechnique_01063 / 01067 live in their State_* classes

    // duelChooseTechnique_01013 / 01036 / 01063 / 01067 live in their State_* classes

    // duelResolveManeuver_01051 / 01059 / 01079 / _2 / 01164 / 01165 live in their State_* classes

    States::DUEL_RESOLVE_MANEUVER_01200 => [
        "name" => "duelResolveManeuver_01200",
        "description" => clienttranslate('${actplayer} is choosing an opponent for Crystal Eye\'s Forced Ability.'),
        "descriptionmyturn" => clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose an opponent:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass",
        ],
        "transitions" => [
            "opponentChosen" => States::DUEL_RESOLVE_MANEUVER_01200_2,
            "pass" => States::DUEL_RESOLVE_MANEUVER_EVENTS,
        ]
    ],
    
    States::DUEL_RESOLVE_MANEUVER_01200_2 => [
        "name" => "duelResolveManeuver_01200_2",
        "description" => clienttranslate('${actplayer} is choosing a card for Crystal Eye\'s Forced Ability.'),
        "descriptionmyturn" => clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose a card:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass",
        ],
        "transitions" => [
            "" => States::DUEL_RESOLVE_MANEUVER_EVENTS,
            
        ]
    ],
    
    // duelApplyCombatCardStats_01085 lives in State_duelApplyCombatCardStats_01085

    // duelEndOfRound_01031 lives in State_duelEndOfRound_01031

    States::DUEL_END_OF_ROUND_01200 => [
        "name" => "duelEndOfRound_01200",
        "description" => clienttranslate('${actplayer} is choosing an opponent for Crystal Eye\'s Forced Ability.'),
        "descriptionmyturn" => clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose an opponent:'),
        "type" => "activeplayer",
        "args" => "argsForState",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass",
        ],
        "transitions" => [
            "opponentChosen" => States::DUEL_END_OF_ROUND_01200_2,
            "pass" => States::DUEL_END_OF_ROUND_EVENTS,
        ]
    ],
    
    States::DUEL_END_OF_ROUND_01200_2 => [
        "name" => "duelEndOfRound_01200_2",
        "description" => clienttranslate('${actplayer} is choosing a card for Crystal Eye\'s Forced Ability.'),
        "descriptionmyturn" => clienttranslate('Crystal Eye') . clienttranslate(': ${you} must choose a card:'),
        "type" => "activeplayer",
        "args" => "argsForStatePrivate",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass",
        ],
        "transitions" => [
            "" => States::DUEL_END_OF_ROUND_EVENTS,
            
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
