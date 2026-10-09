<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\CityLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use ReflectionProperty;

/**
 * Theah subclass that keeps world state in memory (no DB rebuild).
 * WHY: Real queueEvent → buildCity() wipes $cards from DB; unit tests seed cards in RAM.
 */
class TestTheah extends Theah
{
    /** @var list<Event> */
    public array $queuedEvents = [];

    public ?Character $duelOpponent = null;
    public ?Character $duelActor = null;
    /** Stub for Technique_01050 thrust gate (real path hits duel_round DB). */
    public int $currentRoundThrust = 0;
    /** @var array<int, \Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader> playerId => Leader */
    public array $leadersByPlayerId = [];

    public function __construct(Game $game)
    {
        parent::__construct($game);

        // Prevent first queueEvent from calling buildCity() and clearing seeded cards.
        $cityBuilt = new ReflectionProperty(Theah::class, 'cityBuilt');
        $cityBuilt->setAccessible(true);
        $cityBuilt->setValue($this, true);

        $this->seedDefaultCityLocations();
    }

    public function queueEvent(Event $event): void
    {
        $event->theah = $this;
        $this->queuedEvents[] = $event;
    }

    public function stackEvent(Event $event): void
    {
        $event->theah = $this;
        array_unshift($this->queuedEvents, $event);
    }

    public function eventCheck(Event $event): void
    {
        // Skip DB-backed checks in unit tests.
    }

    public function buildCity(): void
    {
        // no-op — tests manage cards/locations explicitly
    }

    public function getDuelRoundOpponent(): ?Character
    {
        return $this->duelOpponent;
    }

    public function getDuelRoundActor(): ?Character
    {
        return $this->duelActor;
    }

    // WHY: Real getDuelOpponentId / challenger / defender hit duel table in DB.
    public function getDuelOpponentId($actorId): int
    {
        if ($this->duelOpponent !== null) {
            return $this->duelOpponent->Id;
        }
        return 0;
    }

    public function getDuelChallengerId(): ?int
    {
        return $this->duelActor?->Id;
    }

    public function getDuelDefenderId(): int
    {
        return $this->duelOpponent?->Id ?? 0;
    }

    // WHY: Real getLeaderByPlayerId hits player.leader_card_id in DB (Action_01024 muster).
    public function getLeaderByPlayerId($playerId): ?\Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader
    {
        $leader = $this->leadersByPlayerId[(int)$playerId] ?? null;
        if ($leader instanceof \Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader) {
            return $leader;
        }
        return null;
    }

