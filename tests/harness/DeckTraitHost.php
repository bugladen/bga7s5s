<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness;

use Bga\Games\SeventhSeaCityOfFiveSails\DeckTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

/**
 * Runs the real DeckTrait without loading it onto FakeGame for card suites.
 *
 * WHY extend Game (FakeGame): getCardPropertiesInLocation → Card::getPropertyArray(Game).
 * WHY alias some trait methods: FakeGame stubs declare return types DeckTrait omits;
 * PHP rejects the trait import unless we re-expose compatible wrappers.
 *
 * Do not use this class from card suites — its DeckTrait methods replace FakeGame's
 * Deck-/recording stubs (Faction- naming, real moveCard Location sync, etc.).
 */
class DeckTraitHost extends Game
{
    use DeckTrait {
        getGameDeckObject as private traitGetGameDeckObject;
        getPlayerFactionDeckName as private traitGetPlayerFactionDeckName;
        getPlayerDiscardDeckName as private traitGetPlayerDiscardDeckName;
        getPlayerLockerName as private traitGetPlayerLockerName;
        getCardsOnTopOfCityDeck as private traitGetCardsOnTopOfCityDeck;
        getCardsOnTopOfPlayerFactionDeck as private traitGetCardsOnTopOfPlayerFactionDeck;
        updateCardObjectInDb as private traitUpdateCardObjectInDb;
    }

    public object $cards;

    private int $nextCardId = 1000;
    private int $lastInsertId = 0;

    public function __construct()
    {
        parent::__construct();
        $this->theah = new TestTheah($this);
        $this->cards = new MemoryDeck($this);
    }

    public function getGameDeckObject(): object
    {
        return $this->traitGetGameDeckObject();
    }

    public function getPlayerFactionDeckName(int $playerId): string
    {
        return $this->traitGetPlayerFactionDeckName($playerId);
    }

    public function getPlayerDiscardDeckName(int $playerId): string
    {
        return $this->traitGetPlayerDiscardDeckName($playerId);
    }

    public function getPlayerLockerName($playerId): string
    {
        return $this->traitGetPlayerLockerName($playerId);
    }

    public function getCardsOnTopOfCityDeck(int $nbr): array
    {
        return $this->traitGetCardsOnTopOfCityDeck($nbr);
    }

    public function getCardsOnTopOfPlayerFactionDeck(int $playerId, int $count): array
    {
        return $this->traitGetCardsOnTopOfPlayerFactionDeck($playerId, $count);
    }

    /**
     * WHY wrap DeckTrait's SQL serialize write: tests assert Location / Used flags on
     * the same RAM objects getCardObjectFromDb returns. Still invoke the trait so
     * DbQuery records the UPDATE shape.
     */
    public function updateCardObjectInDb(?Card $card): void
    {
        if ($card !== null) {
            $this->dbCards[$card->Id] = $card;
            $this->updatedCardObjectIds[] = $card->Id;
            $this->traitUpdateCardObjectInDb($card);
        }
    }

    public function DbGetLastId(): int
    {
        return $this->lastInsertId;
    }

    public function DbQuery(string $sql): void
    {
        parent::DbQuery($sql);
        if (preg_match('/^\s*INSERT\s+INTO\s+card\b/i', $sql)) {
            $this->lastInsertId = ++$this->nextCardId;
        }
    }

    /**
     * Seed a card into both twin stores (dbCards + deck row + Theah).
     * Callers that need Location lag (park / moveCardInDeck) seed then move the deck row.
     *
     * WHY moveCardInDeck (not $this->cards->moveCard): pre-commit forbids raw deck-component
     * moveCard outside DeckTrait.php; Location is already set above so Game::moveCard would
     * only add seed-time factionDeckCount noise.
     */
    public function place(Card $card, string $location, int $controllerId, ?int $id = null): Card
    {
        $card->setId($id ?? ++$this->nextCardId);
        $card->ControllerId = $controllerId;
        $card->OwnerId = $controllerId;
        $card->Location = $location;
        $this->theah->addCardToWorld($card);
        $this->dbCards[$card->Id] = $card;
        $this->moveCardInDeck($card->Id, $location, $controllerId);
        return $card;
    }

    /** @return list<array{channel:string,type:string,message:string,args:array}> */
    public function notifsOfType(string $type): array
    {
        return array_values(array_filter(
            $this->notify->messages,
            fn(array $m) => $m['type'] === $type
        ));
    }
}

/**
 * Ordered pile stand-in for the BGA Deck component.
 * Index 0 = bottom, last index = top (matches DeckTrait bottom-peek via ascending location_arg).
 */
