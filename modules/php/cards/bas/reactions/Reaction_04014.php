<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04014 extends CardReaction
{
    private int $characterId = 0;
    private int $buffedCharacterId = 0;
    private string $characterName = '';

    public function __construct()
    {
        parent::__construct();

        // Continuous — any number of times per day (once per challenge/intervention).
        $this->Name = clienttranslate("(Continuous) Engage Weapon or Armor for +1 Finesse");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        $name = $this->characterName !== ''
            ? $this->characterName
            : $theah->game->translate('your character');

        return $base . sprintf(
            $theah->game->translate('%s may engage a Weapon or Armor for +1[Finesse]. ${you} may choose: '),
            $name
        );
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $character = $theah->getCharacterById($this->characterId);
        if ($character !== null)
        {
            foreach ($this->getEligibleAttachments($theah, $character) as $attachment)
            {
                $label = sprintf(
                    $theah->game->translate('Engage %s (+1 Finesse)'),
                    $attachment->Name
                );
                $array[] = $this->createButtonProperty($theah->game, $label, "engage-{$attachment->Id}");
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    /**
     * @return Attachment[]
     */
    private function getEligibleAttachments(Theah $theah, Character $character): array
    {
        $eligible = [];
        foreach ($character->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if ($attachment === null || $attachment->Engaged || $attachment->FakeAttachment)
            {
                continue;
            }

            if ($attachment->hasTrait("Weapon") || $attachment->hasTrait("Armor"))
            {
                $eligible[] = $attachment;
            }
        }

        return $eligible;
    }

    private function offerReaction(Theah $theah, Character $character): void
    {
        $owner = $this->getOwningCard($theah);
        if ($owner === null)
        {
            return;
        }

        if (count($this->getEligibleAttachments($theah, $character)) == 0)
        {
            return;
        }

        $this->characterId = $character->Id;
        $this->characterName = $character->Name;
        $owner->IsUpdated = true;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $theah->queueEvent($transition);
    }

    private function applyFinesseBuff(Theah $theah, Character $character, string $reason): void
    {
        $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
            $character->ControllerId,
            $character->Id,
            $character->ModifiedFinesse,
            $character->ModifiedFinesse + 1,
            $reason
        );
        $theah->queueEvent($finesseEvent);

        // WHY: Condition so the tooltip shows the Finesse mod came from Forged for Battle
        // (Soline / Harpoon pattern) — the chip alone does not name the source.
        $character->addCondition(Game::FORGED_FOR_BATTLE_CONDITION);
        $theah->game->updateCardObjectInDb($character);

        $theah->game->notify->all("forgedForBattleConditionStarted", '', [
            "cardId" => $character->Id,
        ]);

        $this->buffedCharacterId = $character->Id;
    }

    private function clearFinesseBuff(Theah $theah): void
    {
        if ($this->buffedCharacterId == 0)
        {
            return;
        }

        $owner = $this->getOwningCard($theah);
        $character = $theah->getCharacterById($this->buffedCharacterId);
        if ($character !== null
            && $character->hasCondition(Game::FORGED_FOR_BATTLE_CONDITION)
            && ! $theah->game->characterIsInDiscardOrLocker($character))
        {
            $reason = $owner !== null ? $owner->getInjectCode() : '';
            $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
                $character->ControllerId,
                $character->Id,
                $character->ModifiedFinesse,
                $character->ModifiedFinesse - 1,
                $reason
            );
            $theah->queueEvent($finesseEvent);

            $character->removeCondition(Game::FORGED_FOR_BATTLE_CONDITION);
            $theah->game->updateCardObjectInDb($character);

            $theah->game->notify->all("forgedForBattleConditionEnded", '', [
                "cardId" => $character->Id,
            ]);
        }

        $this->buffedCharacterId = 0;
        if ($owner !== null)
        {
            $owner->IsUpdated = true;
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued && $this->isAvailable() && ! $event->canceled)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }

            $challenger = $event->theah->getCharacterById($event->challengerId);
            if ($challenger === null || $challenger->ControllerId != $owner->ControllerId)
            {
                return;
            }

            $this->offerReaction($event->theah, $challenger);
        }

        if ($event instanceof EventCharacterIntervened && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner === null)
            {
                return;
            }

            // WHY: Intervening player owns the action; newTargetId is the intervener.
            if ($event->playerId != $owner->ControllerId)
            {
                return;
            }

            $intervener = $event->theah->getCharacterById($event->newTargetId);
            if ($intervener === null || $intervener->ControllerId != $owner->ControllerId)
            {
                return;
            }

            $this->offerReaction($event->theah, $intervener);
        }

        // WHY: Mid-duel ActionResolved must not wipe Finesse needed for gambling —
        // same gate as Action_04009. At duel end IN_DUEL is already false.
        if ($event instanceof EventActionResolved
            && $this->buffedCharacterId != 0
            && ! $event->theah->game->globals->get(Game::IN_DUEL, false))
        {
            $this->clearFinesseBuff($event->theah);
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->buffedCharacterId)
        {
            $this->buffedCharacterId = 0;
            $owner = $this->getOwningCard($event->theah);
            if ($owner !== null)
            {
                $owner->IsUpdated = true;
            }
        }

        if ($event instanceof EventDuskEndOfDay && $this->buffedCharacterId != 0)
        {
            $this->clearFinesseBuff($event->theah);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);
        if ($owner === null)
        {
            $game->gamestate->nextState("done");
            return;
        }

        if ($reactionId !== 'pass' && str_starts_with($reactionId, 'engage-'))
        {
            $attachmentId = (int) substr($reactionId, strlen('engage-'));
            $character = $game->theah->getCharacterById($this->characterId);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found."));
            }

            $attachment = $game->theah->getAttachmentById($attachmentId);
            if ($attachment === null
                || $attachment->Engaged
                || $attachment->FakeAttachment
                || ! in_array($attachmentId, $character->Attachments, true)
                || (! $attachment->hasTrait("Weapon") && ! $attachment->hasTrait("Armor")))
            {
                throw new UserException($game->translate("Attachment must be an unengaged Weapon or Armor equipped to that character."));
            }

            // Clear any prior buff before applying a new one (should not overlap, but safe).
            if ($this->buffedCharacterId != 0)
            {
                $this->clearFinesseBuff($game->theah);
            }

            $engageEvent = EventFactory::createCardEngagedEvent($owner->ControllerId, $attachmentId, $owner->Id, $this->Id);
            $game->theah->queueEvent($engageEvent);

            $this->applyFinesseBuff($game->theah, $character, $owner->getInjectCode());

            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} engages ${attachment_inject_code}. ${character_inject_code} gains +1[Finesse] for the duration of the action.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "attachment_inject_code" => $attachment->getInjectCode(),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            // Continuous Reaction: intentionally do NOT call $this->setUsed(true).
            // Can fire any number of times per day; once per challenge/intervention is
            // enforced by a single transition per EventChallengeIssued / EventCharacterIntervened.
        }

        $this->characterId = 0;
        $this->characterName = '';
        $owner->IsUpdated = true;

        $game->gamestate->nextState("done");
    }
}
