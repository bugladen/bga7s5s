<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

abstract class CityEvent extends Card
{
    public int $CityCardNumber;

    public function __construct()
    {
        parent::__construct();

        $this->CityCardNumber = 0;
    }
}