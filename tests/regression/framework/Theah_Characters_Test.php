<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05SnowWhite;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;

/**
 * Theah: card world queries, character filters, crew-cap accounting, attachments.
 */
class Theah_Characters_Test extends TestCase
{
    public function name(): string
    {
        return 'Theah: characters (queries, opposing, crew cap, attachments)';
    }

    private function world(): TestWorld
    {
        return new TestWorld();
    }

    /** @return list<int> */
    private function ids(array $cards): array
    {
        return array_values(array_map(fn($c) => $c->Id, $cards));
    }

    public function tests(): array
    {
        return [
            // ---- world / lookups -------------------------------------------------------------

            'addCardToWorld / getAllCards / getWorldCards / getCardById' => function () {
                $world = $this->world();
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $all = $world->theah->getAllCards();
                Assert::true(array_key_exists($a->Id, $all), 'all');
                Assert::same($a, $world->theah->getWorldCards()[$a->Id], 'world');
                Assert::same($a, $world->theah->getCardById($a->Id), 'by id');
                Assert::same(null, $world->theah->getCardById(0), 'id 0');
                Assert::same(null, $world->theah->getCardById(99999), 'missing');
            },

            'getCardByName and getCardByType' => function () {
                $world = $this->world();
                $risk = $world->placeCard(new GenericRisk('Named Risk'), Game::LOCATION_HAND, 1);
                Assert::same($risk, $world->theah->getCardByName('Named Risk'), 'name');
                Assert::same($risk, $world->theah->getCardByType(GenericRisk::class), 'type');
                Assert::same(null, $world->theah->getCardByName('Nope'), 'missing name');
            },

            'getCardsInPlay requires controlled city or Home' => function () {
                $world = $this->world();
                $city = $world->placeCharacter(new GenericCharacter('C'), Game::LOCATION_CITY_DOCKS, 1);
                $home = $world->placeCharacter(new GenericCharacter('H'), Game::LOCATION_PLAYER_HOME, 1);
                $hand = $world->placeCharacter(new GenericCharacter('Hand'), Game::LOCATION_HAND, 1);
                $uncontrolled = $world->placeCharacter(
                    new GenericCityCharacter('Loose Merc'),
                    Game::LOCATION_CITY_FORUM,
                    0
                );

                $ids = $this->ids($world->theah->getCardsInPlay());
                Assert::true(in_array($city->Id, $ids, true), 'city');
                Assert::true(in_array($home->Id, $ids, true), 'home');
                Assert::false(in_array($hand->Id, $ids, true), 'hand out');
                Assert::false(in_array($uncontrolled->Id, $ids, true), 'uncontrolled out');
            },

            'getCharacterById / getAttachmentById / getRiskById type gates' => function () {
                $world = $this->world();
                $char = $world->placeCharacter(new GenericCharacter('Char'), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new GenericAttachment('Att'), Game::LOCATION_CITY_DOCKS, 1);
                $risk = $world->placeCard(new GenericRisk('Risk'), Game::LOCATION_HAND, 1);

                Assert::instanceOf(Character::class, $world->theah->getCharacterById($char->Id), 'char');
                Assert::same(null, $world->theah->getCharacterById($att->Id), 'att not char');
                Assert::same($att, $world->theah->getAttachmentById($att->Id), 'att');
                Assert::same($risk, $world->theah->getRiskById($risk->Id), 'risk');
                Assert::same(null, $world->theah->getRiskById($char->Id), 'char not risk');
            },

            // ---- character location filters --------------------------------------------------

            'getCharactersInPlay / ByPlayerId / InCity / AtHome / AtLocation' => function () {
                $world = $this->world();
                $mineCity = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $mineHome = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);
                $theirs = $world->placeCharacter(new GenericCharacter('Theirs'), Game::LOCATION_CITY_DOCKS, 2);
                $loose = $world->placeCharacter(
                    new GenericCityCharacter('Loose'),
                    Game::LOCATION_CITY_DOCKS,
                    0
                );

                Assert::same(
                    [$mineCity->Id, $mineHome->Id],
                    $this->ids($world->theah->getCharactersInPlayByPlayerId(1)),
                    'mine in play'
                );
                Assert::same(
                    [$mineCity->Id],
                    $this->ids($world->theah->getCharactersInCityByPlayerId(1)),
                    'mine in city'
                );
                Assert::same(
                    [$mineHome->Id],
                    $this->ids($world->theah->getCharactersAtHomeByPlayerId(1)),
                    'mine at home'
                );

                $atDocks = $this->ids($world->theah->getCharactersAtLocation(Game::LOCATION_CITY_DOCKS));
                Assert::true(in_array($mineCity->Id, $atDocks, true), 'controlled at docks');
                Assert::true(in_array($theirs->Id, $atDocks, true), 'opponent at docks');
                Assert::false(in_array($loose->Id, $atDocks, true), 'uncontrolled excluded by default');

                $withLoose = $this->ids(
                    $world->theah->getCharactersAtLocation(Game::LOCATION_CITY_DOCKS, true)
                );
                Assert::true(in_array($loose->Id, $withLoose, true), 'uncontrolled included');
            },

            'opposing characters and mercenaries at location / in city' => function () {
                $world = $this->world();
                $me = $world->placeCharacter(new GenericCharacter('Me'), Game::LOCATION_CITY_FORUM, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $merc = $world->placeCharacter(
                    new GenericCityCharacter('Opp Merc', traits: ['Mercenary']),
                    Game::LOCATION_CITY_FORUM,
                    2
                );
                $lonely = $world->placeCharacter(
                    new GenericCharacter('Lonely'),
                    Game::LOCATION_CITY_BAZAAR,
                    1
                );

                $opp = $this->ids(
                    $world->theah->getOpposingCharactersAtLocation(Game::LOCATION_CITY_FORUM, 1)
                );
                sort($opp);
                Assert::same([$foe->Id, $merc->Id], $opp, 'opposing at forum');

                Assert::same(
                    [$merc->Id],
                    $this->ids($world->theah->getOpposingMercenariesAtLocation(Game::LOCATION_CITY_FORUM, 1)),
                    'opposing mercs'
                );

                Assert::same(
                    [$me->Id],
                    $this->ids($world->theah->getCharactersInCityWithOpposingCharacters(1)),
                    'performers with foes (lonely at empty Bazaar excluded)'
                );
                Assert::same(
                    [$me->Id],
                    $this->ids($world->theah->getCharactersInCityWithOpposingMercenaries(1)),
                    'performers with opposing mercs'
                );
                Assert::count(
                    2,
                    $world->theah->getCharactersInCityWithOpposingCharacters(2),
                    'foe+merc both see Me at Forum'
                );
                Assert::same(
                    [],
                    $this->ids($world->theah->getCharactersInCityWithOpposingMercenaries(2)),
                    'p2 has no opposing Mercenary (Me is not one)'
                );
            },

            'getTotalPlayerInfluence sums ModifiedInfluence of in-play characters' => function () {
                $world = $this->world();
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $a->Influence = 3;
                $a->resetCard();
                $b = $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_PLAYER_HOME, 1);
                $b->Influence = 2;
                $b->resetCard();
                $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2)
                    ->Influence = 9;

                Assert::same(5, $world->theah->getTotalPlayerInfluence(1), '3+2');
            },

