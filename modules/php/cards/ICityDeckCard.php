<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

interface ICityDeckCard
{
    public function addCityProperties(&$properties);
    public function canBeDiscardedFromCity(): bool;
}