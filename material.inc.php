<?php
/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © <Your name here> <Your email address here>
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 *
 * material.inc.php
 *
 * SeventhSeaCityOfFiveSails game material description
 *
 * Here, you can describe the material of your game with PHP variables.
 *
 * This file is loaded in your game logic class constructor, ie these variables
 * are available everywhere in your game logic code.
 *
*/

$this->city_decks = <<<JSON
 {
    "decks": [
        {
            "id": "7s5s",
            "cards" : [
                "01177",
                "01178",
                "01179",
                "01180",
                "01181",
                "01182",
                "01183",
                "01184",
                "01185",
                "01186",
                "01187",
                "01188",
                "01189",
                "01190",
                "01191",
                "01192",
                "01193",
                "01194",
                "01195",
                "01196",
                "01197",
                "01198",
                "01199",
                "01200",
                "01201",
                "01202",
                "01203",
                "01204",
                "01205",
                "01206"
            ]
        }
    ]
 }
JSON;


$this->starter_decks = <<<JSON
{
    "decks": [
        {
            "id": "SDCastille",
            "name": "Castille Starter",
            "faction": "Castille",
            "leader": "01089",
            "approach_deck": [ "01098", "01099", "01148", "01149", "01150", "01091", "01092", "01093", "01094", "01097" ],
            "faction_deck": [
                { "id": "01100", "count": 1 },
                { "id": "01101", "count": 2 },
                { "id": "01102", "count": 2 },
                { "id": "01103", "count": 1 },
                { "id": "01104", "count": 2 },
                { "id": "01105", "count": 2 },
                { "id": "01106", "count": 2 },
                { "id": "01107", "count": 1 },
                { "id": "01108", "count": 2 },
                { "id": "01109", "count": 2 },
                { "id": "01110", "count": 1 },
                { "id": "01111", "count": 1 },
                { "id": "01112", "count": 2 },
                { "id": "01113", "count": 2 },
                { "id": "01114", "count": 2 },
                { "id": "01115", "count": 2 },
                { "id": "01156", "count": 1 },
                { "id": "01157", "count": 2 },
                { "id": "01158", "count": 2 },
                { "id": "01164", "count": 1 },
                { "id": "01165", "count": 1 },
                { "id": "01167", "count": 1 },
                { "id": "01168", "count": 1 },
                { "id": "01169", "count": 2 },
                { "id": "01171", "count": 1 },
                { "id": "01176", "count": 1 }
            ]
        }
    ]
}
JSON;