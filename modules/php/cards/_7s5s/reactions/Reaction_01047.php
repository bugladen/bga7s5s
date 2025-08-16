<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01047 extends AttachmentReaction
{
    public string $TechniqueId;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Cancel Adversary's Technique");
        $this->TechniqueId = '';
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to cancel Adversary\'s Technique: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Cancel Technique'), 'cancel');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventTechniqueActivated && $this->isAvailable())
        {
            $inDuel = $event->theah->game->globals->get(Game::IN_DUEL);
            if ($inDuel)
            {
                $scheme = $this->getOwningCard($event->theah);
                $owner = $event->theah->getCardById($event->ownerId);
                if ($owner->ControllerId != $scheme->ControllerId)
                {
                    $reactionEvent = EventFactory::createReactionTransitionEvent($scheme->ControllerId, $scheme->Id, $this->Id);
                    $reactionEvent->priority = Event::HIGH_PRIORITY;
                    $event->theah->queueEvent($reactionEvent);
    
                    $this->TechniqueId = $event->techniqueId;
                    $scheme->IsUpdated = true;
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'cancel')
        {
            $scheme = $this->getOwningCard($game->theah);

            if ($this->TechniqueId != '')
            {
                $owner = $this->getOwningCard($game->theah);
                $technique = $game->theah->getTechniqueById($this->TechniqueId);
                $game->notifyAllPlayers('message', clienttranslate('${card_inject_code}: ${player_name} chose to cancel Technique: [${technique}]'), [
                    'card_inject_code' => $owner->getInjectCode(),
                    'player_name' => $game->getActivePlayerName(),
                    'technique' => $technique->Name
                ]);
                $game->globals->delete(Game::CHOSEN_TECHNIQUE);
                $game->globals->delete(Game::CHOSEN_TECHNIQUE_IS_MAIN);
                $game->theah->deleteTechniqueEvents($this->TechniqueId);
                $scheme->IsUpdated = true;

                $canceledEvent = EventFactory::createTechniqueCanceledEvent($scheme->ControllerId, $this->TechniqueId);
                $game->theah->queueEvent($canceledEvent);

                $this->TechniqueId = '';
            }

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");    }
}