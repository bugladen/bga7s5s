<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04002 extends CharacterAction implements IAbilityThatTargetsCharacters
{
    // WHY: Persist across nextState into the wound/draw choice (card serialization).
    public int $IntervenerId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage Danilo. Issue an Influence Challenge.");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $danilo = $this->getOwningCharacter($theah);

        if (! $theah->cardInCity($danilo))
        {
            return false;
        }

        // WHY Engage printed: trichotomy (a) — only unengaged Danilo is eligible.
        if (! $danilo->canChallenge($theah) || $danilo->Engaged || $danilo->DashedInfluence)
        {
            return false;
        }

        return count($this->getOpposingTargets($theah, $danilo)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $danilo = $this->getOwningCharacter($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $danilo->Id, "04002", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Intervene is processed in GENERATE_THREAT_EVENTS; queue choice there.
        if ($event instanceof EventCharacterIntervened
            && $event->theah->game->globals->get(Game::CHALLENGE_TYPE) == Game::DANILO_CHALLENGE_TYPE)
        {
            $danilo = $this->getOwningCharacter($event->theah);
            $intervener = $event->theah->getCharacterById($event->newTargetId);
            if ($intervener === null || $danilo === null)
            {
                return;
            }

            $this->IntervenerId = $intervener->Id;
            $danilo->IsUpdated = true;

            $event->theah->game->notify->all(
                "message",
                clienttranslate('${intervener_inject_code} has intervened in the challenge from ${danilo_inject_code}.'),
                [
                    "intervener_inject_code" => $intervener->getInjectCode(),
                    "danilo_inject_code" => $danilo->getInjectCode(),
                ]
            );

            $transition = EventFactory::createTransitionEvent(
                $danilo->ControllerId,
                $danilo->Id,
                "04002_3",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $danilo = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $danilo->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $danilo->Location)
        {
            return [false, $game->translate("Target must be at Danilo's location.")];
        }

        return [true, ""];
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04002)
        {
            $danilo = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $danilo->Id;

            $targets = $this->getOpposingTargets($game->theah, $danilo);
            $args['ids'] = array_values(array_map(fn($character) => $character->Id, $targets));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04002_3)
        {
            $args['intervenerId'] = $this->IntervenerId;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04002)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Invalid character."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $danilo = $this->getOwningCharacter($game->theah);

            $game->globals->set(Game::CHOSEN_PERFORMER, $danilo->Id);
            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_INFLUENCE);
            // WHY DANILO_CHALLENGE_TYPE: keys the intervene wound/draw follow-up.
            // Engage printed → added to stIssueChallenge auto-engage list.
            $game->globals->set(Game::CHALLENGE_TYPE, Game::DANILO_CHALLENGE_TYPE);

            $transitionEvent = EventFactory::createTransitionEvent($danilo->ControllerId, $danilo->Id, "04002_2", $this->Id);
            $game->theah->queueEvent($transitionEvent);

            // createActionResolvedEvent is queued by the challenge resolution flow.

            $game->gamestate->nextState("targetChosen");
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04002_3)
        {
            $danilo = $this->getOwningCharacter($game->theah);
            $intervener = $game->theah->getCharacterById($this->IntervenerId);
            if ($intervener === null)
            {
                throw new UserException($game->translate("Intervening character not found."));
            }

            // id 0 = Wound, id 1 = Draw
            if ($id == 0)
            {
                $game->notify->all(
                    "message",
                    clienttranslate('${danilo_inject_code}: ${player_name} chooses to wound ${intervener_inject_code}.'),
                    [
                        "danilo_inject_code" => $danilo->getInjectCode(),
                        "player_name" => $game->getPlayerNameById($danilo->ControllerId),
                        "intervener_inject_code" => $intervener->getInjectCode(),
                    ]
                );

                $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                    $intervener->Id,
                    $danilo->Id,
                    1,
                    $danilo->getInjectCode(),
                    $this->Id
                );
                $game->theah->eventCheck($woundEvent);
                $game->theah->queueEvent($woundEvent);
            }
            else if ($id == 1)
            {
                $game->notify->all(
                    "message",
                    clienttranslate('${danilo_inject_code}: ${player_name} chooses to draw a card.'),
                    [
                        "danilo_inject_code" => $danilo->getInjectCode(),
                        "player_name" => $game->getPlayerNameById($danilo->ControllerId),
                    ]
                );

                $drawEvent = EventFactory::createCardDrawnEvent($danilo->ControllerId, $danilo->getInjectCode());
                $game->theah->queueEvent($drawEvent);
            }
            else
            {
                throw new UserException($game->translate("Invalid choice."));
            }

            $this->IntervenerId = 0;
            $danilo->IsUpdated = true;

            $game->gamestate->nextState("done");
            return;
        }
    }

    /**
     * @return list<Character>
     */
    private function getOpposingTargets(Theah $theah, Character $danilo): array
    {
        return array_values($theah->getOpposingCharactersAtLocation($danilo->Location, $danilo->ControllerId));
    }
}
