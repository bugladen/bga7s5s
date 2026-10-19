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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03055;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03055_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03055';
    }

    public function tests(): array
    {
        return [
            'is an attachment action' => function () {
                Assert::instanceOf(AttachmentAction::class, new Action_03055(), 'attachment action');
            },

            // WHY: The compass itself is an Artifact, but the current location is excluded.
            // Destinations are other city locations or Home with a Scion or a non-fake Artifact,
            // equipped or available. The trigger only opens the picker. Engage pays the attachment.
            'moves the equipped character to a scion or artifact' => function () {
                $world = new TestWorld();
                $bearer = $world->placeCharacter(new GenericCharacter('Bearer'), Game::LOCATION_CITY_DOCKS, 1);
                $compass = $world->placeCard(new _03055(), Game::LOCATION_CITY_DOCKS, 1);
                $compass->AttachedToId = $bearer->Id;
                $bearer->Attachments[] = $compass->Id;
                /** @var Action_03055 $action */
                $action = $compass->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nowhere to go');

                $scion = $world->placeCharacter(new GenericCharacter('Scion', ['Scion']), Game::LOCATION_CITY_FORUM, 2);
                $fake = $world->placeCard(new _03055(), Game::LOCATION_CITY_BAZAAR, 2);
                $fake->FakeAttachment = true;
                $fakeHost = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_BAZAAR, 2);
                $fake->AttachedToId = $fakeHost->Id;
                $fakeHost->Attachments[] = $fake->Id;
                $loose = $world->placeCard(new _03055(), Game::LOCATION_CITY_GOVERNORS_GARDEN, 0);
                $homeScion = $world->placeCharacter(new GenericCharacter('Home Scion', ['Scion']), Game::LOCATION_PLAYER_HOME, 1);

                $compass->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged');
                $compass->Engaged = false;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'destinations');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03055, 'x');
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $args['locationIds'], true), 'scion');
                Assert::true(in_array(Game::LOCATION_CITY_GOVERNORS_GARDEN, $args['locationIds'], true), 'available artifact');
                Assert::true(in_array(Game::LOCATION_PLAYER_HOME, $args['locationIds'], true), 'home scion');
                Assert::false(in_array(Game::LOCATION_CITY_DOCKS, $args['locationIds'], true), 'not current');
                Assert::false(in_array(Game::LOCATION_CITY_BAZAAR, $args['locationIds'], true), 'fake artifact');
                unset($scion, $loose, $homeScion);

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03055', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move yet');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03055, 'x', [Game::LOCATION_CITY_BAZAAR]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'illegal');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03055, 'x', [Game::LOCATION_CITY_FORUM]);
                Assert::same($compass->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage compass');
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::false($move->engage, 'no move engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['locationChosen'], array_slice($world->game->gamestate->transitions, -1), 'chosen');
            },
        ];
    }
}
