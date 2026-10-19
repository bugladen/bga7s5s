<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04026;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class Technique_04026_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04026';
    }

    public function tests(): array
    {
        return [
            // WHY: Engage is the cost, so a temporary or effects-only copy must not
            // engage the attachment. +1 Parry is applied on calculate. The bearer must
            // be the duel actor and the attachment must be en garde.
            'engages the attachment for one parry, unless the technique is a copy' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $host = $world->placeCharacter(new GenericCharacter('Envoy', ['Diplomat']), Game::LOCATION_CITY_DOCKS, 1);
                $pompon = $world->placeCard(new _04026(), Game::LOCATION_CITY_DOCKS, 1);
                $pompon->AttachedToId = $host->Id;
                /** @var Technique_04026 $technique */
                $technique = $pompon->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no actor');
                $world->theah->duelActor = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'other actor');
                $world->theah->duelActor = $host;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'bearer acting');
                $pompon->Engaged = true;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'attachment engaged');
                $pompon->Engaged = false;

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->playerId = 1;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                Assert::same($pompon->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engages pompon');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->parry, 'parry');

                $world->theah->takeQueuedEvents();
                $technique->IsTemporaryCopy = true;
                $technique->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'temporary copy');
                $technique->IsTemporaryCopy = false;
                $technique->IsEffectsOnlyCopy = true;
                $technique->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'effects-only copy');
            },
        ];
    }
}
