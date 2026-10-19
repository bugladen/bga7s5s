<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04005;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04005;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01028;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04005_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04005';
    }

    public function tests(): array
    {
        return [
            'is a red hand city action' => function () {
                $action = new Action_04005();
                Assert::instanceOf(SchemeCityAction::class, $action, 'SchemeCityAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: The destroy target is another character you control here, and the
            // location must be claimable or the action is not offered.
            'requires a red hand, a sacrifice, and a claimable location' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04005(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Hand', ['Red Hand']), Game::LOCATION_CITY_DOCKS, 1);
                $sacrifice = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_04005 $action */
                $action = $scheme->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'ready');

                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unclaimable');
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, true);
                $sacrifice->ControllerId = 2;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'only an enemy here');
                $sacrifice->ControllerId = 1;
                $performer->ModifiedTraits = ['Hero'];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not red hand');
            },

            // WHY: Claim is skipped with a message when the location became unclaimable
            // after the offer. Discard is asEffect, and only players with a hand are asked.
            'destroy claims, then each hand discards' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04005(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Hand', ['Red Hand']), Game::LOCATION_CITY_DOCKS, 1);
                $sacrifice = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $hand = $world->placeCard(new _01028(), Game::LOCATION_HAND, 2);
                /** @var Action_04005 $action */
                $action = $scheme->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04005, '', $sacrifice->Id);

                Assert::same($sacrifice->Id, $world->theah->queuedOfType(EventCharacterDestroyed::class)[0]->characterId, 'destroyed');
                Assert::same(Game::LOCATION_CITY_DOCKS, $world->theah->queuedOfType(EventLocationClaimed::class)[0]->location, 'claimed');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved before the discard step');
                Assert::same('04005_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'discard');

                $world->game->currentPlayerId = 2;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04005_2, '', $hand->Id);
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0];
                Assert::true($discard->asEffect, 'effect');
                Assert::false($discard->AsPayment, 'not a payment');
                Assert::same('multipleOk', $world->game->gamestate->nonMultiactive[0]['transition'], 'released');

                $threw = false;
                try {
                    $world->game->currentPlayerId = 1;
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04005_2, '', $hand->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not in that hand');
            },
        ];
    }
}
