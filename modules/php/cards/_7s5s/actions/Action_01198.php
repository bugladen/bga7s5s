<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\Theah\Events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_01198 extends AttachmentAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Issue a Finesse Challenge");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (!parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        if ($owner->Engaged || ! $theah->cardInCity($owner))
        {
            return false;
        }

        //Get all opposing characters at the same location
        $characters = $theah->getCharactersAtLocation($owner->Location);
        $characters = array_filter($characters, fn($character) => $character->ControllerId != $playerId);

        //If there are no opposing characters, do not show as available
        if (count($characters) == 0)
        {
            return false;
        }

        return true;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You cannot challenge a character that is controlled by you.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Character is not at the same location as the performer")];
        }

        return [true, ""];
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventChallengeRejected)
        {
            $attachment = $this->getOwningAttachment($event->theah);
            if ($attachment->isAttached())
            {
                $challengeType = $event->theah->game->globals->get(Game::CHALLENGE_TYPE);
                $owner = $this->getOwningCharacter($event->theah);
                $target = $event->theah->getCharacterById($event->targetId);
                if ($challengeType == Game::TRISKELION_CHALLENGE_TYPE 
                    && $owner->Id == $event->challengerId 
                    && $target instanceof Character 
                    && ! $target->hasTrait("Leader"))
                {
                    throw new UserException($event->theah->game->translate("Guild Triskelion: Only Leaders can reject a challenge!"));
                }
            }
        }
    }
    
    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $event->theah->game->globals->set(Game::CHALLENGE_TYPE, Game::TRISKELION_CHALLENGE_TYPE);
            $event->theah->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);

            $owner = $this->getOwningCharacter($event->theah);
            $event->theah->game->globals->set(Game::CHOSEN_PERFORMER, $owner->Id);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->OwnerId, "01198", $this->Id);
            $event->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved
        }
    }
    
    
}
