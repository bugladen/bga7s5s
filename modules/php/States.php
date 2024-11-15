<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

class States
{
    const GAME_SETUP = 1;
    const PICK_DECKS = 5;
    const BUILD_TABLE = 10;
    
    const DAWN_NEW_DAY = 15;
    const DAWN_BEGINNING = 20;
    const DAWN_CITY_CARDS = 25;
    
    const PLANNING_PHASE_BEGINNING = 30;
    const PLANNING_PHASE = 35;
    const PLANNING_PHASE_APPROACH_CARDS_PLAYED = 40;

    const HIGH_DRAMA_BEGINNING = 45;
    const HIGH_DRAMA_PHASE = 50;
    const PLAYER_TURN = 55;
    const NEXT_PLAYER = 60;
    const END_GAME = 99;
   
}