<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03022 extends Maneuver implements IAbilityThatTargetsCharacters
{
    private int $FinalStrikeParticipantId;
    private string $DuelLocation;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Final Strike: En Garde Target and Draw");

        $this->FinalStrikeParticipantId = 0;
        $this->DuelLocation = "";
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor === null)
        {
            return false;
        }

        $targets = $this->getValidTargets($theah, $actor->Location);
        return count($targets) > 0;
    }

    private function getResolutionLocation(Theah $theah): string
    {
        if ($this->DuelLocation !== "")
        {
            return $this->DuelLocation;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor !== null ? $actor->Location : "";
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->FinalStrikeParticipantId = $event->theah->getDuelOpponentId($event->adversaryId);
            $participant = $event->theah->getCharacterById($this->FinalStrikeParticipantId);
            $this->DuelLocation = $participant->Location;
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->FinalStrikeParticipantId)
        {
            $game = $event->theah->game;
            $inDuel = $game->globals->get(Game::IN_DUEL);
            if ($inDuel)
            {
                $character = $event->theah->getCharacterById($this->FinalStrikeParticipantId);
                $owner = $this->getOwningCard($game->theah);
                $playerId = $character->ControllerId;

                if ($character->hasTrait('Zealot') || $character->hasTrait('Hunter'))
                {
                    $cardDrawEvent = EventFactory::createCardDrawnEvent($playerId, clienttranslate("Overzealous"));
                    $event->theah->queueEvent($cardDrawEvent);

                    $game->notify->all("message", clienttranslate('${maneuver_inject_code}: ${character_inject_code} was a ${trait} and triggered Final Strike. ${performer} draws a card.'), [
                        "maneuver_inject_code" => $owner->getInjectCode(),
                        "character_inject_code" => $character->getInjectCode(),
                        "trait" => ($character->hasTrait('Zealot') ? clienttranslate('Zealot') : clienttranslate('Hunter')),
                        "performer" => $game->getPlayerNameById($playerId),
                    ]);
                }
                else
                {
                    $game->notify->all("message", clienttranslate('${maneuver_inject_code}: ${character_inject_code} was not a Zealot or Hunter.'), [
                        "maneuver_inject_code" => $owner->getInjectCode(),
                        "character_inject_code" => $character->getInjectCode(),
                    ]);
                }

                $transitionEvent = EventFactory::createTransitionEvent($playerId, $owner->Id, "03022", $this->Id);
                $event->theah->queueEvent($transitionEvent);
            }
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->FinalStrikeParticipantId = 0;
            $this->DuelLocation = "";
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelNewRound && $this->FinalStrikeParticipantId != 0)
        {
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
            $this->FinalStrikeParticipantId = 0;
            $this->DuelLocation = "";
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_END_OF_ROUND_03022)
        {
            $location = $this->getResolutionLocation($game->theah);
            $targets = $this->getValidTargets($game->theah, $location);
            $args['characterIds'] = array_map(fn($character) => $character->Id, $targets);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $location = $this->getResolutionLocation($game->theah);

        if ($character->Location != $location)
        {
            return [false, $game->translate("Character is not at this location")];
        }

        if ($game->characterIsInDiscardOrLocker($character))
        {
            return [false, $game->translate("Character is not in play")];
        }

        if (! $character->Engaged)
        {
            return [false, $game->translate("Character is already En Garded.")];
        }

        return [true, ""];
    }

    public function actFromManeuverPass(Game $game, int $state): void
    {
        parent::actFromManeuverPass($game, $state);

        if ($state == States::DUEL_END_OF_ROUND_03022)
        {
            $location = $this->getResolutionLocation($game->theah);
            $targets = $this->getValidTargets($game->theah, $location);
            if (count($targets) > 0)
            {
                throw new UserException($game->translate("There are engaged characters at this location — you must choose one to En Garde."));
            }

            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${maneuver_inject_code}: No engaged characters at this location to En Garde.'), [
                "maneuver_inject_code" => $owner->getInjectCode(),
            ]);

            $game->gamestate->nextState();
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_END_OF_ROUND_03022)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);

            $engardeEvent = EventFactory::createCardEngardedEvent($character->ControllerId, $character->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engardeEvent);

            $game->notify->all("message", clienttranslate('${maneuver_inject_code}: ${player_name} En Gardes ${character_inject_code}.'), [
                "maneuver_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $game->gamestate->nextState();
        }
    }

    private function getValidTargets(Theah $theah, string $location): array
    {
        $characters = $theah->getCharactersAtLocation($location);
        return array_values(array_filter($characters, function($character) use ($theah) {
            if ($theah->game->characterIsInDiscardOrLocker($character))
            {
                return false;
            }
            return $character->Engaged;
        }));
    }
}
