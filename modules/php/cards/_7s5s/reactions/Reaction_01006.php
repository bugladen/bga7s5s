<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseBegin;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01006 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Remove Brute from Character');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose a character at Constanzo\'s Location to lose Brute: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $don = $this->getOwningCharacter($theah);
        $characters = $theah->getCharactersAtLocation($don->Location);
        $characters = array_filter($characters, fn($character) => 
            $character->Id != $don->Id && 
            $character->ControllerId == $don->ControllerId && 
            $character->hasTrait("Brute"));
        foreach ($characters as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, $character->Name, "loseBrute-$character->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuskPhaseBegin && $this->isAvailable())
        {
            $don = $this->getOwningCharacter($event->theah);    
            if ($event->theah->cardInCity($don))
            {
                $characters = $event->theah->getCharactersAtLocation($don->Location);
                $characters = array_filter($characters, fn($character) => 
                    $character->Id != $don->Id && 
                    $character->ControllerId == $don->ControllerId && 
                    $character->hasTrait("Brute"));
                if (count($characters) > 0)
                {
                    $reactionEvent = EventFactory::createReactionTransitionEvent($don->ControllerId, $don->Id, $this->Id);
                    $event->theah->queueEvent($reactionEvent);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "pass")
        {
            //Get the id of the character from the reactionId
            $id = str_replace("loseBrute-", "", $reactionId);
            $character = $game->theah->getCardById($id);
            $character->removeTrait("Brute");
            $character->IsUpdated = true;

            $don = $this->getOwningCharacter($game->theah);

            $game->notifyAllPlayers("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction and removed [Brute] from ${character_name}.'), [
                "reaction_inject_code" => $don->getInjectCode(),
                "player_name" => $game->getPlayerNameById($don->ControllerId),
                "character_name" => $character->Name,
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");

    }
}