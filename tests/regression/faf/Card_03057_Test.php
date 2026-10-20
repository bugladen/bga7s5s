<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03057;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03057;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;

class Card_03057_Test extends TestCase
{
    public function name(): string
    {
        return '_03057 Censure';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran challenge risk' => function () {
                $card = new _03057();
                Assert::instanceOf(Risk::class, $card, 'risk');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(0, $card->WealthCost, 'wealth');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(1, $card->Thrust, 'thrust');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Challenge'), 'Challenge');
                Assert::true($card->hasTrait('Bureaucracy'), 'Bureaucracy');
                Assert::instanceOf(Action_03057::class, $card->getActions()[0], 'action');
            },

            // WHY: EventChallengeRejected has no action id. Only CENSURE_CHALLENGE_TYPE claims.
            // Home and an unclaimable city location do not.
            'a refused censure challenge claims the challenger location' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03057(), Game::LOCATION_HAND, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $rejected = new EventChallengeRejected();
                $rejected->challengerId = $challenger->Id;

                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $world->fireOn($risk, $rejected);
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'other type');

                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::CENSURE_CHALLENGE_TYPE);
                $challenger->Location = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($risk, $rejected);
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'home');

                $challenger->Location = Game::LOCATION_CITY_DOCKS;
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, false);
                $world->fireOn($risk, $rejected);
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'cannot claim');

                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_DOCKS, true);
                $world->fireOn($risk, $rejected);
                $claim = $world->theah->queuedOfType(EventLocationClaimed::class)[0];
                Assert::same(1, $claim->playerId, 'challenger controller');
                Assert::same(Game::LOCATION_CITY_DOCKS, $claim->location, 'docks');
            },
        ];
    }
}
