<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02030a extends RiskReaction
{
    private ?int $MusketeerCharacterId = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Gain Musketeer when ability announced");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('An ability has been announced. ${you} may choose a character to gain Musketeer until the action resolves: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCard($theah);
        $characters = $theah->getCharactersInCityByPlayerId($owner->ControllerId);
        foreach ($characters as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, $character->Name, "gainMusketeer-$character->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    private function isAbilityAnnouncedEvent(Event $event): bool
    {
        return $event instanceof EventActionActivated
            || $event instanceof EventReactionActivated
            || $event instanceof EventTechniqueActivated
            || $event instanceof EventManeuverActivated;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($this->isAbilityAnnouncedEvent($event) && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);

            if ($event instanceof EventReactionActivated && $event->sourceId == $owner->Id)
                return;

            if ($owner->Location == Game::LOCATION_HAND)
            {
                $characters = $event->theah->getCharactersInCityByPlayerId($owner->ControllerId);
                if (count($characters) > 0)
                {
                    $owner->IsUpdated = true;

                    $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }

        if ($event instanceof EventRiskReactionTriggered && $event->internalId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $character = $event->theah->getCharacterById($this->MusketeerCharacterId);

            $character->addTrait($game, "Musketeer");

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction. ${character_inject_code} gains Musketeer until the action resolves.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $this->setUsed($event->theah, true);
        }

        if ($event instanceof EventActionResolved && $this->MusketeerCharacterId !== null)
        {
            $game = $event->theah->game;
            $character = $event->theah->getCharacterById($this->MusketeerCharacterId);
            if ($character)
            {
                $owner = $this->getOwningCard($event->theah);
                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${character_inject_code} loses Musketeer as the action has resolved.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                $character->removeTrait($game, "Musketeer");
            }

            $this->MusketeerCharacterId = null;
            $owner = $this->getOwningCard($event->theah);
            if ($owner)
            {
                $owner->IsUpdated = true;
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != 'pass')
        {
            $owner = $this->getOwningCard($game->theah);
            $characterId = str_replace("gainMusketeer-", "", $reactionId);
            $this->MusketeerCharacterId = (int) $characterId;
            $owner->IsUpdated = true;

            $event = EventFactory::createEnteringPayStateEvent($owner->ControllerId, $owner->Id, Game::PAY_STATE_IN_HAND_REACTION, $this->Id);
            $game->theah->queueEvent($event);

            $event = EventFactory::createReactionPayTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $game->theah->queueEvent($event);
        }

        $game->gamestate->nextState("done");
    }
}
