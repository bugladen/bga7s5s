<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03034 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("En Garde Another Character; Heal or Draw");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Diplomat")
                && ! $performer->Engaged
                && count($this->getValidTargets($theah, $performer)) > 0
        ));
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->Id == $performerId)
        {
            return [false, $game->translate("You cannot target the performer.")];
        }

        if ($character->ControllerId != $performer->ControllerId)
        {
            return [false, $game->translate("You may only target a character you control.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character must be at your performer's location.")];
        }

        // WHY: "En garde" only applies to engaged characters (Engaged = true → ready). Mirror Action_02051.
        if (! $character->Engaged)
        {
            return [false, $game->translate("Character must be engaged.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER, 0);
            $performer = $event->theah->getCharacterById($performerId);

            if ($performer === null || ! $performer->hasTrait("Diplomat"))
            {
                throw new UserException($game->translate("Performer must be a Diplomat."));
            }

            if ($performer->Engaged)
            {
                throw new UserException($game->translate("Performer is already engaged."));
            }

            // WHY: engage cost resolves at announcement (before target chooser), same shape as Action_03021 / Action_03030.
            $engageEvent = EventFactory::createCardEngagedEvent($performer->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $event->theah->queueEvent($engageEvent);

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03034", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03034)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performer->Id;
            $args['ids'] = array_map(fn(Character $c) => $c->Id, $this->getValidTargets($game->theah, $performer));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03034_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);

            $args['performerId'] = $performerId;
            $args['targetId'] = $targetId;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03034)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);
            $game->globals->set(Game::CHOSEN_TARGET, $character->Id);

            $engardeEvent = EventFactory::createCardEngardedEvent($character->ControllerId, $character->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engardeEvent);

            // WHY: "may heal / if they do not, draw" — no wounds means they cannot heal, so draw immediately (mirror Action_01049 already-engaged auto-wound).
            if ($character->Wounds > 0)
            {
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03034_2", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
                $game->theah->queueEvent($drawEvent);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);
            }

            $game->gamestate->nextState("targetChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03034_2)
        {
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $target = $game->theah->getCharacterById($targetId);
            if ($target == null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            $owner = $this->getOwningCard($game->theah);

            if ($id == 1)
            {
                if ($target->Wounds == 0)
                {
                    throw new UserException($game->translate("Character is not wounded."));
                }

                $game->notify->all("message", clienttranslate('${player_name} chose to heal a wound on ${character_inject_code}'), [
                    'player_name' => $game->getPlayerNameById($owner->ControllerId),
                    'character_inject_code' => $target->getInjectCode(),
                ]);

                $healEvent = EventFactory::createCharacterBeingHealedEvent($target->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($healEvent);
            }
            elseif ($id == 2)
            {
                $game->notify->all("message", clienttranslate('${player_name} chose not to heal ${character_inject_code} and draws a card'), [
                    'player_name' => $game->getPlayerNameById($owner->ControllerId),
                    'character_inject_code' => $target->getInjectCode(),
                ]);

                $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
                $game->theah->queueEvent($drawEvent);
            }
            else
            {
                throw new UserException($game->translate("Invalid choice."));
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("done");
        }
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $characters = $theah->getCharactersAtLocation($performer->Location);

        return array_values(array_filter(
            $characters,
            fn(Character $c) => $c->Id != $performer->Id
                && $c->ControllerId == $performer->ControllerId
                && $c->Engaged
        ));
    }
}
