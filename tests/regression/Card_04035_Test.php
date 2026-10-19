<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04035;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04035_Test extends TestCase
{
    public function name(): string
    {
        return '_04035 Meeting of the Minds';
    }

    public function tests(): array
    {
        return [
            'is a discovery scheme' => function () {
                $card = new _04035();
                Assert::instanceOf(Scheme::class, $card, 'Scheme');
                Assert::same(83, $card->Initiative, 'initiative');
                Assert::same(-1, $card->PanacheModifier, 'panache');
                Assert::true($card->hasTrait('Discovery'), 'Discovery');
                Assert::count(1, $card->getReactions(), 'reaction');
            },

            // WHY: Bazaar and Forum renown always. The risk retrieval is contingent on
            // controlling an Academic and having a non-Revelry risk in discard or locker.
            // Pass is illegal while one is waiting. Revelry does not qualify.
            'retrieves a non-revelry risk only when you control an academic' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04035(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::same(Game::LOCATION_CITY_BAZAAR, $renown[0]->location, 'bazaar');
                Assert::same(Game::LOCATION_CITY_FORUM, $renown[1]->location, 'forum');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no academic');

                $world->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $revelry = $world->placeCard(new _04027(), $world->game->getPlayerDiscardDeckName(1), 1);
                $revelry->addTrait($world->game, 'Revelry');
                $world->theah->takeQueuedEvents();
                $world->fireOn($scheme, $resolve);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'revelry does not qualify');

                $risk = $world->placeCard(new _04027(), $world->game->getPlayerDiscardDeckName(1), 1);
                $locker = $world->placeCard(new _04027(), $world->game->getPlayerLockerName(1), 1);
                $world->theah->takeQueuedEvents();
                $world->fireOn($scheme, $resolve);
                Assert::same('04035', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pick');
                $ids = $scheme->argsFromCard($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04035, '', '')['ids'];
                Assert::true(in_array($risk->Id, $ids, true), 'discard');
                Assert::true(in_array($locker->Id, $ids, true), 'locker');
                Assert::false(in_array($revelry->Id, $ids, true), 'revelry hidden');

                $blocked = false;
                try {
                    $scheme->actFromCardPass($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04035, '', '');
                } catch (UserException) {
                    $blocked = true;
                }
                Assert::true($blocked, 'must choose');

                $notRisk = false;
                try {
                    $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04035, '', '', $revelry->Id);
                } catch (UserException) {
                    $notRisk = true;
                }
                Assert::true($notRisk, 'revelry rejected');

                $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04035, '', '', $risk->Id);
                Assert::same($risk->Id, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class)[0]->cardId, 'from discard');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventCardAddedToHand::class)[0]->cardId, 'to hand');
                Assert::same('', end($world->game->gamestate->transitions), 'empty transition');

                $scheme->actFromCardWithId($world->game, States::PLANNING_PHASE_RESOLVE_SCHEMES_04035, '', '', $locker->Id);
                Assert::same($locker->Id, $world->theah->queuedOfType(EventCardRemovedFromLocker::class)[0]->cardId, 'from locker');
            },
        ];
    }
}
