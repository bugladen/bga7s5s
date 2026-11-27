<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01156 extends AttachmentAction implements IAbilityThatTargetsCharacters, IRangedAbility
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Discard Card, Wound Adjacent Opposing Character");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
            return false;

        $deck = $theah->game->getGameDeckObject();  
        $hand = $deck->getCardsInLocation(Game::LOCATION_HAND, $playerId);
        if (count($hand) == 0)
            return false;

        $perfomer = $this->getOwningCharacter($theah);
        if (! $theah->cardInCity($perfomer))
            return false;

        $adjacentLocations = $theah->getAdjacentCityLocations($perfomer->Location, $includeHome = false);
        foreach ($adjacentLocations as $adjacentLocation)
        {
            $opposingCharacters = $theah->getCharactersAtLocation($adjacentLocation);
            $opposingCharacter = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($playerId));
            if (count($opposingCharacter) > 0)
                return true;
        }

        return false;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            // WHY: Matchlock cost is "Discard a card • …". Katain copies effects only —
            // jump straight to the target picker (01156_2), skipping the discard state.
            $transitionName = $this->IsEffectsOnlyCopy ? "01156_2" : "01156";
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, $transitionName, $this->Id);
            $event->theah->queueEvent($transition);
        }

        // WHY: Wound / engage-or-wound only when EventCharacterTargeted survives. Cloud Bottle /
        // UL / Maryam cancel that hook — resolving beside the pick meant "when targeted"
        // reactions never saw this Action (same seat as Bleed Out 01160 / Vantage Point 04020).
        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $this->resolveTargetedCharacter($event);
        }
    }

    private function resolveTargetedCharacter(EventCharacterTargeted $event): void
    {
        $theah = $event->theah;
        $game = $theah->game;
        $target = $theah->getCharacterById($event->targetId);
        $musket = $this->getOwningCard($theah);
        $performer = $this->getOwningCharacter($theah);
        if ($target === null || $musket === null || $performer === null)
        {
            return;
        }

        // WHY shared batchId: if this Action queues before Cloud Bottle's HAND cancel during
        // CharacterTargeted dispatch, deleteEventBatch strips the wound / choice-transition
        // siblings. RangedAbilityPlayed / ActionResolved stay unbatched like 01160 so the
        // action still resolves.
        $batchId = $event->batchId ?? $game->getNextEventBatchId();

        if ($target->Engaged)
        {
            $game->notify->all("message", clienttranslate('${character_inject_code} was already Engaged and must take the wound from ${musket_inject_code}.'), [
                "character_inject_code" => $target->getInjectCode(),
                "musket_inject_code" => $musket->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $musket->Id, 1, $musket->getInjectCode(), $this->Id);
            $woundEvent->batchId = $batchId;
            $theah->queueEvent($woundEvent);

            $rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($musket->ControllerId, $musket->Id, $this->Id, $performer->Id, $target->Id, $target->Location);
            $theah->queueEvent($rangedAbilityPlayedEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $theah->queueEvent($actionResolvedEvent);
        }
        else
        {
            $game->globals->set(Game::CHOSEN_TARGET, $target->Id);
            $game->notify->all("message", clienttranslate('${target_player_name} must decide to Engage ${character_inject_code} or suffer a wound.'), [
                "target_player_name" => $game->getPlayerNameById($target->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
            ]);

            $transition = EventFactory::createTransitionEvent($target->ControllerId, $musket->Id, "01156_3", $this->Id);
            $transition->batchId = $batchId;
            $theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156)
        {
            $performer = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $performer->Id;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156_2)
        {
            $performer = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $performer->Id;

            $charactersIds = [];
            $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
            foreach ($adjacentLocations as $adjacentLocation)
            {
                $opposingCharacters = $game->theah->getCharactersAtLocation($adjacentLocation);
                $opposingCharacters = array_filter($opposingCharacters, fn($c) => $c->isNotControlledByPlayer($performer->ControllerId));
                foreach ($opposingCharacters as $opposingCharacter)
                {
                    $charactersIds[] = $opposingCharacter->Id;
                }
            }

            $charactersIds = array_unique($charactersIds);
            $args['charactersIds'] = array_values($charactersIds);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156_3)
        {
            $performer = $this->getOwningCharacter($game->theah);
            $args['performerId'] = $performer->Id;

            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $args['targetId'] = $targetId;
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performer = $this->getOwningCharacter($game->theah);

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You cannot target your own character")];
        }

        $adjacentLocations = $game->theah->getAdjacentCityLocations($performer->Location, $includeHome = false);
        if (! in_array($character->Location, $adjacentLocations))
        {
            return [false, $game->translate("Target is not adjacent to the performer")];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156)
        {
            $performer = $this->getOwningCard($game->theah);

            $card = $game->getCardObjectFromDb($id);

            if ($card->ControllerId != $performer->ControllerId)
            {
                throw new UserException($game->translate("You do not control this card"));
            }

            if ($card->Location != Game::LOCATION_HAND)
            {
                throw new UserException($game->translate("Card not in your hand"));
            }

            $game->parkCard($card->Id);
            $game->globals->set(Game::CHOSEN_CARD, $card->Id);
            
            $game->gamestate->nextState("cardChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156_2)
        {
            $target = $game->theah->getCharacterById($id);

            if ($target == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            $performer = $this->getOwningCharacter($game->theah);

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $musket = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${player_name} has used the action of ${musket_inject_code} targeting ${character_inject_code}.'), [
                "player_name" => $game->getPlayerNameById($performer->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
                "musket_inject_code" => $musket->getInjectCode(),
            ]);

            // WHY: Discard is the cost, paid whether or not the targeting hook survives
            // (the card is already parked) — only the effects wait on EventCharacterTargeted.
            if (! $this->IsEffectsOnlyCopy)
            {
                $discardCardId = $game->globals->get(Game::CHOSEN_CARD);
                $discardCard = $game->getCardObjectFromDb($discardCardId);
                $discardEvent = EventFactory::createCardDiscardedFromHandEvent($discardCard->OwnerId, $discardCard->Id, $musket->Id);
                $game->theah->queueEvent($discardEvent);
            }

            // WHY: Fire targeting alone here. Wound / engage-or-wound emit from handleEvent
            // only when EventCharacterTargeted is not canceled, so Cloud Bottle and other
            // "when targeted" reactions see the pick.
            $batchId = $game->getNextEventBatchId();
            $targetedEvent = EventFactory::createCharacterTargetedEvent(
                $musket->ControllerId,
                $target->Id,
                $musket->Id,
                $this->Id
            );
            $targetedEvent->batchId = $batchId;
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01156_3)
        {
            $targetId = $game->globals->get(Game::CHOSEN_TARGET);
            $performer = $this->getOwningCharacter($game->theah);
            $target = $game->theah->getCharacterById($targetId);
            $musket = $this->getOwningCard($game->theah);

            //Engage
            if ($id == 1)
            {
                $engageEvent = EventFactory::createCardEngagedEvent($target->ControllerId, $target->Id, $musket->Id, $this->Id);
                $game->theah->queueEvent($engageEvent);
            }

            //Wound
            if ($id == 2)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($target->Id, $musket->Id, 1, $musket->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);
            }

            $rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($musket->ControllerId, $musket->Id, $this->Id, $performer->Id, $target->Id, $target->Location);
            $game->theah->queueEvent($rangedAbilityPlayedEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($musket->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState();
        }
    }
}