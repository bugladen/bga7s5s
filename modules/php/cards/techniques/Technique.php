<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\CardAbilityTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ICardAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Technique implements ICardAbility
{
    use CardAbilityTrait;

    public bool $ResetOnDuelEnd;
    public bool $ResetOnDayEnd;
    public bool $IsTemporaryCopy = false;

    public function __construct()
    {
        $this->initializeAbility();
        $this->ResetOnDuelEnd = true;
        $this->ResetOnDayEnd = false;
    }

    public function eventCheck(Event $event) {}

    public function handleEvent(Event $event)
    {
        if ($event instanceof EventTechniqueActivated && $event->techniqueId == $this->Id)
        {
            $this->setUsed($event->theah, true);
        }

        if ($event instanceof EventDuskEndOfDay && $this->ResetOnDayEnd)
        {
            $this->setUsed($event->theah, false);
        }

        if ($event instanceof EventDuelNewRound && $this->IsTemporaryCopy)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner && $owner->ControllerId == $event->playerId && $owner instanceof IHasTechniques)
            {
                $owner->removeTechnique($this, $event->theah->game, $notify = false);
                $owner->IsUpdated = true;
            }
        }

        // WHY: Once-per-duel Techniques activate at challenge time (Sabre +1 Thrust,
        // etc.). Refuse / no-duel ends never fire EventDuelEnd, so Used would stick
        // for the rest of the Day (ResetOnDayEnd defaults false). Treat reject like
        // duel end for ResetOnDuelEnd techniques.
        if (($event instanceof EventDuelEnd || $event instanceof EventChallengeRejected)
            && $this->ResetOnDuelEnd)
        {
            $this->setUsed($event->theah, false);
        }

        if ($event instanceof EventDuelEnd && $this->IsTemporaryCopy)
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner && $owner instanceof IHasTechniques)
            {
                $owner->removeTechnique($this, $event->theah->game, $notify = false);
                $owner->IsUpdated = true;
            }
        }
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        // WHY: Fate's Silence — Techniques on a blanked Character cannot be used.
        $owner = $this->getOwningCard($theah);
        if ($owner instanceof Character && $owner->abilitiesAreBlanked())
        {
            return false;
        }

        return true;
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array 
    {
        return [];
    }

    public function actFromTechniquePass(Game $game, int $state): void { }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void  { }
    
    public function actFromTechniqueWithIds(Game $game, int $state, string $stateName, array $ids): void  { }

    public function stateFromTechnique(Game $game, int $state, string $stateName): void { }

    public function getNumberOfGambleCardsToReveal(Theah $theah, Character $actor, Array &$explanations): int { return 0; }

    public function doCost(Game $game): void {}

    public function doEffect(Game $game): void {}

}