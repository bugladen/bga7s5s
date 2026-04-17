<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_02054 extends Technique implements IRangedAbility
{
    public bool $AdversarySufferedWound = false;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Adversary may suffer a wound. If not, +1 Parry");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $inDuel = $theah->game->globals->get(Game::IN_DUEL, false);
        if (! $inDuel)
        {
            return false;
        }

        return true;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $this->AdversarySufferedWound = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;

            $adversary = $event->theah->getCharacterById($event->adversaryId);
            $transition = EventFactory::createTechniqueTransitionEvent($adversary->ControllerId, $owner->Id, "02054", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            if (! $this->AdversarySufferedWound)
            {
                $owner = $this->getOwningCard($event->theah);
                $event->parry += 1;
                $event->explanations[] = sprintf($event->theah->game->translate("%s: Technique [%s] adds +1 Parry because the adversary did not suffer a wound."), $owner->getInjectCode(), $this->Name);
            }
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->AdversarySufferedWound = false;
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelNewRound || $event instanceof EventDuelEnd)
        {
            $this->AdversarySufferedWound = false;
            $owner = $this->getOwningCard($event->theah);
            if ($owner)
            {
                $owner->IsUpdated = true;
            }
        }
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_02054)
        {
            $owner = $this->getOwningCard($game->theah);
            $adversaryId = $game->theah->getDuelOpponentId($owner->ControllerId);

            if ($id == 1)
            {
                $this->AdversarySufferedWound = true;
                $owner->IsUpdated = true;

                $woundEvent = EventFactory::createCharacterBeingWoundedEvent($adversaryId, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
                $game->theah->queueEvent($woundEvent);

                $adversary = $game->theah->getCharacterById($adversaryId);
                $game->notify->all("message", clienttranslate('${player_name} has chosen to suffer a wound.'), [
                    "player_name" => $game->getPlayerNameById($adversary->ControllerId),
                ]);
            }

            if ($id == 2)
            {
                $adversary = $game->theah->getCharacterById($adversaryId);
                $game->notify->all("message", clienttranslate('${player_name} has declined to suffer a wound. +1 Parry.'), [
                    "player_name" => $game->getPlayerNameById($adversary->ControllerId),
                ]);
            }

            $performer = $this->getOwningCharacter($game->theah);
            $rangedAbilityPlayedEvent = EventFactory::createRangedAbilityPlayedEvent($owner->ControllerId, $owner->Id, $this->Id, $performer->Id, $adversaryId, $adversary->Location);
            $game->theah->queueEvent($rangedAbilityPlayedEvent);
        }

        $game->gamestate->nextState();
    }
}
