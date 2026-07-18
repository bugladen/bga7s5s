<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03060;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03060 extends RiskCityAction implements ISorcererAbility
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Heal Two Wounds from Another Character");
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
            fn(Character $performer) => $performer->hasTrait("Sorcerer")
                && count($this->getValidHealCharacters($theah, $performer)) > 0
        ));
    }

    public function getActionFromHandDiscount(Theah $theah, ?Character $performer, CardAction $action, array &$explanations): int
    {
        $discount = parent::getActionFromHandDiscount($theah, $performer, $action, $explanations);
        $owner = $this->getOwningCard($theah);

        // WHY: optional engage (pre-pay state) sets WillEngage — discount equals printed WealthCost ("ignore all costs").
        // Id gate so a sticky WillEngage cannot discount unrelated hand Actions.
        if ($action->Id == $this->Id && $owner instanceof _03060 && $owner->WillEngage)
        {
            $discount += $owner->WealthCost;
            $explanations[] = sprintf(
                $theah->game->translate("%s: ignore all costs (performer engaged)."),
                $owner->getInjectCode()
            );
        }

        return $discount;
    }

    public function isValidHealCharacter(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->Id == $performerId)
        {
            return [false, $game->translate("You cannot heal the performer.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character must be at your performer's location.")];
        }

        if ($character->Wounds <= 0)
        {
            return [false, $game->translate("Character is not wounded.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Pre-pay engage choice as a GameState (not RiskReaction). stackEvent so it runs
        // before CalculatePayDiscount stacked by EventHub — WillEngage must be set for the discount.
        if ($event instanceof EventEnteringPayState)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($event->cardId != $owner->Id || ! ($owner instanceof _03060) || $owner->Location != Game::LOCATION_HAND)
            {
                return;
            }

            $owner->WillEngage = false;
            $owner->IsUpdated = true;

            $performerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);
            if ($performerId == null)
            {
                return;
            }

            $performer = $event->theah->getCharacterById($performerId);
            if ($performer === null || $performer->Engaged)
            {
                return;
            }

            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03060_2", $this->Id);
            $event->theah->stackEvent($transition);
        }

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03060", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03060 || $state == States::HIGH_DRAMA_PLAYER_TURN_03060_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['performerId'] = $performer->Id;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03060)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args['ids'] = array_map(fn(Character $c) => $c->Id, $this->getValidHealCharacters($game->theah, $performer));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03060_2)
        {
            if ($id != 1)
            {
                throw new UserException($game->translate("Invalid choice"));
            }

            $owner = $this->getOwningCard($game->theah);
            if (! ($owner instanceof _03060))
            {
                throw new UserException($game->translate("Card not found"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null || $performer->Engaged)
            {
                throw new UserException($game->translate("Performer cannot engage."));
            }

            $owner->WillEngage = true;
            $owner->IsUpdated = true;

            $game->notify->all("message", clienttranslate('${player_name} chooses to engage ${character_inject_code} to pay for the cost of ${card_inject_code}'), [
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'character_inject_code' => $performer->getInjectCode(),
                'card_inject_code' => $owner->getInjectCode(),
            ]);

            $game->globals->set(Game::ABNORMAL_FLOW, true);

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $performerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            // WHY: EventEnteringPayState has runEventHubAfterCards=true. Cards stack the
            // 03060_2 Transition first, then EventHub stackEvent's CalculatePayDiscount —
            // which gets a lower priority and runs BEFORE the Transition. That early calc
            // sees WillEngage=false. Recalc now that Engage set WillEngage (mirror
            // Reaction_01116b / Reaction_03013).
            $game->theah->calculateInHandPayDiscount(
                $owner->ControllerId,
                Game::PAY_STATE_IN_HAND_ACTION,
                $owner->Id,
                $this->Id
            );

            $game->gamestate->nextState("done");
            return;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03060)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidHealCharacter($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $owner = $this->getOwningCard($game->theah);

            $sorcererAbilityStartedEvent = EventFactory::createSorcererAbilityStartEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $performerId,
                $character->Id,
                $character->Location
            );
            $game->theah->queueEvent($sorcererAbilityStartedEvent);

            $healEvent = EventFactory::createCharacterBeingHealedEvent(
                $character->Id,
                $owner->Id,
                2,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($healEvent);

            $sorcererAbilityPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent(
                $owner->ControllerId,
                $owner->Id,
                $this->Id,
                $performerId,
                $character->Id,
                $character->Location
            );
            $game->theah->queueEvent($sorcererAbilityPlayedEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("targetChosen");
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03060_2)
        {
            // Pass = do not engage; WillEngage already false from EventEnteringPayState.
            // WHY: same ABNORMAL_FLOW as Engage — after the engage chooser, Back on pay must
            // not return to choosePerformer (would re-enter EnteringPayState / re-prompt).
            $game->globals->set(Game::ABNORMAL_FLOW, true);
            $game->gamestate->nextState("done");
        }
    }

    /**
     * @return list<Character>
     */
    private function getValidHealCharacters(Theah $theah, Character $performer): array
    {
        $characters = $theah->getCharactersAtLocation($performer->Location);

        return array_values(array_filter(
            $characters,
            fn(Character $character) => $character->Id != $performer->Id && $character->Wounds > 0
        ));
    }
}
