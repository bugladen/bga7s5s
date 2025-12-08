<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01025;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01030;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01085;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01133;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01161;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01172;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IWealthCost;
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

        if ($event instanceof EventSorcererAbilityPlayed && $this->isAvailable())
        {
            $cesca = $this->getOwningCharacter($event->theah);
            $source = $event->theah->getCardById($event->sourceId);
            $ability = $source->getAbilityById($event->abilityId);

            //From Rules Team: the ability MUST say "target character" or similar wording in the card text in order for this reaction to trigger
            $abilityTargetedCharacterAtHerLocation = $ability instanceof IAbilityThatTargetsCharacters && $event->targetId != 0 && $event->targetLocation == $cesca->Location;

            //If the ability is from the cesca herself, or she is the performer, or the ability targeted a character at her location
            if ($source?->Id == $cesca->Id || $event->performerId == $cesca->Id || $abilityTargetedCharacterAtHerLocation)
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
                    $deck = $game->getGameDeckObject();
                    if ($card->Location == Game::LOCATION_HAND)
                    {
                        $game->notify->all("cardRemovedFromHand", clienttranslate('Private: Temporary copy of ${card_inject_code} removed from your hand.'), [
                            "card_inject_code" => $card->getInjectCode(),
                            "playerId" => $cesca->ControllerId,
                            "cardId" => $card->Id,
                            'handCount' => count($deck->getPlayerHand($cesca->ControllerId)),
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
        $game->notify->all("message", clienttranslate('${character_inject_code}: ${player_name} used Reaction to copy the Sorcerer Ability [${ability_name}]'), [
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

            //Boon
            if ($ability instanceof Action_01161)
            {
                $cardCopied = true;
                $card = $this->copyCard($game, "01161", $cesca->ControllerId);
                $ability = $card->getAbilityById("{$card->Id}_Action_01161");
            }

            //Cesca's own ability to Reveal Top Card of your Faction Deck
            if ($ability instanceof Action_01008)
            {
                $event = EventFactory::createTransitionEvent($cesca->ControllerId, $cesca->Id, "01008", $ability->Id);
                $game->theah->queueEvent($event);

                $this->setUsed($game->theah, true);
                $this->announceReaction($game, $ability);
            }

            //Fates' Burden
            if ($ability instanceof Action_01025)
            {
                $cardCopied = true;
                $card = $this->copyCard($game, "01025", $cesca->ControllerId);
                $ability = $card->getAbilityById("{$card->Id}_Action_01025");
            }

            //Matushka's Efficiency
            if ($ability instanceof Action_01133)
            {
                $cardCopied = true;
                $card = $this->copyCard($game, "01133", $cesca->ControllerId);
                $ability = $card->getAbilityById("{$card->Id}_Action_01133");
            }

            //Porté Travel
            if ($ability instanceof Action_01085)
            {
                $cardCopied = true;
                $card = $this->copyCard($game, "01085", $cesca->ControllerId);
                $ability = $card->getAbilityById("{$card->Id}_Action_01085");
            }

            //Pull
            if ($ability instanceof Action_01172)
            {
                $cardCopied = true;
                $card = $this->copyCard($game, "01172", $cesca->ControllerId);
                $ability = $card->getAbilityById("{$card->Id}_Action_01172");
            }

            //Pull the Strand
            if ($ability instanceof Action_01030)
            {
                $cardCopied = true;
                $card = $this->copyCard($game, "01030", $cesca->ControllerId);
                $ability = $card->getAbilityById("{$card->Id}_Action_01030");
            }

            //Sibella Scarpa
            if ($ability instanceof Action_01012)
            {
                $copyAction = true;
                $action = new Action_01012();
                $action->setId("Action_01012");
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
                $transition = EventFactory::createActionTriggeredEvent($cesca->ControllerId, $cesca->Id, $cesca->Id, $action->Id);
                $game->theah->queueEvent($transition);
    
                $this->setUsed($game->theah, true);
                $this->announceReaction($game, $ability);
            }

            if ($cardCopied)
            {
                if ($ability instanceof Action && ! $ability->isAvailableToPlayer($cesca->ControllerId, $game->theah))
                {
                    throw new \BgaUserException($game->translate("Action is not available to perform due to prerequisites."));
                }

                [$discount, $explanations] = $game->theah->getACtionFromHandDiscount($cesca, $ability);
                if ($card instanceof IWealthCost)
                {
                    $cost = $card->getWealthCost() - $discount;
                }
                $handWealth = $game->handWealthCount($cesca->ControllerId);
                if ($handWealth < $cost)
                {
                    throw new \BgaUserException(sprintf($game->translate("You do not have enough Wealth (%d) to pay for the Card (%d with a discount of %d)."), $handWealth, $cost, $discount));
                }                

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
        $card = $game->createCardInLocation($className, Game::LOCATION_HAND, $playerId, $playerId);
        $this->copiedCards[] = $card->Id;

        $game->notify->player($playerId, "drawCard", '${card_inject_code} was temporarily copied into your Faction Hand to immediately be used.', [
            "card_inject_code" => $card->getInjectCode(),
            "card" => $card->getPropertyArray($game),
        ]);

        return $card;
    }
}