class MemoryDeck
{
    /** @var array<string, list<array{id:int,location:string,location_arg:int}>> */
    private array $piles = [];

    /** @var array<int, array{id:int,location:string,location_arg:int}> */
    private array $byId = [];

    public function __construct(private DeckTraitHost $host)
    {
    }

    public function countCardsInLocation($location): int
    {
        return count($this->piles[(string) $location] ?? []);
    }

    public function getCard($id): ?array
    {
        $id = (int) $id;
        return $this->byId[$id] ?? null;
    }

    public function moveCard($cardId, $location, $locationArg = 0): void
    {
        $cardId = (int) $cardId;
        $location = (string) $location;
        $this->remove($cardId);
        if (!isset($this->piles[$location])) {
            $this->piles[$location] = [];
        }
        $this->piles[$location][] = [
            'id' => $cardId,
            'location' => $location,
            'location_arg' => (int) $locationArg,
        ];
        $this->reindexArgs($location);
    }

    public function pickCard($location, $playerId): array
    {
        $top = $this->getCardOnTop($location);
        $this->moveCard($top['id'], Game::LOCATION_HAND, $playerId);
        return $this->byId[(int) $top['id']];
    }

    public function getCardOnTop($location): array
    {
        $pile = $this->piles[(string) $location] ?? [];
        if ($pile === []) {
            throw new \RuntimeException("getCardOnTop: empty location {$location}");
        }
        return $pile[array_key_last($pile)];
    }

    /**
     * @return list<array{id:int,location:string,location_arg:int}> top-first
     */
    public function getCardsOnTop($n, $location): array
    {
        $pile = $this->piles[(string) $location] ?? [];
        $n = min((int) $n, count($pile));
        if ($n <= 0) {
            return [];
        }
        return array_reverse(array_slice($pile, -$n));
    }

    /**
     * @param mixed $locationArg unused when null; when set, filters by location_arg value
     * @param string|null $orderBy "card_location_arg" => ascending (bottom first)
     * @return array<int, array{id:int,location:string,location_arg:int}>
     */
    public function getCardsInLocation($location, $locationArg = null, $orderBy = null): array
    {
        $pile = $this->piles[(string) $location] ?? [];
        if ($locationArg !== null) {
            $pile = array_values(array_filter(
                $pile,
                fn(array $row) => (int) $row['location_arg'] === (int) $locationArg
            ));
        }
        if ($orderBy === 'card_location_arg') {
            usort($pile, static fn(array $a, array $b): int => $a['location_arg'] <=> $b['location_arg']);
        }
        $rows = [];
        foreach ($pile as $row) {
            $rows[$row['id']] = $row;
        }
        return $rows;
    }

    public function insertCardOnExtremePosition($cardId, $location, $bOnTop): void
    {
        $cardId = (int) $cardId;
        $location = (string) $location;
        $this->remove($cardId);
        if (!isset($this->piles[$location])) {
            $this->piles[$location] = [];
        }
        $row = [
            'id' => $cardId,
            'location' => $location,
            'location_arg' => 0,
        ];
        if ($bOnTop) {
            $this->piles[$location][] = $row;
        } else {
            array_unshift($this->piles[$location], $row);
        }
        $this->reindexArgs($location);
        $this->host->deckInserts[] = [
            'id' => $cardId,
            'deck' => $location,
            'onTop' => (bool) $bOnTop,
        ];
    }

    public function shuffle($location, $playerId = null): void
    {
        unset($playerId);
        $location = (string) $location;
        $this->host->deckShuffles[] = $location;
        // Deterministic "shuffle": reverse. Tests that need a specific top seed after.
        if (isset($this->piles[$location]) && count($this->piles[$location]) > 1) {
            $this->piles[$location] = array_reverse(array_values($this->piles[$location]));
            $this->reindexArgs($location);
        }
    }

    private function remove(int $cardId): void
    {
        if (!isset($this->byId[$cardId])) {
            return;
        }
        $loc = $this->byId[$cardId]['location'];
        $this->piles[$loc] = array_values(array_filter(
            $this->piles[$loc] ?? [],
            fn(array $row) => $row['id'] !== $cardId
        ));
        $this->reindexArgs($loc);
        unset($this->byId[$cardId]);
    }

    private function reindexArgs(string $location): void
    {
        if (!isset($this->piles[$location])) {
            $this->piles[$location] = [];
            return;
        }
        $this->piles[$location] = array_values($this->piles[$location]);
        foreach ($this->piles[$location] as $i => $row) {
            $row['location_arg'] = $i;
            $this->piles[$location][$i] = $row;
            $this->byId[$row['id']] = $row;
        }
    }
}
