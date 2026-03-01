<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use BgaUserException;

class Action_01052 extends RiskAction implements IAbilityThatTargetsCards, IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Heal a Wound");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        $characters = $theah->getCharactersInPlayByPlayerId($playerId);
        $characters = array_filter($characters, fn($character) => $character->Wounds > 0);
        foreach ($characters as $character)
        {
            if (count($character->Attachments) > 0)
            {
                return true;
            }
        }

        return false;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        $performers = array_values(array_filter($performers, fn($perforer) => count($perforer->Attachments) > 0 && $perforer->Wounds > 0));

        return $performers;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        $owner = $this->getOwningCard($game->theah);
        if ($performer->ControllerId != $owner->ControllerId)
        {
            return [false, $game->translate("You cannot heal a wound on a character that is not yours.")];
        }

        if ($performer->Wounds == 0)
        {
            return [false, $game->translate("You cannot heal a wound on a character that is not wounded.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if (! $performer)
            {
                throw new UserException($game->translate("Invalid character"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $performer);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);
            $healEvent = EventFactory::createCharacterBeingHealedEvent($performerId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($healEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($performer->ControllerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}