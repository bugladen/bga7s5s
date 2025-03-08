<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

$machinestates += [
    States::DUSK_PHASE_BEGIN_01177 =>[
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
    States::DUSK_PHASE_BEGIN_01177_2 =>[
        "name" => "duskPhaseBegin01177_2",
        "description" => clienttranslate('${actplayer} is choosing a new order for the top 3 cards of the City Deck.'),
        "descriptionmyturn" => clienttranslate('${you} must choose the order to replace the top 3 cards of the City Deck:'),
        "type" => "activeplayer",
        "args" => "argsFromCard",
        "possibleactions" => [
            "actFromCardWithIds",
            "actPass"
        ],
        "transitions" => ["" => States::DUSK_PHASE_BEGIN_EVENTS]
    ],

];