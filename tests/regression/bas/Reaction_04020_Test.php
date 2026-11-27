<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04020;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04020;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04020_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04020';
    }

    public function tests(): array
    {
        return [
            // WHY: The risk must be in hand. The performer is en garde at a location
            // adjacent to the pressure, with a ranged weapon. Use pays before the -1.
            // One opponent is applied immediately once pay resolves. Pass does not spend it.
            'offers beside an adjacent pressure, then marks that opponent' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::PLAYER_COUNT, 2);
                $risk = $world->placeCard(new _04020(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $bow = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 1);
                $bow->AttachedToId = $performer->Id;
                $performer->Attachments = [$bow->Id];
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                /** @var Reaction_04020 $reaction */
                $reaction = $risk->getReactions()[0];

                $pressure = new EventPressureOccuring();
                $pressure->location = Game::LOCATION_CITY_FORUM;
                $pressure->theah = $world->theah;
                $reaction->handleEvent($pressure);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');
                Assert::same($performer->Id, $reaction->performerId ?? 0, 'first qualifying performer');

                $reaction->performReaction($world->game, 0, '', 'use');
                Assert::false($reaction->Used, 'pay comes first');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType ?? $world->theah->queuedOfType(EventEnteringPayState::class)[0]->type ?? null, 'in-hand reaction pay');
                Assert::same('pay', $world->theah->queuedOfType(EventTransition::class)[1]->transition, 'pay transition');

                $world->theah->takeQueuedEvents();
                $paid = new EventRiskReactionTriggered();
                $paid->internalId = $reaction->Id;
                $paid->theah = $world->theah;
                $reaction->handleEvent($paid);
                Assert::same(Game::VANTAGE_POINT_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'penalty flag');
                Assert::same($foe->ControllerId, $world->game->globals->get(Game::VANTAGE_POINT_PLAYER_ID), 'that opponent');

                $performer->Engaged = true;
                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($pressure);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'engaged performer');

                $risk->Location = 'Discard-1';
                $performer->Engaged = false;
                $reaction->handleEvent($pressure);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not in hand');
            },

            // WHY: Character pick used to call resolveTargetCharacter inline — Cloud Bottle
            // never saw a targeting hook. Act queues ONLY CharacterTargeted; survive continues.
            'character pick queues CharacterTargeted only; survive opens engage-or-wound' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::PLAYER_COUNT, 2);
                $risk = $world->placeCard(new _04020(), Game::LOCATION_HAND, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                /** @var Reaction_04020 $reaction */
                $reaction = $risk->getReactions()[0];
                $reaction->stage = 'chooseCharacter';
                $reaction->pressureLocation = Game::LOCATION_CITY_FORUM;
                $reaction->performerId = 1;

                $reaction->performReaction($world->game, 0, '', "character-{$foe->Id}");

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'targeting hook');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($reaction->Id, $targeted[0]->abilityId, 'ability');
                Assert::same('chooseCharacter', $reaction->stage, 'not yet engageOrWound');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound beside pick');

                $world->theah->takeQueuedEvents();
                $hook = $targeted[0];
                $hook->canceled = false;
                $hook->theah = $world->theah;
                $reaction->handleEvent($hook);

                Assert::same('engageOrWound', $reaction->stage, 'survived');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'opponent choice');
                Assert::same($foe->ControllerId, $transitions[0]->playerId, 'target controller');
                Assert::same($hook->batchId, $transitions[0]->batchId, 'shared batch');
            },

            'canceled CharacterTargeted does not open engage-or-wound' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04020(), Game::LOCATION_HAND, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                /** @var Reaction_04020 $reaction */
                $reaction = $risk->getReactions()[0];
                $reaction->stage = 'chooseCharacter';
                $reaction->pressureLocation = Game::LOCATION_CITY_FORUM;
                $reaction->targetCharacterId = $foe->Id;

                $hook = new EventCharacterTargeted();
                $hook->playerId = 1;
                $hook->targetId = $foe->Id;
                $hook->sourceId = $risk->Id;
                $hook->abilityId = $reaction->Id;
                $hook->batchId = 9;
                $hook->canceled = true;
                $hook->theah = $world->theah;
                $reaction->handleEvent($hook);

                Assert::same('chooseCharacter', $reaction->stage, 'stays');
                Assert::count(0, $world->theah->queuedEvents, 'no continue');
            },

            'surviving CharacterTargeted auto-wounds an already-engaged target' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04020(), Game::LOCATION_HAND, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $foe->Engaged = true;
                /** @var Reaction_04020 $reaction */
                $reaction = $risk->getReactions()[0];
                $reaction->stage = 'chooseCharacter';
                $reaction->pressureLocation = Game::LOCATION_CITY_FORUM;
                $reaction->performerId = 1;
                $reaction->targetCharacterId = $foe->Id;

                $hook = new EventCharacterTargeted();
                $hook->playerId = 1;
                $hook->targetId = $foe->Id;
                $hook->sourceId = $risk->Id;
                $hook->abilityId = $reaction->Id;
                $hook->batchId = 11;
                $hook->theah = $world->theah;
                $reaction->handleEvent($hook);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'auto wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
                Assert::same(11, $wounds[0]->batchId, 'shared batch');
                Assert::true($reaction->Used, 'finalized');
            },
        ];
    }
}
