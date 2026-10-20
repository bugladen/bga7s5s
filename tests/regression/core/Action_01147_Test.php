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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCards;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01147;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01147;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

final class TestAttachment_Action_01147 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'Bazaar Attachment';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->WealthCost = 3;
        $this->resetCard();
    }
}

class Action_01147_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_01147';
    }

    /** @return array{0:_01147,1:Action_01147} */
    private function scene(TestWorld $world, bool $withAttachment = true, bool $withPerformer = true): array
    {
        $scheme = $world->placeCard(new _01147(), Game::LOCATION_PLAYER_HOME, 1);
        if ($withPerformer) {
            $world->placeCharacter(new GenericCharacter('Buyer'), Game::LOCATION_CITY_DOCKS, 1);
        }
        if ($withAttachment) {
            $world->placeCard(new TestAttachment_Action_01147(), Game::LOCATION_CITY_BAZAAR, 0);
        }
        /** @var Action_01147 $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action];
    }

    public function tests(): array
    {
        return [
            'implements IAbilityThatTargetsCards' => function () {
                Assert::instanceOf(IAbilityThatTargetsCards::class, new Action_01147(), 'targets cards');
            },

            'available with city character and unattached Bazaar attachment' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable with no Bazaar attachment' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no attachment');
            },

            'unavailable when attachment is already equipped' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world, false);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_BAZAAR, 1);
                $att = $world->placeCard(new TestAttachment_Action_01147(), Game::LOCATION_CITY_BAZAAR, 0);
                $att->AttachedToId = $host->Id;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'attached');
                unset($scheme);
            },

            'unavailable with no city character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, true, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no performer');
            },

            'unavailable once used' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');
            },

            'trigger queues transition 01147' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one');
                Assert::same('01147', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            'args list unattached Bazaar attachments and performer' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $performer = $world->placeCharacter(new GenericCharacter('Buyer'), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new TestAttachment_Action_01147(), Game::LOCATION_CITY_BAZAAR, 0);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_01147, 'x');

                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::same([$att->Id], $args['attachmentsInPlay'], 'attachments');
            },

            // WHY: createActionResolvedEvent intentionally omitted — hands off to equip pay flow.
            'selecting attachment sets LETS_HAGGLE equip globals and transitions' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, false);
                $performer = $world->placeCharacter(new GenericCharacter('Buyer'), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new TestAttachment_Action_01147(), Game::LOCATION_CITY_BAZAAR, 0);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01147,
                    'x',
                    $att->Id
                );

                Assert::same($att->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(Game::LETS_HAGGLE_EQUIP_TYPE, $world->game->globals->get(Game::EQUIP_TYPE), 'equip type');
                Assert::same(['attachmentSelected'], $world->game->gamestate->transitions, 'named');
            },

            'refuses an invalid attachment id' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $performer = $world->placeCharacter(new GenericCharacter('Buyer'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01147,
                        'x',
                        99999
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'invalid');
            },
        ];
    }
}
