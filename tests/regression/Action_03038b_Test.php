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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03038b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03038b_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03038b';
    }

    /**
     * @return array{0:_03038,1:Action_03038b,2:GenericCharacter,3:_03044}
     */
    private function scene(TestWorld $world): array
    {
        $damya = $world->placeCharacter(new _03038(), Game::LOCATION_CITY_DOCKS, 1);
        $mover = $world->placeCharacter(new GenericCharacter('Mover'), Game::LOCATION_CITY_FORUM, 1);
        $attachment = $world->placeCard(new _03044(), Game::LOCATION_CITY_FORUM, 1);
        $attachment->WealthCost = 2;
        $attachment->AttachedToId = $mover->Id;
        $mover->Attachments[] = $attachment->Id;
        /** @var Action_03038b $action */
        $action = $damya->getActions()[1];
        return [$damya, $action, $mover, $attachment];
    }

    public function tests(): array
    {
        return [
            // WHY: Move has no Engage cost. The character must already be equipped and
            // must not already be at Damya's location. A fake attachment does not count.
            'moves an equipped character to Damya, then destroys the attachment to draw' => function () {
                $world = new TestWorld();
                [$damya, $action, $mover, $attachment] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');

                $mover->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'already here');
                $mover->Location = Game::LOCATION_CITY_FORUM;

                $attachment->FakeAttachment = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'fake attachment');
                $attachment->FakeAttachment = false;

                $damya->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Damya is home');
                $damya->Location = Game::LOCATION_CITY_DOCKS;

                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03038b', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'character step');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03038b, 'x');
                Assert::same([$mover->Id], $args['ids'], 'the mover');
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03038b, 'x', $mover->Id);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_DOCKS, $move->toLocation, 'to Damya');
                Assert::false($move->engage, 'no engage');
                Assert::same($mover->Id, $world->game->globals->get(Game::CHOSEN_TARGET), 'remembered');
                Assert::same('03038b_2', $world->theah->queuedOfType(EventTransition::class)[1]->transition, 'attachment step');

                $second = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03038b_2, 'x');
                Assert::same($attachment->Id, $second['attachments'][0]['id'], 'the cloak');
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03038b_2, 'x', $attachment->Id);
                Assert::same($attachment->Id, $world->theah->queuedOfType(EventAttachmentUnequipped::class)[0]->attachmentId, 'unequipped');
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'destroyed');
                Assert::count(3, $world->theah->queuedOfType(EventCardDrawn::class), 'printed cost 2 plus one');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_TARGET), 'cleared');
                Assert::same(['characterChosen', 'attachmentDestroyed'], $world->game->gamestate->transitions, 'states');
            },

            'a fake attachment or one equipped to someone else throws' => function () {
                $world = new TestWorld();
                [, $action, $mover, $attachment] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_TARGET, $mover->Id);
                $attachment->FakeAttachment = true;
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03038b_2, 'x', $attachment->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'fake');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');

                $attachment->FakeAttachment = false;
                $mover->Attachments = [];
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03038b_2, 'x', $attachment->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not equipped to them');
            },
        ];
    }
}
