<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\RiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterPutIntoApproachDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03010_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03010';
    }

    /**
     * @return array{0:_03010,1:Reaction_03010,2:GenericCharacter}
     */
    private function scene(TestWorld $world, int $strega = 1): array
    {
        $risk = $world->placeCard(new _03010(), Game::LOCATION_HAND, 1);
        for ($i = 0; $i < $strega; $i++) {
            $location = $i === 0 ? Game::LOCATION_PLAYER_HOME : Game::LOCATION_CITY_DOCKS;
            if ($i === 0) {
                $world->placeCharacter(new _03004(), $location, 1);
            } else {
                $world->placeCharacter(new GenericCharacter('Strand', ['Strega']), $location, 1);
            }
        }
        $mustered = $world->placeCharacter(new GenericCharacter('Recruit'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_03010 $reaction */
        $reaction = $risk->getReactions()[0];
        return [$risk, $reaction, $mustered];
    }

    private function played(TestWorld $world, int $characterId, int $playerId = 2): EventApproachCharacterPlayed
    {
        $event = new EventApproachCharacterPlayed();
        $event->theah = $world->theah;
        $event->playerId = $playerId;
        $event->characterId = $characterId;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a risk reaction' => function () {
                Assert::instanceOf(RiskReaction::class, new Reaction_03010(), 'risk reaction');
            },

            // WHY: "Strega Reaction", not a City Reaction. A Strega at Home is enough.
            // The risk itself must be in hand, and the muster must be an opponent's.
            'offers after an opponent musters from an Approach deck' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $mustered] = $this->scene($world);
                $reaction->handleEvent($this->played($world, $mustered->Id));
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same(1, $transitions[0]->playerId, 'owner');
                Assert::same($risk->Id, $transitions[0]->sourceId, 'risk');
                Assert::true($risk->IsUpdated, 'target latched');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['use', 'pass'], $buttons, 'buttons');
            },

            'a CharacterMustered from Approach offers, and other origins do not' => function () {
                $world = new TestWorld();
                [, $reaction, $mustered] = $this->scene($world);
                $event = new EventCharacterMustered();
                $event->theah = $world->theah;
                $event->playerId = 2;
                $event->characterId = $mustered->Id;
                $event->fromLocation = Game::LOCATION_APPROACH;
                $reaction->handleEvent($event);
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'approach muster');

                $world->theah->takeQueuedEvents();
                $event->fromLocation = Game::LOCATION_HAND;
                $reaction->handleEvent($event);
                Assert::count(0, $world->theah->queuedEvents, 'hand muster');
            },

            'your own muster, a risk outside the hand, or no Strega in play does not offer' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $mustered] = $this->scene($world);
                $reaction->handleEvent($this->played($world, $mustered->Id, 1));
                Assert::count(0, $world->theah->queuedEvents, 'own muster');

                $risk->Location = 'Discard-1';
                $reaction->handleEvent($this->played($world, $mustered->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not in hand');
                $risk->Location = Game::LOCATION_HAND;

                foreach ($world->theah->getCharactersInPlayByPlayerId(1) as $character) {
                    $character->Location = $world->game->getPlayerLockerName(1);
                }
                $reaction->handleEvent($this->played($world, $mustered->Id));
                Assert::count(0, $world->theah->queuedEvents, 'strega not in play');
            },

            'use keeps the target and opens payment; pass forgets it' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $mustered] = $this->scene($world);
                $reaction->handleEvent($this->played($world, $mustered->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'use');
                $pay = $world->theah->queuedOfType(EventEnteringPayState::class);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same($risk->Id, $pay[0]->cardId, 'paying for the risk');
                Assert::same(Game::PAY_STATE_IN_HAND_REACTION, $pay[0]->payStateType, 'in-hand reaction');
                Assert::same($reaction->Id, $pay[0]->internalId, 'ability');
                Assert::same('pay', $transitions[0]->transition, 'pay transition');
                Assert::false($reaction->Used, 'not spent yet');

                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'pass cleared the target');
                Assert::false($reaction->Used, 'pass');
            },

            'with no other Approach character the wound is mandatory, and three Strega make it two wounds' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $mustered] = $this->scene($world, 3);
                $reaction->handleEvent($this->played($world, $mustered->Id));
                $world->theah->takeQueuedEvents();
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);

                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::same($mustered->Id, $wound[0]->characterId, 'target');
                Assert::same(2, $wound[0]->wounds, 'three Strega');
                Assert::same($risk->Id, $wound[0]->sourceId, 'source');
                Assert::true($reaction->Used, 'spent');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no choice was queued by the trigger');
            },

            'one Strega wounds once, and a replacement muster returns the original to Approach' => function () {
                $world = new TestWorld();
                [$risk, $reaction, $mustered] = $this->scene($world, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_APPROACH, 2);
                $reaction->handleEvent($this->played($world, $mustered->Id));
                $world->theah->takeQueuedEvents();

                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same(2, $transitions[0]->playerId, 'opponent chooses');
                Assert::false($reaction->Used, 'waiting');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['return', 'accept'], $buttons, 'choice');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'accept');
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::same(1, $wound[0]->wounds, 'one Strega');
                Assert::true($reaction->Used, 'spent');

                $world = new TestWorld();
                [$risk, $reaction, $mustered] = $this->scene($world, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_APPROACH, 2);
                $reaction->handleEvent($this->played($world, $mustered->Id));
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'return');
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(["muster-{$other->Id}"], $buttons, 'replacement');

                $reaction->performReaction($world->game, 0, $reaction->Id, "muster-{$other->Id}");
                $returned = $world->theah->queuedOfType(EventCharacterPutIntoApproachDeck::class);
                $muster = $world->theah->queuedOfType(EventCharacterMustered::class);
                Assert::same($mustered->Id, $returned[0]->characterId, 'returned');
                Assert::same(2, $returned[0]->playerId, 'opponent');
                Assert::same($other->Id, $muster[0]->characterId, 'replacement');
                Assert::same(Game::LOCATION_PLAYER_HOME, $muster[0]->location, 'home');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::true($reaction->Used, 'spent');
                unset($risk);
            },

            'an invalid replacement throws' => function () {
                $world = new TestWorld();
                [, $reaction, $mustered] = $this->scene($world);
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_APPROACH, 2);
                $reaction->handleEvent($this->played($world, $mustered->Id));
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'return');
                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, $reaction->Id, 'muster-999');
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'unknown character');
                Assert::false($reaction->Used, 'not spent');
            },
        ];
    }
}
