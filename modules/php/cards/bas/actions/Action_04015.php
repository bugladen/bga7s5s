<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICityDeckCard;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04015 extends SchemeAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move Kaspar and Daniella to an uncontrolled City location");
        // WHY: Printed keyword is Action (not City Action) — no performer pick.
        $this->RequiresPerformerSelected = false;
    }

    /**
     * @return list<string>
     */
    private function getUncontrolledCityLocationNames(Theah $theah): array
    {
        $names = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Controller == 0)
            {
                $names[] = $location->Name;
            }
        }

        return $names;
    }

    /**
     * Match by printed Name — Kaspar/Daniella exist on multiple card ids (_01035/_03014, _01036/_03013).
     *
     * @return list<Character>
     */
    private function getControlledDietrichs(Theah $theah, int $playerId): array
    {
        $kasparName = clienttranslate("Kaspar Dietrich");
        $daniellaName = clienttranslate("Daniella Dietrich");
        $kaspars = [];
        $daniellas = [];

        foreach ($theah->getCharactersInPlayByPlayerId($playerId) as $character)
        {
            if ($character->Name === $kasparName)
            {
                $kaspars[] = $character;
            }
            elseif ($character->Name === $daniellaName)
            {
                $daniellas[] = $character;
            }
        }

        // WHY: Printed order is Kaspar then Daniella — process in that order for move/heal.
        return array_merge($kaspars, $daniellas);
    }

    /**
     * @return list<ICityDeckCard>
     */
    private function getDiscardableCityCardsAtLocation(Theah $theah, string $locationName): array
    {
        $cards = [];
        foreach ($theah->getAllCards() as $card)
        {
            if (
                $card->Location === $locationName
                && ! $card->isControlled()
                && $card instanceof ICityDeckCard
                && $card->canBeDiscardedFromCity()
            )
            {
                $cards[] = $card;
            }
        }

        return $cards;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        // WHY: Primary effect needs at least one Dietrich in play; complete-as-much-as-possible
        // still requires something to do. Discard-only with no Dietrichs is not offered.
        if (count($this->getControlledDietrichs($theah, $playerId)) == 0)
        {
            return false;
        }

        return count($this->getUncontrolledCityLocationNames($theah)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);

            if (count($this->getControlledDietrichs($event->theah, $event->playerId)) == 0)
            {
                throw new UserException($game->translate("You must control Kaspar Dietrich or Daniella Dietrich in play."));
            }

            if (count($this->getUncontrolledCityLocationNames($event->theah)) == 0)
            {
                throw new UserException($game->translate("There is no uncontrolled City location."));
            }

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04015", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04015)
        {
            $args["locationIds"] = $this->getUncontrolledCityLocationNames($game->theah);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04015_2)
        {
            $locationName = (string)$game->globals->get(Game::CHOSEN_LOCATION, '');
            $cards = $this->getDiscardableCityCardsAtLocation($game->theah, $locationName);
            $args["ids"] = array_map(fn($card) => $card->Id, $cards);
            $args["location"] = $locationName;
        }

        return $args;
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04015)
        {
            $owner = $this->getOwningCard($game->theah);
            $locationName = $ids[0];
            $validLocations = $this->getUncontrolledCityLocationNames($game->theah);

            if (! in_array($locationName, $validLocations, true))
            {
                throw new UserException($game->translate("Location must be an uncontrolled City location."));
            }

            $game->globals->set(Game::CHOSEN_LOCATION, $locationName);

            $dietrichs = $this->getControlledDietrichs($game->theah, $owner->ControllerId);
            if (count($dietrichs) == 0)
            {
                throw new UserException($game->translate("You must control Kaspar Dietrich or Daniella Dietrich in play."));
            }

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} targets ${location_name}.'), [
                "i18n" => ["location_name"],
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "location_name" => $locationName,
            ]);

            // WHY: Complete as much as possible — move/heal each controlled Dietrich; skip missing names.
            foreach ($dietrichs as $character)
            {
                if ($character->Location !== $locationName)
                {
                    // WHY: engage=false — printed text says Move only (no Engage).
                    $moveEvent = EventFactory::createCardMovingEvent(
                        $character->ControllerId,
                        $character->Id,
                        $character->Location,
                        $locationName,
                        false,
                        $owner->Id,
                        $this->Id
                    );
                    $game->theah->eventCheck($moveEvent);
                    $game->theah->queueEvent($moveEvent);
                }

                if ($character->Wounds > 0)
                {
                    $healEvent = EventFactory::createCharacterBeingHealedEvent(
                        $character->Id,
                        $owner->Id,
                        1,
                        $owner->getInjectCode(),
                        $this->Id
                    );
                    $game->theah->queueEvent($healEvent);
                }
            }

            $discardable = $this->getDiscardableCityCardsAtLocation($game->theah, $locationName);
            if (count($discardable) > 0)
            {
                $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "04015_2", $this->Id);
                $game->theah->queueEvent($transition);
            }
            else
            {
                $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
                $game->theah->queueEvent($actionResolvedEvent);
            }

            $game->gamestate->nextState("locationChosen");
        }
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04015_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $locationName = (string)$game->globals->get(Game::CHOSEN_LOCATION, '');
            $card = $game->theah->getCardById($id);

            if ($card == null)
            {
                throw new UserException($game->translate("Invalid card id"));
            }

            if (! $card instanceof ICityDeckCard)
            {
                throw new UserException($game->translate("Card is not a City Card"));
            }

            if ($card->isControlled())
            {
                throw new UserException($game->translate("Card is controlled"));
            }

            if ($card->Location !== $locationName)
            {
                throw new UserException($game->translate("Card is not at the targeted location"));
            }

            if (! $card->canBeDiscardedFromCity())
            {
                throw new UserException($game->translate("Card cannot be discarded"));
            }

            $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent(
                $owner->ControllerId,
                $card->Id,
                $card->Location,
                $owner->Id,
                $asEffect = true
            );
            $game->theah->queueEvent($discardEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("cardDiscarded");
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04015_2)
        {
            // WHY: "you may discard" — Pass is always legal even when discardable cards remain.
            $owner = $this->getOwningCard($game->theah);
            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("pass");
        }
    }
}
