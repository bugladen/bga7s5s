<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericFactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;

/**
 * Theah: High Drama legality helpers and absent-actor challenge threat.
 *
 * WHY: playerCan* / characterCan* are the menu gates for Move / Recruit / Equip /
 * Challenge / Claim. Pinning them here catches regressions that card suites miss
 * when they stub availability.
 */
class Theah_Capabilities_Test extends TestCase
{
    public function name(): string
    {
        return 'Theah: capabilities (move, recruit, equip, challenge, claim, threat)';
    }

    private function world(): TestWorld
    {
        return new TestWorld();
    }

    public function tests(): array
    {
        return [
            // ---- move ------------------------------------------------------------------------

            'playerCanMove requires an en garde in-play character' => function () {
                $world = $this->world();
                Assert::false($world->theah->playerCanMove(1), 'empty');

                $c = $world->placeCharacter(new GenericCharacter('Mover'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::true($world->theah->playerCanMove(1), 'en garde');
                Assert::true($world->theah->characterCanMove($c), 'character en garde');

                $c->Engaged = true;
                Assert::false($world->theah->playerCanMove(1), 'all engaged');
                Assert::false($world->theah->characterCanMove($c), 'engaged cannot move');
            },

            // ---- recruit ---------------------------------------------------------------------

            'playerCanRecruit / characterCanRecruit need uncontrolled Mercenary at site' => function () {
                $world = $this->world();
                $performer = $world->placeCharacter(
                    new GenericCharacter('Recruiter'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                Assert::false($world->theah->playerCanRecruit(1), 'no merc');
                Assert::false($world->theah->characterCanRecruit($performer), 'char no merc');

                $world->placeCharacter(
                    new GenericCityCharacter('Hireling'),
                    Game::LOCATION_CITY_DOCKS,
                    0
                );
                Assert::true($world->theah->playerCanRecruit(1), 'merc present');
                Assert::true($world->theah->characterCanRecruit($performer), 'char can');

                $home = $world->placeCharacter(
                    new GenericCharacter('Homebody'),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );
                Assert::false($world->theah->characterCanRecruit($home), 'home cannot recruit');
            },

            // ---- equip -----------------------------------------------------------------------

            'characterCanEquip sees loose city attachment or hand attachment' => function () {
                $world = $this->world();
                $performer = $world->placeCharacter(
                    new GenericCharacter('Equipper'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                Assert::false($world->theah->characterCanEquip($performer), 'nothing to equip');
                Assert::false($world->theah->playerCanEquip(1), 'player nothing');

                $world->placeCard(new GenericAttachment('City Gear'), Game::LOCATION_CITY_DOCKS, 0);
                Assert::true($world->theah->characterCanEquip($performer), 'city attachment');
                Assert::true($world->theah->playerCanEquip(1), 'player city');
            },

            'playerCanEquip falls through to hand attachments' => function () {
                $world = $this->world();
                $performer = $world->placeCharacter(
                    new GenericCharacter('Equipper'),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $world->placeCard(
                    new GenericFactionAttachment('Hand Gear'),
                    Game::LOCATION_HAND,
                    1
                );
                Assert::true($world->theah->characterCanEquip($performer), 'hand path');
                Assert::true($world->theah->playerCanEquip(1), 'player hand');
            },

            'characterCanEquip respects canReceiveAttachments' => function () {
                $world = $this->world();
                $locked = new class ('Locked') extends GenericCharacter {
                    public function canReceiveAttachments(): bool
                    {
                        return false;
                    }
                };
                $performer = $world->placeCharacter($locked, Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCard(new GenericAttachment('Gear'), Game::LOCATION_CITY_DOCKS, 0);
                Assert::false($world->theah->characterCanEquip($performer), 'Orel-style lock');
            },

            'playerCanEquipToOpponents needs FactionAttachment with CanEquipToOpponents' => function () {
                $world = $this->world();
                $world->placeCard(
                    new GenericFactionAttachment('Normal', canEquipToOpponents: false),
                    Game::LOCATION_HAND,
                    1
                );
                Assert::false($world->theah->playerCanEquipToOpponents(1), 'normal hand att');

                $world->placeCard(
                    new GenericFactionAttachment('Hostile', canEquipToOpponents: true),
                    Game::LOCATION_HAND,
                    1
                );
                Assert::true($world->theah->playerCanEquipToOpponents(1), 'hostile');
            },

            // ---- challenge -------------------------------------------------------------------

            'characterCanBasicChallenge needs city, Combat, en garde, and opponent' => function () {
                $world = $this->world();
                $challenger = $world->placeCharacter(
                    new GenericCharacter('Challenger'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                Assert::false($world->theah->characterCanBasicChallenge($challenger), 'no foe');
                Assert::false($world->theah->playerCanBasicChallenge(1), 'player no foe');

                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                Assert::true($world->theah->characterCanBasicChallenge($challenger), 'foe present');
                Assert::true($world->theah->playerCanBasicChallenge(1), 'player can');

                $challenger->Engaged = true;
                Assert::false($world->theah->characterCanBasicChallenge($challenger), 'engaged');
                $challenger->Engaged = false;

                $challenger->DashedCombat = true;
                Assert::false($world->theah->characterCanBasicChallenge($challenger), 'dashed Combat');
                $challenger->DashedCombat = false;

                $home = $world->placeCharacter(
                    new GenericCharacter('Home'),
                    Game::LOCATION_PLAYER_HOME,
                    1
                );
                Assert::false($world->theah->characterCanBasicChallenge($home), 'not in city');
            },

            // ---- claim -----------------------------------------------------------------------

            'characterCanBasicClaim needs city, en garde, and Influence' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(
                    new GenericCharacter('Claimer'),
                    Game::LOCATION_CITY_BAZAAR,
                    1
                );
                Assert::true($world->theah->characterCanBasicClaim($c), 'ready');
                Assert::true($world->theah->playerCanBasicClaim(1), 'player');

                $c->Engaged = true;
                Assert::false($world->theah->characterCanBasicClaim($c), 'engaged');
                Assert::false($world->theah->playerCanBasicClaim(1), 'player engaged');
                $c->Engaged = false;

                $c->DashedInfluence = true;
                Assert::false($world->theah->characterCanBasicClaim($c), 'dashed Influence');
            },

            // ---- brutes in hand --------------------------------------------------------------

            'playerHasBrutes finds playable Brutes in hand' => function () {
                $world = $this->world();
                Assert::false($world->theah->playerHasBrutes(1), 'empty');

                $world->placeCharacter(
                    new GenericCharacter('Thug', ['Brute']),
                    Game::LOCATION_HAND,
                    1
                );
                Assert::true($world->theah->playerHasBrutes(1), 'brute in hand');
            },

            // ---- zombies ---------------------------------------------------------------------

            'isPlayerZombie caches zombie flags from loadPlayersBasicInfos' => function () {
                $world = $this->world();
                $world->game->playerZombies[2] = true;
                Assert::false($world->theah->isPlayerZombie(1), 'p1 live');
                Assert::true($world->theah->isPlayerZombie(2), 'p2 zombie');
                // cache sticks even if the map changes mid-request
                $world->game->playerZombies[2] = false;
                Assert::true($world->theah->isPlayerZombie(2), 'cached');
            },

            // ---- absent actor challenge threat -----------------------------------------------

            'applyAbsentActorBaseChallengeThreat uses last-known snapshot first' => function () {
                $world = $this->world();
                $snapshot = new GenericCharacter('Ghost');
                $snapshot->setId(77);
                $snapshot->Combat = 4;
                $snapshot->resetCard();
                $world->game->challengeLastKnownById[77] = $snapshot;

                $event = new EventGenerateChallengeThreat();
                $event->actorId = 77;
                $event->statUsed = Game::STAT_COMBAT;
                $world->theah->applyAbsentActorBaseChallengeThreat($event);

                Assert::same(4, $event->adversaryThreat, 'combat from snapshot');
                Assert::count(1, $event->explanations, 'logged');
            },

            'applyAbsentActorBaseChallengeThreat falls back to DB card when absent from city' => function () {
                $world = $this->world();
                $actor = $world->placeCharacter(
                    new GenericCharacter('Stilettoed'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $actor->Finesse = 3;
                $actor->resetCard();
                $world->theah->removeCardFromWorld($actor);

                $event = new EventGenerateChallengeThreat();
                $event->actorId = $actor->Id;
                $event->statUsed = Game::STAT_FINESSE;
                $world->theah->applyAbsentActorBaseChallengeThreat($event);

                Assert::same(3, $event->adversaryThreat, 'finesse from db fallback');
            },

            'applyAbsentActorBaseChallengeThreat no-ops when actor still in city or skipped' => function () {
                $world = $this->world();
                $actor = $world->placeCharacter(
                    new GenericCharacter('Present'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $actor->Combat = 5;
                $actor->resetCard();

                $event = new EventGenerateChallengeThreat();
                $event->actorId = $actor->Id;
                $event->statUsed = Game::STAT_COMBAT;
                $world->theah->applyAbsentActorBaseChallengeThreat($event);
                Assert::same(0, $event->adversaryThreat, 'still in cards map');

                $event->skipBaseStatThreat = true;
                $world->theah->removeCardFromWorld($actor);
                $world->theah->applyAbsentActorBaseChallengeThreat($event);
                Assert::same(0, $event->adversaryThreat, 'skip flag');
            },
        ];
    }
}
