<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

interface IFactionCard
{
    public function addFactionProperties(&$properties);
    public function getRiposte(): int;
}