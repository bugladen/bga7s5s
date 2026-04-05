<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\maneuvers;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_02038 extends Maneuver
{
    private string $RequiredTrait;
    private string $StatType;

    public function __construct(string $requiredTrait, string $statType, string $name)
    {
        parent::__construct();

        $this->RequiredTrait = $requiredTrait;
        $this->StatType = $statType;
        $this->Name = $name;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if (! $actor->hasTrait($this->RequiredTrait))
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();

        $actorStat = match($this->StatType) {
            'Combat' => $actor->ModifiedCombat,
            'Finesse' => $actor->ModifiedFinesse,
        };
        $adversaryStat = match($this->StatType) {
            'Combat' => $adversary->ModifiedCombat,
            'Finesse' => $adversary->ModifiedFinesse,
        };

        return $actorStat > $adversaryStat;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();

            $transitionEvent = EventFactory::createTransitionEvent($adversary->ControllerId, $owner->Id, "02038", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_02038)
        {
            $owner = $this->getOwningCard($game->theah);
            $adversaryId = $game->theah->getDuelOpponentId($owner->ControllerId);

            if ($id == 1)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversaryId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);

                $adversary = $game->theah->getCharacterById($adversaryId);
                $game->notify->all("message", clienttranslate('${player_name} has chosen to suffer a wound.'), [
                    "player_name" => $game->getPlayerNameById($adversary->ControllerId),
                ]);
            }

            if ($id == 2)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
                $game->theah->queueEvent($drawEvent);

                $adversary = $game->theah->getCharacterById($adversaryId);
                $game->notify->all("message", clienttranslate('${player_name} has chosen to let their opponent draw a card.'), [
                    "player_name" => $game->getPlayerNameById($adversary->ControllerId),
                ]);
            }
        }

        $game->gamestate->nextState();
    }
}
