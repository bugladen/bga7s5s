<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_03072 extends RiskCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Destroy Engaged Attachments; Engage Remaining");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        return array_values(array_filter(
            $performers,
            fn(Character $performer) => count($this->getValidTargets($theah, $performer)) > 0
        ));
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, $game->translate("Target must be controlled by an opponent.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        if (count($this->getRealAttachments($game->theah, $character)) == 0)
        {
            return [false, $game->translate("Target must have at least one attachment.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03072", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03072)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args['performerId'] = $performer->Id;
            $args['ids'] = array_map(
                fn(Character $c) => $c->Id,
                $this->getValidTargets($game->theah, $performer)
            );
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_03072)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);

            // WHY snapshot before queue: unequip/discard events are not applied mid-act,
            // so re-reading Attachments would still include cards about to be destroyed.
            $toDestroy = [];
            $toEngage = [];
            foreach ($this->getRealAttachments($game->theah, $character) as $attachment)
            {
                if ($attachment->Engaged)
                {
                    $toDestroy[] = $attachment;
                }
                else
                {
                    $toEngage[] = $attachment;
                }
            }

            foreach ($toDestroy as $attachment)
            {
                $unequipEvent = EventFactory::createAttachmentUnequippedEvent(
                    $attachment->ControllerId,
                    $attachment->AttachedToId,
                    $attachment->Id
                );
                $game->theah->eventCheck($unequipEvent);
                $game->theah->queueEvent($unequipEvent);

                $discardEvent = EventFactory::createCardDiscardedFromPlayEvent(
                    $attachment->OwnerId,
                    $attachment->Id,
                    $attachment->Location,
                    $owner->Id,
                    $asEffect = true
                );
                $game->theah->queueEvent($discardEvent);
            }

            foreach ($toEngage as $attachment)
            {
                $engageEvent = EventFactory::createCardEngagedEvent(
                    $owner->ControllerId,
                    $attachment->Id,
                    $owner->Id,
                    $this->Id
                );
                $game->theah->queueEvent($engageEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("targetChosen");
        }
    }

    private function getValidTargets(Theah $theah, Character $performer): array
    {
        $opposing = $theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId);
        return array_values(array_filter(
            $opposing,
            fn(Character $c) => count($this->getRealAttachments($theah, $c)) > 0
        ));
    }

    /**
     * @return Attachment[]
     */
    private function getRealAttachments(Theah $theah, Character $character): array
    {
        $attachments = [];
        foreach ($character->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment === null || $attachment->FakeAttachment)
            {
                continue;
            }
            $attachments[] = $attachment;
        }
        return $attachments;
    }
}
