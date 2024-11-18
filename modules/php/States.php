<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

class States
{
    const GAME_SETUP = 1;
    const PICK_DECKS = 5;
    const BUILD_TABLE = 9;
    
    const DAWN_NEW_DAY = 10;
    const DAWN_BEGINNING = 11;
    const DAWN_CITY_CARDS = 12;
    const DAWN_ENDING = 13;
    
    const PLANNING_PHASE_BEGINNING = 20;
    const PLANNING_PHASE = 21;
    const PLANNING_PHASE_APPROACH_CARDS_PLAYED = 22;
    const PLANNING_PHASE_MUSTER = 23;
    const PLANNING_PHASE_SCHEMES = 24;

    const HIGH_DRAMA_BEGINNING = 45;
    const HIGH_DRAMA_PHASE = 50;
    const PLAYER_TURN = 55;
    const NEXT_PLAYER = 60;
    const END_GAME = 99;
   
}