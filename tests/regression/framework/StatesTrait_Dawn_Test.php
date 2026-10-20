<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\StatesTraitGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventNewDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseDawnBeginning;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseDawnEnding;

/** StatesTrait: Dawn phase states. */
class StatesTrait_Dawn_Test extends TestCase
{
    public function name(): string
    {
        return 'StatesTrait: dawn phase';
    }

    /** Seed `$count` city-deck cards (ids 7001..) the way getCardsOnTopOfCityDeck would return them. */
    private function seedCityDeck(StatesTraitGame $game, int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $card = new GenericCharacter('City ' . $i);
            $game->place($card, Game::LOCATION_CITY_DECK, 0, 7000 + $i);
            $game->topCityCards[] = ['id' => 7000 + $i];
        }
    }

    /** @return list<string> locations in the order the city-card events were queued */
    private function cityCardLocations(StatesTraitGame $game): array
    {
        return array_map(
            fn(EventCityCardAddedToLocation $e) => $e->location,
            $game->queued(EventCityCardAddedToLocation::class)
        );
    }

    public function tests(): array
    {
        return [
            'stDawnNewDay increments the day, notifies and queues NewDay with the day number' => function () {
                $game = new StatesTraitGame();
                $game->setGameStateValue(Game::DAY, 2);
                $game->stDawnNewDay();

                Assert::same(3, $game->getGameStateValue(Game::DAY), 'day incremented');
                $events = $game->queued(EventNewDay::class);
                Assert::count(1, $events, 'NewDay queued');
                Assert::same(3, $events[0]->dayNumber, 'event day number');
                Assert::same('newDay', $game->notify->messages[0]['type'], 'newDay notification');
                Assert::same(3, $game->notify->messages[0]['args']['day'], 'notification day');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stDawnNewDay starts at day 1 when no day is stored' => function () {
                $game = new StatesTraitGame();
                $game->stDawnNewDay();
                Assert::same(1, $game->getGameStateValue(Game::DAY), 'first day');
            },

            'stDawnBeginning sets the Dawn phase and queues PhaseDawnBeginning' => function () {
                $game = new StatesTraitGame();
                $game->setGameStateValue(Game::TURN_PHASE, Game::DUSK);
                $game->stDawnBeginning();

                Assert::same(Game::DAWN, $game->getGameStateValue(Game::TURN_PHASE), 'turn phase');
                Assert::count(1, $game->queued(EventPhaseDawnBeginning::class), 'event');
                Assert::same('dawnBeginning', $game->notify->messages[0]['type'], 'notification');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stDawnCityCards in a 2-player game fills Docks, Forum and Bazaar only' => function () {
                $game = new StatesTraitGame();
                $this->seedCityDeck($game, 3);
                $game->stDawnCityCards();

                Assert::same(
                    [Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_BAZAAR],
                    $this->cityCardLocations($game),
                    'locations'
                );
                $ids = array_map(fn($e) => $e->cardId, $game->queued(EventCityCardAddedToLocation::class));
                Assert::same([7001, 7002, 7003], $ids, 'cards come off the top of the deck in order');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stDawnCityCards moves each drawn card in the deck to its location' => function () {
                $game = new StatesTraitGame();
                $this->seedCityDeck($game, 3);
                $game->stDawnCityCards();

                Assert::same(Game::LOCATION_CITY_DOCKS, $game->getCardObjectFromDb(7001)->Location, 'first card at Docks');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $game->getCardObjectFromDb(7003)->Location, 'last card at Bazaar');
            },

            "stDawnCityCards with 3 players adds Ole's Inn first" => function () {
                $game = new StatesTraitGame();
                $game->playerNames[3] = 'Player Three';
                $this->seedCityDeck($game, 4);
                $game->stDawnCityCards();

                Assert::same(
                    [
                        Game::LOCATION_CITY_OLES_INN,
                        Game::LOCATION_CITY_DOCKS,
                        Game::LOCATION_CITY_FORUM,
                        Game::LOCATION_CITY_BAZAAR,
                    ],
                    $this->cityCardLocations($game),
                    'locations'
                );
            },

            "stDawnCityCards with 4 players adds Ole's Inn first and the Governor's Garden last" => function () {
                $game = new StatesTraitGame();
                $game->playerNames[3] = 'Player Three';
                $game->playerNames[4] = 'Player Four';
                $this->seedCityDeck($game, 5);
                $game->stDawnCityCards();

                Assert::same(
                    [
                        Game::LOCATION_CITY_OLES_INN,
                        Game::LOCATION_CITY_DOCKS,
                        Game::LOCATION_CITY_FORUM,
                        Game::LOCATION_CITY_BAZAAR,
                        Game::LOCATION_CITY_GOVERNORS_GARDEN,
                    ],
                    $this->cityCardLocations($game),
                    'locations'
                );
            },

            'stDawnCityCards debug city card goes to the first location only, then the flag is cleared' => function () {
                $game = new StatesTraitGame();
                $this->seedCityDeck($game, 3);
                $game->globals->set(Game::DEBUG_INCLUDE_CITY_CARD, '05Teens');
                $game->stDawnCityCards();

                Assert::false($game->globals->has(Game::DEBUG_INCLUDE_CITY_CARD), 'debug flag deleted');
                Assert::count(1, $game->createdCardsInLocation, 'one debug card created');
                $debugId = $game->createdCardsInLocation[0]->Id;

                $events = $game->queued(EventCityCardAddedToLocation::class);
                Assert::count(3, $events, 'three locations filled');
                Assert::same($debugId, $events[0]->cardId, 'first location gets the debug card');
                Assert::same(7001, $events[1]->cardId, 'second location draws from the deck');
                Assert::same(7002, $events[2]->cardId, 'third location draws from the deck');
            },

            'stDawnEnding queues PhaseDawnEnding' => function () {
                $game = new StatesTraitGame();
                $game->stDawnEnding();
                Assert::count(1, $game->queued(EventPhaseDawnEnding::class), 'event');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },
        ];
    }
}
