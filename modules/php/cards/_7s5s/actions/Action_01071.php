<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01071 extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public bool $firstWoundOccured = false;
    
    public function __construct()
    {
        parent::__construct();

        $this->RequiresPerformerSelected = true;
        $this->Name = clienttranslate("Issue Fight Challenge");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->hasTrait("Musketeer") && $character->canChallenge($theah));

        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        $performers = array_filter($performers, fn($performer) => $performer->hasTrait("Musketeer") && $performer->canChallenge($theah));

        return $performers;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You cannot challenge a character that is controlled by you.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the same location as the performer")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $event->theah->game->globals->set(Game::CHALLENGE_TYPE, Game::EPEE_SANGLANTE_CHALLENGE_TYPE);
            $event->theah->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01071", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }

        if ($event instanceof EventCharacterWounded)
        {
            $scheme = $this->getOwningCard($event->theah);
            if ($scheme->Location == Game::LOCATION_PLAYER_HOME)
            {
                $game = $event->theah->game;
                $inDuel = $game->globals->get(Game::IN_DUEL);
                $challengeType = $game->globals->get(Game::CHALLENGE_TYPE);
                if ($inDuel && $challengeType == Game::EPEE_SANGLANTE_CHALLENGE_TYPE && ! $this->firstWoundOccured)
                {
                    $woundedCharacter = $event->theah->getCharacterById($event->characterId);
                    $woundedPlayerReknown = $game->getPlayerReknown($woundedCharacter->ControllerId);
                    $agressor = $event->theah->getCharacterById($event->sourceId);

                    if ($woundedPlayerReknown > 0)
                    {
                        $stealEvent = EventFactory::createPlayerGainsReknownEvent($agressor->ControllerId, 1);
                        $event->theah->queueEvent($stealEvent);
                        
                        $loseEvent = EventFactory::createPlayerLosesReknownEvent($woundedCharacter->ControllerId, 1);
                        $event->theah->queueEvent($loseEvent);
                        
                        $owner = $this->getOwningCard($event->theah);
                        $game->notify->all("message", clienttranslate('${action_inject_code}: ${agressor_name} is the first player to wound in this duel. They will steal 1 Renown from ${player_name}.'), [
                            "action_inject_code" => $owner->getInjectCode(),
                            "agressor_name" => $game->getPlayerNameById($agressor->ControllerId),
                            "player_name" => $game->getPlayerNameById($woundedCharacter->ControllerId),
                        ]);
                    }
                    else
                    {
                        $owner = $this->getOwningCard($event->theah);
                        $game->notify->all("message", clienttranslate('${action_inject_code}: ${agressor_name} is the first player to wound in this duel. However, ${player_name} has no Renown to steal.'), [
                            "action_inject_code" => $owner->getInjectCode(),
                            "agressor_name" => $game->getPlayerNameById($agressor->ControllerId),
                            "player_name" => $game->getPlayerNameById($woundedCharacter->ControllerId),
                        ]);
                    }

                    $this->firstWoundOccured = true;
                    $scheme->IsUpdated = true;
                }
            }
        }

        if ($event instanceof EventDuelEnd)
        {
            $scheme = $this->getOwningCard($event->theah);
            if ($scheme->Location == Game::LOCATION_PLAYER_HOME)
            {
                $this->firstWoundOccured = false;
                $scheme->IsUpdated = true;
            }
        }
    }
}