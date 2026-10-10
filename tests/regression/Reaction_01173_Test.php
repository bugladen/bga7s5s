<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01173;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01173;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Reaction_01173_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01173';
    }

    /**
     * Risk in hand; controlled character already at destination (post-move).
     *
     * @return array{0:_01173,1:Reaction_01173,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01173(), Game::LOCATION_HAND, 1);
        $character = $world->placeCharacter(new GenericCharacter('Sailor'), Game::LOCATION_CITY_FORUM, 1);
        /** @var Reaction_01173 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $character];
    }

    private function moved(TestWorld $world, int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->theah = $world->theah;
        return $event;
    }

    private function chosenLocation(Reaction_01173 $reaction): string
    {
        $prop = new ReflectionProperty(Reaction_01173::class, 'ChosenLocation');
        $prop->setAccessible(true);
        return (string) $prop->getValue($reaction);
    }

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_01173(), 'RiskReaction');
            },

            // WHY: RiskReaction offer must gate Location == HAND (pre-commit / hand-only pay).
            'offers when your character moves into a City location while Risk is in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $character] = $this->scene($world);

                $reaction->handleEvent($this->moved(
                    $world,
                    $character->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM
                ));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same(1, $transitions[0]->playerId, 'controller');
                Assert::true($risk->IsUpdated, 'sticky ToLocation/CharacterId');
            },

            'does not offer when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $character] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->moved(
                    $world,
                    $character->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM
                ));

                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
            },

            'does not offer when the mover is an enemy' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->moved(
                    $world,
                    $foe->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM
                ));

                Assert::count(0, $world->theah->queuedEvents, 'enemy');
            },

            'does not offer when the destination is not a City location' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $character->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->moved(
                    $world,
                    $character->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_PLAYER_HOME
                ));

                Assert::count(0, $world->theah->queuedEvents, 'Home');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->moved(
                    $world,
                    $character->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM
                ));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons list adjacent City destinations plus Decline' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $reaction->handleEvent($this->moved(
                    $world,
                    $character->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM
                ));

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('moveAgain-' . Game::LOCATION_CITY_DOCKS, $ids, true), 'Docks');
                Assert::true(in_array('moveAgain-' . Game::LOCATION_CITY_BAZAAR, $ids, true), 'Bazaar');
                Assert::true(in_array('decline', $ids, true), 'decline');
                Assert::false(in_array('moveAgain-' . Game::LOCATION_PLAYER_HOME, $ids, true), 'no Home');
            },

            // WHY (journal 2026-09-25-03): stash ChosenLocation on the reaction — Soline (or any
            // intercalated reaction) can overwrite Game::REACTION_ID between choice and pay.
            'accept stashes ChosenLocation, queues pay, and marks Used' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $character] = $this->scene($world);
                $reaction->handleEvent($this->moved(
                    $world,
                    $character->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM
                ));
                $world->theah->takeQueuedEvents();

                $dest = Game::LOCATION_CITY_BAZAAR;
                $reaction->performReaction($world->game, 0, $reaction->Id, "moveAgain-{$dest}");

                Assert::same($dest, $this->chosenLocation($reaction), 'ChosenLocation');
                Assert::true($risk->IsUpdated, 'dirty for ChosenLocation');
                Assert::true($reaction->Used, 'used');
                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'EnteringPay');
                $pays = array_filter(
                    $world->theah->queuedOfType(EventTransition::class),
                    fn($t) => $t->transition === 'pay'
                );
                Assert::count(1, $pays, 'pay');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'decline finishes without pay, Used, or ChosenLocation' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $reaction->handleEvent($this->moved(
                    $world,
                    $character->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM
                ));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::same('', $this->chosenLocation($reaction), 'empty');
                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: prefer sticky ChosenLocation over parsing reactionId — corrupted "pass"
            // from an intervening Soline Pass must still move to the stashed destination.
            'triggered move prefers ChosenLocation over a corrupted reactionId' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $character] = $this->scene($world);
                $reaction->handleEvent($this->moved(
                    $world,
                    $character->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM
                ));
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveAgain-' . Game::LOCATION_CITY_BAZAAR);
                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'pass';
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($character->Id, $moves[0]->cardId, 'character');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $moves[0]->toLocation, 'Bazaar');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::same($reaction->Id, $moves[0]->abilityId, 'ability');
            },

            'triggered without ChosenLocation still parses moveAgain- from reactionId' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $character] = $this->scene($world);
                $reaction->handleEvent($this->moved(
                    $world,
                    $character->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM
                ));
                $world->theah->takeQueuedEvents();

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'moveAgain-' . Game::LOCATION_CITY_DOCKS;
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->toLocation, 'Docks');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
            },

            'triggered with a non-adjacent destination queues no move' => function () {
                $world = new TestWorld();
                [, $reaction, $character] = $this->scene($world);
                $reaction->handleEvent($this->moved(
                    $world,
                    $character->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM
                ));
                $world->theah->takeQueuedEvents();

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->reactionId = 'moveAgain-' . Game::LOCATION_CITY_OLES_INN;
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'not adjacent');
            },
        ];
    }
}
