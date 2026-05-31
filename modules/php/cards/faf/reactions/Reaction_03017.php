<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03017 extends CardReaction
{
    private string $location = '';
    private bool $destroyedWasZealot = false;
    private string $destroyedName = '';

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Wound opposing and heal your characters when your character is destroyed at a City location');
    }

    public function getReactionDescription(Theah $theah): string
    {
        $base = parent::getReactionDescription($theah);
        if ($this->location === '')
        {
            return $base;
        }

        $name = $this->destroyedName !== '' ? $this->destroyedName : $theah->game->translate('your character');
        return $base . sprintf(
            $theah->game->translate('%s was destroyed at %s. ${you} may wound each opposing character there and heal each of your characters there%s: '),
            $name,
            $this->location,
            $this->destroyedWasZealot ? $theah->game->translate(' (and draw a card)') : ''
        );
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Resolve'), 'resolve');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');
        return $array;
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

            if ($destroyed->ControllerId != $owner->ControllerId)
            {
                return;
            }

            if (! $event->theah->locationInCity($destroyed->Location))
            {
                return;
            }

            $this->location = $destroyed->Location;
            $this->destroyedWasZealot = $destroyed->hasTrait("Zealot");
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

        if ($reactionId == 'resolve' && $this->location != '')
        {
            $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} resolves the Reaction at ${location_name}.'), [
                "i18n" => ["location_name"],
                "reaction_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "location_name" => $this->location,
            ]);

            $charactersAtLocation = $game->theah->getCharactersAtLocation($this->location);
            foreach ($charactersAtLocation as $character)
            {
                if ($character->ControllerId != $owner->ControllerId)
                {
                    $woundEvent = EventFactory::createCharacterBeingWoundedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                    $game->theah->queueEvent($woundEvent);
                }
                else
                {
                    $healEvent = EventFactory::createCharacterBeingHealedEvent($character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                    $game->theah->queueEvent($healEvent);
                }
            }

            if ($this->destroyedWasZealot)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($owner->ControllerId, $owner->getInjectCode());
                $game->theah->queueEvent($drawEvent);
            }

            $this->setUsed($game->theah, true);
        }

        $this->location = '';
        $this->destroyedWasZealot = false;
        $this->destroyedName = '';
        $owner->IsUpdated = true;

        $game->gamestate->nextState("done");
    }
}
