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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02047;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02047_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02047';
    }

    /**
     * @return array{0:_02047,1:Action_02047,2:GenericCharacter,3:_02047}
     */
    private function scene(TestWorld $world, bool $artifact = false): array
    {
        $temnota = $world->placeCard(new _02047(), Game::LOCATION_CITY_DOCKS, 1);
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $temnota->AttachedToId = $host->Id;

        $target = $world->placeCard(new _02047(), Game::LOCATION_CITY_DOCKS, 0);
        $target->ControllerId = 0;
        if ($artifact) {
            $target->addTrait($world->game, 'Artifact');
        }

        /** @var Action_02047 $action */
        $action = $temnota->getActions()[0];
        return [$temnota, $action, $host, $target];
    }

    public function tests(): array
    {
        return [
            'is an AttachmentAction that targets cards' => function () {
                $action = new Action_02047();
                Assert::instanceOf(AttachmentAction::class, $action, 'AttachmentAction');
                Assert::instanceOf(IAbilityThatTargetsCards::class, $action, 'targets cards');
            },

            'state constant registered' => function () {
                Assert::same(402047, States::HIGH_DRAMA_PLAYER_TURN_02047, 'choose');
            },

            'available while equipped in the city and an unattached attachment is there' => function () {
                $world = new TestWorld();
                [, $action, $host, $target] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_02047, 'highDramaPlayerTurn_02047');
                Assert::same($host->Id, $args['performerId'], 'performer');
                Assert::same([$target->Id], $args['attachmentsInPlay'], 'targets');
            },

            'unavailable when unequipped, outside the city, with nothing to send, or used' => function () {
                $world = new TestWorld();
                [$temnota, $action, $host, $target] = $this->scene($world);

                $temnota->AttachedToId = 0;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'unequipped');

                $temnota->AttachedToId = $host->Id;
                $host->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');

                $host->Location = Game::LOCATION_CITY_DOCKS;
                $target->AttachedToId = $host->Id;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'already attached');

                $target->AttachedToId = 0;
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            // WHY: The location scan treats any unattached attachment as available, including one
            // that still has a controller. The click then rejects controlled attachments.
            'a controlled unattached attachment is offered and then rejected' => function () {
                $world = new TestWorld();
                [, $action, , $target] = $this->scene($world);
                $target->ControllerId = 2;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'listed');

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02047,
                        'highDramaPlayerTurn_02047',
                        $target->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not available');
                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'not sent');
            },

            'trigger opens the attachment chooser' => function () {
                $world = new TestWorld();
                [$temnota, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02047', $transitions[0]->transition, 'name');
                Assert::same($temnota->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability');
            },

            'sending a non-Artifact attachment goes to the locker with no Renown' => function () {
                $world = new TestWorld();
                [, $action, $host, $target] = $this->scene($world, false);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02047,
                    'highDramaPlayerTurn_02047',
                    $target->Id
                );

                $sent = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::count(1, $sent, 'locker');
                Assert::same($target->Id, $sent[0]->cardId, 'card');
                Assert::same($host->ControllerId, $sent[0]->playerId, 'player');
                Assert::count(0, $world->theah->queuedOfType(EventRenownAddedToLocation::class), 'no renown');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'sending an Artifact also adds a Renown at the host location' => function () {
                $world = new TestWorld();
                [$temnota, $action, $host, $target] = $this->scene($world, true);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02047,
                    'highDramaPlayerTurn_02047',
                    $target->Id
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'renown');
                Assert::same($host->Location, $renown[0]->location, 'location');
                Assert::same(1, $renown[0]->amount, 'one');
                Assert::same($host->ControllerId, $renown[0]->playerId, 'player');
                Assert::same($temnota->getInjectCode(), $renown[0]->description, 'source');
            },

            'refuses a missing attachment, one at another location, or one already attached' => function () {
                $world = new TestWorld();
                [, $action, $host] = $this->scene($world);
                $elsewhere = $world->placeCard(new _02047(), Game::LOCATION_CITY_FORUM, 0);
                $elsewhere->ControllerId = 0;
                $attached = $world->placeCard(new _02047(), Game::LOCATION_CITY_DOCKS, 0);
                $attached->ControllerId = 0;
                $attached->AttachedToId = $host->Id;

                foreach ([9999, $elsewhere->Id, $attached->Id] as $id) {
                    $threw = false;
                    try {
                        $action->actFromActionWithId(
                            $world->game,
                            States::HIGH_DRAMA_PLAYER_TURN_02047,
                            'highDramaPlayerTurn_02047',
                            $id
                        );
                    } catch (UserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, 'rejected ' . $id);
                }
                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'none sent');
            },
        ];
    }
}
