<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03069a extends Maneuver
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Swap Participant with Other Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        return count($this->getOtherCharactersAtDuelLocation($theah, $playerId)) > 0;
    }

    // WHY: Keep the maneuver button visible when Harpooned so the player can attempt
    // it and see the UserException. Hiding via isAvailableToPlayer made the restriction
    // invisible (same shape as Technique_01063Swap / Technique_03013). Fire on
    // ManeuverActivated before the character picker so failure is immediate.
    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventManeuverActivated && $event->maneuverId == $this->Id)
        {
            $actor = $event->theah->getDuelRoundActor();
            if ($actor !== null
                && $event->theah->game->globals->get(Game::IN_DUEL, false)
                && $actor->hasCondition(Game::HARPOON_CONDITION))
            {
                throw new UserException(sprintf(
                    $event->theah->game->translate("%s is Harpooned and cannot be swapped for the remainder of the duel."),
                    $actor->Name
                ));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03069", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_03069)
        {
            $actor = $game->theah->getDuelRoundActor();
            $characters = $this->getOtherCharactersAtDuelLocation($game->theah, $actor->ControllerId);
            $args['ids'] = array_map(fn(Character $character) => $character->Id, $characters);
            $args['performerId'] = $actor->Id;
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_03069)
        {
            $actor = $game->theah->getDuelRoundActor();
            $target = $game->theah->getCharacterById($id);
            if ($target === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            if ($target->ControllerId != $actor->ControllerId)
            {
                throw new UserException($game->translate("You may only swap with a character you control."));
            }

            if ($target->Id == $actor->Id)
            {
                throw new UserException($game->translate("You cannot swap your participant with themselves."));
            }

            if ($target->Location != $actor->Location)
            {
                throw new UserException($game->translate("Character must be at this location."));
            }

            // WHY: Fail on confirm (not later in swapParticipantsInDuel alone) so the
            // player is not left mid-chooser after a soft failure message. Central
            // swapParticipantsInDuel still gates too — activate-time covers early fail.
            if ($actor->hasCondition(Game::HARPOON_CONDITION))
            {
                throw new UserException(sprintf(
                    $game->translate("%s is Harpooned and cannot be swapped for the remainder of the duel."),
                    $actor->Name
                ));
            }

            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} swaps ${actor_inject_code} with ${target_inject_code}.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "actor_inject_code" => $actor->getInjectCode(),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            $duelId = $game->globals->get(Game::DUEL_ID);
            $round = $game->globals->get(Game::DUEL_ROUND);
            $game->theah->swapParticipantsInDuel($duelId, $round, $actor->Id, $target->Id);

            $game->gamestate->nextState();
        }
    }

    /**
     * @return Character[]
     */
    protected function getOtherCharactersAtDuelLocation(Theah $theah, int $playerId): array
    {
        $actor = $theah->getDuelRoundActor();
        if ($actor === null)
        {
            return [];
        }

        $characters = $theah->getCharactersAtLocationByPlayerId($actor->Location, $playerId);
        return array_values(array_filter($characters, fn(Character $character) => $character->Id != $actor->Id));
    }
}
