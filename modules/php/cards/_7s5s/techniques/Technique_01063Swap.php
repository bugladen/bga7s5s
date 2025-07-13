<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;

class Technique_01063Swap extends Technique
{
    private int $swapId = 0;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Swap this Character with a Musketeer");
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventTechniqueActivated && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $characters = $event->theah->getCharactersAtLocation($owner->Location);
            $characters = array_filter($characters, fn($character) => 
                $character->Id != $owner->Id && 
                $character->ControllerId == $owner->ControllerId && 
                $character->hasTrait("Musketeer"));
            if (count($characters) > 0)
            {
                $transition = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "01063", $this->Id);
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $game = $event->theah->game;

            $inDuel = $game->globals->get(GAME::IN_DUEL);
            if ($inDuel)
            {
                $game = $event->theah->game;
                $duelId = $game->globals->get(Game::DUEL_ID);
                $round = $game->globals->get(Game::DUEL_ROUND);
                $owner = $this->getOwningCharacter($game->theah);

                $game->theah->swapParticipantsInDuel($duelId, $round, $owner->Id, $this->swapId);
            }
            else
            {
                //If not in a duel, this will only happen during a duel challenge, so player is the challenger
                $owner = $this->getOwningCharacter($game->theah);
                $newChallenger = $game->theah->getCharacterById($this->swapId);

                //Reset the conditions for challenger
                $owner->removeCondition(Game::DUEL_CHALLENGER);
                $owner->IsUpdated = true;

                $newChallenger->addCondition(Game::DUEL_CHALLENGER);
                $newChallenger->IsUpdated = true;

                $game->globals->set(Game::CHOSEN_PERFORMER, $newChallenger->Id);

                $challengerSwappedEvent = EventFactory::createChallengerSwappedEvent($owner->ControllerId, $owner->Id, $newChallenger->Id);
                $game->theah->queueEvent($challengerSwappedEvent);
            }
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array 
    {
        $args =  parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_01063 || $state == States::DUEL_CHOOSE_TECHNIQUE_01063)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $owner->Id;
            $characters = $game->theah->getCharactersAtLocation($owner->Location);
            $characters = array_values(array_filter($characters, fn($character) => 
                $character->Id != $owner->Id && 
                $character->ControllerId == $owner->ControllerId && 
                $character->hasTrait("Musketeer")));
            $args["characterIds"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void 
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_01063 || $state == States::DUEL_CHOOSE_TECHNIQUE_01063)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $musketeer = $game->theah->getCharacterById($id);
            if ($musketeer == null)
            {
                throw new \Exception($game->translate("Character not found"));
            }

            if ($musketeer->ControllerId != $owner->ControllerId)
            {
                throw new \Exception($game->translate("Character is not controlled by you."));
            }

            if (! $musketeer->hasTrait("Musketeer"))
            {
                throw new \Exception($game->translate("Character is not a Musketeer."));
            }

            if ($musketeer->Location != $owner->Location)
            {
                throw new \Exception(sprintf($game->translate("Character is not at the same location as %s."), $owner->Name));
            }

            if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_ACTIVATE_TECHNIQUE_01063)
                $game->notifyAllPlayers("message", $game->translate('${player_name} has used Technique [${technique_name}] to swap <strong>${challenger_name}</strong> with <strong>${musketeer_name}</strong>.'), [
                    "i18n" => ["technique_name", "player_name", "musketeer_name", "challenger_name"],
                    "player_name" => $owner->Name,
                    "technique_name" => $this->Name,
                    "musketeer_name" => $musketeer->Name,
                    "challenger_name" => $owner->Name,
                ]);

            $this->swapId = $musketeer->Id;
            $owner->IsUpdated = true;

            $game->gamestate->nextState();
        }
    }
}