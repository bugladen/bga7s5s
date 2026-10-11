<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\EventCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01179;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01179;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToCard;

class Card_01179_Test extends TestCase
{
    public function name(): string
    {
        return '_01179 Siren\'s Scream';
    }

    /** @return _01179 */
    private function placed(TestWorld $world): _01179
    {
        /** @var _01179 $siren */
        $siren = $world->placeCard(new _01179(), Game::LOCATION_CITY_DOCKS, 0);
        return $siren;
    }

    public function tests(): array
    {
        return [
            'constructs Catastrophe Monster CityEvent with Action_01179' => function () {
                $siren = new _01179();
                Assert::instanceOf(CityEventCard::class, $siren, 'CityEvent');
                Assert::instanceOf(IHasActions::class, $siren, 'actions');
                Assert::same(3, $siren->CityCardNumber, 'city #');
                Assert::same(0, $siren->Reknown, 'no Renown');
                Assert::true($siren->hasTrait('Catastrophe'), 'Catastrophe');
                Assert::true($siren->hasTrait('Monster'), 'Monster');
                Assert::instanceOf(Action_01179::class, $siren->getActions()[0], 'Action');
                Assert::instanceOf(EventCityAction::class, $siren->getActions()[0], 'EventCityAction');
            },

            'owns Action_01179 when placed' => function () {
                $world = new TestWorld();
                $siren = $this->placed($world);
                Assert::same($siren->Id, $siren->getActions()[0]->OwnerId, 'owner');
            },

            // WHY (journal 2026-03-28-04): Forced on reveal spends 1 Renown per player who has any.
            'reveal queues lose+add Renown per player with Renown' => function () {
                $world = new TestWorld();
                $siren = $this->placed($world);
                $world->game->setPlayerReknown(1, 2);
                $world->game->setPlayerReknown(2, 1);

                $event = new EventCityCardAddedToLocation();
                $event->cardId = $siren->Id;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($siren, $event);

                $losses = $world->theah->queuedOfType(EventPlayerLosesReknown::class);
                $adds = $world->theah->queuedOfType(EventReknownAddedToCard::class);
                Assert::count(2, $losses, 'two losses');
                Assert::count(2, $adds, 'two adds');
                Assert::same(1, $losses[0]->amount, 'amount');
                Assert::same($siren->Id, $adds[0]->cardId, 'to card');
            },

            'reveal skips players with zero Renown' => function () {
                $world = new TestWorld();
                $siren = $this->placed($world);
                $world->game->setPlayerReknown(1, 0);
                $world->game->setPlayerReknown(2, 3);

                $event = new EventCityCardAddedToLocation();
                $event->cardId = $siren->Id;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($siren, $event);

                $losses = $world->theah->queuedOfType(EventPlayerLosesReknown::class);
                Assert::count(1, $losses, 'only p2');
                Assert::same(2, $losses[0]->playerId, 'p2');
            },

            'canBeDiscardedFromCity only when Renown is zero' => function () {
                $world = new TestWorld();
                $siren = $this->placed($world);
                Assert::true($siren->canBeDiscardedFromCity(), 'zero');
                $siren->Reknown = 2;
                Assert::false($siren->canBeDiscardedFromCity(), 'has Renown');
            },

            // WHY (journal 2026-03-28-04 / 2026-04-08-03): even during Dusk — eventCheck refuses
            // city discard while Renown remains.
            'eventCheck refuses city discard while Renown remains' => function () {
                $world = new TestWorld();
                $siren = $this->placed($world);
                $siren->Reknown = 1;

                $event = new EventCardAddedToCityDiscardPile();
                $event->cardId = $siren->Id;
                $event->fromLocation = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $siren->eventCheck($event);
                } catch (UserException | \BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'blocked');
            },

            'eventCheck allows city discard at zero Renown' => function () {
                $world = new TestWorld();
                $siren = $this->placed($world);
                $siren->Reknown = 0;

                $event = new EventCardAddedToCityDiscardPile();
                $event->cardId = $siren->Id;
                $event->fromLocation = Game::LOCATION_CITY_DOCKS;
                $event->theah = $world->theah;
                $siren->eventCheck($event);
                Assert::true(true, 'allowed');
            },

            'getSirensScreamUsedListData delegates to the Action' => function () {
                $world = new TestWorld();
                $siren = $this->placed($world);
                /** @var Action_01179 $action */
                $action = $siren->getActions()[0];
                $prop = new \ReflectionProperty(Action_01179::class, 'playersUsed');
                $prop->setAccessible(true);
                $prop->setValue($action, [2]);

                $data = $siren->getSirensScreamUsedListData($world->game);
                Assert::count(1, $data, 'one');
                Assert::same(2, $data[0]['playerId'], 'id');
                Assert::same('Player Two', $data[0]['playerName'], 'name');
            },
        ];
    }
}
