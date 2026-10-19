<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01078 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = "Enemy Character Issues Challenge";
        $this->RequiresPerformerSelected = true;
    }

    private function getQualifiedCharacters(int $playerId, Theah $theah): array
    {
        // Get enemy characters in the city
        $characters = $theah->getCharactersInPlay();
        $characters = array_filter($characters, fn($character) => $character->isNotControlledByPlayer($playerId) && $theah->cardInCity($character) && $character->canChallenge($theah));

        //Filter characters that have a friendly character opposing them
        $qualifiedCharacters = [];
        foreach ($characters as $character)
        {
            $opposingFriendlies = $theah->getCharactersAtLocation($character->Location);
            $opposingFriendlies = array_filter($opposingFriendlies, fn($friendly) => $friendly->ControllerId == $playerId);
            if (count($opposingFriendlies) > 0)
            {
                $qualifiedCharacters[] = $character;
            }
        }

        return $qualifiedCharacters;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $qualifiedCharacters = $this->getQualifiedCharacters($playerId, $theah);

        return count($qualifiedCharacters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        //We will not call the parent method because this action is unique in that enemy characters will be the performers
        $qualifiedCharacters = $this->getQualifiedCharacters($playerId, $theah);
        return $qualifiedCharacters;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCard($game->theah);
        $playerId = $owner->ControllerId;
        $theah = $game->theah;

        if ( ! $character->isNotControlledByPlayer($playerId))
        {
            return [false, $game->translate("Target must be an enemy character.")];
        }

        if ( ! $theah->cardInCity($character))
        {
            return [false, $game->translate("Target must be in the city.")];
        }

        if ( ! $character->canChallenge($theah))
        {
            return [false, $game->translate("Target cannot challenge.")];
        }

        $opposingFriendlies = $theah->getCharactersAtLocation($character->Location);
        $opposingFriendlies = array_filter($opposingFriendlies, fn($friendly) => $friendly->ControllerId == $playerId);
        if (count($opposingFriendlies) == 0)
        {
            return [false, $game->translate("Target has no friendly characters opposing them.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            //Transition to the player owning the selected performer.  This will jump in the the challenge state.
            $owner = $this->getOwningCard($event->theah);
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::DEFENDING_HONOR_CHALLENGE_TYPE);

            $game->notify->all("message", clienttranslate('${owner_inject_code}: ${target_inject_code} is the target.'), [
                "owner_inject_code" => $owner->getInjectCode(),
                "target_inject_code" => $performer->getInjectCode(),
            ]);

            // WHY: The picker transition is not queued here. Unyielding Loyalty cancels
            // this targeting and deleteEventBatch's the sibling. Queuing "01078" now
            // left that transition behind, so the challenge picker still opened after
            // the cost was paid. It is emitted only when this event survives (below).
            $batchId = $game->getNextEventBatchId();
            $eventCharacterTargeted = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $eventCharacterTargeted->batchId = $batchId;
            $event->theah->queueEvent($eventCharacterTargeted);

            // createActionResolvedEvent() is called when the challenge is resolved
        }

        // For this Action, the ability's target IS the performer (the enemy character who will
        // issue the challenge). If a reaction (e.g. Vittoria) redirects the target, the
        // re-queued EventCharacterTargeted carries the new targetId — keep CHOSEN_PERFORMER
        // in sync so the downstream challenge state uses the right performer/location.
        //
        // WHY the transition lives here: Pass / redirect re-deliver this event, so the
        // picker is rebuilt for the (possibly new) performer. A paid cancel does not
        // re-deliver, so the challenge never starts. Same batch as the targeting event
        // so a same-pass cancel still strips a transition this handler already queued.
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $game->globals->set(Game::CHOSEN_PERFORMER, $event->targetId);
            $performer = $event->theah->getCharacterById($event->targetId);
            if ($performer === null)
            {
                return;
            }

            $owner = $this->getOwningCard($event->theah);
            $event->theah->deleteQueuedTransitionsNamed("01078");
            $transitionEvent = EventFactory::createTransitionEvent($performer->ControllerId, $owner->Id, "01078", $this->Id);
            $transitionEvent->batchId = $event->batchId;
            $event->theah->queueEvent($transitionEvent);
        }
    }
}