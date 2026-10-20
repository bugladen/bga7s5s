<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03017_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03017';
    }

    /**
     * @return array{0:_03017,1:Reaction_03017,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world, bool $zealot = true): array
    {
        $scheme = $world->placeCard(new _03017(), Game::LOCATION_PLAYER_HOME, 1);
        $traits = $zealot ? ['Zealot', 'Hero'] : ['Hero'];
        $fallen = $world->placeCharacter(new GenericCharacter('Fallen', $traits), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_03017 $reaction */
        $reaction = $scheme->getReactions()[0];
        return [$scheme, $reaction, $fallen, $foe, $ally];
    }

    private function destroyed(TestWorld $world, int $characterId): EventCharacterDestroyed
    {
        $event = new EventCharacterDestroyed();
        $event->theah = $world->theah;
        $event->characterId = $characterId;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a card reaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_03017(), 'card reaction');
            },

            // WHY: The Zealot flag is snapshotted at the offer. Resolve uses that flag,
            // not a fresh trait check. The destroyed character is still at the location,
            // so they are healed along with your other characters. Opponents are wounded.
            // Uncontrolled characters are not in the location list.
            'a Zealot destroyed in the city wounds opponents, heals yours, and draws' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $fallen, $foe, $ally] = $this->scene($world);
                $loose = $world->placeCharacter(new GenericCharacter('Loose'), Game::LOCATION_CITY_DOCKS, 0);
                $reaction->handleEvent($this->destroyed($world, $fallen->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
                $fallen->removeTrait($world->game, 'Zealot');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'resolve');
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                $heals = $world->theah->queuedOfType(EventCharacterBeingHealed::class);
                $woundIds = array_map(fn($event) => $event->characterId, $wounds);
                $healIds = array_map(fn($event) => $event->characterId, $heals);
                Assert::same([$foe->Id], $woundIds, 'opposing only');
                Assert::false(in_array($loose->Id, $woundIds, true), 'uncontrolled omitted');
                Assert::same([$fallen->Id, $ally->Id], $healIds, 'destroyed character is still here, then the ally');
                Assert::same($scheme->Id, $wounds[0]->sourceId, 'scheme is the source');
                Assert::count(1, $world->theah->queuedOfType(EventCardDrawn::class), 'zealot snapshot still draws');
                Assert::true($reaction->Used, 'spent');
            },

            'home, an opponent character, or pass does not resolve' => function () {
                $world = new TestWorld();
                [, $reaction, $fallen] = $this->scene($world, false);
                $fallen->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->destroyed($world, $fallen->Id));
                Assert::count(0, $world->theah->queuedEvents, 'home');

                $fallen->Location = Game::LOCATION_CITY_DOCKS;
                $fallen->ControllerId = 2;
                $reaction->handleEvent($this->destroyed($world, $fallen->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not yours');

                $world = new TestWorld();
                [, $reaction, $fallen] = $this->scene($world, false);
                $reaction->handleEvent($this->destroyed($world, $fallen->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wounds');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'not a zealot');
            },
        ];
    }
}
