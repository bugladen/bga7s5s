<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04044;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationBecomesUncontrolled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04044_Test extends TestCase
{
    public function name(): string
    {
        return '_04044 Adrift in the Wind';
    }

    public function tests(): array
    {
        return [
            'is a relentless brawl scheme' => function () {
                $card = new _04044();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(65, $card->Initiative, 'initiative');
                Assert::same(0, $card->PanacheModifier, 'panache');
                Assert::true($card->hasTrait('Brawl'), 'Brawl');
                Assert::true($card->hasTrait('Relentless'), 'Relentless');
                Assert::count(1, $card->getReactions(), 'reaction');
            },

            // WHY: The aura is only while the scheme is at Home. EventCardMoved still has
            // the old Location, so the recompute reads toLocation. Claim and uncontrolled
            // already wrote CityLocation->Controller. Locker strips the bonus.
            'gives the leader +1 finesse only at an uncontrolled city location' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04044(), Game::LOCATION_PLAYER_HOME, 1);
                $leader = new GenericLeader('Leader');
                $world->placeCharacter($leader, Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 0);

                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $resolve->playerName = 'Player One';
                $world->fireOn($scheme, $resolve);
                Assert::same('04044', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'two locations');
                Assert::same(\Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event::MEDIUM_PRIORITY, $world->theah->queuedOfType(EventTransition::class)[0]->priority, 'medium');
                Assert::false($leader->hasCondition(Game::ADRIFT_IN_THE_WIND_CONDITION), 'docks are controlled');

                $moved = new EventCardMoved();
                $moved->cardId = $leader->Id;
                $moved->fromLocation = Game::LOCATION_CITY_DOCKS;
                $moved->toLocation = Game::LOCATION_CITY_FORUM;
                $world->fireOn($scheme, $moved);
                Assert::true($leader->hasCondition(Game::ADRIFT_IN_THE_WIND_CONDITION), 'forum is uncontrolled');
                Assert::same($leader->ModifiedFinesse + 1, $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0]->NewFinesse, '+1');

                $world->theah->takeQueuedEvents();
                $world->fireOn($scheme, $moved);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'does not stack');

                $leader->Location = Game::LOCATION_CITY_FORUM;
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 1);
                $claimed = new EventLocationClaimed();
                $claimed->location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($scheme, $claimed);
                Assert::false($leader->hasCondition(Game::ADRIFT_IN_THE_WIND_CONDITION), 'claim removes it');

                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 0);
                $free = new EventLocationBecomesUncontrolled();
                $free->location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($scheme, $free);
                Assert::true($leader->hasCondition(Game::ADRIFT_IN_THE_WIND_CONDITION), 'uncontrolled restores it');

                $locker = new EventCardSentToLocker();
                $locker->cardId = $scheme->Id;
                $world->fireOn($scheme, $locker);
                Assert::false($leader->hasCondition(Game::ADRIFT_IN_THE_WIND_CONDITION), 'locker');

                $scheme->Location = Game::LOCATION_HAND;
                $world->theah->takeQueuedEvents();
                $world->fireOn($scheme, $moved);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'scheme left home');
            },
        ];
    }
}