            // ---- crew cap --------------------------------------------------------------------

            'characterCountsAgainstCrewCap skips Brutes unless includeBrutes' => function () {
                $world = $this->world();
                $brute = $world->placeCharacter(
                    new GenericCharacter('Brute', ['Brute']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $normal = $world->placeCharacter(
                    new GenericCharacter('Normal'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );

                Assert::false(
                    $world->theah->characterCountsAgainstCrewCap($brute, 1, false),
                    'brute excluded'
                );
                Assert::true(
                    $world->theah->characterCountsAgainstCrewCap($brute, 1, true),
                    'brute included when asked'
                );
                Assert::true(
                    $world->theah->characterCountsAgainstCrewCap($normal, 1, false),
                    'normal counts'
                );
            },

            'Wildcall at Home exempts Animals from crew cap' => function () {
                $world = $this->world();
                $animal = $world->placeCharacter(
                    new GenericCharacter('Wolf', ['Animal']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                Assert::true(
                    $world->theah->characterCountsAgainstCrewCap($animal, 1),
                    'animal counts without scheme'
                );

                $scheme = new _05SnowWhite();
                $world->placeCard($scheme, Game::LOCATION_PLAYER_HOME, 1);
                Assert::true($world->theah->playerAnimalsExemptFromCrewCap(1), 'exempt active');
                Assert::false(
                    $world->theah->characterCountsAgainstCrewCap($animal, 1),
                    'animal skipped'
                );

                $scheme->Location = 'Locker-1';
                Assert::false($world->theah->playerAnimalsExemptFromCrewCap(1), 'locker ends day');
                Assert::true(
                    $world->theah->characterCountsAgainstCrewCap($animal, 1),
                    'animal counts again'
                );
            },

            'getCharacterCountByPlayerId respects crew-cap filters' => function () {
                $world = $this->world();
                $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Brute', ['Brute']), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Away'), Game::LOCATION_HAND, 1);

                Assert::same(1, $world->theah->getCharacterCountByPlayerId(1, false), 'no brutes');
                Assert::same(2, $world->theah->getCharacterCountByPlayerId(1, true), 'with brutes');
            },

            // ---- attachments -----------------------------------------------------------------

            'getAvailableAttachmentsAtLocation ignores attached cards' => function () {
                $world = $this->world();
                $loose = $world->placeCard(
                    new GenericAttachment('Loose'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $stuck = $world->placeCard(
                    new GenericAttachment('Stuck'),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $stuck->AttachedToId = 42;
                $elsewhere = $world->placeCard(
                    new GenericAttachment('Elsewhere'),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                $atDocks = $world->theah->getAvailableAttachmentsAtLocation(Game::LOCATION_CITY_DOCKS);
                Assert::same([$loose], $atDocks, 'only loose at docks');
                Assert::true(
                    in_array($elsewhere, $world->theah->getAvailableAttachmentsInCity(), true),
                    'city includes forum'
                );
                Assert::false(
                    in_array($stuck, $world->theah->getAvailableAttachmentsInCity(), true),
                    'attached excluded city-wide'
                );
            },

            'getCardObjectsAtLocation filters by location and controller' => function () {
                $world = $this->world();
                $mine = $world->placeCard(new GenericRisk('Mine'), Game::LOCATION_HAND, 1);
                $theirs = $world->placeCard(new GenericRisk('Theirs'), Game::LOCATION_HAND, 2);

                $hand1 = $world->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, 1);
                Assert::true(array_key_exists($mine->Id, $hand1), 'mine');
                Assert::false(array_key_exists($theirs->Id, $hand1), 'theirs filtered');
            },
        ];
    }
}
