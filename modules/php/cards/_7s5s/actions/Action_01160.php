<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01160 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Character that is Wounded");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInPlay();
        $characters = array_filter($characters, fn($character) => $theah->cardInCity($character));
        $characters = array_filter($characters, fn($character) => ! $character->hasTrait("Leader"));
        $characters = array_filter($characters, fn($character) => $character->Wounds > 0);

        return count($characters) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01160", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Wound only when EventCharacterTargeted survives. Cloud Bottle / UL /
        // Maryam set canceled=true on that hook — queuing the wound beside the pick
        // meant "when targeted" reactions never saw Bleed Out (journal 2026-11-20).
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            $character = $event->theah->getCharacterById($event->targetId);
            if ($owner === null || $character === null)
            {
                return;
            }

            $batchId = $event->batchId ?? $event->theah->game->getNextEventBatchId();

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $character->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $woundEvent->batchId = $batchId;
            $event->theah->queueEvent($woundEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, Array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

        if ($action->Id == $this->Id)
        {
            $owner = $this->getOwningCard($theah);
            $leader = $theah->getLeaderByPlayerId($owner->ControllerId);
            if ($leader->hasTrait('Villain'))
            {
                $discount += 1;
                $explanations[] = sprintf($theah->game->translate("%s: -1 because Leader is a Villain."), $owner->getInjectCode());
            }
        }

        return $discount;
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01160)
        {
            // WHY: Match isAvailable / isValidTarget / act — Home wounded non-Leaders must not appear in UI ids.
            $characters = $game->theah->getCharactersInPlay();
            $characters = array_filter($characters, fn($character) => $game->theah->cardInCity($character));
            $characters = array_filter($characters, fn($character) => ! $character->hasTrait("Leader"));
            $characters = array_values(array_filter($characters, fn($character) => $character->Wounds > 0));
            $args['ids'] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        if (! $game->theah->cardInCity($character))
        {
            return [false, $game->translate("Character is not in the city")];
        }

        if ($character->Wounds == 0)
        {
            return [false, $game->translate("Character is not wounded")];
        }

        if ($character->hasTrait('Leader'))
        {
            return [false, $game->translate("Character is a leader")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01160)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);

            $game->notify->all("message", clienttranslate('${player_name} uses ${card_inject_code} targeting ${character_inject_code}.'), [
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "card_inject_code" => $owner->getInjectCode(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            // WHY: Fire targeting alone here. Wound + ActionResolved emit from handleEvent
            // only when EventCharacterTargeted is not canceled (01174 / Red Tape / Torvo).
            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $owner->ControllerId,
                $character->Id,
                $owner->Id,
                $this->Id
            );
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}
