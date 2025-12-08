<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01096 extends Technique
{
    private bool $AdversaryWoundedThisRound = false;
    private int $AdversaryId = 0;
    public bool $IsActive = false;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Steal Adversary's Equipped Card at End of Round");
        $this->IsActive = false;
        $this->AdversaryWoundedThisRound = false;
        $this->AdversaryId = 0;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        return parent::isAvailableToPlayer($playerId, $theah);
    }
    
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $this->IsActive = true;
            $this->AdversaryWoundedThisRound = false;
            $this->AdversaryId = $event->theah->getDuelRoundOpponent()->Id;
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventCharacterWounded && $this->IsActive)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($event->characterId == $this->AdversaryId)
            {
                $inDuel = $event->theah->game->globals->get(Game::IN_DUEL);
                if ($inDuel && $event->wounds > 0)
                {
                    $this->AdversaryWoundedThisRound = true;
                    $owner->IsUpdated = true;
                }
            }
        }
    
        if ($event instanceof EventDuelEndOfRound && $this->IsActive)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($this->AdversaryId == $event->actorId)
            {
                if (! $this->AdversaryWoundedThisRound)
                {
                    $owner = $this->getOwningCard($event->theah);
                    $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01096", $this->Id);
                    $event->theah->queueEvent($transition);
                }

                $this->IsActive = false;
                $this->AdversaryWoundedThisRound = false;
                $this->AdversaryId = 0;
                $owner->IsUpdated = true;
            }
        }
    
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_END_OF_ROUND_01096)
        {
            $adversary = $game->theah->getDuelRoundActor();
            $attachments = [];
            foreach ($adversary->Attachments as $attachmentId)
            {
                $attachment = $game->theah->getAttachmentById($attachmentId);
                if ($attachment == null)
                {
                    continue;
                }
                $attachments[] = [
                    "id" => $attachmentId,
                    "name" => $game->translate($attachment->Name),
                ];
            }
            $args['attachments'] = $attachments;
        }
        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_END_OF_ROUND_01096)
        {
            $attachment = $game->theah->getAttachmentById($id);

            if ($attachment == null)
            {
                throw new \BgaUserException($game->translate("Invalid attachment ID."));
            }

            $adversary = $game->theah->getDuelRoundActor();
            if ( ! in_array($attachment->Id, $adversary->Attachments))
            {
                throw new \BgaUserException($game->translate("Attachment is not equipped to Adversary."));
            }
            
            $unequipEvent = EventFactory::createAttachmentUnequippedEvent($adversary->ControllerId, $adversary->Id, $attachment->Id);
            $game->theah->queueEvent($unequipEvent);

            $owner = $this->getOwningCard($game->theah);
            $equipEvent = EventFactory::createAttachmentEquippedEvent($owner->ControllerId, $owner->Id, $attachment->Id, 0, 0, $asAction = true, $explanations = '');
            $game->theah->queueEvent($equipEvent);

            $this->setUsed($game->theah, true);

            $game->gamestate->nextState();
        }
    }


}