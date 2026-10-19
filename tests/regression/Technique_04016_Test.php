<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04016;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class Technique_04016_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04016';
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling technique. IsActive waits for the equipped character's own
            // end of round. Another actor's end of round leaves it armed. Cancel and
            // duel end clear it without adding threat.
            'each participant gains a threat at the end of the equipped character round' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                $card = $world->placeCard(new _04016(), Game::LOCATION_CITY_DOCKS, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $card->AttachedToId = $host->Id;
                $world->theah->duelActor = $host;
                /** @var Technique_04016 $technique */
                $technique = $card->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'gambled and acting');
                $world->game->globals->set(Game::DUEL_GAMBLED, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'did not gamble');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                Assert::true($technique->IsActive, 'armed');
                Assert::false($technique->Used, 'resolve does not spend it');

                $otherRound = new EventDuelEndOfRound();
                $otherRound->actorId = $foe->Id;
                $otherRound->theah = $world->theah;
                $technique->handleEvent($otherRound);
                Assert::true($technique->IsActive, 'not their round');
                Assert::count(0, $world->theah->queuedOfType(EventThreatModified::class), 'no threat yet');

                $ownRound = new EventDuelEndOfRound();
                $ownRound->actorId = $host->Id;
                $ownRound->theah = $world->theah;
                $technique->handleEvent($ownRound);
                $threat = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::same(1, $threat->challengerThreat, 'challenger');
                Assert::same(1, $threat->defenderThreat, 'defender');
                Assert::false($technique->IsActive, 'cleared');

                $technique->IsActive = true;
                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = $technique->Id;
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);
                Assert::false($technique->IsActive, 'cancel');

                $technique->IsActive = true;
                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $technique->handleEvent($end);
                Assert::false($technique->IsActive, 'duel end');
            },
        ];
    }
}
