<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

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

// Blackmail: Covert Action - Equip this card to target opposing character.
// Discount: If your performer is a Scoundrel or Villain, this card has -1 cost.
class Action_05Shakedown extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Covert: Equip Blackmail to target opposing character";
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Character>
     */
    private function getValidTargets(Theah $theah, Character $performer): array
    {
        return $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        // WHY not RiskCityAction: the heading says "Covert Action", not "City Action".
        // City comes from Covert (uncontrolled City location), checked explicitly below.
        $performers = $theah->getCharactersInCityWithOpposingCharacters($playerId);
        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($theah)
            {
                // WHY: Covert abilities may only be used at uncontrolled locations (Valeri / Overcoat).
                $cityLocation = $theah->getCityLocation($performer->Location);
                if ($cityLocation === null || $cityLocation->isControlled())
                {
                    return false;
                }

                return count($this->getValidTargets($theah, $performer)) > 0;
            }
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);

        // WHY: Action-only Risk — combat-card pay has no Maneuver discount channel.
        // Do not invent a Maneuver solely to carry getManeuverFromCombatCardDiscount.
        if ($action->Id == $this->Id)
        {
            if ($performer === null)
            {
                return $discount;
            }

            // WHY pass $owner as queryCard: Patricia (_05Serenissima) / Uwe fiction only
            // fires when the paying ability identifies itself. Null query = printed traits.
            $owner = $this->getOwningCard($theah);
            if ($performer->hasTrait("Scoundrel", $owner) || $performer->hasTrait("Villain", $owner))
            {
                $discount += 1;
                $explanations[] = sprintf(
                    "%s: -1 because your performer is a Scoundrel or Villain.",
                    $owner->getInjectCode()
                );
            }
        }

        return $discount;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "05Shakedown", $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Equip only when targeting survives. UL / Terraria / Maryam cancel
        // EventCharacterTargeted; queuing createRiskAttachment beside the hook would
        // equip anyway (same trap as Action_04040 / Action_05Teens).
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $character = $event->theah->getCharacterById($event->targetId);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ($owner === null || $character === null || $performer === null)
            {
                return;
            }

            // WHY: Keep Covert honest if control flipped between confirm and resolve.
            $cityLocation = $event->theah->getCityLocation($performer->Location);
            if ($cityLocation === null || $cityLocation->isControlled())
            {
                return;
            }

            // WHY controller = target's controller: equipped character gains the destroy Action
            // (01102 Unfortunate). Owner stays the Risk controller so removeRiskAttachment
            // returns Blackmail to that player's discard.
            $game->createRiskAttachment(
                $game,
                "05Shakedown_Att",
                $owner->Id,
                $character->Location,
                $performer->ControllerId,
                $character->ControllerId,
                $character->Id,
                $this->Id
            );

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05SHAKEDOWN)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;
            $args["ids"] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getValidTargets($game->theah, $performer))
                : [];
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            return [false, "Performer not found"];
        }

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, "Target must be controlled by an opponent."];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, "Target must be at your performer's location."];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05SHAKEDOWN)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException("Character not found");
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException("Performer not found");
            }

            // WHY: Keep Covert honest if control flipped between announce and resolve.
            $cityLocation = $game->theah->getCityLocation($performer->Location);
            if ($cityLocation === null || $cityLocation->isControlled())
            {
                throw new UserException("Covert Actions can only be used at uncontrolled City locations.");
            }

            $owner = $this->getOwningCard($game->theah);

            // WHY: Cancel hook first. Equip emits from handleEvent only when
            // EventCharacterTargeted survives (Teens / 04040 / RedTape).
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

            $game->gamestate->nextState("characterChosen");
        }
    }
}
