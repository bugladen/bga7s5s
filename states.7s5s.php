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
            "actGameActionOnCardWithIds",
            "actPass"
        ],
        "transitions" => ["" => States::DUSK_PHASE_BEGIN_EVENTS]
    ],

];