<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;

class Action_01152b extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Performer, Engage Character");
        $this->RequiresPerformerSelected = true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "01152b", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Engage the target only when EventCharacterTargeted survives. Cloud Bottle /
        // UL / Maryam cancel that hook — queuing the effect beside the pick meant "when
        // targeted" reactions never saw this Action. The performer wound is the COST and
        // stays in actFromActionWithId (unbatched, always applies).
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $scheme = $this->getOwningCard($theah);
            $target = $theah->getCharacterById($event->targetId);
            if ($scheme === null || $target === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $theah->game->getNextEventBatchId();

            // WHY: Already engaged is a legal target (players may take the wound for
            // beneficial wound effects), so skip the effect rather than refuse.
            if (! $target->Engaged)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($scheme->ControllerId, $target->Id, $scheme->Id, $this->Id);
                $engageEvent->batchId = $batchId;
                $theah->eventCheck($engageEvent);
                $theah->queueEvent($engageEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($scheme->ControllerId);
            $theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01152b)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;

            $performer = $game->theah->getCharacterById($performerId);
            $characters = $game->theah->getCharactersAtLocation($performer->Location);
            // WHY: Effects may fail. Any character at the location is choosable; Engage
            // is skipped below when the target is already engaged (wound still applies).
            $args['characterIds'] = array_map(fn($character) => $character->Id, array_values($characters));
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the same location as the Performer")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01152b)
        {
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $scheme = $this->getOwningCard($game->theah);

            $event = EventFactory::createCharacterBeingWoundedEvent($performer->Id, $scheme->Id, 1, $scheme->getInjectCode(), $this->Id);
            $game->theah->eventCheck($event);
            $game->theah->queueEvent($event);

            // WHY: Wound is the cost and always applies (outside the targeting batch). The
            // Engage effect + ActionResolved emit from handleEvent only when
            // EventCharacterTargeted is not canceled.
            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $scheme->ControllerId,
                $target->Id,
                $scheme->Id,
                $this->Id
            );
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState("targetChosen");
        }
    }
}
