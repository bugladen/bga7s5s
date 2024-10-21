<?php

class CharacterCard extends StatCard
{
    public function __construct($type, $value)
    {
        parent::__construct($type, $value);
    }

    public function immediateEffect($player)
    {
        // Add your card's immediate effect here.
    }
}