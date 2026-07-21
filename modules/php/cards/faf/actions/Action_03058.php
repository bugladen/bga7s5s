<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03058 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue Combat Challenge (Outnumbered)");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        $performers = array_filter(
            $performers,
            fn(Character $c) => $c->hasTrait("Duelist") && $c->canChallenge($theah)
        );

        foreach ($performers as $performer)
        {
            if (count($this->getValidTargets($theah, $performer)) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter($performers, function (Character $performer) use ($theah) {
            if (! $performer->hasTrait("Duelist") || ! $performer->canChallenge($theah))
            {
                return false;
            }
            return count($this->getValidTargets($theah, $performer)) > 0;
        }));
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        if (! $this->controllerHasMoreCharactersAtLocation($game->theah, $character, $performer))
        {
            return [false, $game->translate("Their controller must have more characters at this location than you.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03058", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $adversaries = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        return array_values(array_filter(
            $adversaries,
            fn(Character $adversary) => $adversary->ControllerId != 0
                && $this->controllerHasMoreCharactersAtLocation($theah, $adversary, $performer)
        ));
    }

    private function controllerHasMoreCharactersAtLocation(Theah $theah, Character $target, Character $performer): bool
    {
        $theirCount = count($theah->getCharactersAtLocationByPlayerId($target->Location, $target->ControllerId));
        $yourCount = count($theah->getCharactersAtLocationByPlayerId($performer->Location, $performer->ControllerId));
        return $theirCount > $yourCount;
    }
}
