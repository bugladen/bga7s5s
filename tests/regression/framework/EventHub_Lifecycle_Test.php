<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericBrute;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/**
 * EventHub: destroy / locker / approach / challenge issue / wound cascade.
 */
class EventHub_Lifecycle_Test extends TestCase
{
    public function name(): string
    {
        return 'EventHub: lifecycle (destroy, locker, approach, challenge, wound)';
    }

    private function world(): TestWorld
    {
        return new TestWorld();
    }

    /** Register a recreate factory so EventHub's substr($pos+2) class strip still works for Generic*. */
    private function recreateAs(TestWorld $world, int $id, callable $factory): void
    {
        $world->game->cardRecreateById[$id] = $factory;
    }

    public function tests(): array
    {
        return [
            // ---- destroy ---------------------------------------------------------------------

            'CharacterDestroyed recreates into locker and bumps locker stat' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_FORUM, 1);
                $c->Wounds = 3;
                $c->Engaged = true;
                $this->recreateAs($world, $c->Id, fn() => new GenericCharacter('Victim'));

                $world->applyHub(EventFactory::createCharacterDestroyedEvent(1, $c->Id, 'lethal'));

                $fresh = $world->theah->getCardById($c->Id);
                Assert::same('Locker-1', $fresh->Location, 'locker');
                Assert::same(0, $fresh->Wounds, 'memory wiped');
                Assert::false($fresh->Engaged, 'en garde reset');
                Assert::same(1, $fresh->ControllerId, 'controller kept');
                Assert::same(
                    [['name' => Game::STAT_CHARACTERS_SENT_TO_LOCKER, 'delta' => 1, 'playerId' => 1]],
                    $world->game->playerStatIncs,
                    'stat'
                );
                Assert::same('characterDestroyed', $world->game->notify->messages[0]['type'], 'notify');
            },

            'CharacterDestroyed sends Brute to discard instead of locker' => function () {
                $world = $this->world();
                $brute = $world->placeCharacter(new GenericBrute('Thug'), Game::LOCATION_CITY_DOCKS, 1);
                $this->recreateAs($world, $brute->Id, fn() => new GenericBrute('Thug'));

                $world->applyHub(EventFactory::createCharacterDestroyedEvent(1, $brute->Id, 'brutal'));

                $fresh = $world->theah->getCardById($brute->Id);
                Assert::same('Discard-1', $fresh->Location, 'discard');
                Assert::count(0, $world->game->playerStatIncs, 'no locker stat for brutes');
                Assert::same('cardDiscardedFromPlay', $world->game->notify->messages[0]['type'], 'notify');
            },

