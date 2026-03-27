<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;

class _01064 extends Character implements IHasActions
{
    use ActionTrait;

    private bool $hasBonus = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Guillén de Murrieta");
        $this->Image = "01064.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 64;

        $this->initializeFaction("Montaigne");
        $this->Title = clienttranslate("High Marketeer");
        $this->Resolve = 4;
        $this->Combat = 2;
        $this->Finesse = 1;
        $this->Influence = 2;

        $this->Traits = [
            clienttranslate("Diplomat"),
            clienttranslate("Merchant"),
            clienttranslate("Castille"),
        ];

        $this->Text = clienttranslate("<p>While an opponent has more Renown than you, Guillén gains +1 [Com].</p><p>City Action: Discard a card • Move an adjacent Renown to this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01064(),
        ];
    }

    private function checkRenown(Game $game)
    {
        $playerRenown = $game->getPlayerReknown($this->ControllerId);
        $highestRenown = $playerRenown;
        $playerHasHigherRenown = true;

        $players = $game->loadPlayersBasicInfos();
        foreach ($players as $playerId => $player)
        {
            if ($playerId == $this->ControllerId)
            {
                continue;
            }

            $renown = $game->getPlayerReknown($playerId);
            if ($renown >= $playerRenown)
            {
                $playerHasHigherRenown = false;
                $highestRenown = $renown;
                break;
            }
        }

        if ($playerHasHigherRenown && $this->hasBonus)
        {
            $this->hasBonus = false;
            $this->IsUpdated = true;

            $event = EventFactory::createCharacterCombatModifiedEvent(
                $this->ControllerId,
                $this->Id,
                $this->ModifiedCombat,
                $this->ModifiedCombat - 1,
                $this->getInjectCode()
            );

            $game->theah->queueEvent($event);
        }
        else if (!$playerHasHigherRenown && !$this->hasBonus && $highestRenown != $playerRenown) //No ties
        {
            $this->hasBonus = true;
            $this->IsUpdated = true;

            $event = EventFactory::createCharacterCombatModifiedEvent(
                $this->ControllerId,
                $this->Id,
                $this->ModifiedCombat,
                $this->ModifiedCombat + 1,
                $this->getInjectCode()
            );

            $game->theah->queueEvent($event);
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (($event instanceof EventCharacterMustered || $event instanceof EventApproachCharacterPlayed) && $event->characterId == $this->Id)
        {
            $this->checkRenown($event->theah->game);
        }
        
        if ($event instanceof EventPlayerGainsReknown || $event instanceof EventPlayerLosesReknown)
        {
            $this->checkRenown($event->theah->game);
        }
    }
}

