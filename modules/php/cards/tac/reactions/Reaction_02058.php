<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02058 extends RiskReaction
{
    private int $PerformerId = 0;
    private string $ChallengeLocation = '';
    private int $DefenderId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage Adjacent Performer to Intervene");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('A challenge has been issued. ${you} may engage an adjacent performer and move them to intervene: ');
    }

    private function getValidPerformers(Theah $theah): array
    {
        $owner = $this->getOwningCard($theah);
        $adjacentLocations = $theah->getAdjacentCityLocations($this->ChallengeLocation);
        $validPerformers = [];

        foreach ($adjacentLocations as $adjLocation)
        {
            $characters = $theah->getCharactersAtLocationByPlayerId($adjLocation, $owner->ControllerId);
            foreach ($characters as $character)
            {
                if (!$character->Engaged && $character->canIntervene())
                {
                    $validPerformers[] = $character;
                }
            }
        }

        // For legendary reputation, only Leaders can intervene
        $challengeType = $theah->game->globals->get(Game::CHALLENGE_TYPE);
        if ($challengeType == Game::LEGENDARY_REPUTATION_CHALLENGE_TYPE)
        {
            $validPerformers = array_filter($validPerformers, fn($c) => $c instanceof Leader);
            $validPerformers = array_values($validPerformers);
        }

        return $validPerformers;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $validPerformers = $this->getValidPerformers($theah);
        foreach ($validPerformers as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Intervene with %s'), $character->Name), "intervene-$character->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued && !$event->canceled && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_HAND)
            {
                $defender = $event->theah->getCharacterById($event->defenderId);
                if ($defender && $defender->ControllerId == $owner->ControllerId)
                {
                    // Challenge types that prohibit all intervention
                    $challengeType = $event->theah->game->globals->get(Game::CHALLENGE_TYPE);
                    if ($challengeType == Game::VALERI_MIKHAILOV_CHALLENGE_TYPE ||
                        $challengeType == Game::TORVO_ESPADA_CHALLENGE_TYPE)
                    {
                        return;
                    }

                    $this->ChallengeLocation = $defender->Location;
                    $this->DefenderId = $defender->Id;
                    $owner->IsUpdated = true;

                    $validPerformers = $this->getValidPerformers($event->theah);
                    if (count($validPerformers) > 0)
                    {
                        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($transition);
                    }
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($game->theah);
            $performer = $game->theah->getCharacterById($this->PerformerId);

            // Engage the performer
            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $performer->Id, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            // Move performer to challenge location
            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $performer->Id, $performer->Location, $this->ChallengeLocation, false, $owner->Id, $this->Id);
            $game->theah->queueEvent($moveEvent);

            // Perform the intervention
            $target = $game->theah->getCardById($game->globals->get(Game::CHOSEN_TARGET));
            $target->removeCondition(Game::DUEL_DEFENDER);
            $performer->addCondition(Game::DUEL_DEFENDER);
            $game->globals->set(Game::CHOSEN_TARGET, $performer->Id);

            $interveneEvent = EventFactory::createCharacterIntervenedEvent($owner->ControllerId, $target->Id, $performer->Id);
            $game->theah->eventCheck($interveneEvent);
            $game->theah->queueEvent($interveneEvent);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} uses Reaction. ${performer_inject_code} moves to ${location_name} and INTERVENES in the Challenge.'), [
                "i18n" => ["location_name"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "performer_inject_code" => $performer->getInjectCode(),
                "location_name" => $this->ChallengeLocation,
            ]);

            $game->globals->set(Game::CHALLENGE_ACCEPTED, true);

            $this->setUsed($game->theah, true);
        }

        if ($event instanceof EventPlayerTurnEnd && ($this->PerformerId != 0 || $this->ChallengeLocation != ''))
        {
            $owner = $this->getOwningCard($event->theah);
            $this->PerformerId = 0;
            $this->ChallengeLocation = '';
            $this->DefenderId = 0;
            $owner->IsUpdated = true;
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'pass')
        {
            $characterId = (int) str_replace("intervene-", "", $reactionId);
            $this->PerformerId = $characterId;

            $owner = $this->getOwningCard($game->theah);
            $owner->IsUpdated = true;

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }

        $game->gamestate->nextState("done");
    }

    public function getReactionFromHandDiscount(Theah $theah, CardReaction $reaction, array &$explanations): int
    {
        $discount = parent::getReactionFromHandDiscount($theah, $reaction, $explanations);

        $owner = $this->getOwningCard($theah);
        if ($owner->Location == Game::LOCATION_HAND && $reaction->Id == $this->Id && $this->PerformerId != 0)
        {
            $performer = $theah->getCharacterById($this->PerformerId);
            if ($performer && ($performer->hasTrait("Hero") || $performer->hasTrait("Knight")))
            {
                $discount += 1;
                $explanations[] = sprintf($theah->game->translate("%s: -1 because Performer is a Hero or Knight."), $owner->getInjectCode());
            }
        }

        return $discount;
    }
}
