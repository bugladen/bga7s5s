<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

// Covert Maneuver: The adversary may discard an Attachment. If they do not, engage target opposing character.
class Maneuver_05Stash extends Maneuver implements IAbilityThatTargetsCharacters
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Name = "Adversary may discard Attachment or you engage opposing";
    }

    private function isCovertSite(Theah $theah, Character $actor): bool
    {
        if (! $theah->cardInCity($actor))
        {
            return false;
        }

        $cityLocation = $theah->getCityLocation($actor->Location);
        return $cityLocation !== null && ! $cityLocation->isControlled();
    }

    /**
     * Real Attachments equipped to the duel adversary (skip FakeAttachment).
     *
     * @return list<Attachment>
     */
    private function getDiscardableAttachments(Theah $theah): array
    {
        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary === null)
        {
            return [];
        }

        $attachments = [];
        foreach ($adversary->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            // WHY skip FakeAttachment: RiskAttachments are not printed Attachments to discard.
            if ($attachment === null || $attachment->FakeAttachment)
            {
                continue;
            }
            $attachments[] = $attachment;
        }

        return $attachments;
    }

    /**
     * Opposing (same location, other controller) en garde characters at the actor's location.
     *
     * @return list<Character>
     */
    private function getEngageTargets(Theah $theah): array
    {
        $actor = $theah->getDuelRoundActor();
        if ($actor === null)
        {
            return [];
        }

        $opposing = $theah->getOpposingCharactersAtLocation($actor->Location, $actor->ControllerId);
        return array_values(array_filter(
            $opposing,
            fn(Character $character) => ! $character->Engaged
        ));
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        $actor = $theah->getDuelRoundActor();
        if ($actor === null || ! $this->isCovertSite($theah, $actor))
        {
            return false;
        }

        // WHY engage targets required: "If they do not, engage" is the stick. Without a legal
        // engage target, Decline would softlock — grey the Maneuver instead.
        return count($this->getEngageTargets($theah)) > 0;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $actor = $game->theah->getDuelRoundActor();
        if ($actor === null)
        {
            return [false, 'No duel participant.'];
        }

        if ($character->ControllerId == $actor->ControllerId)
        {
            return [false, 'You cannot engage your own character.'];
        }

        if ($character->Location != $actor->Location)
        {
            return [false, 'Character is not at this location.'];
        }

        if ($character->Engaged)
        {
            return [false, 'Character is already engaged.'];
        }

        return [true, ''];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventManeuverCanceled handler not needed

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $theah = $event->theah;
            $owner = $this->getOwningCard($theah);
            $target = $theah->getCharacterById($event->targetId);
            if ($owner === null || $target === null)
            {
                return;
            }

            $theah->game->notify->all('message', '${maneuver_inject_code}: ${player_name} engages ${character_inject_code}.', [
                'maneuver_inject_code' => $owner->getInjectCode(),
                'player_name' => $theah->game->getPlayerNameById($owner->ControllerId),
                'character_inject_code' => $target->getInjectCode(),
            ]);

            $engageEvent = EventFactory::createCardEngagedEvent(
                $owner->ControllerId,
                $target->Id,
                $owner->Id,
                $this->Id
            );
            $engageEvent->batchId = $event->batchId ?? $theah->game->getNextEventBatchId();
            $theah->eventCheck($engageEvent);
            $theah->queueEvent($engageEvent);
        }

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();
            if ($owner === null || $adversary === null)
            {
                return;
            }

            $attachments = $this->getDiscardableAttachments($event->theah);
            if (count($attachments) > 0)
            {
                // Adversary may discard — offer Pass that routes to engage.
                $transition = EventFactory::createTransitionEvent(
                    $adversary->ControllerId,
                    $owner->Id,
                    '05Stash',
                    $this->Id
                );
                $event->theah->queueEvent($transition);
                return;
            }

            // No Attachment to discard ⇒ they did not ⇒ engage chooser for the Maneuver owner.
            $transition = EventFactory::createTransitionEvent(
                $owner->ControllerId,
                $owner->Id,
                '05Stash_2',
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_RESOLVE_MANEUVER_05STASH)
        {
            $attachments = [];
            foreach ($this->getDiscardableAttachments($game->theah) as $attachment)
            {
                $attachments[] = [
                    'id' => $attachment->Id,
                    'name' => $attachment->Name,
                ];
            }
            $args['attachments'] = $attachments;
        }

        if ($state == States::DUEL_RESOLVE_MANEUVER_05STASH_2)
        {
            $actor = $game->theah->getDuelRoundActor();
            $args['performerId'] = $actor?->Id ?? 0;
            $args['ids'] = array_values(array_map(
                fn(Character $character) => $character->Id,
                $this->getEngageTargets($game->theah)
            ));
        }

        return $args;
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_RESOLVE_MANEUVER_05STASH)
        {
            $owner = $this->getOwningCard($game->theah);
            $adversary = $game->theah->getDuelRoundOpponent();
            if ($owner === null || $adversary === null)
            {
                throw new UserException('Invalid duel state.');
            }

            // id 0 = Decline / Pass → engage path for the Maneuver owner.
            if ($id === 0)
            {
                $game->notify->all('message', '${maneuver_inject_code}: ${player_name} declined to discard an Attachment.', [
                    'maneuver_inject_code' => $owner->getInjectCode(),
                    'player_name' => $game->getPlayerNameById($adversary->ControllerId),
                ]);

                $transition = EventFactory::createTransitionEvent(
                    $owner->ControllerId,
                    $owner->Id,
                    '05Stash_2',
                    $this->Id
                );
                $game->theah->queueEvent($transition);
                $game->gamestate->nextState();
                return;
            }

            $attachment = $game->theah->getAttachmentById($id);
            if ($attachment === null || $attachment->FakeAttachment)
            {
                throw new UserException('Invalid attachment.');
            }

            if (! in_array($attachment->Id, $adversary->Attachments, true))
            {
                throw new UserException('Attachment is not equipped to the adversary.');
            }

            $game->notify->all('message', '${maneuver_inject_code}: ${player_name} discards ${attachment_inject_code}.', [
                'maneuver_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($adversary->ControllerId),
                'attachment_inject_code' => $attachment->getInjectCode(),
            ]);

            $unequipEvent = EventFactory::createAttachmentUnequippedEvent(
                $adversary->ControllerId,
                $adversary->Id,
                $attachment->Id
            );
            $game->theah->queueEvent($unequipEvent);

            $discardEvent = EventFactory::createAttachmentDiscardedFromPlayEvent(
                $attachment,
                $owner->Id,
                $asEffect = true,
                $adversary->Location
            );
            $game->theah->queueEvent($discardEvent);

            $game->gamestate->nextState();
            return;
        }

        if ($state == States::DUEL_RESOLVE_MANEUVER_05STASH_2)
        {
            $owner = $this->getOwningCard($game->theah);
            $target = $game->theah->getCharacterById($id);
            if ($owner === null || $target === null)
            {
                throw new UserException('Character not found.');
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $target);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $game->notify->all('message', '${maneuver_inject_code}: ${player_name} targets ${character_inject_code}.', [
                'maneuver_inject_code' => $owner->getInjectCode(),
                'player_name' => $game->getPlayerNameById($owner->ControllerId),
                'character_inject_code' => $target->getInjectCode(),
            ]);

            // WHY: Fire targeting alone here. The Engage emits from handleEvent only when
            // EventCharacterTargeted survives (Cloud Bottle / UL cancel it). The adversary's
            // earlier discard-or-decline choice is not part of the targeting. No ActionResolved:
            // a Maneuver never had one (same as Maneuver_02057).
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $target->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }
}
