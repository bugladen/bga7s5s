<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03040 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Pressure with Finesse; claim or engage opposing character");
    }

    /**
     * @return Character[] unengaged opposing characters at Soline's location
     */
    private function getEngageableOpponents(Theah $theah, Character $soline): array
    {
        $opponents = $theah->getOpposingCharactersAtLocation($soline->Location, $soline->ControllerId);
        return array_values(array_filter($opponents, fn($character) => ! $character->Engaged));
    }

    private function isValidEngageCandidate(Character $soline, Character $character): bool
    {
        if ($character->ControllerId == $soline->ControllerId || $character->ControllerId == 0)
        {
            return false;
        }

        if ($character->Location != $soline->Location)
        {
            return false;
        }

        if ($character->Engaged)
        {
            return false;
        }

        return true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $soline = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($soline))
        {
            return false;
        }

        if ($soline->Engaged)
        {
            return false;
        }

        if (! $soline->canPressure(Game::STAT_FINESSE))
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId === $this->Id)
        {
            $game = $event->theah->game;
            $soline = $this->getOwningCharacter($event->theah);

            $game->globals->set(Game::PRESSURING_PLAYER, $soline->ControllerId);
            $game->globals->set(Game::CHOSEN_PERFORMER, $soline->Id);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);
            $game->globals->set(Game::PRESSURE_STAT, Game::STAT_FINESSE);
            $game->setGlobalFlag(Game::PRESSURE_TYPE, Game::SOLINE_PRESSURE_TYPE);

            $engageEvent = EventFactory::createCardEngagedEvent($soline->ControllerId, $soline->Id, $soline->Id, $this->Id);
            $event->theah->queueEvent($engageEvent);

            $pressureStats = $event->theah->getPressureStats($soline, $soline->Location, Game::STAT_FINESSE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent(
                $soline->ControllerId,
                $soline->Id,
                $soline->Location,
                $pressureStats
            );
            $event->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($soline->ControllerId, $soline->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            $soline = $this->getOwningCharacter($event->theah);

            if ($event->success)
            {
                $canClaim = $event->theah->canLocationBeClaimedBy($soline->ControllerId, $soline->Location);
                $engageable = $this->getEngageableOpponents($event->theah, $soline);

                if ($canClaim || count($engageable) > 0)
                {
                    $transitionEvent = EventFactory::createTransitionEvent($soline->ControllerId, $soline->Id, "03040", $this->Id);
                    $event->theah->queueEvent($transitionEvent);
                    return;
                }

                $event->theah->game->notify->all("message", clienttranslate('${location} cannot be claimed and there is no opposing character to engage.'), [
                    'i18n' => ['location'],
                    'location' => $soline->Location,
                ]);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($soline->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03040)
        {
            $soline = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $soline->Id;
            $args['canClaim'] = $game->theah->canLocationBeClaimedBy($soline->ControllerId, $soline->Location);

            $engageable = $this->getEngageableOpponents($game->theah, $soline);
            $args['ids'] = array_values(array_map(fn($character) => $character->Id, $engageable));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03040)
        {
            $soline = $this->getOwningCharacter($game->theah);

            // id 0 = Claim the location
            if ($id == 0)
            {
                if (! $game->theah->canLocationBeClaimedBy($soline->ControllerId, $soline->Location))
                {
                    throw new UserException($game->translate("Location cannot be claimed."));
                }

                $claimEvent = EventFactory::createLocationClaimedEvent($soline->ControllerId, $soline->Id, $soline->Location);
                $game->theah->queueEvent($claimEvent);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($soline->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $game->gamestate->nextState("choiceMade");
                return;
            }

            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            if (! $this->isValidEngageCandidate($soline, $character))
            {
                throw new UserException($game->translate("Must engage an unengaged opposing character at Soline's location."));
            }

            $engageEvent = EventFactory::createCardEngagedEvent($soline->ControllerId, $character->Id, $soline->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            $game->notify->all("message", clienttranslate('${action_inject_code}: ${player_name} engages ${character_inject_code}.'), [
                "action_inject_code" => $soline->getInjectCode(),
                "player_name" => $game->getPlayerNameById($soline->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($soline->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("choiceMade");
        }
    }
}
