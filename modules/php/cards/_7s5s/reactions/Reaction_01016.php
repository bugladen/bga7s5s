<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;

class Reaction_01016 extends CardReaction implements IAbilityThatTargetsCharacters
{
    private string $claimedLocation;
    
    public function __construct()
    {
        parent::__construct();

        $this->Name = "En Garde your Character after Claiming Location with Opponent";
        $this->claimedLocation = "";
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $owner = $this->getOwningCard($game->theah);
        if ($character->ControllerId != $owner->ControllerId)
        {
            return [false, $game->translate("You cannot target a character that is not controlled by you.")];
        }

        if (! $character->Engaged)
        {
            return [false, $game->translate("Character is not engaged.")];
        }

        if ($character->Location != $this->claimedLocation)
        {
            return [false, $game->translate("Character is not at the claimed location.")];
        }

        return [true, ""];
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose a character to En Garde: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $scheme = $this->getOwningCard($theah);
        $characters = $theah->getCharactersAtLocation($this->claimedLocation);
        $characters = array_filter($characters, fn($character) => $character->ControllerId == $scheme->ControllerId && $character->Engaged);

        foreach ($characters as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, $character->Name, "enGarde-$character->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, "Pass", "pass");

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventLocationClaimed && $this->isAvailable())
        {
            $scheme = $this->getOwningCard($event->theah);
            if ($event->playerId == $scheme->ControllerId)
            {
                $characters = $event->theah->getCharactersAtLocation($event->location);
                
                $engagedCharacters = array_filter($characters, fn($character) => $character->ControllerId == $event->playerId && $character->Engaged);
                $opposingCharacters = array_filter($characters, fn($character) => $character->isNotControlledByPlayer($event->playerId));

                if (count($engagedCharacters) > 0 && count($opposingCharacters) > 0)
                {
                    $transition = EventFactory::createReactionTransitionEvent($event->playerId, $scheme->Id, $this->Id);
                    $event->theah->queueEvent($transition);

                    $this->claimedLocation = $event->location;
                    $scheme->IsUpdated = true;
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "pass")
        {
            $scheme = $this->getOwningCard($game->theah);
            $id = str_replace("enGarde-", "", $reactionId);
            $character = $game->theah->getCardById($id);
            $event = EventFactory::createCardEngardedEvent($scheme->ControllerId, $id, $scheme->Id, $this->Id);
            $game->theah->queueEvent($event);

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} used Reaction to En Garde ${character_inject_code}.'), [
                "reaction_inject_code" => $scheme->getInjectCode(),
                "player_name" => $game->getPlayerNameById($scheme->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");

    }
}