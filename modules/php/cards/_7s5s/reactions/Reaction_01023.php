<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeAccepted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01023 extends RiskReaction
{
    public bool $PreventIntervention = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Prevent Intervention');
    }

    public function getReactionAnnouncement(Game $game, int $state, string $internalId, string $reactionId): string
    {
        $announcement = parent::getReactionAnnouncement($game, $state, $internalId, $reactionId);
        return $announcement . $game->translate("Choose whether interventions will be allowed this Challenge: ");
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Prevent Intervention'), 'preventIntervention');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Prevent Intervention: ');
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventCharacterIntervened)
        {            
            $risk = $this->getOwningCard($event->theah);
            $discardDeckName = $event->theah->game->getPlayerDiscardDeckName($risk->ControllerId);
            if ($risk->Location == $discardDeckName && $this->PreventIntervention)
            {
                throw new \BgaUserException($event->theah->game->translate("Ambush: No characters may intervene in this Challenge."));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued && $this->isAvailable())
        {
            $risk = $this->getOwningCard($event->theah);
            if ($risk->Location == Game::LOCATION_HAND && $risk->ControllerId == $event->playerId)
            {
                $transition = EventFactory::createReactionTransitionEvent($risk->ControllerId, $risk->Id, $this->Id);
                $event->theah->queueEvent($transition);
            }
        }

        if ($event instanceof EventDuelStarted)
        {
            $risk = $this->getOwningCard($event->theah);
            $discardDeckName = $event->theah->game->getPlayerDiscardDeckName($risk->ControllerId);
            if ($risk->Location == $discardDeckName)
            {
                $this->PreventIntervention = false;
                $risk->IsUpdated = true;
            }
        }

        if ($event instanceof EventChallengeAccepted || $event instanceof EventChallengeRejected || $event instanceof EventPlayerTurnEnd)
        {
            $this->PreventIntervention = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            if ($event->reactionId == 'preventIntervention')
            {
                $game = $event->theah->game;
                $owner = $this->getOwningCard($game->theah);
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction. Other characters will not be allowed to intervene in this Challenge.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                ]);
    
                $this->PreventIntervention = true;
                $owner->IsUpdated = true;
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'preventIntervention')
        {
            $owner = $this->getOwningCard($game->theah);
            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }

        $game->gamestate->nextState('done');
    }

    public function getReactionFromHandDiscount(Theah $theah, CardReaction $reaction, Array &$explanations) : int
    {
        $discount = parent::getReactionFromHandDiscount($theah, $reaction, $explanations);

        $owner = $this->getOwningCard($theah);
        if ($owner->Location == Game::LOCATION_HAND && $reaction->Id == $this->Id)
        {
            $challengeLocation = $theah->game->globals->get(GAME::CHOSEN_LOCATION);
            if ($challengeLocation == null)
            {
                return $discount;
            }

            //Get all characters at the location of the challenge controlled by the player
            $characters = $theah->getCharactersAtLocationByPlayerId($challengeLocation, $owner->ControllerId);
            //Filter to characters that have the Brute trait
            $characters = array_filter($characters, fn($character) => $character->hasTrait('Brute'));
            if (count($characters) > 0)
            {
                $discount += 1;
            }
        }

        return $discount;

    }
}