<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_04016 extends AttachmentReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Hunter or Berserker heals a wound after equipping");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah)
            . $theah->game->translate('${you} may heal a wound on the equipped Hunter or Berserker: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Heal a wound'), 'healWound');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventAttachmentEquipped))
        {
            return;
        }

        if (! $this->isAvailable())
        {
            return;
        }

        if (! $this->ownerIsAttached($event->theah))
        {
            return;
        }

        $owner = $this->getOwningAttachment($event->theah);
        if ($owner === null || $event->attachmentId != $owner->Id)
        {
            return;
        }

        $character = $event->theah->getCharacterById($event->characterId);
        if (! ($character instanceof Character))
        {
            return;
        }

        // WHY: Not an equip restriction — printed text only gates the Reaction on Hunter/Berserker.
        if (! $character->hasTrait('Hunter') && ! $character->hasTrait('Berserker'))
        {
            return;
        }

        if ($character->Wounds <= 0)
        {
            return;
        }

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId === 'healWound')
        {
            $owner = $this->getOwningAttachment($game->theah);
            $character = $this->getOwningCharacter($game->theah);

            if ($owner !== null
                && $character instanceof Character
                && $character->Wounds > 0
                && ($character->hasTrait('Hunter') || $character->hasTrait('Berserker')))
            {
                $healEvent = EventFactory::createCharacterBeingHealedEvent(
                    $character->Id,
                    $owner->Id,
                    1,
                    $owner->getInjectCode(),
                    $this->Id
                );
                $game->theah->queueEvent($healEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${character_inject_code} heals a wound.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                $this->setUsed($game->theah, true);
            }
        }

        $game->gamestate->nextState("done");
    }
}
