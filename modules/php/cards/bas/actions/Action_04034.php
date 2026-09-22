<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04034 extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Opponent may lose control or opposing characters are wounded");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($playerId, $theah) {
                if (! $theah->cardInCity($performer))
                {
                    return false;
                }

                $location = $theah->getCityLocation($performer->Location);
                if ($location === null)
                {
                    return false;
                }

                // Opponent controls this location.
                return $location->Controller != 0 && $location->Controller != $playerId;
            }
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    /**
     * Characters opposing the scheme controller at the location (not the controller's own).
     *
     * @return list<Character>
     */
    private function getOpposingCharactersAtLocation(Theah $theah, string $location, int $actingPlayerId): array
    {
        return array_values(array_filter(
            $theah->getCharactersAtLocation($location),
            fn(Character $character) => $character->ControllerId != 0
                && $character->ControllerId != $actingPlayerId
        ));
    }

    private function resolveLoseControl(Game $game, string $location, int $actingPlayerId): void
    {
        if ($game->theah->canLocationBecomeUncontrolledBy($actingPlayerId, $location))
        {
            $uncontrolledEvent = EventFactory::createLocationBecomesUncontrolledEvent($actingPlayerId, $location);
            $game->theah->eventCheck($uncontrolledEvent);
            $game->theah->queueEvent($uncontrolledEvent);

            $game->notify->all("message", clienttranslate('${player_name} chooses to lose control of ${location_name}.'), [
                "i18n" => ["location_name"],
                "player_name" => $game->getPlayerNameById($game->getActivePlayerId()),
                "location_name" => $location,
            ]);
        }
        else
        {
            $game->notify->all("message", clienttranslate('${location} cannot become uncontrolled.'), [
                'i18n' => ['location'],
                'location' => $location,
            ]);
        }

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($actingPlayerId);
        $game->theah->queueEvent($actionResolvedEvent);
    }

    private function resolveDecline(Game $game, string $location, int $actingPlayerId): void
    {
        $owner = $this->getOwningCard($game->theah);

        $game->notify->all("message", clienttranslate('${player_name} declines to lose control of ${location_name}. Opposing characters there are wounded.'), [
            "i18n" => ["location_name"],
            "player_name" => $game->getPlayerNameById($game->getActivePlayerId()),
            "location_name" => $location,
        ]);

        foreach ($this->getOpposingCharactersAtLocation($game->theah, $location, $actingPlayerId) as $character)
        {
            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $character->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->eventCheck($woundEvent);
            $game->theah->queueEvent($woundEvent);
        }

        $actionResolvedEvent = EventFactory::createActionResolvedEvent($actingPlayerId);
        $game->theah->queueEvent($actionResolvedEvent);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ($performer === null || $performer->ControllerId != $event->playerId)
            {
                throw new UserException($game->translate("Invalid performer"));
            }

            if (! $event->theah->cardInCity($performer))
            {
                throw new UserException($game->translate("Performer must be at a City location."));
            }

            $location = $event->theah->getCityLocation($performer->Location);
            if ($location === null || $location->Controller == 0 || $location->Controller == $event->playerId)
            {
                throw new UserException($game->translate("An opponent must control this location."));
            }

            $game->globals->set(Game::CHOSEN_LOCATION, $performer->Location);

            // Opponent who controls the location becomes active for the choice.
            $transition = EventFactory::createTransitionEvent(
                (int)$location->Controller,
                $owner->Id,
                "04034",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04034)
        {
            $location = $game->globals->get(Game::CHOSEN_LOCATION);
            $actingPlayerId = $this->getOwningCard($game->theah)->ControllerId;
            $args["location"] = $location;
            $args["canLoseControl"] = $game->theah->canLocationBecomeUncontrolledBy($actingPlayerId, $location);
            $args["performerId"] = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04034)
        {
            $location = $game->globals->get(Game::CHOSEN_LOCATION);
            $actingPlayerId = $this->getOwningCard($game->theah)->ControllerId;
            $controller = $game->theah->getCityLocation($location)?->Controller;

            if ($controller != $game->getActivePlayerId())
            {
                throw new UserException($game->translate("Only the player who controls this location may choose."));
            }

            // id 1 = lose control; id 2 = decline (wound opposing)
            if ($id == 1)
            {
                if (! $game->theah->canLocationBecomeUncontrolledBy($actingPlayerId, $location))
                {
                    throw new UserException($game->translate("This location cannot become uncontrolled."));
                }

                $this->resolveLoseControl($game, $location, $actingPlayerId);
                $game->gamestate->nextState("choiceMade");
                return;
            }

            if ($id == 2)
            {
                $this->resolveDecline($game, $location, $actingPlayerId);
                $game->gamestate->nextState("choiceMade");
                return;
            }

            throw new UserException($game->translate("Invalid choice."));
        }
    }
}
