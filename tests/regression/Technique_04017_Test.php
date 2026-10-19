<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_04017_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04017';
    }

    public function tests(): array
    {
        return [
            // WHY: Engage is the cost, so a copy skips it. Outside a duel there is no
            // round actor. Discard waits for an accepted challenge and only fires for an
            // Academic or Hunter whose adversary has a hand. Threat is +1 even on refuse.
            // Preview queues neither discard nor the ranged event.
            'engages for thrust, and the discard waits for an accepted challenge' => function () {
                $world = new TestWorld();
                $bow = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 1);
                $host = $world->placeCharacter(new GenericCharacter('Hunter', ['Hunter']), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $bow->AttachedToId = $host->Id;
                $world->placeCard(new _04006(), Game::LOCATION_HAND, 2);
                /** @var Technique_04017 $technique */
                $technique = $bow->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'challenge, bow ready');
                $bow->Engaged = true;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'engaged');
                $bow->Engaged = false;

                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not the actor');
                $world->theah->duelActor = $host;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'he is acting');

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->inDuel = true;
                $resolve->playerId = 1;
                $resolve->actorId = $host->Id;
                $resolve->adversaryId = $foe->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                Assert::same($bow->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage cost');
                Assert::same('04017', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'adversary discards');
                Assert::same(2, $world->theah->queuedOfType(EventTransition::class)[0]->playerId, 'adversary chooses');

                $technique->IsEffectsOnlyCopy = true;
                $world->theah->takeQueuedEvents();
                $technique->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'a copy does not engage');
                Assert::same('04017', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'a copy still discards in a duel');
                $world->theah->takeQueuedEvents();

                $preview = new EventGenerateChallengeThreat();
                $preview->techniqueId = $technique->Id;
                $preview->actorId = $host->Id;
                $preview->adversaryId = $foe->Id;
                $preview->preview = true;
                $preview->adversaryThreat = 0;
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);
                $preview->theah = $world->theah;
                $technique->handleEvent($preview);
                Assert::same(1, $preview->adversaryThreat, 'preview still adds threat');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'preview does not discard');
                Assert::count(0, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'preview does not play ranged');

                $refused = new EventGenerateChallengeThreat();
                $refused->techniqueId = $technique->Id;
                $refused->actorId = $host->Id;
                $refused->adversaryId = $foe->Id;
                $refused->adversaryThreat = 0;
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, false);
                $refused->theah = $world->theah;
                $technique->handleEvent($refused);
                Assert::same(1, $refused->adversaryThreat, 'refuse still wounds');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'refuse does not discard');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged still played');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->actorId = $host->Id;
                $calc->adversaryId = $foe->Id;
                $calc->thrust = 2;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                Assert::same(3, $calc->thrust, 'plus one');
            },
        ];
    }
}
