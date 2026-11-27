<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03061 extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Move en garde non-Leader from Home to performer");
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getEligibleTargets(Theah $theah): array
    {
        $characters = $theah->getCharactersAtHome();
        return array_values(array_filter(
            $characters,
            fn(Character $character) => ! $character->Engaged && ! $character->hasTrait("Leader")
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0
            && count($this->getEligibleTargets($theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        // WHY: pass the owning card as queryCard so "counts as a ..." characters (Patricia) qualify as performer.
        $owner = $this->getOwningCard($theah);

        // WHY: "Hero City Action" is a mechanical trait gate, not ISorcererAbility
        // (same discipline as Strega / Diplomat prefixes on scheme actions).
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Hero", $owner)
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $performerId = (int)$event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ($performer === null || $performer->ControllerId != $event->playerId)
            {
                throw new UserException($event->theah->game->translate("Invalid performer"));
            }

            if (! $performer->hasTrait("Hero", $owner))
            {
                throw new UserException($event->theah->game->translate("Performer must be a Hero."));
            }

            if (! $event->theah->cardInCity($performer))
            {
                throw new UserException($event->theah->game->translate("Performer must be at a City location."));
            }

            if (count($this->getEligibleTargets($event->theah)) == 0)
            {
                throw new UserException($event->theah->game->translate("No eligible target at Home."));
            }

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "03061", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $owner = $this->getOwningCard($theah);
            $target = $theah->getCharacterById($event->targetId);
            $performer = $theah->getCharacterById((int)$theah->game->globals->get(Game::CHOSEN_PERFORMER));
            if ($owner === null || $target === null || $performer === null)
            {
                return;
            }

            // WHY: destination = performer's location at survive time (it may have moved).
            $moveEvent = $this->buildMoveEvent($owner, $target, $performer);
            $moveEvent->batchId = $event->batchId ?? $theah->game->getNextEventBatchId();
            $theah->queueEvent($moveEvent);

            // ActionResolved stays unbatched (01160 convention).
            $theah->queueEvent(EventFactory::createActionResolvedEvent($owner->ControllerId));
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03061)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;
            $args['ids'] = array_map(
                fn(Character $character) => $character->Id,
                $this->getEligibleTargets($game->theah)
            );
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        if ($character->Location != Game::LOCATION_PLAYER_HOME)
        {
            return [false, $game->translate("Target must be at a player's Home.")];
        }

        if ($character->Engaged)
        {
            return [false, $game->translate("Target character must be en garde.")];
        }

        if ($character->hasTrait("Leader"))
        {
            return [false, $game->translate("Target cannot be a Leader.")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03061)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found"));
            }

            // WHY: Permission pre-check stays at the pick so a veto surfaces to the player here
            // (a throw inside the survive hook would crash event dispatch). The move actually
            // queued is rebuilt in handleEvent.
            $game->theah->eventCheck($this->buildMoveEvent($owner, $target, $performer));

            $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name} targets ${character_inject_code}.'), [
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
            ]);

            // WHY: Fire targeting alone here; the move + ActionResolved emit from handleEvent
            // only when EventCharacterTargeted survives (Cloud Bottle / UL cancel it).
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $target->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("");
        }
    }

    private function buildMoveEvent(Card $owner, Character $target, Character $performer): EventCardMoving
    {
        // WHY: engage=false — text says Move only (no Engage printed).
        return EventFactory::createCardMovingEvent(
            $owner->ControllerId,
            $target->Id,
            $target->Location,
            $performer->Location,
            false,
            $owner->Id,
            $this->Id
        );
    }
}
