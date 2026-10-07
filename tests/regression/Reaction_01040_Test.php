<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01048;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01040;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01040_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01040';
    }

    private function armRena(TestWorld $world): array
    {
        $rena = $world->placeCharacter(new _01040(), Game::LOCATION_CITY_DOCKS, 1);
        $weapon = $world->placeCard(new _01048(), Game::LOCATION_CITY_DOCKS, 1);
        $weapon->Engaged = false;
        $rena->Attachments[] = $weapon->Id;
        return [$rena, $weapon];
    }

    public function tests(): array
    {
        return [
            'offers when Rena intervenes with engarde Weapon' => function () {
                $world = new TestWorld();
                [$rena] = $this->armRena($world);

                /** @var Reaction_01040 $reaction */
                $reaction = $rena->getReactions()[0];
                $event = new EventCharacterIntervened();
                $event->playerId = 1;
                $event->newTargetId = $rena->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            'does not offer without engarde Weapon' => function () {
                $world = new TestWorld();
                $rena = $world->placeCharacter(new _01040(), Game::LOCATION_CITY_DOCKS, 1);

                /** @var Reaction_01040 $reaction */
                $reaction = $rena->getReactions()[0];
                $event = new EventCharacterIntervened();
                $event->playerId = 1;
                $event->newTargetId = $rena->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'no weapon');
            },

            'engage weapon choice queues CardEngaged on weapon' => function () {
                $world = new TestWorld();
                [$rena, $weapon] = $this->armRena($world);
                /** @var Reaction_01040 $reaction */
                $reaction = $rena->getReactions()[0];

                $reaction->performReaction($world->game, 0, $reaction->Id, 'engageWeapon-' . $weapon->Id);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($weapon->Id, $engages[0]->cardId, 'weapon');
                Assert::false($reaction->Used, 'always available — not marked used');
            },

            'decline engages Rena herself' => function () {
                $world = new TestWorld();
                [$rena] = $this->armRena($world);
                /** @var Reaction_01040 $reaction */
                $reaction = $rena->getReactions()[0];

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::same($rena->Id, $engages[0]->cardId, 'rena');
            },
        ];
    }
}
