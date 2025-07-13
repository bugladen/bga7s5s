<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01065 extends CardReaction
{
    private $preventedCharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Prevent Character from Intervening");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose a character to prevent from intervening: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $henri = $this->getOwningCharacter($theah);
        $defenderId = $theah->game->globals->get(Game::CHOSEN_TARGET);
        $characters = $theah->getCharactersAtLocation($henri->Location);
        $characters = array_filter($characters, fn($character) => $character->ControllerId != $henri->ControllerId && $character->Id != $defenderId);
        foreach ($characters as $character)
        {
            $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Prevent %s from Intervening'), $character->Name), "prevent-$character->Id");
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued)
        {
            $henriHasWeapon = false;
            $henri = $this->getOwningCharacter($event->theah);
            if ($event->challengerId == $henri->Id && count($henri->Attachments) > 0)
            {
                foreach ($henri->Attachments as $attachmentId)
                {
                    $attachment = $event->theah->getAttachmentById($attachmentId);
                    if ($attachment->hasTrait("Weapon") && ! $attachment->Engaged)
                    {
                        $henriHasWeapon = true;
                        break;
                    }
                }
            }

            $characters = $event->theah->getCharactersAtLocation($henri->Location);
            $characters = array_filter($characters, fn($character) => $character->ControllerId != $henri->ControllerId && $character->Id != $event->defenderId);
            
            if (count($characters) > 0 && $henriHasWeapon)
            {
                $event->theah->queueEvent(EventFactory::createReactionTransitionEvent($henri->ControllerId, $henri->Id, $this->Id));
            }
        }

        if ($event instanceof EventDuskEndOfDay)
        {
            $henri = $this->getOwningCharacter($event->theah);
            $henri->IsUpdated = true;
            $this->preventedCharacterId = 0;
        }
    }

    public function eventCheck(Event $event)
    {
        if ($event instanceof EventCharacterIntervened)
        {
            $challengerId = $event->theah->game->globals->get(Game::CHOSEN_PERFORMER);            
            $henri = $this->getOwningCharacter($event->theah);
            if ($event->newTargetId == $this->preventedCharacterId && $challengerId == $henri->Id)
            {
                $newTarget = $event->theah->getCharacterById($event->newTargetId);
                throw new \BgaUserException(sprintf($event->theah->game->translate('Henri Michelet: %s is prevented from Intervening.'), $newTarget->Name));
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId != "decline")
        {
            $henri = $this->getOwningCharacter($game->theah);
            $henri->IsUpdated = true;

            $characterId = str_replace("prevent-", "", $reactionId);
            $character = $game->theah->getCharacterById($characterId);

            $this->preventedCharacterId = $characterId;

            $game->notifyAllPlayers("message", clienttranslate('<strong>Henri Michelet</strong>: ${player_name} used Reaction and prevented ${character_name} from intervening.'), [
                "i18n" => ["player_name", "character_name"],
                "player_name" => $game->getPlayerNameById($henri->ControllerId),
                "character_name" => $character->Name,
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }
}