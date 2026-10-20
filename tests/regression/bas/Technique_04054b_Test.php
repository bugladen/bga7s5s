<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04054b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_04054b_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04054b';
    }

    public function tests(): array
    {
        return [
            // WHY: In a duel, the sabre is en garde, and the bearer is the actor.
            // Resolve engages the sabre. A temporary or effects-only copy does not.
            'engages the sabre and adds one riposte' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $sabre = $world->placeCard(new _04054(), Game::LOCATION_CITY_DOCKS, 1);
                $bearer = $world->placeCharacter(new GenericCharacter('Bearer'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $sabre->AttachedToId = $bearer->Id;
                /** @var Technique_04054b $technique */
                $technique = $sabre->getTechniques()[1];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no actor');

                $world->theah->duelActor = $foe;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'foe is acting');
                $world->theah->duelActor = $bearer;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'bearer');

                $sabre->Engaged = true;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'sabre engaged');
                $sabre->Engaged = false;

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->riposte = 1;
                $world->fireOn($sabre, $calc);
                Assert::same(2, $calc->riposte, '+1 riposte');

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->playerId = 1;
                $world->fireOn($sabre, $resolve);
                Assert::same($sabre->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage cost');

                $world->theah->takeQueuedEvents();
                $technique->IsTemporaryCopy = true;
                $world->fireOn($sabre, $resolve);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'temporary copy');

                $technique->IsTemporaryCopy = false;
                $technique->IsEffectsOnlyCopy = true;
                $world->fireOn($sabre, $resolve);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'effects only');
            },
        ];
    }
}
