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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03063;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03063;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03063_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03063';
    }

    public function tests(): array
    {
        return [
            'is a scheme city action that needs a performer' => function () {
                $action = new Action_03063();
                Assert::instanceOf(SchemeCityAction::class, $action, 'scheme city');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Scoundrel, and the location must have Renown or an available attachment.
            // Id 0 is Renown. MoveMode 0 is not a legal destination click. Attachment moves do not engage.
            'moves renown or an available attachment to another city location' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03063(), Game::LOCATION_PLAYER_HOME, 1);
                $scoundrel = $world->placeCharacter(new GenericCharacter('Scoundrel', ['Scoundrel']), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_FORUM, 1);
                /** @var Action_03063 $action */
                $action = $scheme->getActions()[0];
                Assert::count(0, $action->getPerformersForAction(1, $world->theah), 'nothing to move');
                unset($plain);

                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $loose = $world->placeCard(new _03055(), Game::LOCATION_CITY_DOCKS, 0);
                Assert::same([$scoundrel->Id], array_map(fn ($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'scoundrel');

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $scoundrel->Id);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same(0, $action->MoveMode, 'reset');
                Assert::same('03063', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'thing');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03063, 'x', 0);
                Assert::same(1, $action->MoveMode, 'renown');
                Assert::same(['thingChosen'], array_slice($world->game->gamestate->transitions, -1), 'thing chosen');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03063_2, 'x', [Game::LOCATION_CITY_DOCKS]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'same location');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03063_2, 'x', [Game::LOCATION_CITY_FORUM]);
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $added->location, 'forum');
                Assert::true($added->isMove, 'move');
                Assert::same(0, $action->MoveMode, 'cleared');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');

                $world->theah->queuedEvents = [];
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03063, 'x', $loose->Id);
                Assert::same(2, $action->MoveMode, 'attachment');
                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03063_2, 'x', [Game::LOCATION_CITY_BAZAAR]);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($loose->Id, $move->cardId, 'attachment');
                Assert::false($move->engage, 'no engage');
            },

            'move mode 0 throws before anything moves' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03063(), Game::LOCATION_PLAYER_HOME, 1);
                $scoundrel = $world->placeCharacter(new GenericCharacter('Scoundrel', ['Scoundrel']), Game::LOCATION_CITY_DOCKS, 1);
                $action = $scheme->getActions()[0];
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $scoundrel->Id);
                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03063_2, 'x', [Game::LOCATION_CITY_FORUM]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid option');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');
            },
        ];
    }
}
