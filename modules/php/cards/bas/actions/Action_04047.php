<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04047 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue Finesse Challenge (3+ Finesse Intervene)");
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
            fn(Character $c) => $c->hasTrait("Duelist")
                && $c->canChallenge($theah)
                && ! $c->Engaged
                && ! $c->DashedFinesse
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
            if (! $p->hasTrait("Duelist") || ! $p->canChallenge($theah) || $p->Engaged || $p->DashedFinesse)
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

    // WHY: Engage is the printed cost. Pay at announce so Night of Drinking (01109)
    // cancel — which deletes ActionTriggered — still leaves engage in the queue.
    // ("All costs are still paid.") Keep CELERITY off stIssueChallenge's auto-engage
    // list so a non-cancelled play does not double-engage. Mirror Action_03057.
    public function announceAction(Game $game): void
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);
        if ($performer && ! $performer->Engaged)
        {
            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id);
            $game->theah->queueEvent($engageEvent);
        }

        parent::announceAction($game);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            // WHY CELERITY_CHALLENGE_TYPE (not AJA): intervene-only Finesse ≥ 3 gate.
            // AJA also blocks refuse when defender Finesse < 3 — Celerity does not.
            // Type stays off auto-engage (engage paid in announceAction).
            $game->globals->set(Game::CHALLENGE_TYPE, Game::CELERITY_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, '04047', $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        return array_values($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId));
    }
}
