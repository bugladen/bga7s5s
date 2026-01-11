<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;

class _01153 extends FactionAttachment
{
    private bool $hasBlockedWound;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Breastplate");
        $this->Image = "01153.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        
        $this->ResolveModifier = 0;
        $this->CombatModifier = 0;
        $this->FinesseModifier = 0;
        $this->InfluenceModifier = 0;

        $this->WealthCost = 1;
        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 3;
        $this->Thrust = 1;

        $this->Traits = [
            'Armor',
        ];

        $this->resetCard();

        $this->hasBlockedWound = false;
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventCharacterWounded && $event->characterId == $this->AttachedToId)
        {
            $inDuel = $event->theah->game->globals->get(Game::IN_DUEL);            
            if ($inDuel)
            {
                $actor = $event->theah->getDuelRoundActor();
                $adversaryId = $event->theah->getDuelOpponentId($actor->Id);
                if (($this->AttachedToId == $adversaryId || $this->AttachedToId == $actor->Id) && ! $this->hasBlockedWound)
                {
                    $oldWounds = $event->wounds;
                    $event->wounds--;
                    if ($event->wounds < 0)
                    {
                        $event->wounds = 0;
                    }
        
                    $event->theah->game->notifyAllPlayers("message", clienttranslate('${card_inject_code} blocked a wound. Wounds went from ${oldWounds} to ${newWounds}'), [
                        "card_inject_code" => $this->getInjectCode(),
                        "oldWounds" => $oldWounds,
                        "newWounds" => $event->wounds,
                    ]);
        
                    $this->hasBlockedWound = true;
                    $this->IsUpdated = true;
                }
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterWounded && $event->characterId == $this->AttachedToId)
        {
           if ($event->wounds > 0) 
           {
                $game = $event->theah->game;
                $game->notify->all("message", clienttranslate('${card_inject_code}: Character this is attached to has taken a wound so this will be destroyed.'), [
                    "card_inject_code" => $this->getInjectCode(),
                ]);

                $detachEvent = EventFactory::createAttachmentUnequippedEvent($this->ControllerId, $event->characterId, $this->Id);
                $event->theah->queueEvent($detachEvent);

                $destroyEvent = EventFactory::createCardDiscardedFromPlayEvent($this->OwnerId, $this->Id, $this->Location, $this->Id, $asEffect = true);
                $event->theah->queueEvent($destroyEvent);
           }
        }
    }
}