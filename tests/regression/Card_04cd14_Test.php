<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd14;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneThrust;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_04cd14_Test extends TestCase
{
    public function name(): string
    {
        return '_04cd14 Millstone Rhud';
    }

    public function tests(): array
    {
        return [
            'is a negotiable berserker with two distinct thrust techniques and dashed influence' => function () {
                $card = new _04cd14();
                Assert::instanceOf(CityCharacter::class, $card, 'CityCharacter');
                Assert::same(14, $card->CityCardNumber, 'city number');
                Assert::true($card->Negotiable, 'negotiable');
                Assert::true($card->DashedInfluence, 'dashed influence');
                Assert::same(0, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Berserker'), 'Berserker');
                $techniques = $card->getTechniques();
                Assert::count(2, $techniques, 'two techniques');
                Assert::instanceOf(Technique_PlusOneThrust::class, $techniques[0], 'thrust');
                Assert::same('Technique_04cd14_1', $techniques[0]->ClassId, 'first id');
                Assert::same('Technique_04cd14_2', $techniques[1]->ClassId, 'second id');
            },

            // WHY: The opposing wound only opens when a controlled opponent is at his
            // location. The self-wound always happens. Uncontrolled neighbors do not count.
            'recruit wounds him and opens a picker only when an opponent is here' => function () {
                $world = new TestWorld();
                $mill = $world->placeCharacter(new _04cd14(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Uncontrolled'), Game::LOCATION_CITY_DOCKS, 0);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $recruited = new EventCharacterRecruited();
                $recruited->characterId = $mill->Id;
                $recruited->playerId = 1;
                $world->fireOn($mill, $recruited);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'only himself');
                Assert::same($mill->Id, $wounds[0]->characterId, 'self');
                Assert::same((string) $mill->Id, $wounds[0]->abilityId, 'card id');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no picker');

                $world->theah->queuedEvents = [];
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 2);
                $world->fireOn($mill, $recruited);
                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'self again');
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('04cd14', $transition->transition, 'picker');
                Assert::same(1, $transition->playerId, 'his controller');

                $args = $mill->argsFromCard($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD14, '', '');
                Assert::same([$foe->Id], $args['ids'], 'only the foe here');
            },

            'recruiting someone else does nothing' => function () {
                $world = new TestWorld();
                $mill = $world->placeCharacter(new _04cd14(), Game::LOCATION_CITY_DOCKS, 1);
                $recruited = new EventCharacterRecruited();
                $recruited->characterId = 999;
                $world->fireOn($mill, $recruited);
                Assert::count(0, $world->theah->queuedEvents, 'silent');
            },

            'the wound target must be an opposing character here' => function () {
                $world = new TestWorld();
                $mill = $world->placeCharacter(new _04cd14(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $threw = false;
                try {
                    $mill->actFromCardWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD14, '', '', $mill->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not himself');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');

                $mill->actFromCardWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04CD14, '', '', $foe->Id);
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($foe->Id, $wound->characterId, 'foe');
                Assert::same(1, $wound->wounds, 'one');
                Assert::same(null, $world->game->gamestate->transitions[0], 'bare nextState');
            },
        ];
    }
}
