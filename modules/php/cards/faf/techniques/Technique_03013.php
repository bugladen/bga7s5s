<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03013 extends Technique
{
    private int $swapId = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Wound Daniella and Swap her with a Hunter or Zealot");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        $owner = $this->getOwningCharacter($theah);
        $characters = $theah->getCharactersAtLocation($owner->Location);
        $characters = array_filter($characters, fn($character) =>
            $character->Id != $owner->Id &&
            $character->ControllerId == $owner->ControllerId &&
            ($character->hasTrait("Hunter") || $character->hasTrait("Zealot")));

        return count($characters) > 0;
    }

    // WHY: Same shape as Technique_01063Swap — keep the technique button visible when
    // Harpooned so the player can attempt it and see why it failed. Fire on
    // TechniqueActivated (before wound cost + Hunter/Zealot picker) so Daniella is
    // not wounded and then blocked from swapping.
    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventTechniqueActivated && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            if ($owner !== null
                && $event->theah->game->globals->get(Game::IN_DUEL, false)
                && $owner->hasCondition(Game::HARPOON_CONDITION))
            {
                throw new UserException(sprintf($event->theah->game->translate("%s is Harpooned and cannot be swapped for the remainder of the duel."), $owner->Name));
            }
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);

            // Pay the technique's cost: Wound Daniella. Cost is paid before the
            // effect (target selection + swap) per the "Wound Daniella •" cost/
            // effect split in the card Text.
            $woundedEvent = EventFactory::createCharacterBeingWoundedEvent($owner->Id, $owner->Id, 1, $owner->getInjectCode(), $this->Id);
            $event->theah->queueEvent($woundedEvent);

            $transition = EventFactory::createTechniqueTransitionEvent($owner->ControllerId, $owner->Id, "03013", $this->Id);
            $event->theah->queueEvent($transition);
        }

        if ($event instanceof EventGenerateChallengeThreat && $event->techniqueId == $this->Id && $this->swapId != 0)
        {
            // WHY: Redirect the event's actor to the swapped character so that
            // Character::handleEvent (which adds the actor's stat to adversaryThreat
            // when actorId matches) and the EventHub threat notification both use
            // the new challenger instead of the original challenger. The DUEL_CHALLENGER
            // condition swap + ChallengerSwappedEvent are already done in
            // actFromTechniqueWithId; this event-time hook only adjusts the in-flight
            // event payload.
            $event->actorId = $this->swapId;
        }

        if ($event instanceof EventTechniqueCanceled && $event->techniqueId == $this->Id)
        {
            $this->swapId = 0;
            $owner = $this->getOwningCharacter($event->theah);
            $owner->IsUpdated = true;
        }
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_03013 || $state == States::DUEL_CHOOSE_TECHNIQUE_03013)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $args["performerId"] = $owner->Id;
            $characters = $game->theah->getCharactersAtLocation($owner->Location);
            $characters = array_values(array_filter($characters, fn($character) =>
                $character->Id != $owner->Id &&
                $character->ControllerId == $owner->ControllerId &&
                ($character->hasTrait("Hunter") || $character->hasTrait("Zealot"))));
            $args["characterIds"] = array_map(fn($character) => $character->Id, $characters);
        }

        return $args;
    }

    public function actFromTechniqueWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromTechniqueWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_03013 || $state == States::DUEL_CHOOSE_TECHNIQUE_03013)
        {
            $owner = $this->getOwningCharacter($game->theah);
            $target = $game->theah->getCharacterById($id);
            if ($target == null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            if ($target->ControllerId != $owner->ControllerId)
            {
                throw new UserException($game->translate("Character is not controlled by you."));
            }

            if (! $target->hasTrait("Hunter") && ! $target->hasTrait("Zealot"))
            {
                throw new UserException($game->translate("Character is not a Hunter or Zealot."));
            }

            if ($target->Location != $owner->Location)
            {
                throw new UserException(sprintf($game->translate("Character is not at the same location as %s."), $owner->Name));
            }

            $game->notify->all("message", $game->translate('${player_name} has used Technique [${technique_name}] to wound ${challenger_inject_code} and swap her with ${target_inject_code}.'), [
                "i18n" => ["technique_name"],
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "technique_name" => $this->Name,
                "target_inject_code" => $target->getInjectCode(),
                "challenger_inject_code" => $owner->getInjectCode(),
            ]);

            $this->swapId = $target->Id;

            if ($state == States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_03013)
            {
                // Challenge context: the duel has not started yet. Redirect the
                // chosen performer and move the DUEL_CHALLENGER condition from
                // Daniella to the target so the new challenger is the one who
                // actually enters the duel.
                $game->globals->set(Game::CHOSEN_PERFORMER, $target->Id);

                $owner->removeCondition(Game::DUEL_CHALLENGER);
                $owner->IsUpdated = true;

                $target->addCondition(Game::DUEL_CHALLENGER);
                $target->IsUpdated = true;

                $game->updateCardObjectInDb($owner);
                $game->updateCardObjectInDb($target);

                $challengerSwappedEvent = EventFactory::createChallengerSwappedEvent($owner->ControllerId, $owner->Id, $target->Id);
                $game->theah->queueEvent($challengerSwappedEvent);
            }
            else // DUEL_CHOOSE_TECHNIQUE_03013
            {
                // Duel context: the duel is already in progress with Daniella as
                // a participant. Rewrite the duel's participant list so the
                // target takes her seat for the remainder of the duel.
                $duelId = $game->globals->get(Game::DUEL_ID);
                $round = $game->globals->get(Game::DUEL_ROUND);
                $game->theah->swapParticipantsInDuel($duelId, $round, $owner->Id, $target->Id);

                $game->updateCardObjectInDb($owner);
                $game->updateCardObjectInDb($target);
            }

            $game->gamestate->nextState();
        }
    }
}
