<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02045;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02045 extends SchemeCityAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Influence Pressure; search deck');
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<\Bga\Games\SeventhSeaCityOfFiveSails\cards\Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $owner = $this->getOwningCard($theah);
        if (!($owner instanceof _02045) || $owner->ChosenLocation === '')
        {
            return [];
        }

        $characters = $theah->getCharactersAtLocationByPlayerId($owner->ChosenLocation, $playerId);
        $sorcerers = array_filter($characters, fn($c) => $c->hasTrait("Sorcerer"));

        return array_values($sorcerers);
    }

    // WHY: Full override — SchemeAction requires Location == PLAYER_HOME,
    // but this scheme lives in the city after placement. Replicate the
    // CardAction checks (owner controlled + not used) manually.
    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        $owner = $this->getOwningCard($theah);

        if ($owner->isControlled() && $owner->ControllerId != $playerId && !$overrideInHandCheck)
        {
            return false;
        }

        if ($this->Used)
        {
            return false;
        }

        if (!($owner instanceof _02045) || $owner->ChosenLocation === '')
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId);
            $event->theah->queueEvent($sorceryStartEvent);

            $game->globals->set(Game::PRESSURING_PLAYER, $owner->ControllerId);
            $game->globals->set(Game::PRESSURE_TYPE, Game::NORMAL_PRESSURE_TYPE);

            $pressureStats = $game->theah->getPressureStats($performer, $performer->Location, Game::STAT_INFLUENCE);
            $pressureOccuringEvent = EventFactory::createPressureOccuringEvent($owner->ControllerId, $performer->Id, $performer->Location, $pressureStats);
            $event->theah->queueEvent($pressureOccuringEvent);

            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "pressureLocation", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }

        if ($event instanceof EventLocationPressureResult && $event->abilityId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $game = $event->theah->game;

            if ($event->success)
            {
                $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($sorceryPlayedEvent);

                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02045", $this->Id);
                $event->theah->queueEvent($transition);
            }
            else
            {
                $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id);
                $event->theah->queueEvent($sorceryPlayedEvent);

                $actionResolvedEvent = EventFactory::createActionResolvedEvent($event->playerId);
                $event->theah->queueEvent($actionResolvedEvent);
            }
        }
    }

    private function getMatchingCardsInDeck(Game $game, int $playerId): array
    {
        $deckName = $game->getPlayerFactionDeckName($playerId);
        $deckObject = $game->getGameDeckObject();
        $deck = $deckObject->getCardsInLocation($deckName);
        $matches = [];
        foreach ($deck as $deckCard) {
            $card = $game->getCardObjectFromDb($deckCard['id']);
            if ($card->hasTrait("Dar Matushki") || $card->hasTrait("Poluchatel"))
            {
                $matches[] = $card;
            }
        }
        return $matches;
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02045)
        {
            $playerId = $game->getActivePlayerId();
            $matches = $this->getMatchingCardsInDeck($game, $playerId);
            $args["cards"] = array_map(fn($card) => $card->getPropertyArray($game), $matches);
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02045)
        {
            $owner = $this->getOwningCard($game->theah);
            $playerId = $game->getActivePlayerId();

            $card = $game->theah->getCardById($id);
            if ($card == null)
            {
                throw new \BgaUserException($game->translate("Invalid card ID."));
            }

            $deckName = $game->getPlayerFactionDeckName($playerId);
            if ($card->Location != $deckName)
            {
                throw new \BgaUserException($game->translate("Card is not in your Faction Deck."));
            }

            if (!$card->hasTrait("Dar Matushki") && !$card->hasTrait("Poluchatel"))
            {
                throw new \BgaUserException($game->translate("Card must have the Dar Matushki or Poluchatel trait."));
            }

            $removeEvent = EventFactory::createCardRemovedFromPlayerFactionDeckEvent($playerId, $card->Id);
            $game->theah->eventCheck($removeEvent);

            $addEvent = EventFactory::createCardAddedToHandEvent($playerId, $card->Id);
            $game->theah->eventCheck($addEvent);

            $game->theah->queueEvent($removeEvent);
            $game->theah->queueEvent($addEvent);

            $game->notifyAllPlayers("message", clienttranslate('${scheme_inject_code}: ${player_name} revealed ${card_inject_code} and put it into their hand.'), [
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "card_inject_code" => $card->getInjectCode(),
            ]);

            $game->getGameDeckObject()->shuffle($deckName);
            $game->notifyAllPlayers("message", clienttranslate('${player_name} shuffles their deck.'), [
                "player_name" => $game->getPlayerNameById($playerId),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02045)
        {
            $playerId = $game->getActivePlayerId();
            $matches = $this->getMatchingCardsInDeck($game, $playerId);

            if (count($matches) > 0)
            {
                throw new \BgaUserException($game->translate("There are Dar Matushki or Poluchatel cards in your Faction Deck."));
            }

            $owner = $this->getOwningCard($game->theah);

            $game->getGameDeckObject()->shuffle($game->getPlayerFactionDeckName($playerId));
            $game->notifyAllPlayers("message", clienttranslate('${player_name} shuffles their deck.'), [
                "player_name" => $game->getPlayerNameById($playerId),
            ]);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}
