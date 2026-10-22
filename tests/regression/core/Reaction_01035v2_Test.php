<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01035v2;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01035v2;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

final class TestMercenary_Reaction01035v2 extends CityCharacter
{
    public function __construct(bool $negotiable = true)
    {
        parent::__construct();
        $this->Name = 'Test Mercenary';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->Resolve = 3;
        $this->Combat = 1;
        $this->Finesse = 1;
        $this->Influence = 1;
        $this->WealthCost = 2;
        $this->Negotiable = $negotiable;
        $this->Traits = ['Mercenary'];
        $this->resetCard();
    }
}

class Reaction_01035v2_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01035v2';
    }

    /** @return array{0:_01035v2,1:Reaction_01035v2,2:CityCharacter} */
    private function scene(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS, bool $negotiable = true): array
    {
        $kaspar = $world->placeCharacter(new _01035v2(), $location, 1);
        $merc = $world->placeCard(new TestMercenary_Reaction01035v2($negotiable), Game::LOCATION_CITY_DOCKS, 0);
        /** @var Reaction_01035v2 $reaction */
        $reaction = $kaspar->getReactions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $kaspar->Id);
        $world->game->globals->set(Game::PERFORMER_PARLEYED, true);
        return [$kaspar, $reaction, $merc];
    }

    private function enteringPay(TestWorld $world, int $cardId, int $playerId = 1, int $payState = Game::PAY_STATE_RECRUIT_MERCENARY): EventEnteringPayState
    {
        $event = new EventEnteringPayState();
        $event->playerId = $playerId;
        $event->cardId = $cardId;
        $event->payStateType = $payState;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a card reaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_01035v2(), 'CardReaction');
            },

            'offers when Kaspar parleys a negotiable mercenary in the city' => function () {
                $world = new TestWorld();
                [$kaspar, $reaction, $merc] = $this->scene($world);
                $kaspar->Engaged = true;

                $reaction->handleEvent($this->enteringPay($world, $merc->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'this reaction');
                Assert::same($kaspar->Id, $transitions[0]->sourceId, 'Kaspar');
                Assert::same(1, $transitions[0]->playerId, 'controller');
            },

            'does not offer at Home' => function () {
                $world = new TestWorld();
                [, $reaction, $merc] = $this->scene($world, Game::LOCATION_PLAYER_HOME);

                $reaction->handleEvent($this->enteringPay($world, $merc->Id));
                Assert::count(0, $world->theah->queuedEvents, 'home');
            },

            'does not offer when he is not parleying' => function () {
                $world = new TestWorld();
                [, $reaction, $merc] = $this->scene($world);
                $world->game->globals->set(Game::PERFORMER_PARLEYED, false);

                $reaction->handleEvent($this->enteringPay($world, $merc->Id));
                Assert::count(0, $world->theah->queuedEvents, 'no parley');
            },

            'does not offer when another character is the performer' => function () {
                $world = new TestWorld();
                [, $reaction, $merc] = $this->scene($world);
                $other = $world->placeCard(new TestMercenary_Reaction01035v2(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $other->Id);

                $reaction->handleEvent($this->enteringPay($world, $merc->Id));
                Assert::count(0, $world->theah->queuedEvents, 'other performer');
            },

            'does not offer for a non-recruit payment' => function () {
                $world = new TestWorld();
                [, $reaction, $merc] = $this->scene($world);

                $reaction->handleEvent($this->enteringPay($world, $merc->Id, 1, Game::PAY_STATE_EQUIP_ATTACHMENT));
                Assert::count(0, $world->theah->queuedEvents, 'equip');
            },

            'does not offer when the mercenary is not negotiable' => function () {
                $world = new TestWorld();
                [, $reaction, $merc] = $this->scene($world, Game::LOCATION_CITY_DOCKS, false);

                $reaction->handleEvent($this->enteringPay($world, $merc->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not negotiable');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction, $merc] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->enteringPay($world, $merc->Id));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'buttons are Gain +2 Influence and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['gainInfluence', 'pass'], $ids, 'buttons');
            },

            'accepting arms the parley bonus and marks the reaction used' => function () {
                $world = new TestWorld();
                [$kaspar, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'gainInfluence');

                Assert::true($reaction->ParleyBonusArmed, 'armed');
                Assert::true($reaction->Used, 'used');
                Assert::count(1, $world->theah->queuedOfType(EventReactionUsed::class), 'used event');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                $explanations = [];
                Assert::same(4, $kaspar->getParleyDiscount($world->theah, $kaspar, true, $explanations), 'discount');
            },

            'pass does not arm the bonus or mark the reaction used' => function () {
                $world = new TestWorld();
                [$kaspar, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->ParleyBonusArmed, 'not armed');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                $explanations = [];
                Assert::same(2, $kaspar->getParleyDiscount($world->theah, $kaspar, true, $explanations), 'influence only');
            },

            'resolving the action clears the armed bonus and marks Kaspar to save' => function () {
                $world = new TestWorld();
                [$kaspar, $reaction] = $this->scene($world);
                $reaction->ParleyBonusArmed = true;

                $event = new EventActionResolved();
                $event->playerId = 1;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($reaction->ParleyBonusArmed, 'cleared');
                Assert::true($kaspar->IsUpdated, 'saved');
                $explanations = [];
                Assert::same(2, $kaspar->getParleyDiscount($world->theah, $kaspar, true, $explanations), 'bonus gone');
            },

            'ending the turn clears the armed bonus and marks Kaspar to save' => function () {
                $world = new TestWorld();
                [$kaspar, $reaction] = $this->scene($world);
                $reaction->ParleyBonusArmed = true;

                $event = new EventPlayerTurnEnd();
                $event->playerId = 1;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($reaction->ParleyBonusArmed, 'cleared');
                Assert::true($kaspar->IsUpdated, 'saved');
                $explanations = [];
                Assert::same(2, $kaspar->getParleyDiscount($world->theah, $kaspar, true, $explanations), 'bonus gone');
            },
        ];
    }
}
