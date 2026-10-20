<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04055a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_04055a_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04055a';
    }

    public function tests(): array
    {
        return [
            // WHY: Engage is the cost, so an engaged shield is hidden and a copy does not
            // engage the host. The bearer must be the actor.
            'engages the shield and adds one parry' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $shield = $world->placeCard(new _04055(), Game::LOCATION_CITY_DOCKS, 1);
                $bearer = $world->placeCharacter(new GenericCharacter('Bearer'), Game::LOCATION_CITY_DOCKS, 1);
                $shield->AttachedToId = $bearer->Id;
                $world->theah->duelActor = $bearer;
                /** @var Technique_04055a $technique */
                $technique = $shield->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'en garde bearer');

                $shield->Engaged = true;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'engaged');
                $shield->Engaged = false;

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->parry = 2;
                $world->fireOn($shield, $calc);
                Assert::same(3, $calc->parry, '+1 parry');

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->playerId = 1;
                $world->fireOn($shield, $resolve);
                Assert::same($shield->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage cost');

                $world->theah->takeQueuedEvents();
                $technique->IsEffectsOnlyCopy = true;
                $world->fireOn($shield, $resolve);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'effects only');
            },
        ];
    }
}
