<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01147 extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->RequiresPerformerSelected = true;
        $this->Name = clienttranslate("Equip Attachment From Bazaar");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        //Parent already checks that the player has a character in the city
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        //See if there any attachments at the Bazaar
        $attachments = $theah->getAvailableAttachmentsAtLocation(Game::LOCATION_CITY_BAZAAR);
        if (count($attachments) == 0)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01147", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01147)
        {
            $args['performerId'] = $game->globals->get(Game::CHOSEN_PERFORMER);
            $attachments = $game->theah->getAvailableAttachmentsAtLocation(Game::LOCATION_CITY_BAZAAR);
            $args['attachmentsInPlay'] = array_map(fn($attachment) => $attachment->Id, $attachments);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);
        
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01147)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if (! $attachment)
            {
                throw new \BgaUserException($game->translate("Invalid attachment"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $game->globals->set(Game::CHOSEN_CARD, $attachment->Id);
            [$discount, $explanations] = $game->theah->getEquipDiscount($performer, $attachment);
            if ($discount != 0)
                $game->notify->player($performer->ControllerId, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
                    "explanations" => $explanations,
                ]);
            $game->globals->set(Game::DISCOUNT, $discount);
            $game->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);
    
            $game->globals->set(Game::EQUIP_TYPE, Game::LETS_HAGGLE_EQUIP_TYPE);
            
            $this->resetPlayerPassCount($game);
            $this->setUsed($game->theah, true);

            $game->gamestate->nextState("attachmentSelected");
        }
    }
}