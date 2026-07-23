<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;
use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
class Action_04cd09 extends EventCityAction
{
    // WHY numeric cost ids: actFromCardWithId takes int (not string labels).
    public const COST_ENGAGE = 1;
    public const COST_DISCARD = 2;
    public const COST_MODE = "knivesOutCostMode";
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Engage or Discard: Move this Card to Another City Location");
        // WHY false: discard path needs no performer; engage path picks in a later state.
        $this->RequiresPerformerSelected = false;
    }
    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }
        if (count($this->getDestinationLocations($theah)) === 0)
        {
            return false;
        }
        $canEngage = count($this->getEngagePerformers($playerId, $theah)) > 0;
        $canDiscard = count($theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId)) > 0;
        return $canEngage || $canDiscard;
    }
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04cd09", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }
    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);
        $playerId = (int)$game->getActivePlayerId();
        $owner = $this->getOwningCard($game->theah);
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD09)
        {
            $args["cardId"] = $owner->Id;
            $args["canEngage"] = count($this->getEngagePerformers($playerId, $game->theah)) > 0;
            $args["canDiscard"] = count($game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId)) > 0;
        }
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD09_2)
        {
            $costMode = (int)$game->globals->get(self::COST_MODE);
            $args["costMode"] = $costMode;
            $args["cardId"] = $owner->Id;
            if ($costMode === self::COST_ENGAGE)
            {
                $args["ids"] = array_map(
                    fn(Character $character) => $character->Id,
                    $this->getEngagePerformers($playerId, $game->theah)
                );
            }
        }
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD09_3)
        {
            $args["cardId"] = $owner->Id;
            $args["locationIds"] = $this->getDestinationLocations($game->theah);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER, 0);
            if ($performerId > 0)
            {
                $args["performerId"] = $performerId;
            }
        }
        return $args;
    }
    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD09)
        {
            $this->handleCostChosen($game, $id);
            return;
        }
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD09_2)
        {
            $costMode = (int)$game->globals->get(self::COST_MODE);
            if ($costMode === self::COST_ENGAGE)
            {
                $this->handlePerformerChosen($game, $id);
            }
            else
            {
                $this->handleDiscardChosen($game, $id);
            }
            return;
        }
    }
    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);
        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04CD09_3)
        {
            $this->handleLocationChosen($game, $ids[0]);
        }
    }
    private function handleCostChosen(Game $game, int $costId): void
    {
        $playerId = (int)$game->getActivePlayerId();
        if ($costId === self::COST_ENGAGE)
        {
            if (count($this->getEngagePerformers($playerId, $game->theah)) === 0)
            {
                throw new UserException($game->translate("No unengaged performer at this location."));
            }
        }
        else if ($costId === self::COST_DISCARD)
        {
            if (count($game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId)) === 0)
            {
                throw new UserException($game->translate("You have no cards in hand to discard."));
            }
        }
        else
        {
            throw new UserException($game->translate("Invalid cost choice."));
        }
        $game->globals->set(self::COST_MODE, $costId);
        // Clear stale performer from a prior engage attempt this turn.
        $game->globals->set(Game::CHOSEN_PERFORMER, 0);
        $game->gamestate->nextState("costChosen");
    }
    private function handlePerformerChosen(Game $game, int $performerId): void
    {
        $playerId = (int)$game->getActivePlayerId();
        $eligible = array_map(
            fn(Character $character) => $character->Id,
            $this->getEngagePerformers($playerId, $game->theah)
        );
        if (! in_array($performerId, $eligible))
        {
            throw new UserException($game->translate("Invalid performer."));
        }
        $owner = $this->getOwningCard($game->theah);
        $game->globals->set(Game::CHOSEN_PERFORMER, $performerId);
        $engageEvent = EventFactory::createCardEngagedEvent($playerId, $performerId, $owner->Id, $this->Id);
        $game->theah->queueEvent($engageEvent);

        // WHY: Route through EVENTS so engage processes before the location picker.
        $transition = EventFactory::createTransitionEvent($playerId, $owner->Id, "04cd09_3", $this->Id);
        $game->theah->queueEvent($transition);

        $game->gamestate->nextState("costPaid");
    }
    private function handleDiscardChosen(Game $game, int $cardId): void
    {
        $playerId = (int)$game->getActivePlayerId();
        $card = $game->theah->getCardById($cardId);
        if ($card === null || $card->Location != Game::LOCATION_HAND || $card->ControllerId != $playerId)
        {
            throw new UserException($game->translate("Card must be in your hand."));
        }
        $owner = $this->getOwningCard($game->theah);
        $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
            $playerId,
            $card->Id,
            $owner->Id,
            $asPayment = false,
            $asPlayed = false,
            $asEffect = true
        );
        $game->theah->queueEvent($discardEvent);
        $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} discards a card.'), [
            "card_inject_code" => $owner->getInjectCode(),
            "player_name" => $game->getPlayerNameById($playerId),
        ]);

        // WHY: Route through EVENTS so discard processes before the location picker.
        $transition = EventFactory::createTransitionEvent($playerId, $owner->Id, "04cd09_3", $this->Id);
        $game->theah->queueEvent($transition);

        $game->gamestate->nextState("costPaid");
    }
    private function handleLocationChosen(Game $game, string $location): void
    {
        $playerId = (int)$game->getActivePlayerId();
        $owner = $this->getOwningCard($game->theah);
        $destinations = $this->getDestinationLocations($game->theah);
        if (! in_array($location, $destinations))
        {
            throw new UserException($game->translate("Invalid destination location."));
        }
        // WHY engage=false: cost already paid (engage performer or discard); move is the effect.
        $moveEvent = EventFactory::createCardMovingEvent(
            $playerId,
            $owner->Id,
            $owner->Location,
            $location,
            false,
            $owner->Id,
            $this->Id
        );
        $game->theah->eventCheck($moveEvent);
        $game->theah->queueEvent($moveEvent);
        $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
        $game->theah->queueEvent($actionResolvedEvent);
        // WHY: Unlimited — central confirm sets Used=true; clear so the action stays available.
        $this->setUsed($game->theah, false);
        $game->globals->set(self::COST_MODE, 0);
        $game->globals->set(Game::CHOSEN_PERFORMER, 0);
        $game->gamestate->nextState("locationChosen");
    }
    /**
     * @return list<Character>
     */
    private function getEngagePerformers(int $playerId, Theah $theah): array
    {
        $performers = $this->getPerformersForAction($playerId, $theah);
        return array_values(array_filter($performers, fn(Character $performer) => ! $performer->Engaged));
    }
    /**
     * Any other city location (printed "another City location" — not adjacency-gated).
     *
     * @return string[]
     */
    private function getDestinationLocations(Theah $theah): array
    {
        $owner = $this->getOwningCard($theah);
        $destinations = [];
        foreach ($theah->getCityLocations() as $location)
        {
            if ($location->Name !== $owner->Location)
            {
                $destinations[] = $location->Name;
            }
        }
        return $destinations;
    }
}
