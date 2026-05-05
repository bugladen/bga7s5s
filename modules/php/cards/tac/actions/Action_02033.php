<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02033 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Move target adjacent City Card to Rosa\'s Location');
    }

    private function getSelectableAdjacentCityCardIds(Theah $theah, Character $rosa): array
    {
        $ids = [];
        $adjacent = $theah->getAdjacentCityLocations($rosa->Location, false);
        foreach ($adjacent as $locName) {
            $cards = $theah->getCardObjectsAtLocation($locName);
            foreach ($cards as $card) {
                if (! $card->isControlled() && $card instanceof ICityDeckCard) {
                    $ids[] = $card->Id;
                }
            }
        }
        return array_values(array_unique($ids));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) {
            return false;
        }

        $rosa = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($rosa)) {
            return false;
        }

        return count($this->getSelectableAdjacentCityCardIds($theah, $rosa)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02033", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02033) {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;
            $performer = $game->theah->getCharacterById($performerId);
            $args['ids'] = $this->getSelectableAdjacentCityCardIds($game->theah, $performer);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02033) {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $rosa = $game->theah->getCharacterById($performerId);
            if ($rosa === null) {
                throw new UserException($game->translate('Character not found'));
            }

            $allowed = $this->getSelectableAdjacentCityCardIds($game->theah, $rosa);
            if (! in_array($id, $allowed, true)) {
                throw new UserException($game->translate('Invalid City Card choice'));
            }

            $card = $game->theah->getCardById($id);
            if (! $card instanceof ICityDeckCard) {
                throw new UserException($game->translate('Card is not a City Card'));
            }

            $fromLocation = $card->Location;
            $moveEvent = EventFactory::createCardMovingEvent(
                $rosa->ControllerId,
                $card->Id,
                $fromLocation,
                $rosa->Location,
                false,
                $rosa->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($rosa->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState('cardChosen');
        }
    }
}
