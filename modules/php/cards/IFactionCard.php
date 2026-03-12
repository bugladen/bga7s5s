<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

interface IFactionCard
{
    public int $Riposte { get; set; }
    public int $Parry { get; set; }
    public int $Thrust { get; set; }

    public bool $DashedRiposte { get; set; }
    public bool $DashedParry { get; set; }
    public bool $DashedThrust { get; set; }

    public function addFactionProperties(&$properties);
}