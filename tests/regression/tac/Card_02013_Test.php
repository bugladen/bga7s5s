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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02013;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;

class Card_02013_Test extends TestCase
{
    public function name(): string
    {
        return '_02013 Wilhelm Dünst';
    }

    /**
     * @return array{0:_02013,1:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $wilhelm = $world->placeCharacter(new _02013(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        return [$wilhelm, $foe];
    }

    private function issued(TestWorld $world, int $challengerId, int $defenderId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Eisen Character with Action' => function () {
                $wilhelm = new _02013();
                Assert::instanceOf(Character::class, $wilhelm, 'Character');
                Assert::instanceOf(IHasActions::class, $wilhelm, 'actions');
                Assert::same(5, $wilhelm->Resolve, 'Resolve');
                Assert::same(2, $wilhelm->Combat, 'Combat');
                Assert::same(3, $wilhelm->Finesse, 'Finesse');
                Assert::same(2, $wilhelm->Influence, 'Influence');
                Assert::true($wilhelm->hasTrait('Hero'), 'Hero');
                Assert::true($wilhelm->hasTrait('Zealot'), 'Zealot');
                Assert::true($wilhelm->hasTrait('Hunter'), 'Hunter');
                Assert::true($wilhelm->hasTrait('Eisen'), 'Eisen trait');
                Assert::true($wilhelm->hasFaction('Eisen'), 'Eisen faction');
                Assert::instanceOf(Action_02013::class, $wilhelm->getActions()[0], 'Action');
            },

            'owns Action when placed' => function () {
                $world = new TestWorld();
                $wilhelm = $world->placeCharacter(new _02013(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($wilhelm->Id, $wilhelm->getActions()[0]->OwnerId, 'owner');
            },

            'state constants registered' => function () {
                Assert::same(402013, States::HIGH_DRAMA_PLAYER_TURN_02013, '02013');
                Assert::same(4020132, States::HIGH_DRAMA_PLAYER_TURN_02013_2, '02013_2');
            },

            // WHY: printed restriction — Combat challenges only to Villain|Sorcerer|Monster.
            'eventCheck throws Combat challenge to plain foe' => function () {
                $world = new TestWorld();
                [$wilhelm, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $threw = false;
                try {
                    $wilhelm->eventCheck($this->issued($world, $wilhelm->Id, $foe->Id));
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'blocked');
            },

            'eventCheck allows Combat challenge to Villain' => function () {
                $world = new TestWorld();
                [$wilhelm] = $this->scene($world);
                $villain = $world->placeCharacter(
                    new GenericCharacter('Villain', ['Villain']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $threw = false;
                try {
                    $wilhelm->eventCheck($this->issued($world, $wilhelm->Id, $villain->Id));
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::false($threw, 'Villain ok');
            },

            'eventCheck allows Combat challenge to Sorcerer' => function () {
                $world = new TestWorld();
                [$wilhelm] = $this->scene($world);
                $sorcerer = $world->placeCharacter(
                    new GenericCharacter('Sorcerer', ['Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $threw = false;
                try {
                    $wilhelm->eventCheck($this->issued($world, $wilhelm->Id, $sorcerer->Id));
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::false($threw, 'Sorcerer ok');
            },

            'eventCheck allows Combat challenge to Monster' => function () {
                $world = new TestWorld();
                [$wilhelm] = $this->scene($world);
                $monster = $world->placeCharacter(
                    new GenericCharacter('Monster', ['Monster']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $threw = false;
                try {
                    $wilhelm->eventCheck($this->issued($world, $wilhelm->Id, $monster->Id));
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::false($threw, 'Monster ok');
            },

            // WHY: restriction is Combat-only — Finesse/Influence challenges skip the gate.
            'eventCheck allows non-Combat challenge to plain foe' => function () {
                $world = new TestWorld();
                [$wilhelm, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);

                $threw = false;
                try {
                    $wilhelm->eventCheck($this->issued($world, $wilhelm->Id, $foe->Id));
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::false($threw, 'non-Combat ok');
            },

            'eventCheck ignores challenges not issued by Wilhelm' => function () {
                $world = new TestWorld();
                [$wilhelm, $foe] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $threw = false;
                try {
                    $wilhelm->eventCheck($this->issued($world, $other->Id, $foe->Id));
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::false($threw, 'not Wilhelm');
            },
        ];
    }
}
