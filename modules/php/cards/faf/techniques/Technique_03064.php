<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03064 extends Technique
{
    public int $AffectedCharacterId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Engage Harpoon: Adversary -1 Finesse, Cannot Swap or Move");
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

        // Gambling Technique: actor must have gambled for their combat card this round.
        if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
        {
            return false;
        }

        $attachment = $this->getOwningCard($theah);
        if ($attachment === null || $attachment->Engaged)
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        return $theah->getDuelRoundOpponent() !== null;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $attachment = $this->getOwningCard($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();
            if ($attachment === null || $adversary === null)
            {
                return;
            }

            $engageEvent = EventFactory::createCardEngagedEvent(
                $event->playerId,
                $attachment->Id,
                $attachment->Id,
                $this->Id
            );
            $event->theah->queueEvent($engageEvent);

            $this->applyHarpoon($event->theah, $adversary, $attachment);
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->clearHarpoon($event->theah);
        }

        if ($event instanceof EventDuelEnd && $this->AffectedCharacterId > 0)
        {
            $this->clearHarpoon($event->theah);
        }
    }

    private function applyHarpoon(Theah $theah, Character $adversary, Card $attachment): void
    {
        $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
            $attachment->ControllerId,
            $adversary->Id,
            $adversary->ModifiedFinesse,
            $adversary->ModifiedFinesse - 1,
            $attachment->getInjectCode()
        );
        $theah->queueEvent($finesseEvent);

        $adversary->addCondition(Game::HARPOON_CONDITION);
        $theah->game->updateCardObjectInDb($adversary);

        $theah->game->notify->all("harpoonConditionStarted", clienttranslate('${character_inject_code} is Harpooned: -1[Finesse], cannot be swapped, and cannot move for the remainder of the duel.'), [
            "character_inject_code" => $adversary->getInjectCode(),
            "cardId" => $adversary->Id,
        ]);

        $this->AffectedCharacterId = $adversary->Id;
        $attachment->IsUpdated = true;
    }

    private function clearHarpoon(Theah $theah): void
    {
        if ($this->AffectedCharacterId <= 0)
        {
            return;
        }

        $character = $theah->getCharacterById($this->AffectedCharacterId);
        $attachment = $this->getOwningCard($theah);

        if ($character !== null
            && $character->hasCondition(Game::HARPOON_CONDITION)
            && ! $theah->game->characterIsInDiscardOrLocker($character))
        {
            $reason = $attachment !== null ? $attachment->getInjectCode() : clienttranslate('Harpoon');
            $finesseEvent = EventFactory::createCharacterFinesseModifedEvent(
                $character->ControllerId,
                $character->Id,
                $character->ModifiedFinesse,
                $character->ModifiedFinesse + 1,
                $reason
            );
            $theah->queueEvent($finesseEvent);

            $character->removeCondition(Game::HARPOON_CONDITION);
            $theah->game->updateCardObjectInDb($character);

            $theah->game->notify->all("harpoonConditionEnded", clienttranslate('${character_inject_code} is no longer Harpooned.'), [
                "character_inject_code" => $character->getInjectCode(),
                "cardId" => $character->Id,
            ]);
        }

        $this->AffectedCharacterId = 0;
        if ($attachment !== null)
        {
            $attachment->IsUpdated = true;
        }
    }
}
