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

        $this->Text = clienttranslate("<p>While an opponent has more Renown than you, Guillén gains +1 [Combat].</p><p><b>City Action:</b> Discard a card • Move an adjacent Renown to this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01064(),
        ];
    }

    private function checkRenown(Game $game)
    {
        $playerRenown = $game->getPlayerReknown($this->ControllerId);
        $opponentHasMore = false;

        $players = $game->loadPlayersBasicInfos();
        foreach ($players as $playerId => $player)
        {
            if ($playerId == $this->ControllerId)
            {
                continue;
            }

            $renown = $game->getPlayerReknown($playerId);
            if ($renown > $playerRenown)
            {
                $opponentHasMore = true;
                break;
            }
        }

        if (!$opponentHasMore && $this->hasBonus)
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

            $this->ModifiedCombat = $this->ModifiedCombat - 1;

            $game->theah->queueEvent($event);
        }
        else if ($opponentHasMore && !$this->hasBonus)
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
            
            $this->ModifiedCombat = $this->ModifiedCombat + 1;

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

