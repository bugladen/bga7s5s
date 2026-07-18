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

class Action_03057 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue Influence Challenge; Claim if Refused");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $characters = array_filter(
            $characters,
            fn(Character $c) => $c->canChallenge($theah) && ! $c->Engaged && ! $c->DashedInfluence
        );

        foreach ($characters as $character)
        {
            if (count($this->getValidTargets($theah, $character)) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter($performers, function (Character $p) use ($theah) {
            if (! $p->canChallenge($theah) || $p->Engaged || $p->DashedInfluence)
            {
                return false;
            }
            return count($this->getValidTargets($theah, $p)) > 0;
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

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            // WHY: Engage is the printed cost. Keep CENSURE out of stIssueChallenge's
            // auto-engage list so we do not double-engage.
            if ($performer && ! $performer->Engaged)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id);
                $event->theah->queueEvent($engageEvent);
            }

            $owner = $this->getOwningCard($event->theah);
            // WHY CENSURE_CHALLENGE_TYPE: (1) correlator for refuse→claim on the Risk
            // (EventChallengeRejected has no actionId); (2) stays off the auto-engage list.
            $game->globals->set(Game::CHALLENGE_TYPE, Game::CENSURE_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_INFLUENCE);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, '03057', $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }
    }

    private function getValidTargets(Theah $theah, Character $performer): array
    {
        return array_values($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId));
    }
}
