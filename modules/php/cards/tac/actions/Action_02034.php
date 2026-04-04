<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02034 extends CharacterAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Offer a Combat Challenge to Torvo');
    }

    private function getSelectableOpponentCharacterIds(Theah $theah, Character $torvo): array
    {
        $opponents = $theah->getOpposingCharactersAtLocation($torvo->Location, $torvo->ControllerId);
        $opponents = array_filter(
            $opponents,
            fn(Character $c) => ! $c->Engaged && $c->ModifiedCombat >= 2
        );

        return array_values(array_map(fn(Character $c) => $c->Id, $opponents));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck)) {
            return false;
        }

        $torvo = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($torvo)) {
            return false;
        }

        return count($this->getSelectableOpponentCharacterIds($theah, $torvo)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id) {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, '02034', $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02034) {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;
            $torvo = $game->theah->getCharacterById($performerId);
            $args['ids'] = $torvo
                ? $this->getSelectableOpponentCharacterIds($game->theah, $torvo)
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02034_2) {
            $torvo = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $torvo->Id;
            $args['characterId'] = (int) $game->globals->get(Game::CHOSEN_CARD);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02034) {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $torvo = $game->theah->getCharacterById($performerId);
            if ($torvo === null) {
                throw new UserException($game->translate('Character not found'));
            }

            $allowed = $this->getSelectableOpponentCharacterIds($game->theah, $torvo);
            if (! in_array($id, $allowed, true)) {
                throw new UserException($game->translate('Invalid character choice'));
            }

            $target = $game->theah->getCharacterById($id);
            if ($target === null) {
                throw new UserException($game->translate('Character not found'));
            }

            $game->globals->set(Game::CHOSEN_CARD, $target->Id);

            $this->announceAction($game);

            $transitionEvent = EventFactory::createTransitionEvent($target->ControllerId, $torvo->Id, '02034_2', $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $game->gamestate->nextState('targetChosen');
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02034_2) {
            $torvo = $this->getOwningCharacter($game->theah);
            $targetId = (int) $game->globals->get(Game::CHOSEN_CARD);
            $target = $game->theah->getCharacterById($targetId);
            if ($target === null) {
                throw new UserException($game->translate('Character not found'));
            }

            if ($id != 1 && $id != 2) {
                throw new UserException($game->translate('Invalid action'));
            }

            if ($id == 2) {
                $game->notify->all('message', clienttranslate('${torvo_inject_code}: ${player_name} declined to issue a challenge to Torvo.'), [
                    'torvo_inject_code' => $torvo->getInjectCode(),
                    'player_name' => $game->getPlayerNameById($target->ControllerId),
                ]);

                $drawEvent = EventFactory::createCardDrawnEvent($torvo->ControllerId, $torvo->getInjectCode());
                $game->theah->queueEvent($drawEvent);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($torvo->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);

                $this->setUsed($game->theah, true);
                $this->resetPlayerPassCount($game);
    
                $game->gamestate->nextState('');
                return;
            }

            $game->globals->set(Game::CHOSEN_PERFORMER, $target->Id);
            $game->globals->set(Game::CHOSEN_TARGET, $torvo->Id);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
            $game->globals->set(Game::CHALLENGE_TYPE, Game::TORVO_ESPADA_CHALLENGE_TYPE);

            $this->setUsed($game->theah, true);
            $this->resetPlayerPassCount($game);

            $transitionEvent = EventFactory::createTransitionEvent($target->ControllerId, $torvo->Id, '02034_3', $this->Id);
            $game->theah->queueEvent($transitionEvent);

            $game->gamestate->nextState('');
        }
    }
}
