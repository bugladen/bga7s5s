<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02001 extends CardReaction implements ISorcererAbility, IAbilityThatTargetsCharacters
{
    public int $CharacterId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Non-Sorcerer Intervening or Refusing Challenge");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to Wound Non-Sorcerer Intervening or Refusing Challenge: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Wound Non-Sorcerer'), 'woundNonSorcerer');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');
        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterIntervened && $this->isAvailable())
        {
            $andriana = $this->getOwningCharacter($event->theah);
            // WHY: Printed text is "target opposing non-Sorcerer". Opposing = different
            // controller at the same city location. Without the co-location gate, Andriana
            // at Home would still offer this when someone intervenes elsewhere.
            if ($andriana->ControllerId != $event->playerId && $event->theah->cardInCity($andriana))
            {
                $character = $event->theah->getCharacterById($event->newTargetId);
                if ($character->Location == $andriana->Location && ! $character->hasTrait("Sorcerer"))
                {
                    $this->CharacterId = $character->Id;
                    $andriana->IsUpdated = true;

                    $reactionEvent = EventFactory::createReactionTransitionEvent($andriana->ControllerId, $andriana->Id, $this->Id);
                    $event->theah->queueEvent($reactionEvent);
                }

            }
        }

        if ($event instanceof EventChallengeRejected && $this->isAvailable())
        {
            $andriana = $this->getOwningCharacter($event->theah);
            $character = $event->theah->getCharacterById($event->targetId);
            // WHY: Same "opposing" gate as intervene — must share Andriana's city location.
            if ($andriana->ControllerId != $character->ControllerId
                && $event->theah->cardInCity($andriana)
                && $character->Location == $andriana->Location
                && ! $character->hasTrait("Sorcerer"))
            {
                $this->CharacterId = $character->Id;
                $andriana->IsUpdated = true;

                $reactionEvent = EventFactory::createReactionTransitionEvent($andriana->ControllerId, $andriana->Id, $this->Id);
                $event->theah->queueEvent($reactionEvent);
            }
        }
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $andriana = $this->getOwningCharacter($game->theah);
        
        if ($character->ControllerId == $andriana->ControllerId)
        {
            return [false, $game->translate("You cannot wound a character that is controlled by you.")];
        }

        if ($character->hasTrait("Sorcerer"))
        {
            return [false, $game->translate("You cannot wound a sorcerer.")];
        }

        // WHY: Opposing requires co-location in the city; Home shares LOCATION_PLAYER_HOME
        // across players, so same Location string alone is not enough.
        if (! $game->theah->cardInCity($andriana) || $character->Location != $andriana->Location)
        {
            return [false, $game->translate("Character is not at the same location as Andriana.")];
        }

        return [true, ""];
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'woundNonSorcerer')
        {
            $andriana = $this->getOwningCharacter($game->theah);
            $character = $game->theah->getCharacterById($this->CharacterId);

            $sorceryStartEvent = EventFactory::createSorcererAbilityStartEvent($andriana->ControllerId, $andriana->Id, $this->Id, $andriana->Id, $character->Id, $character->Location);
            $game->theah->queueEvent($sorceryStartEvent);

            $event = EventFactory::createCharacterBeingWoundedEvent($character->Id, $andriana->Id, 1, $andriana->getInjectCode(), $this->Id);
            $game->theah->queueEvent($event);

            $sorceryPlayedEvent = EventFactory::createSorcererAbilityPlayedEvent($andriana->ControllerId, $andriana->Id, $this->Id, $andriana->Id, $character->Id, $character->Location);
            $game->theah->queueEvent($sorceryPlayedEvent);

            $game->notify->all("message", clienttranslate('${andriana_inject_code}: ${player_name} used Reaction to wound ${character_inject_code}'), [
                "andriana_inject_code" => $andriana->getInjectCode(),
                "player_name" => $game->getPlayerNameById($andriana->ControllerId),
                "character_inject_code" => $character->getInjectCode(),
            ]);

            $this->setUsed($game->theah, true);
            $this->CharacterId = 0;
            $andriana->IsUpdated = true;
        }

        $game->gamestate->nextState("done");
    }
}