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

class Action_03021 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue Challenge to Sorcerer or Monster");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_filter($characters, fn(Character $c) => $c->canChallenge($theah) && ! $c->Engaged);

        foreach ($characters as $character) {
            if (count($this->getValidTargets($theah, $character)) > 0) {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter($performers, function (Character $p) use ($theah) {
            if (! $p->canChallenge($theah) || $p->Engaged) {
                return false;
            }
            return count($this->getValidTargets($theah, $p)) > 0;
        }));
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0) {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $performer->Location) {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        if (! ($character->hasTrait('Sorcerer') || $character->hasTrait('Monster'))) {
            return [false, $game->translate("Target must have the Sorcerer or Monster trait.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $performerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            // Engage the performer first
            if ($performer && ! $performer->Engaged) {
                $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id);
                $event->theah->queueEvent($engageEvent);
            }

            $owner = $this->getOwningCard($event->theah);
            $event->theah->game->globals->set(Game::CHALLENGE_TYPE, Game::CORNERED_CHALLENGE_TYPE);
            $event->theah->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, '03021', $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }
    }

    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $adversaries = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        $adversaries = array_filter(
            $adversaries,
            fn(Character $c) => $c->hasTrait('Sorcerer') || $c->hasTrait('Monster')
        );
        return array_values($adversaries);
    }
}
