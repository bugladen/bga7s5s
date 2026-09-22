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

class Action_04004 extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move performer to a location with a wounded enemy");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<string>
     */
    private function getDestinationsWithWoundedEnemy(Theah $theah, Character $performer): array
    {
        $destinations = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Name == $performer->Location)
            {
                continue;
            }

            foreach ($theah->getCharactersAtLocation($location->Name) as $character)
            {
                if ($character->ControllerId != $performer->ControllerId && $character->Wounds > 0)
                {
                    $destinations[] = $location->Name;
                    break;
                }
            }
        }

        return $destinations;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        // WHY: "Duelist City Action" is a mechanical trait gate, not ISorcererAbility.
        // Full legality: at least one other City location has a wounded enemy.
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Duelist")
                && count($this->getDestinationsWithWoundedEnemy($theah, $performer)) > 0
        ));
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

            if (! $performer->hasTrait("Duelist"))
            {
                throw new UserException($game->translate("Performer must be a Duelist."));
            }

            if (! $event->theah->cardInCity($performer))
            {
                throw new UserException($game->translate("Performer must be at a City location."));
            }

            if (count($this->getDestinationsWithWoundedEnemy($event->theah, $performer)) == 0)
            {
                throw new UserException($game->translate("No City location has a wounded enemy."));
            }

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04004", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04004)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args["performerId"] = $performerId;
            $args["locationIds"] = $performer !== null
                ? $this->getDestinationsWithWoundedEnemy($game->theah, $performer)
                : [];
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04004)
        {
            $owner = $this->getOwningCard($game->theah);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found"));
            }

            $locationName = $ids[0];
            $validLocations = $this->getDestinationsWithWoundedEnemy($game->theah, $performer);
            if (! in_array($locationName, $validLocations, true))
            {
                throw new UserException($game->translate("Location must be a City location with a wounded enemy."));
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} moves ${performer_inject_code} to ${location_name}.'), [
                "i18n" => ["location_name"],
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "performer_inject_code" => $performer->getInjectCode(),
                "location_name" => $locationName,
            ]);

            // WHY: engage=false — printed text says Move only (no Engage).
            $moveEvent = EventFactory::createCardMovingEvent(
                $performer->ControllerId,
                $performer->Id,
                $performer->Location,
                $locationName,
                false,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("locationChosen");
        }
    }
}
