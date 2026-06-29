<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02028 extends RiskAction implements IAbilityThatTargetsCharacters
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

        $diplomats = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $diplomats = array_filter($diplomats, fn($c) => $c->hasTrait("Diplomat"));
        $diplomats = array_filter($diplomats, fn($c) => $c->canChallenge($theah) && ! $c->DashedInfluence);

        foreach ($diplomats as $diplomat)
        {
            $opponents = $theah->getOpposingCharactersAtLocation($diplomat->Location, $playerId);
            $opponents = array_filter($opponents, fn($c) => $c->ModifiedInfluence >= 1);
            if (count($opponents) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        $performers = array_filter($performers, fn($c) => $c->hasTrait("Diplomat"));
        $performers = array_filter($performers, fn($c) => $c->canChallenge($theah) && ! $c->DashedInfluence);
        return array_values($performers);
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

        if ($character->ModifiedInfluence < 1)
        {
            return [false, $game->translate("Target character must have at least 1 Influence.")];
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
            $game->globals->set(Game::CHALLENGE_TYPE, Game::BATTLE_OF_WITS_CHALLENGE_TYPE);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02028", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() not needed
        }
    }
}
