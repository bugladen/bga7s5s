<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01068;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01069;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01076;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01085;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01201;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01008 extends CardReaction
{
    public int $sourceId;
    public string $sourceAbilityId;

    public Array $copiedActions = [];
    public Array $copiedCards = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Copy Sorcerer Ability Just Played");
        $this->sourceId = 0;
        $this->sourceAbilityId = "";
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah). $theah->game->translate('${you} may choose a to copy the Sorcerer Ability Just Played: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $source = $theah->getCardById($this->sourceId);
        $ability = $source->getAbilityById($this->sourceAbilityId);
        
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Copy') . " [" . $ability->Name . "]", 'copyAbility');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventSorcererAbilityPlayed && ! $this->Used)
        {
            $cesca = $this->getOwningCharacter($event->theah);
            $source = $event->theah->getCardById($event->sourceId);

            //If the ability is from the cesca herself, or the target is at the same location as cesca, we can copy the ability.
            if ($source?->Id == $cesca->Id || ($event->targetId != 0 && $event->targetLocation == $cesca->Location))
            {
                $this->sourceId = $event->sourceId;
                $this->sourceAbilityId = $event->abilityId;
                $cesca->IsUpdated = true;
                $reactionEvent = EventFactory::createReactionTransitionEvent($cesca->ControllerId, $cesca->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
            }
        }

        if ($event instanceof EventPlayerTurnEnd)
        {
            $cesca = $this->getOwningCharacter($event->theah);
            foreach ($this->copiedActions as $action)
            {
                if ($cesca instanceof IHasActions)
                {
                    $cesca->removeAction($action, $event->theah->game);
                }
            }
            $this->copiedActions = [];

            $game = $event->theah->game;
            foreach ($this->copiedCards as $cardId)
            {
                $card = $game->getCardObjectFromDb($cardId);
                if ($card)
                {
                    if ($card->Location == Game::LOCATION_HAND)
                    {
                        $game->notifyAllPlayers("cardRemovedFromHand", clienttranslate('Private: Temporary copy of ${card_inject_code} removed from your hand.'), [
                            "card_inject_code" => $card->getInjectCode(),
                            "playerId" => $cesca->ControllerId,
                            "cardId" => $card->Id,
                        ]);
                    }

                    $discardPileName = $game->getPlayerDiscardDeckName($cesca->ControllerId);
                    if ($card->Location == $discardPileName)
                    {
                        $event = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($cesca->ControllerId, $card->Id);
                        $game->theah->queueEvent($event);
                    }
                    $deck = $game->getGameDeckObject();
                    $deck->moveCard($card->Id, Game::LOCATION_PERMANENTLY_HIDDEN);
                }
                
            }
            $this->copiedCards = [];
            $cesca->IsUpdated = true;
        }

    }

    private function announceReaction(Game $game, ICardAbility $ability): void
    {
        $game->notifyAllPlayers("message", clienttranslate('${character_inject_code}: ${player_name} used Reaction to copy the Sorcerer Ability [${ability_name}]'), [
            "i18n" => ["ability_name"],
            "character_inject_code" => $this->getOwningCharacter($game->theah)->getInjectCode(),
            "player_name" => $game->getActivePlayerName(),
            "ability_name" => $ability->Name,
        ]);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == "copyAbility")
        {
            $source = $game->theah->getCardById($this->sourceId);
            $ability = $source->getAbilityById($this->sourceAbilityId);
            $cesca = $this->getOwningCharacter($game->theah);

            //Wound Cesca as cost of copying the ability
            $woundEvent = EventFactory::createCharacterWoundedEvent($cesca->Id, $cesca->Id, 1, $cesca->getInjectCode(), $this->Id);
            $game->theah->queueEvent($woundEvent);

            $copyAction = false;
            $cardCopied = false;
            $action = null;

            //Cesca's Reveal Top Card of your Faction Deck ability
            if ($ability instanceof Action_01008)
            {
                $event = EventFactory::createTransitionEvent($cesca->ControllerId, $cesca->Id, "01008", $ability->Id);
                $game->theah->queueEvent($event);

                $this->setUsed($game->theah, true);
                $this->announceReaction($game, $ability);
            }

            //Daniella Dietrich's Action
            if ($ability instanceof Action_01012)
            {
                $copyAction = true;
                $action = new Action_01012();
                $action->setId("Action_01012");
                $action->setOwnerId($cesca->Id);
                if ($cesca instanceof IHasActions) $cesca->addAction($action, $game);
            }

            //Pull the Strand
            if ($ability instanceof Action_01030)
            {
                $copyAction = true;
                $action = new Action_01030();
                $action->setId("Action_01030");
                $action->setOwnerId($cesca->Id);
                if ($cesca instanceof IHasActions) $cesca->addAction($action, $game);
            }

            //Léontine Giroux
            if ($ability instanceof Action_01068)
            {
                $copyAction = true;
                $action = new Action_01068();
                $action->setId("Action_01068");
                $action->setOwnerId($cesca->Id);
                if ($cesca instanceof IHasActions) $cesca->addAction($action, $game);
            }

            //Maxime De Lafayette
            if ($ability instanceof Action_01069)
            {
                $copyAction = true;
                $action = new Action_01069();
                $action->setId("Action_01069");
                $action->setOwnerId($cesca->Id);
                if ($cesca instanceof IHasActions) $cesca->addAction($action, $game);
            }
            
            //Blood Mark
            if ($ability instanceof Action_01076)
            {
                $cardCopied = true;
                $card = $this->copyCard($game, "01076", $cesca->ControllerId);
                $ability = $card->getAbilityById("{$card->Id}_Action_01076");
            }

            //Porté Travel
            if ($ability instanceof Action_01085)
            {
                $cardCopied = true;
                $card = $this->copyCard($game, "01085", $cesca->ControllerId);
                $ability = $card->getAbilityById("{$card->Id}_Action_01085");
            }

            //Ravenna Destine
            if ($ability instanceof Action_01201)
            {
                $copyAction = true;
                $action = new Action_01201();
                $action->setId("Action_01201");
                $action->setOwnerId($cesca->Id);
                if ($cesca instanceof IHasActions) $cesca->addAction($action, $game);
            }

            //If it was an action, check if it is available to copy
            if ($copyAction)
            {
                if (! $action->isAvailableToPlayer($cesca->ControllerId, $game->theah))
                {
                    throw new \BgaUserException($game->translate("Action is not available to perform due to prerequisites."));
                }

                $this->copiedActions[] = $action;
                $cesca->IsUpdated = true;

                $game->globals->set(Game::ABNORMAL_FLOW, true);
                $transition = EventFactory::createActionTriggeredEvent($cesca->ControllerId, $cesca->Id, $action->Id);
                $game->theah->queueEvent($transition);
    
                $this->setUsed($game->theah, true);
                $this->announceReaction($game, $ability);
            }

            if ($cardCopied)
            {
                $cesca->IsUpdated = true;

                $game->globals->set(Game::CHOSEN_PERFORMER, $cesca->Id);
                $game->globals->set(Game::CHOSEN_ACTION, $ability->Id);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, $ability->Id);
                $game->globals->set(Game::ABNORMAL_FLOW, true);
        
                $event = EventFactory::createTransitionEvent($cesca->ControllerId, $cesca->Id, "inHandActionPay", $ability->Id);
                $game->theah->queueEvent($event);

                $this->setUsed($game->theah, true);
                $this->announceReaction($game, $ability);
            }
        }

        $game->gamestate->nextState("done");
    }

    private function copyCard(Game $game, string $className, int $playerId): Card
    {
        $location = Game::LOCATION_HAND;
        $sql = "INSERT INTO card (card_type, card_type_arg, card_location, card_location_arg) VALUES ('{$className}', $playerId, '$location', $playerId)";
        $game->DbQuery($sql);

        $id = $game->DbGetLastId();
        $card = $game->instantiateCard($className, $id);
        $card->OwnerId = $playerId;
        $card->ControllerId = $playerId;
        $card->Location = $location;
        $game->updateCardObjectInDb($card);
        $this->copiedCards[] = $card->Id;

        $game->notifyPlayer($playerId, "drawCard", '${card_inject_code} was temporarily copied into your Faction Hand to immediately be used.', [
            "card_inject_code" => $card->getInjectCode(),
            "card" => $card->getPropertyArray($game),
        ]);

        return $card;
    }
}