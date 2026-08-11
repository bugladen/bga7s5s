<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_04019 extends RiskAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Engage Attachment; Issue Combat Challenge");
        $this->RequiresPerformerSelected = true;
    }

    private function isEligibleAttachment(?Attachment $attachment): bool
    {
        if ($attachment === null || $attachment->Engaged)
        {
            return false;
        }

        return ($attachment->hasTrait("Weapon") && $attachment->hasTrait("Melee"))
            || $attachment->hasTrait("Eisenfaust");
    }

    /**
     * @return list<Attachment>
     */
    private function getEligibleAttachments(Theah $theah, Character $performer): array
    {
        $attachments = [];
        foreach ($performer->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($this->isEligibleAttachment($attachment))
            {
                $attachments[] = $attachment;
            }
        }

        return $attachments;
    }

    /**
     * @return list<Character>
     */
    private function getOpposingTargets(Theah $theah, Character $performer): array
    {
        return array_values($theah->getOpposingCharactersAtLocation($performer->Location, $performer->ControllerId));
    }

    /**
     * @return list<Character>
     */
    private function getEligiblePerformers(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);
        return array_values(array_filter(
            $performers,
            function (Character $performer) use ($theah) {
                // WHY En Garde Action label: performer must already be ready — not an Engage cost.
                if ($performer->Engaged || ! $performer->canChallenge($theah))
                {
                    return false;
                }

                return count($this->getEligibleAttachments($theah, $performer)) > 0
                    && count($this->getOpposingTargets($theah, $performer)) > 0;
            }
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getEligiblePerformers($playerId, $theah)) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        return $this->getEligiblePerformers($playerId, $theah);
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            return [false, $game->translate("Performer not found")];
        }

        if (! $character->isControlled() || $character->ControllerId == $performer->ControllerId)
        {
            return [false, $game->translate("You must target an opposing character.")];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, $game->translate("Target must be at your performer's location.")];
        }

        return [true, ""];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "04019", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04019)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $args["performerId"] = $performerId;

            $args["attachments"] = [];
            if ($performer !== null)
            {
                foreach ($this->getEligibleAttachments($game->theah, $performer) as $attachment)
                {
                    $args["attachments"][] = [
                        "id" => $attachment->Id,
                        "name" => $attachment->Name,
                    ];
                }
            }
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_04019)
        {
            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment === null)
            {
                throw new UserException($game->translate("Attachment not found"));
            }

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException($game->translate("Performer not found"));
            }

            if ($attachment->AttachedToId != $performer->Id)
            {
                throw new UserException($game->translate("Attachment is not attached to the performer"));
            }

            if (! $this->isEligibleAttachment($attachment))
            {
                throw new UserException($game->translate("Attachment is not a Melee Weapon or Eisenfaust"));
            }

            $owner = $this->getOwningCard($game->theah);

            $engageEvent = EventFactory::createCardEngagedEvent(
                $performer->ControllerId,
                $attachment->Id,
                $owner->Id,
                $this->Id
            );
            $game->theah->queueEvent($engageEvent);

            // WHY NO_MORE_WORDS_CHALLENGE_TYPE: attachment Engage is already paid — not NORMAL
            // (Back on highDramaChallengeActionChooseTarget only shows for NORMAL).
            // Still on stIssueChallenge auto-engage list so performer engages when issuing.
            $game->globals->set(Game::CHALLENGE_TYPE, Game::NO_MORE_WORDS_CHALLENGE_TYPE);
            $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

            $transition = EventFactory::createTransitionEvent($performer->ControllerId, $owner->Id, "04019_2", $this->Id);
            $game->theah->queueEvent($transition);

            // createActionResolvedEvent() is called when the challenge is resolved

            $game->gamestate->nextState("attachmentChosen");
        }
    }
}
