<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03073;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;

class Card_03073_Test extends TestCase
{
    public function name(): string
    {
        return '_03073 Victorious';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral glory risk' => function () {
                $card = new _03073();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasManeuvers::class, $card, 'maneuvers');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::true($card->DashedRiposte, 'dashed riposte');
                Assert::same(2, $card->Parry, 'parry');
                Assert::same(3, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Virtue'), 'Virtue');
                Assert::true($card->hasTrait('Glory'), 'Glory');
                Assert::true($card->hasTrait('Triumph'), 'Triumph');
                Assert::instanceOf(Maneuver_03073::class, $card->getManeuvers()[0], 'maneuver');
            },

            // WHY: Draw only from the dueling line, during a duel, when the destroyed character
            // is the adversary. Challenger and defender ids are the seats, not the round actor.
            'draws after your adversary is destroyed' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03073(), Game::LOCATION_HAND, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelActor = $challenger;
                $world->theah->duelOpponent = $defender;
                $world->game->globals->set(Game::IN_DUEL, true);
                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $defender->Id;

                $world->fireOn($risk, $destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'not in the line');

                $risk->Location = Game::LOCATION_DUELING_LINE;
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->fireOn($risk, $destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'not in a duel');

                $world->game->globals->set(Game::IN_DUEL, true);
                $destroyed->characterId = $challenger->Id;
                $world->fireOn($risk, $destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'your participant');

                $destroyed->characterId = $defender->Id;
                $world->fireOn($risk, $destroyed);
                Assert::same(1, $world->theah->queuedOfType(EventCardDrawn::class)[0]->playerId, 'you draw');

                $world->theah->queuedEvents = [];
                $world->theah->duelChallengerIdOverride = $defender->Id;
                $risk->ControllerId = 2;
                $destroyed->characterId = $challenger->Id;
                $world->theah->duelOpponent = $challenger;
                $world->fireOn($risk, $destroyed);
                Assert::same(2, $world->theah->queuedOfType(EventCardDrawn::class)[0]->playerId, 'defender seat');
            },
        ];
    }
}
