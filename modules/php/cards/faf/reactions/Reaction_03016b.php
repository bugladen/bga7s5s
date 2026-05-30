<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03016b extends CardReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Move another character you control to Ise's location");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose another character you control to move to this location: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null)
        {
            foreach ($this->getEligibleMovers($theah, $owner) as $character)
            {
                $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Move %s from %s'), $character->Name, $character->Location), "move-{$character->Id}");
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    /**
     * @return Character[] characters the controller controls that are not Ise and not already at Ise's location.
     */
    private function getEligibleMovers(Theah $theah, Character $owner): array
    {
        $characters = $theah->getCharactersInPlayByPlayerId($owner->ControllerId);
        return array_values(array_filter($characters, fn($c) => $c->Id != $owner->Id && $c->Location != $owner->Location));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoved)) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;
        if (! $event->theah->cardInCity($owner)) return;
        if ($event->cardId == $owner->Id) return;
        if ($event->toLocation != $owner->Location) return;

        $character = $event->theah->getCardById($event->cardId);
        if (! ($character instanceof Character)) return;
        if ($character->ControllerId == 0) return;
        if ($character->ControllerId == $owner->ControllerId) return;

        if (count($this->getEligibleMovers($event->theah, $owner)) == 0) return;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId != 'pass' && str_starts_with($reactionId, 'move-'))
        {
            $characterId = (int) substr($reactionId, strlen('move-'));
            $character = $game->theah->getCharacterById($characterId);

            if ($character !== null
                && $character->ControllerId == $owner->ControllerId
                && $character->Id != $owner->Id
                && $character->Location != $owner->Location)
            {
                $moveEvent = EventFactory::createCardMovingEvent(
                    $character->ControllerId,
                    $character->Id,
                    $character->Location,
                    $owner->Location,
                    false,
                    $owner->Id,
                    $this->Id
                );
                $game->theah->queueEvent($moveEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} moves ${character_inject_code} to ${location_name}.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                    "location_name" => $owner->Location,
                ]);

                $this->setUsed($game->theah, true);
            }
        }

        $game->gamestate->nextState("done");
    }
}