            'CharacterDestroyed clears challenge markers when participant dies pre-duel' => function () {
                $world = $this->world();
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_FORUM, 1);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_FORUM, 2);
                $challenger->addCondition(Game::DUEL_CHALLENGER);
                $defender->addCondition(Game::DUEL_DEFENDER);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $defender->Id);
                $world->game->globals->set(Game::IN_DUEL, false);
                $this->recreateAs($world, $defender->Id, fn() => new GenericCharacter('Defender'));

                $world->applyHub(EventFactory::createCharacterDestroyedEvent(2, $defender->Id, 'Stiletto'));

                Assert::count(1, $world->game->clearChallengeMarkerCalls, 'cleared');
                Assert::same(
                    ['challengerId' => $challenger->Id, 'defenderId' => $defender->Id],
                    $world->game->clearChallengeMarkerCalls[0],
                    'ids'
                );
            },

            'CharacterDestroyed skips marker clear when already IN_DUEL' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Duelist'), Game::LOCATION_CITY_FORUM, 1);
                $c->addCondition(Game::DUEL_CHALLENGER);
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $c->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, 0);
                $this->recreateAs($world, $c->Id, fn() => new GenericCharacter('Duelist'));

                $world->applyHub(EventFactory::createCharacterDestroyedEvent(1, $c->Id, 'in duel'));
                Assert::count(0, $world->game->clearChallengeMarkerCalls, 'left for duel end');
            },

            // ---- locker / city deck ----------------------------------------------------------

            'CardSentToLocker moves card and bumps locker stat for characters' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Sunk'), Game::LOCATION_CITY_BAZAAR, 1);
                $world->applyHub(EventFactory::createCardSentToLockerEvent(1, $c->Id));
                Assert::same('Locker-1', $c->Location, 'loc');
                Assert::same(
                    [['name' => Game::STAT_CHARACTERS_SENT_TO_LOCKER, 'delta' => 1, 'playerId' => 1]],
                    $world->game->playerStatIncs,
                    'stat'
                );
            },

            'CardSentToLocker drops pending scheme reaction transitions' => function () {
                $world = $this->world();
                $scheme = $world->placeCard(new GenericScheme('Plot'), Game::LOCATION_CITY_FORUM, 1);
                $keep = EventFactory::createTransitionEvent(1, 999, 'reaction', 'other');
                $drop = EventFactory::createTransitionEvent(1, $scheme->Id, 'reaction', 'schemeRx');
                $world->theah->queueEvent($keep);
                $world->theah->queueEvent($drop);

                $world->applyHub(EventFactory::createCardSentToLockerEvent(1, $scheme->Id));

                Assert::same('Locker-1', $scheme->Location, 'loc');
                Assert::count(0, $world->game->playerStatIncs, 'schemes skip locker stat');
                $left = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $left, 'other kept');
                Assert::same(999, $left[0]->sourceId, 'kept source');
            },

            'CardRemovedFromPlay to City Deck recreates character with no controller' => function () {
                $world = $this->world();
                $merc = $world->placeCharacter(
                    new GenericCityCharacter('Hireling'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $merc->Wounds = 2;
                $merc->Engaged = true;
                $this->recreateAs($world, $merc->Id, fn() => new GenericCityCharacter('Hireling'));

                $world->applyHub(EventFactory::createCardRemovedFromPlayEvent(
                    1,
                    $merc->Id,
                    Game::LOCATION_CITY_DECK
                ));

                $fresh = $world->theah->getCardById($merc->Id);
                Assert::same(Game::LOCATION_CITY_DECK, $fresh->Location, 'deck');
                Assert::same(0, $fresh->ControllerId, 'no controller');
                Assert::same(0, $fresh->OwnerId, 'no owner');
                Assert::same(0, $fresh->Wounds, 'wounds wiped');
                Assert::false($fresh->Engaged, 'en garde');
            },

            // ---- approach / challenge / wound ------------------------------------------------

            'CharacterPutIntoApproachDeck resets in-play state' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Recalled'), Game::LOCATION_CITY_FORUM, 1);
                $c->Wounds = 2;
                $c->Engaged = true;
                $c->IsDying = true;

                $world->applyHub(EventFactory::createCharacterPutIntoApproachDeckEvent(2, $c->Id));

                Assert::same(Game::LOCATION_APPROACH, $c->Location, 'approach');
                Assert::same(2, $c->ControllerId, 'new controller');
                Assert::same(2, $c->OwnerId, 'new owner');
                Assert::same(0, $c->Wounds, 'healed');
                Assert::false($c->Engaged, 'en garde');
                Assert::false($c->IsDying, 'not dying');
                $types = array_column($world->game->notify->messages, 'type');
                Assert::true(in_array('cardRemovedFromPlay', $types, true), 'was in play');
            },

            'ChallengeIssued stamps conditions and last-known snapshots' => function () {
                $world = $this->world();
                $challenger = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_FORUM, 1);
                $defender = $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_FORUM, 2);
                $challenger->ModifiedCombat = 4;
                $defender->ModifiedCombat = 2;
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $event = new EventChallengeIssued();
                $event->playerId = 1;
                $event->challengerId = $challenger->Id;
                $event->defenderId = $defender->Id;
                $event->activatedTechniqueId = '';
                $world->applyHub($event);

                Assert::true($challenger->hasCondition(Game::DUEL_CHALLENGER), 'challenger chip');
                Assert::true($defender->hasCondition(Game::DUEL_DEFENDER), 'defender chip');
                $snapCh = unserialize($world->game->globals->get(Game::CHALLENGE_LAST_KNOWN_CHALLENGER));
                $snapDef = unserialize($world->game->globals->get(Game::CHALLENGE_LAST_KNOWN_DEFENDER));
                Assert::same(4, $snapCh->ModifiedCombat, 'challenger snapshot');
                Assert::same(2, $snapDef->ModifiedCombat, 'defender snapshot');
                Assert::same('challengeIssued', $world->game->notify->messages[0]['type'], 'notify');
                Assert::same(Game::STAT_COMBAT, $world->game->notify->messages[0]['args']['challengeStat'], 'stat');
            },

            'CharacterBeingWounded queues CharacterWounded when wounds > 0' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 1);
                $world->applyHub(EventFactory::createCharacterBeingWoundedEvent(
                    $c->Id,
                    99,
                    2,
                    'slash',
                    'ability'
                ));
                $queued = $world->theah->queuedOfType(EventCharacterWounded::class);
                Assert::count(1, $queued, 'queued');
                Assert::same(2, $queued[0]->wounds, 'wounds');
                Assert::same(99, $queued[0]->sourceId, 'source');
            },

            'CharacterBeingWounded with zero wounds queues nothing' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Safe'), Game::LOCATION_CITY_DOCKS, 1);
                $world->applyHub(EventFactory::createCharacterBeingWoundedEvent($c->Id, 1, 0, 'blocked'));
                Assert::count(0, $world->theah->queuedOfType(EventCharacterWounded::class), 'empty');
            },

            'ApproachCharacterPlayed moves character Home and adds to world' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Approach'), Game::LOCATION_APPROACH, 1);
                $world->applyHub(EventFactory::createApproachCharacterPlayedEvent(1, $c->Id));
                Assert::same(Game::LOCATION_PLAYER_HOME, $c->Location, 'home');
                Assert::same($c, $world->theah->getCardById($c->Id), 'in world');
                Assert::same('approachCharacterPlayed', $world->game->notify->messages[0]['type'], 'notify');
            },
        ];
    }
}
