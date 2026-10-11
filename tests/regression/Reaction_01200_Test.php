<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01098;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01200;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01200;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerGainsReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01200_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01200';
    }

    /**
     * Crystal Eye equipped; ChosenCard set to $chosenId (0 = none yet).
     *
     * @return array{0:_01200,1:Reaction_01200,2:GenericCharacter}
     */
    private function scene(TestWorld $world, int $chosenId = 0): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var _01200 $eye */
        $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
        $eye->AttachedToId = $host->Id;
        $host->Attachments[] = $eye->Id;
        $eye->ChosenCard = $chosenId;
        /** @var Reaction_01200 $reaction */
        $reaction = $eye->getReactions()[0];
        return [$eye, $reaction, $host];
    }

    public function tests(): array
    {
        return [
            'is an AttachmentReaction' => function () {
                Assert::instanceOf(AttachmentReaction::class, new Reaction_01200(), 'AttachmentReaction');
            },

            'buttons offer Gain Renown and Pass' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['gainReknown', 'pass'], $ids, 'buttons');
            },

            // WHY (journal 2026-04-07-08): description must not fatal when ChosenCard is unset.
            'description falls back when ChosenCard is missing' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world, 0);
                $desc = $reaction->getReactionDescription($world->theah);
                Assert::contains('?', $desc, 'fallback name');
            },

            'offers on ApproachCharacterPlayed of the chosen card' => function () {
                $world = new TestWorld();
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_APPROACH, 2);
                [$eye, $reaction] = $this->scene($world, $target->Id);

                $event = new EventApproachCharacterPlayed();
                $event->characterId = $target->Id;
                $event->playerId = 2;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($eye->Id, $transitions[0]->sourceId, 'eye');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
            },

            'offers on CharacterMustered of the chosen card' => function () {
                $world = new TestWorld();
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_HAND, 2);
                [, $reaction] = $this->scene($world, $target->Id);

                $event = new EventCharacterMustered();
                $event->characterId = $target->Id;
                $event->playerId = 2;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'muster');
            },

            'offers on SchemeCardRevealed of the chosen scheme' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01098(), Game::LOCATION_APPROACH, 2);
                [, $reaction] = $this->scene($world, $scheme->Id);

                $event = new EventSchemeCardRevealed();
                $event->schemeId = $scheme->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'scheme');
            },

            'does not offer for a different played character' => function () {
                $world = new TestWorld();
                $chosen = $world->placeCharacter(new GenericCharacter('Chosen'), Game::LOCATION_APPROACH, 2);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_APPROACH, 2);
                [, $reaction] = $this->scene($world, $chosen->Id);

                $event = new EventApproachCharacterPlayed();
                $event->characterId = $other->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'wrong card');
            },

            'does not offer when unattached' => function () {
                $world = new TestWorld();
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_APPROACH, 2);
                [$eye, $reaction] = $this->scene($world, $target->Id);
                $eye->AttachedToId = 0;

                $event = new EventApproachCharacterPlayed();
                $event->characterId = $target->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'unattached');
            },

            'does not offer once Used' => function () {
                $world = new TestWorld();
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_APPROACH, 2);
                [, $reaction] = $this->scene($world, $target->Id);
                $reaction->Used = true;

                $event = new EventApproachCharacterPlayed();
                $event->characterId = $target->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'gainReknown queues PlayerGainsReknown and marks Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $world->game->activePlayerId = 1;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'gainReknown');

                $gains = $world->theah->queuedOfType(EventPlayerGainsReknown::class);
                Assert::count(1, $gains, 'renown');
                Assert::same(1, $gains[0]->playerId, 'player');
                Assert::same(1, $gains[0]->amount, 'one');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass finishes without Renown or Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventPlayerGainsReknown::class), 'no renown');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
