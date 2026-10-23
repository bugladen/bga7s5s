<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02059;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02059;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02059_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02059';
    }

    /**
     * @return array{0:_02059,1:Reaction_02059,2:GenericCharacter,3:_02057}
     */
    private function scene(TestWorld $world): array
    {
        $risk = $world->placeCard(new _02059(), Game::LOCATION_HAND, 1);
        $character = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
        $source = $world->placeCard(new _02057(), Game::LOCATION_HAND, 2);
        /** @var Reaction_02059 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $character, $source];
    }

    private function wound(TestWorld $world, int $characterId, int $sourceId, string $abilityId): EventCharacterBeingWounded
    {
        $event = new EventCharacterBeingWounded();
        $event->characterId = $characterId;
        $event->sourceId = $sourceId;
        $event->abilityId = $abilityId;
        $event->wounds = 1;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a RiskReaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_02059(), 'RiskReaction');
            },

            // WHY: Opponent is the ability owner's controller when the ability resolves.
            // A card-level abilityId falls back to the source card's controller, and 0 is not an opponent.
            // An unresolved ability on an uncontrolled card still counts, because 0 differs from you.
            'an opponent ability wound on your character is held and offered' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $character, $source] = $this->scene($world);
                $event = $this->wound($world, $character->Id, $source->Id, $source->getManeuvers()[0]->Id);
                $reaction->handleEvent($event);

                Assert::true($event->canceled, 'held');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same($risk->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction');
                Assert::same(
                    ['ignoreWound', 'decline'],
                    array_column($reaction->getReactionButtonProperties($world->theah), 'reaction'),
                    'buttons'
                );
                Assert::contains(
                    'Mine',
                    $reaction->getReactionDescription($world->theah),
                    'prompt names the wounded character'
                );
            },

            'a second wound while one is held is left alone' => function () {
                $world = new TestWorld();
                [, $reaction, $character, $source] = $this->scene($world);
                $reaction->handleEvent($this->wound($world, $character->Id, $source->Id, $source->getManeuvers()[0]->Id));
                $second = $this->wound($world, $character->Id, $source->Id, $source->getManeuvers()[0]->Id);
                $reaction->handleEvent($second);
                Assert::false($second->canceled, 'not held');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'one offer');
            },

            'does not offer your own ability, a combat wound, a missing source, outside hand, or when used' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $character, $source] = $this->scene($world);
                $mine = $world->placeCard(new _02057(), Game::LOCATION_HAND, 1);
                $reaction->handleEvent($this->wound($world, $character->Id, $mine->Id, $mine->getManeuvers()[0]->Id));
                Assert::count(0, $world->theah->queuedEvents, 'your ability');

                $reaction->handleEvent($this->wound($world, $character->Id, $source->Id, ''));
                Assert::count(0, $world->theah->queuedEvents, 'no ability id');

                $reaction->handleEvent($this->wound($world, $character->Id, 999, 'missing'));
                Assert::count(0, $world->theah->queuedEvents, 'no source');

                $source->ControllerId = 0;
                $reaction->handleEvent($this->wound($world, $character->Id, $source->Id, (string) $source->Id));
                Assert::count(0, $world->theah->queuedEvents, 'uncontrolled card-level source');

                $source->ControllerId = 2;
                $reaction->handleEvent($this->wound($world, $character->Id, $source->Id, (string) $source->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'card-level opponent');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                $world->theah->takeQueuedEvents();

                $risk->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->wound($world, $character->Id, $source->Id, $source->getManeuvers()[0]->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not in hand');

                $risk->Location = Game::LOCATION_HAND;
                $reaction->Used = true;
                $reaction->handleEvent($this->wound($world, $character->Id, $source->Id, $source->getManeuvers()[0]->Id));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY: The ability path treats any other controller as an opponent, including 0.
            // The card-level fallback treats 0 as nobody. That case is covered above.
            'an ability owned by an uncontrolled card still counts as an opponent' => function () {
                $world = new TestWorld();
                [, $reaction, $character, $source] = $this->scene($world);
                $source->ControllerId = 0;
                $event = $this->wound($world, $character->Id, $source->Id, $source->getManeuvers()[0]->Id);
                $reaction->handleEvent($event);
                Assert::true($event->canceled, 'held');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            'ignoring the wound enters pay and the paid reaction leaves it canceled' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $character, $source] = $this->scene($world);
                $event = $this->wound($world, $character->Id, $source->Id, $source->getManeuvers()[0]->Id);
                $reaction->handleEvent($event);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'ignoreWound');
                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                Assert::same($risk->Id, $pay[0]->cardId, 'card');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $pay[0]->payStateType, 'type');
                Assert::false($reaction->Used, 'not until paid');

                $world->theah->takeQueuedEvents();
                $paid = new EventRiskReactionTriggered();
                $paid->internalId = $reaction->Id;
                $paid->theah = $world->theah;
                $reaction->handleEvent($paid);
                Assert::true($reaction->Used, 'used');
                Assert::true($event->canceled, 'wound stays canceled');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'not re-emitted');
                Assert::contains('ignore the wound on ${character_inject_code}', $world->game->notify->messages[0]['message'], 'log names who was spared');
                Assert::same($character->getInjectCode(), $world->game->notify->messages[0]['args']['character_inject_code'], 'spared character');
            },

            // WHY: Decline re-emits the clone, which was taken before cancel. skipNextEvent
            // lets that re-emit through. The following wound can be offered again.
            'decline re-emits the wound once and then listens again' => function () {
                $world = new TestWorld();
                [, $reaction, $character, $source] = $this->scene($world);
                $event = $this->wound($world, $character->Id, $source->Id, $source->getManeuvers()[0]->Id);
                $reaction->handleEvent($event);
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');
                $replay = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $replay, 're-emitted');
                Assert::false($replay[0]->canceled, 'clone was taken before cancel');
                Assert::same($character->Id, $replay[0]->characterId, 'same character');
                Assert::false($reaction->Used, 'unused');

                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($replay[0]);
                Assert::false($replay[0]->canceled, 'skip lets it through');
                Assert::count(0, $world->theah->queuedEvents, 'no second offer');

                $again = $this->wound($world, $character->Id, $source->Id, $source->getManeuvers()[0]->Id);
                $reaction->handleEvent($again);
                Assert::true($again->canceled, 'listening again');
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },
        ];
    }
}
