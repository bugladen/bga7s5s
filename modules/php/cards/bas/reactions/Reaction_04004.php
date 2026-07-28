<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04004 extends CardReaction
{
    private string $destroyedName = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Draw a card when an opposing character is destroyed");
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        $name = $this->destroyedName !== ''
            ? $this->destroyedName
            : $theah->game->translate('an opposing character');

        return $base . sprintf(
            $theah->game->translate('%s was destroyed. ${you} may draw a card: '),
            $name
        );
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Draw a Card'), 'draw');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
    }

    /**
     * Duelist Reaction performer at the destroy location (opposing = same location).
     */
    private function findDuelistAtLocation(Theah $theah, int $controllerId, string $location): ?Character
    {
        foreach ($theah->getCharactersAtLocation($location) as $character)
        {
            if ($character->ControllerId == $controllerId && $character->hasTrait("Duelist"))
            {
                return $character;
            }
        }

        return null;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterDestroyed && $this->isAvailable())
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner == null)
            {
                return;
            }

            $destroyed = $event->theah->getCharacterById($event->characterId);
            if ($destroyed == null)
            {
                return;
            }

            if ($destroyed->ControllerId == $owner->ControllerId)
            {
                return;
            }

            // WHY: "opposing" = enemy at the same location as your Duelist performer
            // (not merely opponent-controlled anywhere). Destroy-time Location is still
            // readable here (EventCharacterDestroyed.runEventHubAfterCards = true).
            if ($this->findDuelistAtLocation($event->theah, $owner->ControllerId, $destroyed->Location) === null)
            {
                return;
            }

            $this->destroyedName = $destroyed->Name;
            $owner->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCard($game->theah);

        if ($reactionId == 'draw')
        {
            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} draws a card.'), [
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
            ]);

            $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
            $game->theah->queueEvent($drawEvent);

            $this->setUsed($game->theah, true);
        }

        $this->destroyedName = '';
        $owner->IsUpdated = true;

        $game->gamestate->nextState("done");
    }
}
