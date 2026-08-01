<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_01063Swap extends Technique
{
    private int $swapId = 0;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Swap this Character with a Musketeer");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        $owner = $this->getOwningCharacter($theah);
        $characters = $theah->getCharactersAtLocation($owner->Location);
        $characters = array_filter($characters, fn($character) => 
            $character->Id != $owner->Id && 
            $character->ControllerId == $owner->ControllerId && 
            $character->hasTrait("Musketeer"));

        return count($characters) > 0;
    }

    // WHY: Keep the technique button visible when Harpooned so the player can attempt
    // it and see the UserException. Hiding via isAvailableToPlayer made the restriction
    // invisible. Fire on TechniqueActivated (before the Musketeer picker) so the failure
    // is immediate on the technique button click.
    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventTechniqueActivated && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null
                && $event->theah->game->globals->get(Game::IN_DUEL, false)
                && $owner->hasCondition(Game::HARPOON_CONDITION))
            {
                throw new UserException(sprintf($event->theah->game->translate("%s is Harpooned and cannot be swapped for the remainder of the duel."), $owner->Name));
            }
        }
    }



    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "01063", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id)
        {
            //If not in a duel, this will only happen during a duel challenge, so player is the challenger
            $game = $event->theah->game;

            $newChallenger = $game->theah->getCharacterById($this->swapId);
            $game->globals->set(Game::CHOSEN_PERFORMER, $newChallenger->Id);

            // WHY: Redirect the event's actor to the swapped character so that
            // Character::handleEvent (which adds the actor's stat to adversaryThreat
            // when actorId matches) and the EventHub threat notification both use
            // the new challenger instead of the original challenger.
            $event->actorId = $newChallenger->Id;

            // WHY: GENERATE_THREAT runs on rejection too (to wound the target via
            // CHALLENGER_THREAT). Without this guard the swap re-adds DUEL_CHALLENGER
            // to the swap target after EventChallengeRejected has already cleaned up
            // the original challenger, leaving the condition stuck on a character that
            // never enters a duel.
            if (! $game->globals->get(Game::CHALLENGE_ACCEPTED, false))
            {
                return;
            }

            $owner = $this->getOwningCharacter($game->theah);

            //Reset the conditions for challenger
            $owner->removeCondition(Game::DUEL_CHALLENGER);
            $owner->IsUpdated = true;

            $newChallenger->addCondition(Game::DUEL_CHALLENGER);
            $newChallenger->IsUpdated = true;

            $challengerSwappedEvent = EventFactory::createChallengerSwappedEvent($owner->ControllerId, $owner->Id, $newChallenger->Id);
            $game->theah->queueEvent($challengerSwappedEvent);
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->swapId = 0;
            $owner = $this->getOwningCharacter($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)    
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
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array 
    {
        $args =  parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01063 || $state == States::DUEL_CHOOSE_TECHNIQUE_01063)
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

        if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01063 || $state == States::DUEL_CHOOSE_TECHNIQUE_01063)
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

            // WHY: Fail on confirm (not later in CalculateTechniqueValues) so the player can
            // use the back button instead of leaving a half-resolved technique in the queue.
            if ($state == States::DUEL_CHOOSE_TECHNIQUE_01063 && $owner->hasCondition(Game::HARPOON_CONDITION))
            {
                throw new UserException(sprintf($game->translate("%s is Harpooned and cannot be swapped for the remainder of the duel."), $owner->Name));
            }

            $game->notifyAllPlayers("message", $game->translate('${player_name} has used Technique [${technique_name}] to swap ${challenger_inject_code} with ${musketeer_inject_code}.'), [
                "i18n" => ["technique_name"],
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "technique_name" => $this->Name,
                "musketeer_inject_code" => $musketeer->getInjectCode(),
                "challenger_inject_code" => $owner->getInjectCode(),
            ]);

            $this->swapId = $musketeer->Id;
            $game->updateCardObjectInDb($owner);

            // WHY: Duel chooser has "back" + "characterChosen"; bare nextState() is ambiguous.
            // Challenge resolve still has a single "" transition.
            if ($state == States::DUEL_CHOOSE_TECHNIQUE_01063)
            {
                $game->gamestate->nextState("characterChosen");
            }
            else
            {
                $game->gamestate->nextState();
            }
        }
    }
}