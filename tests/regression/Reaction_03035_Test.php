<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03035;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03035;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03035_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03035';
    }

    private function pressure(TestWorld $world): EventPressureOccuring
    {
        $event = new EventPressureOccuring();
        $event->theah = $world->theah;
        $event->location = Game::LOCATION_CITY_DOCKS;
        $event->playerId = 1;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: Strictly more non-Mercenaries than every opponent. Mercenaries and
            // uncontrolled characters do not count. A tie is not more. The flag is stored
            // for pressureLocation to read later; this reaction does not add the Renown itself.
            'adds the Loyal pressure flag when you have strictly more non-Mercenaries' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03035(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Mine2'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $merc = $world->placeCharacter(new GenericCharacter('Merc', ['Mercenary', 'Hero']), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Wild'), Game::LOCATION_CITY_DOCKS, 0);
                /** @var Reaction_03035 $reaction */
                $reaction = $risk->getReactions()[0];

                $reaction->handleEvent($this->pressure($world));
                Assert::same(['use', 'pass'], array_column($reaction->getReactionButtonProperties($world->theah), 'reaction'), 'play or pass');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventEnteringPayState::class), 'pass does not pay');
                Assert::false($reaction->Used, 'pass');

                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];
                $reaction->performReaction($world->game, 0, $reaction->Id, 'use');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'pay');

                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                Assert::true($world->game->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::LOYAL_PRESSURE_TYPE), 'loyal flag');
                Assert::same(1, $world->game->globals->get(Game::LOYAL_PLAYER_ID), 'controller');
                Assert::true($reaction->Used, 'spent');

                $reaction->Used = false;
                $merc->removeTrait($world->game, 'Mercenary');
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($this->pressure($world));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'tie once the mercenary counts');
            },

            'zero non-Mercenaries, a tie, and a risk outside the hand do not offer' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03035(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('OnlyMerc', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_03035 $reaction */
                $reaction = $risk->getReactions()[0];
                $reaction->handleEvent($this->pressure($world));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no non-mercs');

                $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $reaction->handleEvent($this->pressure($world));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'tie');

                $world->placeCharacter(new GenericCharacter('Mine2'), Game::LOCATION_CITY_DOCKS, 1);
                $risk->Location = Game::LOCATION_CITY_DOCKS;
                $reaction->handleEvent($this->pressure($world));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not in hand');
            },
        ];
    }
}
