<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01033 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue Influence Challenge");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        return count($characters) > 0;        
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $performers = (array_filter($performers, fn($performer) => $performer->canChallenge()));
        $performers = array_values(array_filter($performers, fn($performer) => ! $performer->DashedInfluence));
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
            return [false, $game->translate("You cannot challenge a character that is not at the same location as you.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);

            $game = $event->theah->game;
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_INFLUENCE);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::VERONICAS_GUILLE_CHALLENGE_TYPE);
            
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01033", $this->Id);
            $event->theah->queueEvent($transition);

            //resetPlayerPassCount is called in stSetupChallenge
            // $this->setUsed() is called in stSetupChallenge        
            //createActionResolvedEvent() is called when the challenge is resolved
        }
    }
}