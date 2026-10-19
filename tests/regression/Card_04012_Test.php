<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;

class Card_04012_Test extends TestCase
{
    public function name(): string
    {
        return '_04012 Raven';
    }

    public function tests(): array
    {
        return [
            'is an Eisen hunter' => function () {
                $card = new _04012();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(3, $card->Finesse, 'finesse');
                Assert::true($card->hasTrait('Hunter'), 'Hunter');
                Assert::true($card->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::count(1, $card->getActions(), 'action');
            },

            // WHY: Only the adversary's maneuvers are blocked, and only while she is a
            // duel participant with a Ranged card in her own dueling line.
            'a ranged card in her line blocks the adversary maneuver' => function () {
                $world = new TestWorld();
                $raven = $world->placeCharacter(new _04012(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ranged = $world->placeCard(new _04017(), Game::LOCATION_DUELING_LINE, 1);
                $world->theah->duelActor = $raven;
                $world->theah->duelOpponent = $foe;

                $theirs = new EventResolveManeuver();
                $theirs->adversaryId = $raven->Id;
                $theirs->theah = $world->theah;
                $threw = false;
                try {
                    $raven->eventCheck($theirs);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'adversary blocked');
                Assert::true($ranged->hasTrait('Ranged'), 'the line card');

                $hers = new EventResolveManeuver();
                $hers->adversaryId = $foe->Id;
                $hers->theah = $world->theah;
                $raven->eventCheck($hers);

                $ranged->Location = Game::LOCATION_HAND;
                $raven->eventCheck($theirs);

                $world->theah->duelChallengerIdOverride = $foe->Id;
                $world->theah->duelOpponent = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);
                $ranged->Location = Game::LOCATION_DUELING_LINE;
                $raven->eventCheck($theirs);
            },
        ];
    }
}
