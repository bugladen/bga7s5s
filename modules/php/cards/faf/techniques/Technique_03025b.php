<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03025b extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move Both Participants to Any City Location");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        // Gambling Technique: actor must have gambled for their combat card this round.
        if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03025b", $this->Id);
            $event->theah->queueEvent($transitionEvent);
            $this->setUsed($event->theah, true);
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03025B)
        {
            $args["locationIds"] = array_keys($game->theah->getCityLocations());
        }

        return $args;
    }

    public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromTechniqueWithIds($game, $state, $stateName, $ids);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03025B)
        {
            $location = $ids[0];
            if (! array_key_exists($location, $game->theah->getCityLocations()))
            {
                throw new UserException($game->translate('Invalid location.'));
            }

            $actor = $game->theah->getDuelRoundActor();
            $adversaryId = $game->theah->getDuelOpponentId($actor->Id);
            $adversary = $game->theah->getCharacterById($adversaryId);

            $game->notify->all("message", clienttranslate('${player_name} has chosen to move both participants to ${location_name}.'), [
                "i18n" => ["location_name"],
                "player_name" => $game->getPlayerNameById($actor->ControllerId),
                "location_name" => $location,
            ]);

            $moveEvent = EventFactory::createCardMovingEvent($adversary->ControllerId, $adversary->Id, $adversary->Location, $location, $engage = false, $actor->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            $moveEvent = EventFactory::createCardMovingEvent($actor->ControllerId, $actor->Id, $actor->Location, $location, $engage = false, $actor->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            $game->gamestate->nextState();
        }
    }
}
