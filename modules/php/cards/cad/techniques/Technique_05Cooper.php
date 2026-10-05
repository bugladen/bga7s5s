<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_05Cooper extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = "Wound your Red Hand • +2 Riposte";
    }

    /**
     * Controlled Red Hands at Owner's location (includes Owner if she has the trait).
     *
     * @return \Bga\Games\SeventhSeaCityOfFiveSails\cards\Character[]
     */
    private function getEligibleRedHands(Theah $theah): array
    {
        $owner = $this->getOwningCharacter($theah);
        if ($owner === null)
        {
            return [];
        }

        $characters = $theah->getCharactersAtLocationByPlayerId($owner->Location, $owner->ControllerId);
        $characters = array_filter(
            $characters,
            fn($character) => $character->hasTrait("Red Hand")
        );

        return array_values($characters);
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

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($actor === null || $owner === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        return count($this->getEligibleRedHands($theah)) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // EventTechniqueCanceled handler not needed

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            // WHY: createTechniqueTransitionEvent (HIGHEST_PRIORITY) so the Red Hand
            // picker finishes before EventDuelCalculateTechniqueValues reads +2 Riposte.
            $transition = EventFactory::createTechniqueTransitionEvent(
                $owner->ControllerId,
                $owner->Id,
                "05Cooper",
                $this->Id
            );
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventDuelCalculateTechniqueValues && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $event->riposte += 2;
            $event->explanations[] = sprintf(
                "%s: Technique [%s] adds 2 Riposte.",
                $owner->getInjectCode(),
                $this->Name
            );
            $this->setUsed($event->theah, true);
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_05COOPER)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $owner->Id;
            $args["ids"] = array_map(
                fn($character) => $character->Id,
                $this->getEligibleRedHands($game->theah)
            );
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_05COOPER)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException("Character not found");
            }

            if ($character->ControllerId != $owner->ControllerId)
            {
                throw new UserException("Character is not controlled by you.");
            }

            if ($character->Location != $owner->Location)
            {
                throw new UserException(sprintf(
                    "Character is not at the same location as %s.",
                    $owner->Name
                ));
            }

            if (! $character->hasTrait("Red Hand"))
            {
                throw new UserException("Character must be a Red Hand.");
            }

            // WHY: Wound is the cost; apply after pick (target chosen). Shape = Technique_02006
            // (wound-other picker), not Daniella fixed-self wound-before-transition.
            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $character->Id,
                $owner->Id,
                1,
                $owner->getInjectCode(),
                $this->Id
            );
            $game->theah->queueEvent($woundEvent);

            $game->gamestate->nextState();
        }
    }
}
