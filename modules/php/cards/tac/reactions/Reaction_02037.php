<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02037 extends CardReaction
{
    private ?int $pendingChallengerId = null;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Equip Mysta after opposing challenge');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may equip Mysta to your character at this location: ');
    }

    /** @return Character[] */
    private function getEligibleTargets(Theah $theah, string $location, int $controllerId): array
    {
        $characters = $theah->getCharactersAtLocationByPlayerId($location, $controllerId);
        $out = [];
        foreach ($characters as $character)
        {
            if ($character instanceof Character)
                $out[] = $character;
        }

        return $out;
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $mysta = $this->getOwningAttachment($theah);
        if (! $mysta || $this->pendingChallengerId === null)
            return $array;

        $challenger = $theah->getCharacterById($this->pendingChallengerId);
        if (! $challenger)
            return $array;

        foreach ($this->getEligibleTargets($theah, $challenger->Location, $mysta->ControllerId) as $character)
        {
            if ($mysta->canAttachTo($character))
                $array[] = $this->createButtonProperty($theah->game, $character->Name, 'equip_' . $character->Id);
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventChallengeIssued && $this->isAvailable())
        {
            $mysta = $this->getOwningAttachment($event->theah);
            if (! $mysta || $mysta->Location != Game::LOCATION_HAND)
                return;

            $challenger = $event->theah->getCharacterById($event->challengerId);
            if (! $challenger || $challenger->ControllerId === $mysta->ControllerId)
                return;

            $eligible = $this->getEligibleTargets($event->theah, $challenger->Location, $mysta->ControllerId);
            $eligible = array_values(array_filter($eligible, fn (Character $c) => $mysta->canAttachTo($c)));
            if (count($eligible) === 0)
                return;

            $this->pendingChallengerId = $event->challengerId;
            $mysta->IsUpdated = true;

            $reactionTransition = EventFactory::createReactionTransitionEvent($mysta->ControllerId, $mysta->Id, $this->Id);
            $event->theah->queueEvent($reactionTransition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $mysta = $this->getOwningAttachment($game->theah);
        if (! $mysta)
        {
            $game->gamestate->nextState("done");
            return;
        }

        if (str_starts_with($reactionId, 'equip_'))
        {
            $targetId = (int) substr($reactionId, strlen('equip_'));
            $target = $game->theah->getCharacterById($targetId);
            $challenger = $this->pendingChallengerId !== null
                ? $game->theah->getCharacterById($this->pendingChallengerId)
                : null;

            if (! $target || ! $challenger || $target->ControllerId !== $mysta->ControllerId || $target->Location !== $challenger->Location)
            {
                $this->pendingChallengerId = null;
                $mysta->IsUpdated = true;
                throw new UserException($game->translate('Invalid character for Mysta.'));
            }

            if (! $mysta->canAttachTo($target))
            {
                $this->pendingChallengerId = null;
                $mysta->IsUpdated = true;
                throw new UserException($game->translate('Mysta cannot attach to that character.'));
            }

            if ($mysta->Location != Game::LOCATION_HAND)
            {
                $this->pendingChallengerId = null;
                $mysta->IsUpdated = true;
                throw new UserException($game->translate('Mysta is not in your hand.'));
            }

            $actualTargetId = $mysta->getRequiredAttachTargetId($game->theah, $target->Id);

            $game->notify->all("message", clienttranslate('${player_name} uses Mysta\'s Reaction and equips it to ${character_inject_code}.'), [
                "player_name" => $game->getPlayerNameById($mysta->ControllerId),
                "character_inject_code" => $target->getInjectCode(),
            ]);

            $equipEvent = EventFactory::createAttachmentEquippedEvent($mysta->ControllerId, $actualTargetId, $mysta->Id, 0, 0, $asAction = false);
            $game->theah->queueEvent($equipEvent);

            $this->setUsed($game->theah, true);
        }

        $this->pendingChallengerId = null;
        if ($mysta)
            $mysta->IsUpdated = true;

        $game->gamestate->nextState("done");
    }
}
