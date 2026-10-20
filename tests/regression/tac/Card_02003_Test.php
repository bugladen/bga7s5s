<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02003;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;

class Card_02003_Test extends TestCase
{
    public function name(): string
    {
        return '_02003 Mourad';
    }

    public function tests(): array
    {
        return [
            'constructs Vodacce Bodyguard with Reaction and Technique' => function () {
                $mourad = new _02003();
                Assert::instanceOf(Character::class, $mourad, 'Character');
                Assert::instanceOf(IHasReactions::class, $mourad, 'reactions');
                Assert::instanceOf(IHasTechniques::class, $mourad, 'techniques');
                Assert::same(6, $mourad->Resolve, 'Resolve');
                Assert::same(3, $mourad->Combat, 'Combat');
                Assert::same(1, $mourad->Finesse, 'Finesse');
                Assert::same(1, $mourad->Influence, 'Influence');
                Assert::true($mourad->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($mourad->hasTrait('Bodyguard'), 'Bodyguard');
                Assert::true($mourad->hasTrait('Red Hand'), 'Red Hand');
                Assert::true($mourad->hasFaction('Vodacce'), 'Vodacce');
                Assert::instanceOf(Reaction_02003::class, $mourad->getReactions()[0], 'Reaction');
                Assert::instanceOf(Technique_02003::class, $mourad->getTechniques()[0], 'Technique');
            },

            'owns composites when placed' => function () {
                $world = new TestWorld();
                $mourad = $world->placeCharacter(new _02003(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($mourad->Id, $mourad->getReactions()[0]->OwnerId, 'reaction');
                Assert::same($mourad->Id, $mourad->getTechniques()[0]->OwnerId, 'technique');
            },

            // WHY: printed "Sorcerer abilities cannot target Mourad" — eventCheck throws
            // on EventSorcererAbilityStart with targetId = Mourad.
            'eventCheck throws when Sorcerer ability targets Mourad' => function () {
                $world = new TestWorld();
                $mourad = $world->placeCharacter(new _02003(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventSorcererAbilityStart();
                $event->targetId = $mourad->Id;
                $event->theah = $world->theah;

                $threw = false;
                try {
                    $mourad->eventCheck($event);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'blocked');
            },

            'eventCheck allows Sorcerer ability targeting someone else' => function () {
                $world = new TestWorld();
                $mourad = $world->placeCharacter(new _02003(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventSorcererAbilityStart();
                $event->targetId = 999;
                $event->theah = $world->theah;

                $mourad->eventCheck($event);
                Assert::true(true, 'no throw');
            },
        ];
    }
}
