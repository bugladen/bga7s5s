<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

interface IHasManeuvers
{
    public function addManeuverProperties(&$properties);

    public function getManeuversArray(): Array;
}