    // WHY: Real delete* hits DB; tests keep events in $queuedEvents only.
    public function deleteEventBatch(int $batchId): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            fn($event) => $event->batchId !== $batchId
        ));
    }

    public function deleteTransitionEvents(string $reactionId): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            function ($event) use ($reactionId) {
                if (!$event instanceof \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition) {
                    return true;
                }
                return $event->internalId !== $reactionId;
            }
        ));
    }

    // WHY: Reaction_01027 failPressure deletes pressure-result events before re-queueing failed result.
    public function deletePressureResultEvents(): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            fn($event) => !$event instanceof \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult
        ));
    }

    // WHY: Reaction_01047 cancel clears pending technique resolve/calc events from the queue.
    public function deleteTechniqueEvents(string $techniqueId): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            function ($event) use ($techniqueId) {
                if (property_exists($event, 'techniqueId') && (string)$event->techniqueId === $techniqueId) {
                    return false;
                }
                return true;
            }
        ));
    }

    // WHY: Reaction_01122 cancel clears DB event rows targeting Torsten / from the sorcery
    // source. Unit tests keep events only in $queuedEvents — mirror deleteEventBatch.
    public function deleteEventsTargetingCard(int $cardId): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            function ($event) use ($cardId) {
                foreach (get_object_vars($event) as $value) {
                    if ($value === $cardId) {
                        return false;
                    }
                }
                return true;
            }
        ));
    }

    // WHY: Reaction_01122 cancel also drops transition events keyed by the sorcery sourceId.
    public function deleteTransitionEventsBySourceId(int $sourceId): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            function ($event) use ($sourceId) {
                if (!$event instanceof \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition) {
                    return true;
                }
                return (int)$event->sourceId !== $sourceId;
            }
        ));
    }

    /** @var list<array{duelId:int,round:int,oldId:int,newId:int}> */
    public array $swappedParticipants = [];

    // WHY: Technique_01063Swap rewrites the duel table row; real path SELECTs/UPDATEs duel in DB.
    public function swapParticipantsInDuel(int $duelId, int $round, int $oldParticipantId, int $newParticipantId)
    {
        $this->swappedParticipants[] = [
            'duelId' => $duelId,
            'round' => $round,
            'oldId' => $oldParticipantId,
            'newId' => $newParticipantId,
        ];
    }

    /** @var array<int,int> characterId => current duel threat (Action/_01085 discards all threat). */
    public array $duelThreats = [];

    // WHY: Real getCurrentDuelThreat SELECTs duel_round; _01085 Porté Travel reads both
    // participants' threat to zero it out. Unlisted characters read as 0.
    public function getCurrentDuelThreat($characterId): int
    {
        return $this->duelThreats[(int)$characterId] ?? 0;
    }

    // WHY: Technique_01050 needs thrust >= 1; real path SELECTs duel_round.
    public function getCurrentRoundThrust(): int
    {
        return $this->currentRoundThrust;
    }

    /** Stub for Technique_01093 riposte gate (real path hits duel_round DB). */
    public int $currentRoundRiposte = 0;

    // WHY: Technique_01093 needs combat card Riposte >= 1; real path SELECTs duel_round.
    public function getCurrentRoundRiposte(): int
    {
        return $this->currentRoundRiposte;
    }

    /** @var array<int,int> characterId => wounds taken in prior rounds of this duel (Maneuver_01107 gate). */
    public array $duelWoundsTaken = [];

    // WHY: Real duelParticipantWoundsTaken SELECTs duel_round SUM; Maneuver_01107 needs prior wounds.
    public function duelParticipantWoundsTaken(int $participantId): int
    {
        return $this->duelWoundsTaken[(int)$participantId] ?? 0;
    }

    // WHY: Reaction_01109 (Night of Drinking) de-dupes offers via DB LIKE on EventTransition.
    // Mirror that against in-memory $queuedEvents so ActionActivated / RiskPlayed paths do not double-offer.
    public function areTransitionEventsOfTypeForPlayerQueued(int $playerId, string $reactionType): bool
    {
        foreach ($this->queuedEvents as $event) {
            if (!$event instanceof \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition) {
                continue;
            }
            if ((int)$event->playerId !== (int)$playerId) {
                continue;
            }
            if (str_contains((string)$event->internalId, $reactionType)) {
                return true;
            }
        }
        return false;
    }

    // WHY: Reaction_01109 RiskPlayed path only offers when a RiskReactionTriggered for that source
    // is already queued (Reaction announce) — skips Action plays already handled on ActionActivated.
    public function areRiskReactionTriggeredEventsQueuedForSource(int $sourceId): bool
    {
        if ($sourceId <= 0) {
            return false;
        }
        foreach ($this->queuedEvents as $event) {
            if ($event instanceof \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered
                && (int)$event->sourceId === (int)$sourceId) {
                return true;
            }
        }
        return false;
    }

    // WHY: Reaction_01109 cancel deletes pending ActionTriggered / RiskPlayed / RiskReactionTriggered /
    // Maneuver events from the DB queue. Tests only have $queuedEvents — filter in memory.
    public function deleteActionTriggeredEvents(string $actionId): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            function ($event) use ($actionId) {
                if (!$event instanceof \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered) {
                    return true;
                }
                return !str_contains((string)$event->actionId, $actionId)
                    && (string)$event->sourceId !== (string)$actionId;
            }
        ));
    }

    public function deleteRiskReactionTriggeredEvents(string $reactionId): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            function ($event) use ($reactionId) {
                if (!$event instanceof \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered) {
                    return true;
                }
                return !str_contains((string)$event->internalId, $reactionId)
                    && (string)$event->sourceId !== (string)$reactionId;
            }
        ));
    }

    public function deleteRiskPlayedEvents(int $riskId): void
    {
        if ($riskId <= 0) {
            return;
        }
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            fn($event) => !(
                $event instanceof \Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskPlayed
                && (int)$event->riskId === (int)$riskId
            )
        ));
    }

    public function deleteManeuverEvents(string $maneuverId): void
    {
        $this->queuedEvents = array_values(array_filter(
            $this->queuedEvents,
            function ($event) use ($maneuverId) {
                if (property_exists($event, 'maneuverId') && (string)$event->maneuverId === $maneuverId) {
                    return false;
                }
                return true;
            }
        ));
    }

    // WHY: Real getCardObjectsAtLocation hits DB; hand Thugs for Reaction_01014 live in RAM.
    public function getCardObjectsAtLocation($location, $playerId = null): array
    {
        $cards = [];
        $prop = new ReflectionProperty(Theah::class, 'cards');
        $prop->setAccessible(true);
        /** @var array<int, Card> $map */
        $map = $prop->getValue($this);
        foreach ($map as $card) {
            if ($card->Location !== $location) {
                continue;
            }
            if ($playerId !== null && (int)$card->ControllerId !== (int)$playerId) {
                continue;
            }
            $cards[$card->Id] = $card;
        }
        return $cards;
    }

    public function seedDefaultCityLocations(): void
    {
        $locations = [
            Game::LOCATION_CITY_DOCKS,
            Game::LOCATION_CITY_FORUM,
            Game::LOCATION_CITY_BAZAAR,
            Game::LOCATION_CITY_OLES_INN,
            Game::LOCATION_CITY_GOVERNORS_GARDEN,
        ];

        $cityLocations = [];
        foreach ($locations as $name) {
            $cityLocations[$name] = new CityLocation($name);
        }

        $prop = new ReflectionProperty(Theah::class, 'cityLocations');
        $prop->setAccessible(true);
        $prop->setValue($this, $cityLocations);
    }

    public function setLocationController(string $name, int $controllerId): void
    {
        $this->getCityLocation($name)->Controller = $controllerId;
    }

    public function setLocationRenown(string $name, int $renown): void
    {
        $this->getCityLocation($name)->Renown = $renown;
    }

    /** @return list<Event> */
    public function takeQueuedEvents(): array
    {
        $events = $this->queuedEvents;
        $this->queuedEvents = [];
        return $events;
    }

    /** @return list<Event> */
    public function queuedOfType(string $class): array
    {
        return array_values(array_filter(
            $this->queuedEvents,
            fn($event) => $event instanceof $class
        ));
    }
}
