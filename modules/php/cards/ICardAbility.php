<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

interface ICardAbility
{
    function initializeAbility();

    function setId($id);

    function getId(): string;

    function setOwnerId($id);
    
    function isAvailable(): bool;

    function getOwningCard(Theah $theah): ?Card;

    function getOwningAttachment(Theah $theah): ?Attachment;

    function getOwningCharacter(Theah $theah): ?Character;

    function getPropertyArray(Game $game): array;
    
    function setUsed(Theah $theah, bool $used);
    
    function doCost(Game $game): void;
    
    function doEffect(Game $game): void;

}