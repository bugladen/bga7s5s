<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_03025 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Wound an Engaged Opposing Character");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may wound an engaged character opposing Angeline: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $owner = $this->getOwningCharacter($theah);
        if ($owner !== null)
        {
            foreach ($this->getEligibleTargets($theah, $owner, $owner->Location) as $character)
            {
                $array[] = $this->createButtonProperty($theah->game, sprintf($theah->game->translate('Wound %s'), $character->Name), "wound-{$character->Id}");
            }
        }

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    /**
     * @return Character[] engaged opposing characters at the given location
     */
    private function getEligibleTargets(Theah $theah, Character $owner, string $location): array
    {
        $characters = $theah->getOpposingCharactersAtLocation($location, $owner->ControllerId);
        return array_values(array_filter($characters, fn($c) => $c->Engaged));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if (! ($event instanceof EventCardMoved)) return;
        if (! $this->isAvailable()) return;

        $owner = $this->getOwningCharacter($event->theah);
        if ($owner === null) return;

        // Trigger: Angeline herself moves
        if ($event->cardId != $owner->Id) return;

        // Location gate: moves TO a city location
        if (! $event->theah->locationInCity($event->toLocation)) return;

        // Precondition: at least one engaged opposing character exists at the destination.
        // EventCardMoved has runEventHubAfterCards = true, so $owner->Location is still the OLD
        // location here — read targets at $event->toLocation, the location Angeline is moving TO.
        if (count($this->getEligibleTargets($event->theah, $owner, $event->toLocation)) == 0) return;

        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
        $event->theah->queueEvent($transition);
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        $owner = $this->getOwningCharacter($game->theah);

        if ($reactionId != 'pass' && str_starts_with($reactionId, 'wound-'))
        {
            $characterId = (int) substr($reactionId, strlen('wound-'));
            $character = $game->theah->getCharacterById($characterId);

            if ($character !== null
                && $character->ControllerId != $owner->ControllerId
                && $character->Location == $owner->Location
                && $character->Engaged)
            {
                $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                    $character->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id
                );
                $game->theah->queueEvent($woundEvent);

                $game->notify->all("message", clienttranslate('${reaction_inject_code}: ${player_name} wounds ${character_inject_code}.'), [
                    "reaction_inject_code" => $owner->getInjectCode(),
                    "player_name" => $game->getPlayerNameById($owner->ControllerId),
                    "character_inject_code" => $character->getInjectCode(),
                ]);

                // Continuous Reaction: intentionally do NOT call $this->setUsed(true).
                // The reaction remains available and can fire every time Angeline moves to a city location.
            }
        }

        $game->gamestate->nextState("done");
    }
}
