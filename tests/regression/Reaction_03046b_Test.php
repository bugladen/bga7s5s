<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03046;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03046b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRiskReactionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03046b_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03046b';
    }

    public function tests(): array
    {
        return [
            // WHY: There is no EventChallengeAccepted on the intervene path. The performer
            // is the engaged Pirate stored in CHOSEN_PERFORMER. An en-garde challenger is
            // not offered, because Engarde would do nothing.
            'en gardes your engaged Pirate after the adversary intervenes' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03046(), Game::LOCATION_HAND, 1);
                $pirate = $world->placeCharacter(new GenericCharacter('Pirate', ['Pirate']), Game::LOCATION_CITY_DOCKS, 1);
                $pirate->Engaged = true;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);
                /** @var Reaction_03046b $reaction */
                $reaction = $risk->getReactions()[1];
                $intervened = new EventCharacterIntervened();
                $intervened->theah = $world->theah;
                $intervened->newTargetId = 999;
                $reaction->handleEvent($intervened);
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'use');
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                Assert::same($pirate->Id, $world->theah->queuedOfType(EventCardEngarded::class)[0]->cardId, 'the pirate, not the intervener');
                Assert::true($reaction->Used, 'spent');
            },

            'a ready Pirate, a non-Pirate, and pass do not en garde' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03046(), Game::LOCATION_HAND, 1);
                $pirate = $world->placeCharacter(new GenericCharacter('Pirate', ['Pirate']), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $pirate->Id);
                /** @var Reaction_03046b $reaction */
                $reaction = $risk->getReactions()[1];
                $intervened = new EventCharacterIntervened();
                $intervened->theah = $world->theah;
                $reaction->handleEvent($intervened);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not engaged');

                $pirate->Engaged = true;
                $pirate->addTrait($world->game, 'Hero', true);
                $pirate->removeTrait($world->game, 'Pirate');
                $reaction->handleEvent($intervened);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not a pirate');

                $pirate->addTrait($world->game, 'Pirate', true);
                $reaction->handleEvent($intervened);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                $triggered = new EventRiskReactionTriggered();
                $triggered->theah = $world->theah;
                $triggered->internalId = $reaction->Id;
                $reaction->handleEvent($triggered);
                Assert::false($reaction->Used, 'pass forgot the performer');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngarded::class), 'no engarde');
            },
        ];
    }
}
