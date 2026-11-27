<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\maneuvers\Maneuver;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Maneuver_03022 extends Maneuver implements IAbilityThatTargetsCharacters
{
    private int $FinalStrikeParticipantId;
    private string $DuelLocation;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Final Strike: En Garde Target and Draw");

        $this->FinalStrikeParticipantId = 0;
        $this->DuelLocation = "";
    }

    private function getResolutionLocation(Theah $theah): string
    {
        if ($this->DuelLocation !== "")
        {
            return $this->DuelLocation;
        }

        $actor = $theah->getDuelRoundActor();
        return $actor !== null ? $actor->Location : "";
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveManeuver && $event->maneuverId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $this->FinalStrikeParticipantId = $event->theah->getDuelOpponentId($event->adversaryId);
            $participant = $event->theah->getCharacterById($this->FinalStrikeParticipantId);
            $this->DuelLocation = $participant->Location;
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->FinalStrikeParticipantId)
        {
            $game = $event->theah->game;
            $inDuel = $game->globals->get(Game::IN_DUEL);
            if ($inDuel)
            {
                $character = $event->theah->getCharacterById($this->FinalStrikeParticipantId);
                $owner = $this->getOwningCard($game->theah);
                $playerId = $character->ControllerId;

                // WHY: pass $owner as queryCard so "counts as a ..." characters (Patricia) trigger Final Strike.
                $isZealot = $character->hasTrait('Zealot', $owner);
                if ($isZealot || $character->hasTrait('Hunter', $owner))
                {
                    $cardDrawEvent = EventFactory::createCardDrawnEvent($playerId, clienttranslate("Overzealous"));
                    $event->theah->queueEvent($cardDrawEvent);

                    $game->notify->all("message", clienttranslate('${maneuver_inject_code}: ${character_inject_code} was a ${trait} and triggered Final Strike. ${performer} draws a card.'), [
                        "maneuver_inject_code" => $owner->getInjectCode(),
                        "character_inject_code" => $character->getInjectCode(),
                        "trait" => ($isZealot ? clienttranslate('Zealot') : clienttranslate('Hunter')),
                        "performer" => $game->getPlayerNameById($playerId),
                    ]);
                }
                else
                {
                    $game->notify->all("message", clienttranslate('${maneuver_inject_code}: ${character_inject_code} was not a Zealot or Hunter.'), [
                        "maneuver_inject_code" => $owner->getInjectCode(),
                        "character_inject_code" => $character->getInjectCode(),
                    ]);
                }

                $transitionEvent = EventFactory::createTransitionEvent($playerId, $owner->Id, "03022", $this->Id);
                $event->theah->queueEvent($transitionEvent);
            }
        }

        if ($event instanceof EventCharacterTargeted && $event->abilityId == $this->Id && ! $event->canceled)
        {
            $game = $event->theah->game;
            $owner = $this->getOwningCard($event->theah);
            $character = $event->theah->getCharacterById($event->targetId);
            if ($owner !== null && $character !== null)
            {
                // WHY skip if already En Garded (re-read at survive): hub still notifies; re-engarde spam is noise.
                if ($character->Engaged)
                {
                    $engardeEvent = EventFactory::createCardEngardedEvent($character->ControllerId, $character->Id, $owner->Id, $this->Id);
                    $engardeEvent->batchId = $event->batchId ?? $game->getNextEventBatchId();
                    $event->theah->queueEvent($engardeEvent);

                    $game->notify->all("message", clienttranslate('${maneuver_inject_code}: ${player_name} En Gardes ${character_inject_code}.'), [
                        "maneuver_inject_code" => $owner->getInjectCode(),
                        "player_name" => $game->getPlayerNameById($owner->ControllerId),
                        "character_inject_code" => $character->getInjectCode(),
                    ]);
                }
                else
                {
                    $game->notify->all("message", clienttranslate('${maneuver_inject_code}: ${character_inject_code} is already En Garded.'), [
                        "maneuver_inject_code" => $owner->getInjectCode(),
                        "character_inject_code" => $character->getInjectCode(),
                    ]);
                }
            }
        }

        if ($event instanceof EventManeuverCanceled && $event->maneuverId == $this->Id)
        {
            $this->FinalStrikeParticipantId = 0;
            $this->DuelLocation = "";
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
        }

        if ($event instanceof EventDuelNewRound && $this->FinalStrikeParticipantId != 0)
        {
            $owner = $this->getOwningCard($event->theah);
            $owner->IsUpdated = true;
            $this->FinalStrikeParticipantId = 0;
            $this->DuelLocation = "";
        }
    }

    public function getArgsFromManeuver(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromManeuver($game, $state, $stateName);

        if ($state == States::DUEL_END_OF_ROUND_03022)
        {
            $location = $this->getResolutionLocation($game->theah);
            $targets = $this->getValidTargets($game->theah, $location);
            $args['characterIds'] = array_map(fn($character) => $character->Id, $targets);
        }

        return $args;
    }

    public function isValidTargetForAbility(Game $game, Character $character): array
    {
        $location = $this->getResolutionLocation($game->theah);

        if ($character->Location != $location)
        {
            return [false, $game->translate("Character is not at this location")];
        }

        if ($game->characterIsInDiscardOrLocker($character))
        {
            return [false, $game->translate("Character is not in play")];
        }

        // WHY no Engaged check: En garde is the effect after •, not a target gate.
        // Already En Garded is a no-op at resolve (see actFromManeuverWithId).
        return [true, ""];
    }

    public function actFromManeuverPass(Game $game, int $state): void
    {
        parent::actFromManeuverPass($game, $state);

        if ($state == States::DUEL_END_OF_ROUND_03022)
        {
            $location = $this->getResolutionLocation($game->theah);
            $targets = $this->getValidTargets($game->theah, $location);
            if (count($targets) > 0)
            {
                throw new UserException($game->translate("There are characters at this location — you must choose one to En Garde."));
            }

            $owner = $this->getOwningCard($game->theah);
            $game->notify->all("message", clienttranslate('${maneuver_inject_code}: No characters at this location to En Garde.'), [
                "maneuver_inject_code" => $owner->getInjectCode(),
            ]);

            $game->gamestate->nextState();
        }
    }

    public function actFromManeuverWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromManeuverWithId($game, $state, $stateName, $id);

        if ($state == States::DUEL_END_OF_ROUND_03022)
        {
            $character = $game->theah->getCharacterById($id);
            if ($character === null)
            {
                throw new UserException($game->translate("Character not found"));
            }

            [$isValid, $errorMessage] = $this->isValidTargetForAbility($game, $character);
            if (! $isValid)
            {
                throw new UserException($errorMessage);
            }

            $owner = $this->getOwningCard($game->theah);

            // WHY: Fire targeting alone here (also for an already En Garded target — it is still
            // a legal target). The En Garde emits from handleEvent only when EventCharacterTargeted
            // survives (Cloud Bottle / UL cancel it). The state's "" transition already routes to
            // the DUEL_END_OF_ROUND_EVENTS hub, so the hook is delivered there.
            $targetedEvent = EventFactory::createCharacterTargetedEvent($owner->ControllerId, $character->Id, $owner->Id, $this->Id);
            $targetedEvent->batchId = $game->getNextEventBatchId();
            $game->theah->eventCheck($targetedEvent);
            $game->theah->queueEvent($targetedEvent);

            $game->gamestate->nextState();
        }
    }

    private function getValidTargets(Theah $theah, string $location): array
    {
        // WHY no Engaged filter: Engaged is not a cost left of •. Any in-play character
        // at the duel location is a legal En garde target (no-op if already En Garded).
        $characters = $theah->getCharactersAtLocation($location);
        return array_values(array_filter($characters, function($character) use ($theah) {
            return ! $theah->game->characterIsInDiscardOrLocker($character);
        }));
    }
}
