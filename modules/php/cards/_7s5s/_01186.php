<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01186;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;

class _01186 extends CityCharacter
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Maryam Benu Pleroma");
        $this->Image = "01186.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 186;

        $this->Title = clienttranslate('Impervious Champion');

        $this->Resolve = 5;
        $this->Combat = 4;
        $this->Finesse = 3;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->WealthCost = 6;
        $this->CityCardNumber = 10;
        $this->Negotiable = true;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Duelist'),
            clienttranslate('Weapons Master'),
            clienttranslate('Ashur'),
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p><b>Forced:</b> The first time a risk targets Maryam each Day • Cancel the effects. (Costs are still paid.)</p><p><b>Technique:</b> During the adversary's next round, they cannot use Maneuvers.</p>");

        $this->resetCard();

        $this->Techniques = [
            new Technique_01186(),
        ];
    }

    public function handleEvent(Event $event)
    {
        //Maryams imperviousness supersedes the event
        //Handle each event from a Risk source that would target her and cancel them before they are processed.
        //Mark ImperviousnessUsedToday as true so that it cannot be used again until the next day.
        if ( (($event instanceof EventCardMoving && $event->cardId == $this->Id && $event->sourceId != 0) ||
            ($event instanceof EventCardEngaged && $event->cardId == $this->Id && $event->sourceId != 0)) &&
            $this->isControlled() && ! $this->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED)
        )
        {
            $source = $event->theah->getCardById($event->sourceId);
            if ($source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters)
            {
                $this->addMaryamCondition($event->theah->game);

                $batchId = $event->batchId;
                if ($batchId)
                {
                    $event->theah->deleteEventBatch($batchId);
                }

                $event->canceled = true;
                return;
            }
        }

        if ($event instanceof EventCharacterTargeted && $event->targetId == $this->Id && $event->sourceId != 0
            && $this->isControlled() && ! $this->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED)
        )
        {
            $source = $event->theah->getCardById($event->sourceId);
            if ($source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters)
            {
                $this->addMaryamCondition($event->theah->game);

                $batchId = $event->batchId;
                if ($batchId)
                {
                    $event->theah->deleteEventBatch($batchId);
                }

                $event->canceled = true;
                return;
            }
        }

        if ($event instanceof EventChallengeIssued && $event->defenderId == $this->Id && $event->sourceId != 0
            && $this->isControlled() && ! $this->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED)
        )
        {
            $source = $event->theah->getCardById($event->sourceId);
            if ($source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters)
            {
                $this->addMaryamCondition($event->theah->game);

                $batchId = $event->batchId;
                if ($batchId)
                {
                    $event->theah->deleteEventBatch($batchId);
                }

                $event->canceled = true;
                return;
            }
        }

        if ($event instanceof EventCharacterBeingWounded && $event->characterId == $this->Id && $event->sourceId != 0
            && $this->isControlled() && ! $this->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED)
        )
        {
            $source = $event->theah->getCardById($event->sourceId);
            if ($source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters)
            {
                $this->addMaryamCondition($event->theah->game);

                $batchId = $event->batchId;
                if ($batchId)
                {
                    $event->theah->deleteEventBatch($batchId);
                }

                $event->canceled = true;
                return;
            }
        }

        if ($event instanceof EventAttachmentEquipping && $event->characterId == $this->Id && $event->sourceId != 0
            && $this->isControlled() && ! $this->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED)
        )
        {
            $source = $event->theah->getCardById($event->sourceId);
            if ($source && $source instanceof Risk && $source instanceof IRiskThatTargetsCharacters)
            {
                $this->addMaryamCondition($event->theah->game);

                $attachment = $event->theah->getCardById($event->attachmentId);

                $removedEvent = EventFactory::createCardRemovedFromPlayEvent($event->playerId, $attachment->Id, $attachment->Location);
                $event->theah->queueEvent($removedEvent);

                if ($attachment instanceof CityAttachment)
                {
                    $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($event->playerId, $attachment->Id, $attachment->Location);
                    $event->queueEvent($discardEvent);
                }
                else
                {
                    $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location);
                    $event->queueEvent($discardEvent);
                }

                $batchId = $event->batchId;
                if ($batchId)
                {
                    $event->theah->deleteEventBatch($batchId);
                }

                $event->canceled = true;
                return;
            }
        }

        parent::handleEvent($event);

        if ($event instanceof EventDuskEndOfDay)
        {
            $this->removeCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED);
            $event->theah->game->notify->all("maryamBenuPleromaAbilityRemoved", "", [
                "cardId" => $this->Id,
            ]);             
        }
    }

    public function addMaryamCondition(Game $game)
    {
        $this->addCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED);
        $game->notify->all("maryamBenuPleromaAbilityUsed", clienttranslate('Maryam has used her Imperviousness to cancel the ability targeting her.'), [
            "cardId" => $this->Id,
        ]);
    }
}