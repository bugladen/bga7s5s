<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01137;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01137;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01137_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01137';
    }

    /**
     * Risk in hand; pursuer still at from-location; foe already at destination (post-move).
     *
     * @return array{0:_01137,1:Reaction_01137,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _01137(), Game::LOCATION_HAND, 1);
        $pursuer = $world->placeCharacter(new GenericCharacter('Pursuer'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Reaction_01137 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $pursuer, $foe];
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

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_01137(), 'RiskReaction');
            },

            'offers when an enemy leaves a city location where you have a character' => function () {
                $world = new TestWorld();
                [$risk, $reaction, , $foe] = $this->scene($world);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
                Assert::same(1, $transitions[0]->playerId, 'controller');
            },

            'does not offer when the mover is controlled by you' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);

                $reaction->handleEvent($this->moved($world, $ally->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'friendly');
            },

            'does not offer when the enemy moves Home' => function () {
                $world = new TestWorld();
                [, $reaction, , $foe] = $this->scene($world);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_PLAYER_HOME));

                Assert::count(0, $world->theah->queuedEvents, 'to Home');
            },

            'does not offer when the enemy leaves Home' => function () {
                $world = new TestWorld();
                [, $reaction, , $foe] = $this->scene($world);

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'from Home');
            },

            'does not offer without a controlled character at the from location' => function () {
                $world = new TestWorld();
                [, $reaction, $pursuer, $foe] = $this->scene($world);
                $pursuer->Location = Game::LOCATION_CITY_BAZAAR;

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'nobody at origin');
            },

            'does not offer when Risk is not in hand' => function () {
                $world = new TestWorld();
                [$risk, $reaction, , $foe] = $this->scene($world);
                $risk->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction, , $foe] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY: printed text is any different City location — not adjacency-gated.
            'offers for a non-adjacent city destination' => function () {
                $world = new TestWorld();
                [, $reaction, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_BAZAAR;

                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_BAZAAR));

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'any city');
            },

            'buttons list Follow for each character at from-location plus Pass' => function () {
                $world = new TestWorld();
                [, $reaction, $pursuer, $foe] = $this->scene($world);
                $buddy = $world->placeCharacter(new GenericCharacter('Buddy'), Game::LOCATION_CITY_DOCKS, 1);
                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("follow-{$pursuer->Id}", $ids, true), 'pursuer');
                Assert::true(in_array("follow-{$buddy->Id}", $ids, true), 'buddy');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            // WHY: setUsed happens after wealth pay (framework), not on the Follow click.
            'follow queues pay without marking Used' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $pursuer, $foe] = $this->scene($world);
                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, "follow-{$pursuer->Id}");

                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'EnteringPay');
                $pays = array_filter(
                    $world->theah->queuedOfType(EventTransition::class),
                    fn($t) => $t->transition === 'pay'
                );
                Assert::count(1, $pays, 'pay');
                Assert::same($risk->Id, array_values($pays)[0]->sourceId, 'source');
                Assert::false($reaction->Used, 'not used yet');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass finishes without pay or Used' => function () {
                $world = new TestWorld();
                [, $reaction, , $foe] = $this->scene($world);
                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'no pay');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'triggered follow moves the pursuer without engage and wounds the enemy' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $pursuer, $foe] = $this->scene($world);
                $reaction->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, "follow-{$pursuer->Id}");
                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];

                $triggered = new EventRiskReactionTriggered();
                $triggered->internalId = $reaction->Id;
                $triggered->theah = $world->theah;
                $reaction->handleEvent($triggered);

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move');
                Assert::same($pursuer->Id, $moves[0]->cardId, 'pursuer');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to');
                Assert::false($moves[0]->engage, 'no engage');
                Assert::same($risk->Id, $moves[0]->sourceId, 'source');
                Assert::same($reaction->Id, $moves[0]->abilityId, 'ability');

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
                Assert::same(1, $wounds[0]->wounds, 'one');
                Assert::same($risk->Id, $wounds[0]->sourceId, 'source');
                Assert::same($reaction->Id, $wounds[0]->abilityId, 'ability');
            },
        ];
    }
}
