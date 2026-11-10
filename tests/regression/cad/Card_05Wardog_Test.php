<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Wardog;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Wardoga;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Wardogb;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_05Wardog_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Wardog';
    }

    /** @return array{0:GenericCharacter,1:_05Wardog,2:Reaction_05Wardoga,3:Reaction_05Wardogb} */
    private function attach(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), $location, 1);
        $rifle = $world->placeCard(new _05Wardog(), $location, 1);
        $rifle->AttachedToId = $host->Id;
        /** @var Reaction_05Wardoga $moveReaction */
        $moveReaction = $rifle->getReactions()[0];
        /** @var Reaction_05Wardogb $pressureReaction */
        $pressureReaction = $rifle->getReactions()[1];
        return [$host, $rifle, $moveReaction, $pressureReaction];
    }

    private function moved(TestWorld $world, int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->initiatingPlayerId = 2;
        $event->theah = $world->theah;
        return $event;
    }

    private function pressure(TestWorld $world, int $playerId, string $location): EventPressureOccuring
    {
        $event = new EventPressureOccuring();
        $event->playerId = $playerId;
        $event->location = $location;
        $event->performerId = 0;
        $event->pressureTypes = [Game::STAT_INFLUENCE];
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Eisen FactionAttachment with two Covert Reactions' => function () {
                $rifle = new _05Wardog();
                Assert::instanceOf(FactionAttachment::class, $rifle, 'FactionAttachment');
                Assert::instanceOf(IHasReactions::class, $rifle, 'reactions');
                Assert::same(1, $rifle->WealthCost, 'cost');
                Assert::same(0, $rifle->Riposte, 'riposte');
                Assert::true($rifle->DashedRiposte, 'dashed riposte');
                Assert::same(2, $rifle->Parry, 'parry');
                Assert::same(2, $rifle->Thrust, 'thrust');
                Assert::true($rifle->hasTrait('Weapon'), 'Weapon');
                Assert::true($rifle->hasTrait('Rifle'), 'Rifle');
                Assert::true($rifle->hasTrait('Contraband'), 'Contraband');
                Assert::count(2, $rifle->getReactions(), 'two reactions');
                Assert::instanceOf(Reaction_05Wardoga::class, $rifle->getReactions()[0], 'a');
                Assert::instanceOf(Reaction_05Wardogb::class, $rifle->getReactions()[1], 'b');
            },

            'move reaction offers when an enemy arrives at an adjacent City location' => function () {
                $world = new TestWorld();
                [, $rifle, $reaction] = $this->attach($world);
                $enemy = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_BAZAAR, 2);

                $reaction->handleEvent($this->moved(
                    $world,
                    $enemy->Id,
                    Game::LOCATION_CITY_BAZAAR,
                    Game::LOCATION_CITY_FORUM
                ));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($rifle->Id, $transitions[0]->sourceId, 'source');
            },

            'move reaction offers without requiring the enemy to leave the host location' => function () {
                $world = new TestWorld();
                [, , $reaction] = $this->attach($world);
                // WHY: "enemy" not "opposing" — arrival from elsewhere still qualifies.
                $enemy = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_BAZAAR, 2);

                $reaction->handleEvent($this->moved(
                    $world,
                    $enemy->Id,
                    Game::LOCATION_CITY_BAZAAR,
                    Game::LOCATION_CITY_FORUM
                ));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'no fromLocation gate');
            },

            'move reaction does not offer for an ally arrival' => function () {
                $world = new TestWorld();
                [, , $reaction] = $this->attach($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_BAZAAR, 1);

                $reaction->handleEvent($this->moved(
                    $world,
                    $ally->Id,
                    Game::LOCATION_CITY_BAZAAR,
                    Game::LOCATION_CITY_FORUM
                ));
                Assert::count(0, $world->theah->queuedEvents, 'ally');
            },

            'move reaction does not offer when destination is not adjacent' => function () {
                $world = new TestWorld();
                [, , $reaction] = $this->attach($world);
                $enemy = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_FORUM, 2);

                $reaction->handleEvent($this->moved(
                    $world,
                    $enemy->Id,
                    Game::LOCATION_CITY_FORUM,
                    Game::LOCATION_CITY_BAZAAR
                ));
                Assert::count(0, $world->theah->queuedEvents, 'docks not adjacent to bazaar');
            },

            'move reaction does not offer at a controlled City location' => function () {
                $world = new TestWorld();
                [, , $reaction] = $this->attach($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                $enemy = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_BAZAAR, 2);

                $reaction->handleEvent($this->moved(
                    $world,
                    $enemy->Id,
                    Game::LOCATION_CITY_BAZAAR,
                    Game::LOCATION_CITY_FORUM
                ));
                Assert::count(0, $world->theah->queuedEvents, 'Covert gate');
            },

            'move reaction does not offer when not attached' => function () {
                $world = new TestWorld();
                $rifle = $world->placeCard(new _05Wardog(), Game::LOCATION_HAND, 1);
                $enemy = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_BAZAAR, 2);
                /** @var Reaction_05Wardoga $reaction */
                $reaction = $rifle->getReactions()[0];

                $reaction->handleEvent($this->moved(
                    $world,
                    $enemy->Id,
                    Game::LOCATION_CITY_BAZAAR,
                    Game::LOCATION_CITY_FORUM
                ));
                Assert::count(0, $world->theah->queuedEvents, 'hand');
            },

            'wounding the mover spends the reaction' => function () {
                $world = new TestWorld();
                [, $rifle, $reaction] = $this->attach($world);
                $enemy = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_BAZAAR, 2);
                $reaction->handleEvent($this->moved(
                    $world,
                    $enemy->Id,
                    Game::LOCATION_CITY_BAZAAR,
                    Game::LOCATION_CITY_FORUM
                ));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'wound');

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($enemy->Id, $wounds[0]->characterId, 'enemy');
                Assert::same($rifle->Id, $wounds[0]->sourceId, 'source');
                Assert::same($reaction->Id, $wounds[0]->abilityId, 'ability');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass does not spend the move reaction' => function () {
                $world = new TestWorld();
                [, , $reaction] = $this->attach($world);
                $enemy = $world->placeCharacter(new GenericCharacter('Enemy'), Game::LOCATION_CITY_BAZAAR, 2);
                $reaction->handleEvent($this->moved(
                    $world,
                    $enemy->Id,
                    Game::LOCATION_CITY_BAZAAR,
                    Game::LOCATION_CITY_FORUM
                ));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
            },

            'pressure reaction offers when pressure is at an adjacent City location' => function () {
                $world = new TestWorld();
                [, $rifle, , $reaction] = $this->attach($world);

                $reaction->handleEvent($this->pressure($world, 2, Game::LOCATION_CITY_FORUM));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($rifle->Id, $transitions[0]->sourceId, 'source');
            },

            'pressure reaction does not require you to be the pressuring player' => function () {
                $world = new TestWorld();
                [, , , $reaction] = $this->attach($world);

                $reaction->handleEvent($this->pressure($world, 2, Game::LOCATION_CITY_FORUM));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'opponent pressure ok');
            },

            'pressure reaction does not offer for pressure at the host location' => function () {
                $world = new TestWorld();
                [, , , $reaction] = $this->attach($world);

                $reaction->handleEvent($this->pressure($world, 1, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedEvents, 'must be adjacent');
            },

            'pressure reaction does not offer at a controlled City location' => function () {
                $world = new TestWorld();
                [, , , $reaction] = $this->attach($world);
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->pressure($world, 2, Game::LOCATION_CITY_FORUM));
                Assert::count(0, $world->theah->queuedEvents, 'Covert gate');
            },

            'using the pressure reaction sets the WARDOG flag' => function () {
                $world = new TestWorld();
                [, , , $reaction] = $this->attach($world);
                $reaction->handleEvent($this->pressure($world, 2, Game::LOCATION_CITY_FORUM));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'use');
                Assert::true($reaction->Used, 'spent');
                Assert::same(
                    Game::NORMAL_PRESSURE_TYPE | Game::WARDOG_PRESSURE_TYPE,
                    $world->game->globals->get(Game::PRESSURE_TYPE),
                    'flag'
                );
                Assert::same(1, $world->game->globals->get(Game::WARDOG_PLAYER_ID), 'player');
            },

            'pass does not spend the pressure reaction' => function () {
                $world = new TestWorld();
                [, , , $reaction] = $this->attach($world);
                $reaction->handleEvent($this->pressure($world, 2, Game::LOCATION_CITY_FORUM));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::same(Game::NORMAL_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'unchanged');
            },
        ];
    }
}
