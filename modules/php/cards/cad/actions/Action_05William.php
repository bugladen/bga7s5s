<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05William extends SchemeCityAction implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Engage Artifact; move opposing non-Leader Home";
        $this->RequiresPerformerSelected = true;
    }

    /**
     * @return list<Attachment>
     */
    private function getUnengagedArtifacts(Theah $theah, Character $performer): array
    {
        $artifacts = [];
        foreach ($performer->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById((int)$attachmentId);
            if ($attachment !== null
                && ! $attachment->FakeAttachment
                && $attachment->hasTrait("Artifact")
                && ! $attachment->Engaged)
            {
                $artifacts[] = $attachment;
            }
        }

        return $artifacts;
    }

    /**
     * @return list<Character>
     */
    private function getOpposingNonLeaderTargets(Theah $theah, Character $performer): array
    {
        return array_values(array_filter(
            $theah->getCharactersAtLocation($performer->Location),
            fn(Character $character) => $character->ControllerId != $performer->ControllerId
                && $character->ControllerId != 0
                && ! $character->hasTrait("Leader")
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        return array_values(array_filter(
            $performers,
            fn(Character $performer) => count($this->getUnengagedArtifacts($theah, $performer)) > 0
                && count($this->getOpposingNonLeaderTargets($theah, $performer)) > 0
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);

            if ($performer === null || $performer->ControllerId != $event->playerId)
            {
                throw new UserException("Invalid performer");
            }

            if (! $event->theah->cardInCity($performer))
            {
                throw new UserException("Performer must be at a City location.");
            }

            if (count($this->getUnengagedArtifacts($event->theah, $performer)) == 0)
            {
                throw new UserException("Performer has no unengaged Artifact equipped.");
            }

            if (count($this->getOpposingNonLeaderTargets($event->theah, $performer)) == 0)
            {
                throw new UserException("No opposing non-Leader at this location.");
            }

            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "05William", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05WILLIAM)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $args["performerId"] = $performerId;
            // WHY: Buttons for attached Artifacts (not board-selectable) — attachments are
            // often hard to click under the host. CAD: plain Name, no translate.
            $args["attachments"] = $performer !== null
                ? array_map(
                    fn(Attachment $attachment) => [
                        "id" => $attachment->Id,
                        "name" => $attachment->Name,
                    ],
                    $this->getUnengagedArtifacts($game->theah, $performer)
                )
                : [];
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05WILLIAM_2)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            $attachmentId = (int)$game->globals->get(Game::CHOSEN_CARD);

            $args["performerId"] = $performerId;
            $args["attachmentId"] = $attachmentId;
            $args["ids"] = $performer !== null
                ? array_map(fn(Character $character) => $character->Id, $this->getOpposingNonLeaderTargets($game->theah, $performer))
                : [];
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
        $performer = $game->theah->getCharacterById($performerId);

        if ($performer === null)
        {
            return [false, "Performer not found"];
        }

        if ($character->ControllerId == $performer->ControllerId || $character->ControllerId == 0)
        {
            return [false, "Target must be an opposing character"];
        }

        if ($character->Location != $performer->Location)
        {
            return [false, "Target must be at the performer's location"];
        }

        if ($character->hasTrait("Leader"))
        {
            return [false, "Target cannot be a Leader"];
        }

        return [true, ""];
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05WILLIAM)
        {
            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException("Performer not found");
            }

            $artifacts = $this->getUnengagedArtifacts($game->theah, $performer);
            $artifactIds = array_map(fn(Attachment $attachment) => $attachment->Id, $artifacts);
            if (! in_array($id, $artifactIds, true))
            {
                throw new UserException("Attachment must be an unengaged Artifact equipped to the performer.");
            }

            $game->globals->set(Game::CHOSEN_CARD, $id);
            $game->gamestate->nextState("artifactChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_05WILLIAM_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $target = $game->theah->getCharacterById($id);
            if ($target === null)
            {
                throw new UserException("Character not found");
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $performerId = (int)$game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);
            if ($performer === null)
            {
                throw new UserException("Performer not found");
            }

            $attachmentId = (int)$game->globals->get(Game::CHOSEN_CARD);
            $attachment = $game->theah->getAttachmentById($attachmentId);
            $equippedIds = array_map('intval', $performer->Attachments);
            if ($attachment === null
                || $attachment->FakeAttachment
                || ! $attachment->hasTrait("Artifact")
                || $attachment->Engaged
                || ! in_array($attachmentId, $equippedIds, true))
            {
                throw new UserException("Attachment must be an unengaged Artifact equipped to the performer.");
            }

            $game->notify->all("message", '${scheme_inject_code}: ${player_name} engages ${attachment_inject_code} and moves ${target_inject_code} Home.', [
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "attachment_inject_code" => $attachment->getInjectCode(),
                "target_inject_code" => $target->getInjectCode(),
            ]);

            $engageEvent = EventFactory::createCardEngagedEvent(
                $owner->ControllerId,
                $attachment->Id,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($engageEvent);
            $game->theah->queueEvent($engageEvent);

            // WHY: engage=false — printed effect is Move Home only (Artifact already paid Engage).
            $moveEvent = EventFactory::createCardMovingEvent(
                $owner->ControllerId,
                $target->Id,
                $target->Location,
                Game::LOCATION_PLAYER_HOME,
                false,
                $owner->Id,
                $this->Id
            );
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($owner->ControllerId);
            $game->theah->queueEvent($actionResolvedEvent);

            $game->gamestate->nextState("targetChosen");
        }
    }
}
