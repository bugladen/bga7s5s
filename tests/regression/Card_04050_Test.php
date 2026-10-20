<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04050;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Card_04050_Test extends TestCase
{
    public function name(): string
    {
        return '_04050 Severing Arc';
    }

    public function tests(): array
    {
        return [
            'is a unique dismemberment risk' => function () {
                $card = new _04050();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(1, $card->WealthCost, 'cost');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(3, $card->Thrust, 'thrust');
                Assert::true($card->hasTrait('Flourish'), 'Flourish');
                Assert::true($card->hasTrait('Dismemberment'), 'Dismemberment');
                Assert::true($card->hasTrait('Unique'), 'Unique');
                Assert::count(1, $card->getManeuvers(), 'maneuver');
            },

            // WHY: "After they perform" is EventResolveTechnique, in a duel, and only
            // while this card is in your dueling line. The actor must be your adversary,
            // not your own participant and not a bystander.
            'wounds the adversary after their technique resolves' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $risk = $world->placeCard(new _04050(), Game::LOCATION_DUELING_LINE, 1);
                $duelist = $world->placeCharacter(new _04043(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $bystander = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_FORUM, 2);
                $world->theah->duelActor = $duelist;
                $world->theah->duelOpponent = $foe;

                $resolved = new EventResolveTechnique();
                $resolved->inDuel = true;
                $resolved->actorId = $foe->Id;
                $world->fireOn($risk, $resolved);
                Assert::same($foe->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'adversary');
                Assert::same($risk->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->sourceId, 'this card');

                $world->theah->takeQueuedEvents();
                $resolved->actorId = $duelist->Id;
                $world->fireOn($risk, $resolved);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'your own technique');

                $resolved->actorId = $bystander->Id;
                $world->fireOn($risk, $resolved);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'not the adversary');

                $resolved->actorId = $foe->Id;
                $risk->Location = Game::LOCATION_HAND;
                $world->fireOn($risk, $resolved);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'left the dueling line');

                $risk->Location = Game::LOCATION_DUELING_LINE;
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->fireOn($risk, $resolved);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'not in a duel');
            },
        ];
    }
}
