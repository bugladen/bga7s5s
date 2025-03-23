<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

$machinestates += [

    States::HIGH_DRAMA_PLAYER_TURN_01180 => [
        "name" => "highDramaPhase01180",
        "description" => clienttranslate('${actplayer} is choosing to Equip an Artifact from the top 4 cards of the City Deck.'),
        "descriptionmyturn" => clienttranslate('${you} may choose to Equip an Artifact from the top 4 cards of the City Deck:'),
        "type" => "activeplayer",
        "args" => "argsFromCard",
        "possibleactions" => [
            "actFromCardWithId",
            "actFromCardPass"
        ],
        "transitions" => [
            "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_01180_2,
            "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01180_2 => [
        "name" => "highDramaPhase01180_2",
        "description" => clienttranslate('${actplayer} must choose a Performer to Equip Artifact'),
        "descriptionmyturn" => clienttranslate('${you} must choose a Performer to Equip Artifact:'),
        "type" => "activeplayer",
        "args" => "argsFromCard",
        "possibleactions" => [
            "actFromCardWithIds",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01180,
            "performerChosen" => States::HIGH_DRAMA_PLAYER_TURN_01180_3
        ]
    ],
    States::HIGH_DRAMA_PLAYER_TURN_01180_3 => [
        "name" => "highDramaPhase01180_3",
        "description" => clienttranslate('${actplayer} is Equipping an Artifact from the City Deck.'),
        "descriptionmyturn" => clienttranslate('${you} must choose cards from Your Hand to pay for chosen Artifact:'),
        "type" => "activeplayer",
        "args" => "argsFromCard",
        "possibleactions" => [
            "actFromCardWithIds",
            "actBack"
        ],
        "transitions" => [
            "back" => States::HIGH_DRAMA_PLAYER_TURN_01180_2,
            "artifactEquipped" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS
        ]
    ],

    States::DUSK_PHASE_BEGIN_01177 => [
        "name" => "duskPhaseBegin01177",
        "description" => clienttranslate('${actplayer} is choosing one of their characters to follow Penya.'),
        "descriptionmyturn" => clienttranslate('${you} may choose a character to follow Penya:'),
        "type" => "activeplayer",
        "args" => "argsFromCard",
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
        "description" => clienttranslate('${actplayer} is choosing a new order for the top 3 cards of the City Deck.'),
        "descriptionmyturn" => clienttranslate('${you} must choose the order to replace the top 3 cards of the City Deck:'),
        "type" => "activeplayer",
        "args" => "argsFromCardPrivate",
        "possibleactions" => [
            "actFromCardWithIds",
            "actPass"
        ],
        "transitions" => ["" => States::DUSK_PHASE_BEGIN_EVENTS]
    ],
];