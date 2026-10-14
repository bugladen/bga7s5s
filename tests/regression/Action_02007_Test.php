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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02007;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/** Uncontrolled city-deck stand-in for Arson discard targets. */
final class TestCityCard_02007 extends CityAttachment
{
    public bool $Discardable = true;

    public function __construct(bool $discardable = true)
    {
        parent::__construct();
        $this->Name = 'Test City Card';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Discardable = $discardable;
        $this->resetCard();
    }

    public function canBeDiscardedFromCity(): bool
    {
        return $this->Discardable;
    }
}

class Action_02007_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02007';
    }

    /**
     * Arson in hand; Red Hand at Docks. Optional renown / city card.
     *
     * @return array{0:_02007,1:Action_02007,2:GenericCharacter}
     */
    private function scene(
        TestWorld $world,
        int $renown = 1,
        bool $withCityCard = false,
        array $performerTraits = ['Red Hand']
    ): array {
        $risk = $world->placeCard(new _02007(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(
            new GenericCharacter('Arsonist', $performerTraits),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, $renown);
        if ($withCityCard) {
            $world->placeCard(new TestCityCard_02007(), Game::LOCATION_CITY_DOCKS, 0);
        }
        /** @var Action_02007 $action */
        $action = $risk->getActions()[0];
        return [$risk, $action, $performer];
    }

    public function tests(): array
    {
        return [
            'is a RiskCityAction that requires a performer' => function () {
                $action = new Action_02007();
                Assert::instanceOf(RiskCityAction::class, $action, 'RiskCityAction');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY (journal 2026-03-16-01): availability is OR renown>0 OR uncontrolled city card
            // ("do as much as you can") — not AND.
            'available with renown only (no city card)' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1, false);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'renown only');
            },

            'available with uncontrolled city card only (no renown)' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 0, true);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'city card only');
            },

            'unavailable with neither renown nor discardable city card' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 0, false);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'nothing to do');
            },

            'unavailable without a Red Hand in the city' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world, 1, true, []);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Red Hand');
            },

            'unavailable when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $action] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not hand');
            },

            'performers are Red Hands whose location has renown or a discardable city card' => function () {
                $world = new TestWorld();
                [, $action, $docks] = $this->scene($world, 1, false);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $forumRh = $world->placeCharacter(
                    new GenericCharacter('Forum RH', ['Red Hand']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 0);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($docks->Id, $ids, true), 'docks RH');
                Assert::false(in_array($plain->Id, $ids, true), 'plain');
                Assert::false(in_array($forumRh->Id, $ids, true), 'forum empty');
            },

            'trigger wounds performer, removes renown, and resolves when no city card' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world, 1, false);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($performer->Id, $wounds[0]->characterId, 'performer');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');

                $renown = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class);
                Assert::count(1, $renown, 'renown');
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown[0]->location, 'docks');

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no chooser');
            },

            'trigger queues 02007 when a discardable city card is present' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world, 0, true);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('02007', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'chooser');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'source');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not yet');
            },

            'args list uncontrolled discardable city cards at performer location' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world, 0, true);
                $good = null;
                foreach ($world->theah->getCardObjectsAtLocation(Game::LOCATION_CITY_DOCKS) as $card) {
                    if ($card instanceof TestCityCard_02007) {
                        $good = $card;
                    }
                }
                $controlled = $world->placeCard(new TestCityCard_02007(), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02007,
                    'highDramaPlayerTurn_02007'
                );

                Assert::same($performer->Id, $args['performerId'], 'performer');
                Assert::true(in_array($good->Id, $args['ids'], true), 'uncontrolled');
                Assert::false(in_array($controlled->Id, $args['ids'], true), 'controlled excluded');
            },

            // WHY (journal 2026-03-16-01): actFromActionWithId must reject controlled city cards.
            'act rejects controlled city card' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world, 0, false);
                $controlled = $world->placeCard(new TestCityCard_02007(), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02007,
                        'highDramaPlayerTurn_02007',
                        $controlled->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class), 'no discard');
            },

            'act discards uncontrolled city card then ActionResolved' => function () {
                $world = new TestWorld();
                [$risk, $action, $performer] = $this->scene($world, 0, true);
                $city = null;
                foreach ($world->theah->getCardObjectsAtLocation(Game::LOCATION_CITY_DOCKS) as $card) {
                    if ($card instanceof TestCityCard_02007) {
                        $city = $card;
                    }
                }
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02007,
                    'highDramaPlayerTurn_02007',
                    $city->Id
                );

                $discard = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discard, 'discard');
                Assert::same($city->Id, $discard[0]->cardId, 'city card');
                Assert::same($risk->Id, $discard[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            // WHY: id 0 escape hatch when chooser entered with nothing discardable (mirrors 01072).
            'act id 0 resolves when no discardable city cards remain' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world, 1, false);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02007,
                    'highDramaPlayerTurn_02007',
                    0
                );

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
