<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02008_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_02008 extends RiskAction implements ISorcererAbility, IAbilityThatTargetsCharacters, IAbilityThatTargetsCards
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Place a Risk from your Discard pile under Opponent's Character");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $performers = $theah->getCharactersInPlayByPlayerId($playerId);
        $performers = array_filter($performers, fn($performer) => $performer->hasTrait("Sorcerer") && $performer->hasTrait("Strega"));
        if (count($performers) == 0)
        {
            return false;
        }

        $discardName = $theah->game->getPlayerDiscardDeckName($playerId);
        $risks = $theah->getCardObjectsAtLocation($discardName);
        $risks = array_filter($risks, fn($risk) => $risk instanceof Risk);

        return count($risks) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, fn($performer) => $performer->hasTrait("Sorcerer") && $performer->hasTrait("Strega")));
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transitionEvent = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "02008", $this->Id);
            $event->theah->queueEvent($transitionEvent);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02008)
        {
            $owner = $this->getOwningCard($game->theah);
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;

            $discardName = $game->getPlayerDiscardDeckName($game->getActivePlayerId());
            $risks = $game->theah->getCardObjectsAtLocation($discardName);
            $risks = array_filter($risks, fn($risk) => $risk instanceof Risk && $risk->Id != $owner->Id);
            $args['cards'] = array_map(fn($risk) => $risk->getPropertyArray($game), array_values($risks));
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02008_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args['performerId'] = $performerId;

            $characters = $game->theah->getCharactersInPlay();
            $characters = array_filter($characters, fn($character) => $game->theah->cardInCity($character) && $character->isNotControlledByPlayer($owner->ControllerId));
            $args['ids'] = array_map(fn($character) => $character->Id, array_values($characters));
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02008)
        {
            $risk = $game->theah->getCardById($id);
            if ( ! $risk instanceof Risk)
            {
                throw new \BgaUserException($game->translate("Risk not found"));
            }

            $owner = $this->getOwningCard($game->theah);
            $discardName = $game->getPlayerDiscardDeckName($owner->ControllerId);
            if ($risk->Location != $discardName)
            {
                throw new \BgaUserException($game->translate("Risk is not in your Discard Pile"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId);
            $game->theah->queueEvent($sorceryStartEvent);

            $game->globals->set(Game::CHOSEN_CARD, $risk->Id);
            $game->gamestate->nextState();
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_02008_2)
        {
            $character = $game->theah->getCharacterById($id);
            if ( ! $character)
            {
                throw new \BgaUserException($game->translate("Character not found"));
            }

            $owner = $this->getOwningCard($game->theah);
            if ($character->ControllerId == $owner->ControllerId)
            {
                throw new \BgaUserException($game->translate("You cannot place a Risk under your own character"));
            }

            $riskCardId = $game->globals->get(Game::CHOSEN_CARD);
            $riskCard = $game->theah->getCardById($riskCardId);

            //Place original card in special hiding location
            $deck = $game->getGameDeckObject();
            $deck->moveCard($riskCard->Id, Game::LOCATION_PERMANENTLY_HIDDEN);

            $moveEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($riskCard->ControllerId, $riskCard->Id, $isHidden = true);
            $game->theah->queueEvent($moveEvent);

            $card = $game->createCardInLocation('02008_RiskClone', $character->Location, $owner->ControllerId, $owner->ControllerId);
            $card->Name = $riskCard->Name;
            $card->Image = $riskCard->Image;

            if ($card instanceof _02008_RiskClone)
            {
                $card->ClonedCardId = $riskCard->Id;
            }

            $game->updateCardObjectInDb($card);

            // getRequiredAttachTargetId not needed as this is a Risk, not an Attachment
            $attachEvent = EventFactory::createAttachmentEquippedEvent($owner->ControllerId, $character->Id, $card->Id, 0, 0, $asAction = false, '', $isQuiet = true);
            $game->theah->queueEvent($attachEvent);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $sorceryEndEvent = EventFactory::createSorcererAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performerId);
            $game->theah->queueEvent($sorceryEndEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $this->resetPlayerPassCount($game);

            $game->gamestate->nextState();
        }
    }
}