<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02061 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue Unrefusable Combat Challenge");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        $duelists = array_filter($characters, fn($c) => $c->hasTrait("Duelist") && !$c->Engaged && $c->canChallenge($theah));

        foreach ($duelists as $duelist)
        {
            $adversaries = $theah->getOpposingCharactersAtLocation($duelist->Location, $playerId);
            $targets = array_filter($adversaries, fn($c) => !$c->hasTrait("Leader"));
            if (count($targets) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $duelists = array_values(array_filter($performers, fn($c) => $c->hasTrait("Duelist") && !$c->Engaged && $c->canChallenge($theah)));

        $eligible = [];
        foreach ($duelists as $duelist)
        {
            $adversaries = $theah->getOpposingCharactersAtLocation($duelist->Location, $playerId);
            $targets = array_filter($adversaries, fn($c) => !$c->hasTrait("Leader"));
            if (count($targets) > 0)
            {
                $eligible[] = $duelist;
            }
        }

        return $eligible;
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
            return [false, $game->translate("Character is not at the same location as the performer.")];
        }

        if ($character->hasTrait("Leader"))
        {
            return [false, $game->translate("You cannot target a Leader.")];
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
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::UNSANCTIONED_DUEL_CHALLENGE_TYPE);

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, '02061', $this->Id);
            $event->theah->queueEvent($transition);

            // Engagement is handled in stSetupChallenge
            // createActionResolvedEvent() is called when the challenge is resolved
        }

        if ($event instanceof EventGenerateChallengeThreat)
        {
            $challengeType = $event->theah->game->globals->get(Game::CHALLENGE_TYPE);
            if ($challengeType == Game::UNSANCTIONED_DUEL_CHALLENGE_TYPE)
            {
                $owner = $this->getOwningCard($event->theah);
                $event->actorThreat += 1;
                $event->adversaryThreat += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s: Adds 1 Threat to both participants."), $owner->getInjectCode());
            }
        }
    }
}
