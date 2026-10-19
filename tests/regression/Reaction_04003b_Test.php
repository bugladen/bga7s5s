<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04003b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04003b_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04003b';
    }

    public function tests(): array
    {
        return [
            // WHY: sourceId is the card whose ability fired; performerId is who performed it.
            // Either matching Desideria is enough. She must be in the city. Engaged does not hide this one.
            'offers after she performs a sorcerer ability, then wounds her to draw' => function () {
                $world = new TestWorld();
                $desideria = $world->placeCharacter(new _04003(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Reaction_04003b $reaction */
                $reaction = $desideria->getReactions()[1];
                $other = $world->placeCharacter(new GenericCharacter('Other', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);

                $played = new EventSorcererAbilityPlayed();
                $played->sourceId = $other->Id;
                $played->performerId = $other->Id;
                $played->theah = $world->theah;
                $reaction->handleEvent($played);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'someone else');

                $played->performerId = $desideria->Id;
                $reaction->handleEvent($played);
                Assert::same($reaction->Id, $world->theah->queuedOfType(EventTransition::class)[0]->internalId, 'she performed it');

                $reaction->performReaction($world->game, 0, '', 'woundAndDraw');
                Assert::same($desideria->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wound');
                Assert::same(1, $world->theah->queuedOfType(EventCardDrawn::class)[0]->playerId, 'draw');
                Assert::true($reaction->Used, 'used');

                $desideria->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($played);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'home');
            },
        ];
    }
}
