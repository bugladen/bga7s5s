<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03064;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03051;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03051_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03051';
    }

    /**
     * @return array{0:TestWorld,1:Action_03051,2:_03051}
     */
    private function scene(bool $engagedAttachment): array
    {
        $world = new TestWorld();
        $yep = $world->placeCharacter(new _03051(), Game::LOCATION_CITY_DOCKS, 1);
        $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_FORUM, 1);
        $world->theah->leadersByPlayerId[1] = $leader;
        if ($engagedAttachment) {
            $harpoon = $world->placeCard(new _03064(), Game::LOCATION_CITY_DOCKS, 1);
            $harpoon->AttachedToId = $yep->Id;
            $harpoon->Engaged = true;
            $yep->Attachments[] = $harpoon->Id;
        }
        /** @var Action_03051 $action */
        $action = $yep->getActions()[0];
        return [$world, $action, $yep];
    }

    public function tests(): array
    {
        return [
            'is a character action' => function () {
                Assert::instanceOf(CharacterAction::class, new Action_03051(), 'character action');
            },

            // WHY: Printed text has no Engage cost and no attachment requirement.
            // Sharing the Leader's location, sitting at Home, or having no Leader blocks it.
            // The picker opens only for an engaged non-fake attachment that will be at the destination.
            'moves to the leader, and en gardes only an engaged attachment' => function () {
                [$world, $action, $yep] = $this->scene(false);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'no attachment');

                $yep->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'already there');
                $yep->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home');
                $yep->Location = Game::LOCATION_CITY_DOCKS;
                $world->theah->leadersByPlayerId = [];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no leader');

                [$world, $action, $yep] = $this->scene(false);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::false($move->engage, 'no engage');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no picker');

                [$world, $action, $yep] = $this->scene(true);
                $harpoonId = $yep->Attachments[0];
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same('03051', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not yet');

                $ready = $world->theah->getAttachmentById($harpoonId);
                $ready->Engaged = false;
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03051, 'x', $harpoonId);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'already en garde');

                $ready->Engaged = true;
                $ready->FakeAttachment = true;
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03051, 'x', $harpoonId);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'fake');

                $ready->FakeAttachment = false;
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03051, 'x', $harpoonId);
                Assert::same($harpoonId, $world->theah->queuedOfType(EventCardEngarded::class)[0]->cardId, 'engarded');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved after');
                Assert::same(['attachmentChosen'], array_slice($world->game->gamestate->transitions, -1), 'chosen');
            },

            'an attachment already at the destination is also a choice' => function () {
                [$world, $action] = $this->scene(false);
                $leader = $world->theah->leadersByPlayerId[1];
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), $leader->Location, 1);
                $harpoon = $world->placeCard(new _03064(), $leader->Location, 1);
                $harpoon->AttachedToId = $ally->Id;
                $harpoon->Engaged = true;
                $ally->Attachments[] = $harpoon->Id;

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03051, 'x');
                $ids = array_column($args['attachments'], 'id');
                Assert::true(in_array($harpoon->Id, $ids, true), 'destination attachment');
            },
        ];
    }
}
