<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01061 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("En Garde Equipped Performer");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        // WHY: countsAsEquipped so a Ranged Weapon fiction (Eleanor) satisfies
        // "equipped performer" for this player's own Well-Equipped Action.
        $characters = array_filter($characters, fn($character) => $character->countsAsEquipped($theah, $playerId) && $character->Engaged);
        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        parent::getPerformersForAction($playerId, $theah);

        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_values(array_filter($characters, fn($character) => $character->countsAsEquipped($theah, $playerId) && $character->Engaged));
        return $characters;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);
            $owner = $this->getOwningCard($event->theah);

            if (! $performer->Engaged)
            {
                throw new UserException($game->translate("Character must be engaged to use this action"));
            }

            if (! $performer->countsAsEquipped($event->theah, $owner->ControllerId))
            {
                throw new UserException($game->translate("Character must have an attachment to use this action"));
            }

            $engardeEvent = EventFactory::createCardEngardedEvent($owner->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $event->theah->queueEvent($engardeEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }

    }
}