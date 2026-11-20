<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Serenissima;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Serenissima;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_05Serenissima_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_05Serenissima';
    }

    public function tests(): array
    {
        return [
            // WHY: Not Covert — only duel + actor + nonempty hand for the discard cost.
            'is only offered in a duel when Patricia is the actor with a hand card' => function () {
                $world = new TestWorld();
                $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $hand = $world->placeCard(new _04039(), Game::LOCATION_HAND, 1);
                /** @var Technique_05Serenissima $technique */
                $technique = $patricia->getTechniques()[0];

                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $patricia;

                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'baseline');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'controlled still legal (not Covert)');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $world->theah->duelActor = $foe;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not actor');
                $world->theah->duelActor = $patricia;

                $hand->Location = $world->game->getPlayerDiscardDeckName(1);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'empty hand');
                $hand->Location = Game::LOCATION_HAND;

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'queues the hand discard picker on Resolve and adds +1 Parry on Calculate' => function () {
                $world = new TestWorld();
                $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Technique_05Serenissima $technique */
                $technique = $patricia->getTechniques()[0];

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                Assert::same('05Serenissima', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->parry = 0;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->parry, '+1 Parry');
            },

            'discards the chosen hand card and a random adversary hand card' => function () {
                $world = new TestWorld();
                $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $own = $world->placeCard(new _04039(), Game::LOCATION_HAND, 1);
                $own->OwnerId = 1;
                $theirs = $world->placeCard(new _04039(), Game::LOCATION_HAND, 2);
                $theirs->OwnerId = 2;
                /** @var Technique_05Serenissima $technique */
                $technique = $patricia->getTechniques()[0];

                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $patricia;
                $world->theah->duelOpponent = $foe;

                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_05SERENISSIMA, '', $own->Id);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(2, $discards, 'own + random adversary');
                $discardIds = array_map(fn($e) => $e->cardId, $discards);
                Assert::true(in_array($own->Id, $discardIds, true), 'own discarded');
                Assert::true(in_array($theirs->Id, $discardIds, true), 'adversary discarded');
            },

            'skips adversary discard when their hand is empty' => function () {
                $world = new TestWorld();
                $patricia = $world->placeCharacter(new _05Serenissima(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $own = $world->placeCard(new _04039(), Game::LOCATION_HAND, 1);
                $own->OwnerId = 1;
                /** @var Technique_05Serenissima $technique */
                $technique = $patricia->getTechniques()[0];

                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $patricia;
                $world->theah->duelOpponent = $foe;

                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_05SERENISSIMA, '', $own->Id);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'own only');
                Assert::same($own->Id, $discards[0]->cardId, 'own');
            },
        ];
    }
}
