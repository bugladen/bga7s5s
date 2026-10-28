<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\UtilitiesTraitHost;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01006;

/** UtilitiesTrait: pure helpers, flags, wealth, hands, location globals, extra-action lock. */
class UtilitiesTrait_Helpers_Test extends TestCase
{
    public function name(): string
    {
        return 'UtilitiesTrait: helpers (wealth, flags, hands, locations, extra action)';
    }

    private function host(): UtilitiesTraitHost
    {
        return new UtilitiesTraitHost();
    }

    public function tests(): array
    {
        return [
            // ---- isValidWealthPayment --------------------------------------------------------

            'exact wealth payment is valid without a Wealth card' => function () {
                $host = $this->host();
                Assert::true($host->isValidWealthPayment(3, 3, false), 'exact');
                Assert::false($host->isValidWealthPayment(4, 3, false), 'overpay without Wealth');
                Assert::false($host->isValidWealthPayment(2, 3, false), 'underpay');
            },

            'Wealth card may overpay by exactly one' => function () {
                $host = $this->host();
                Assert::true($host->isValidWealthPayment(4, 3, true), 'cost+1 with Wealth');
                Assert::true($host->isValidWealthPayment(3, 3, true), 'exact still ok');
                Assert::false($host->isValidWealthPayment(5, 3, true), 'cost+2 not ok');
            },

            // ---- getCardClassName / instantiateCard ------------------------------------------

            'getCardClassName maps set prefixes to expansion namespaces' => function () {
                $host = $this->host();
                Assert::same(
                    '\\Bga\\Games\\SeventhSeaCityOfFiveSails\\cards\\_7s5s\\_01006',
                    $host->getCardClassName('01006'),
                    'core'
                );
                Assert::same(
                    '\\Bga\\Games\\SeventhSeaCityOfFiveSails\\cards\\tac\\_02044',
                    $host->getCardClassName('02044'),
                    'tac'
                );
                Assert::same(
                    '\\Bga\\Games\\SeventhSeaCityOfFiveSails\\cards\\faf\\_03001',
                    $host->getCardClassName('03001'),
                    'faf'
                );
                Assert::same(
                    '\\Bga\\Games\\SeventhSeaCityOfFiveSails\\cards\\bas\\_04001',
                    $host->getCardClassName('04001'),
                    'bas'
                );
                Assert::same(
                    '\\Bga\\Games\\SeventhSeaCityOfFiveSails\\cards\\cad\\_05Orel',
                    $host->getCardClassName('05Orel'),
                    'cad'
                );
                Assert::same(
                    '\\Bga\\Games\\SeventhSeaCityOfFiveSails\\cards\\_7s5s\\_xx999',
                    $host->getCardClassName('xx999'),
                    'unknown prefix defaults to core'
                );
            },

            'instantiateCard builds the class and optionally sets Id' => function () {
                $host = $this->host();
                $card = $host->instantiateCard('01006', 42);
                Assert::instanceOf(_01006::class, $card, 'Don Constanzo');
                Assert::same(42, $card->Id, 'id applied');
            },

            // ---- location global name helpers ------------------------------------------------

            'renown / control / claim / uncontrolled location keys' => function () {
                $host = $this->host();
                $loc = Game::LOCATION_CITY_DOCKS;
                Assert::same('Reknown_' . $loc, $host->getRenownLocationName($loc), 'renown key');
                Assert::same('Control_' . $loc, $host->getControllerLocationName($loc), 'control key');
                Assert::same('CanBeClaimed_' . $loc, $host->getCanBeClaimedLocationName($loc), 'claim key');
                Assert::same(
                    'CanBecomeUncontrolled_' . $loc,
                    $host->getCanBecomeUncontrolledLocationName($loc),
                    'uncontrolled key'
                );
            },

            'renown / control / claim getters round-trip through globals' => function () {
                $host = $this->host();
                $loc = Game::LOCATION_CITY_FORUM;

                Assert::same(null, $host->getRenownForLocation($loc), 'renown default unset');
                $host->setReknownForLocation($loc, 3);
                Assert::same(3, $host->getRenownForLocation($loc), 'renown set');

                Assert::same(0, $host->getControllerForLocation($loc), 'control default 0');
                $host->setControllerForLocation($loc, 2);
                Assert::same(2, $host->getControllerForLocation($loc), 'control set');

                Assert::true($host->getCanBeClaimedForLocation($loc), 'claimable default true');
                $host->setCanBeClaimedForLocation($loc, false);
                Assert::false($host->getCanBeClaimedForLocation($loc), 'claimable false');

                Assert::true($host->getCanBecomeUncontrolledForLocation($loc), 'uncontrolled default true');
                $host->setCanBecomeUncontrolledForLocation($loc, false);
                Assert::false($host->getCanBecomeUncontrolledForLocation($loc), 'uncontrolled false');
            },

            // ---- flags / batch / scores ------------------------------------------------------

            'setGlobalFlag ORs bits; isGlobalFlagSet requires the full bit' => function () {
                $host = $this->host();
                $host->globals->set(Game::PRESSURE_TYPE, 0);
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::TABARD_PRESSURE_TYPE);
                $host->setGlobalFlag(Game::PRESSURE_TYPE, Game::LOYAL_PRESSURE_TYPE);
                Assert::true($host->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::TABARD_PRESSURE_TYPE), 'tabard');
                Assert::true($host->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::LOYAL_PRESSURE_TYPE), 'loyal');
                Assert::false($host->isGlobalFlagSet(Game::PRESSURE_TYPE, Game::OVERCOAT_PRESSURE_TYPE), 'overcoat off');
                Assert::true(
                    $host->isGlobalFlagSet(
                        Game::PRESSURE_TYPE,
                        Game::TABARD_PRESSURE_TYPE | Game::LOYAL_PRESSURE_TYPE
                    ),
                    'combined bits present'
                );
            },

            'getNextEventBatchId increments from globals' => function () {
                $host = $this->host();
                Assert::same(1, $host->getNextEventBatchId(), 'first');
                Assert::same(2, $host->getNextEventBatchId(), 'second');
                Assert::same(2, $host->globals->get(Game::EVENT_BATCH_ID), 'stored');
            },

            'aux score and player renown helpers' => function () {
                $host = $this->host();
                $host->dbSetAuxScore(1, 9);
                Assert::same(9, $host->dbGetAuxScore(1), 'aux');
                $host->setPlayerReknown(1, 5);
                Assert::same(5, $host->getPlayerReknown(1), 'renown');
                Assert::same(7, $host->incrementPlayerReknown(1, 2), 'increment returns new');
                Assert::same(7, $host->getPlayerReknown(1), 'increment stored');
                Assert::same(7, $host->incrementPlayerReknown(1, 0), 'zero delta no write');
            },

            'goToEndOfGame records stats then transitions endOfGame' => function () {
                $host = $this->host();
                $host->gameStateValues[Game::DAY] = 2;
                $host->scores = [1 => 3, 2 => 1];
                $host->goToEndOfGame(Game::VICTORY_ASSASSINATION);
                Assert::same(['endOfGame'], $host->gamestate->transitions, 'transition');
                Assert::same(2, $host->tableStatSets[0][1] ?? null, 'day recorded');
            },

            // ---- discard / locker / reaction state -------------------------------------------

            'characterIsInDiscardOrLocker matches Discard- and Locker- prefixes' => function () {
                $host = $this->host();
                $a = new GenericCharacter('In play');
                $a->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($host->characterIsInDiscardOrLocker($a), 'city');

                $b = new GenericCharacter('Discarded');
                $b->Location = 'Discard-1';
                Assert::true($host->characterIsInDiscardOrLocker($b), 'discard');

                $c = new GenericCharacter('Locked');
                $c->Location = 'Locker-2';
                Assert::true($host->characterIsInDiscardOrLocker($c), 'locker');
            },

            'isInReactionState only matches HIGH_DRAMA_PLAYER_TURN_REACTIONS' => function () {
                $host = $this->host();
                Assert::true(
                    $host->isInReactionState(States::HIGH_DRAMA_PLAYER_TURN_REACTIONS),
                    'reactions'
                );
                Assert::false($host->isInReactionState(States::HIGH_DRAMA_PLAYER_TURN), 'player turn');
            },

            // ---- hand helpers ----------------------------------------------------------------

            'hand attachment / maneuver helpers filter the hand' => function () {
                $host = $this->host();
                $att = $host->place(new GenericAttachment('Sword'), Game::LOCATION_HAND, 1);
                $risk = $host->place(new GenericRisk('Combat'), Game::LOCATION_HAND, 1);
                $host->place(new GenericCharacter('Not hand'), Game::LOCATION_CITY_DOCKS, 1);
                $host->place(new GenericAttachment('Other hand'), Game::LOCATION_HAND, 2);

                $attachments = $host->getAttachmentsInHand(1);
                Assert::count(1, $attachments, 'one attachment');
                Assert::same($att->Id, $attachments[0]->Id, 'sword');

                $maneuvers = $host->getManeuversInHand(1);
                Assert::count(1, $maneuvers, 'GenericRisk is IHasManeuvers');
                Assert::instanceOf(IHasManeuvers::class, $maneuvers[0], 'interface');
                Assert::same($risk->Id, $maneuvers[0]->Id, 'risk');

                Assert::true($host->handContainsCard(1), 'contains attachment');
                Assert::true($host->handHasAttachments(1), 'has attachments');
                Assert::true($host->handContainsCard(2), 'player 2 attachment');
                Assert::false($host->handHasAttachments(99), 'empty player');
            },

            'handWealthCount doubles Wealth-trait cards' => function () {
                $host = $this->host();
                $plain = new GenericRisk('Plain');
                $wealth = new GenericRisk('Coin');
                $wealth->Traits = ['Wealth'];
                $host->place($plain, Game::LOCATION_HAND, 1);
                $host->place($wealth, Game::LOCATION_HAND, 1);
                Assert::same(3, $host->handWealthCount(1), '1 + 2');
            },

            // ---- characterHasAttachmentOfType / hasEquipRestrictions -------------------------

            'characterHasAttachmentOfType distinguishes OffHand vs typed slots' => function () {
                $host = $this->host();
                $char = $host->place(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = new GenericAttachment('Sabre');
                $weapon->Traits = ['Weapon'];
                $weapon->OffHand = false;
                $host->place($weapon, Game::LOCATION_CITY_DOCKS, 1);
                $char->Attachments[] = $weapon->Id;

                Assert::true($host->characterHasAttachmentOfType($char, 'Weapon', false), 'weapon present');
                Assert::false($host->characterHasAttachmentOfType($char, 'Armor', false), 'no armor');
                Assert::false($host->characterHasAttachmentOfType($char, 'Weapon', true), 'no offhand yet');

                $off = new GenericAttachment('Buckler');
                $off->OffHand = true;
                $host->place($off, Game::LOCATION_CITY_DOCKS, 1);
                $char->Attachments[] = $off->Id;
                Assert::true($host->characterHasAttachmentOfType($char, 'Weapon', true), 'offhand present');
                // WHY: OffHands do not count against Weapon/Armor/Attire (Reaction_AttachmentTypeLimit).
                Assert::true($host->characterHasAttachmentOfType($char, 'Weapon', false), 'weapon still counted');
            },

            'hasEquipRestrictions blocks hosts that cannot receive attachments' => function () {
                $host = $this->host();
                $open = new GenericCharacter('Open');
                $closed = new class('Closed') extends GenericCharacter {
                    public function canReceiveAttachments(): bool
                    {
                        return false;
                    }
                };
                $att = new GenericAttachment('Thing');

                [$blocked, $msg] = $host->hasEquipRestrictions($closed, $att);
                Assert::true($blocked, 'blocked');
                Assert::contains('cannot be equipped', $msg, 'message');

                [$ok, $empty] = $host->hasEquipRestrictions($open, $att);
                Assert::false($ok, 'open host');
                Assert::same('', $empty, 'no message');
            },

            // ---- extra action lock -----------------------------------------------------------

            'extra-action performer lock filters and asserts' => function () {
                $host = $this->host();
                Assert::same(null, $host->getExtraActionPerformerId(), 'unset');
                Assert::false($host->mustPerformExtraAction(), 'no lock');
                Assert::same([10, 20], $host->filterPerformerIdsForExtraAction([10, 20]), 'passthrough');

                $host->globals->set(Game::EXTRA_ACTION_PERFORMER, 20);
                Assert::same(20, $host->getExtraActionPerformerId(), 'locked');
                Assert::true($host->mustPerformExtraAction(), 'must');
                Assert::same([20], $host->filterPerformerIdsForExtraAction([10, 20, 30]), 'filtered');
                Assert::same([], $host->filterPerformerIdsForExtraAction([10, 30]), 'missing locked');

                $host->assertIsExtraActionPerformer(20);
                Assert::throws(
                    fn () => $host->assertIsExtraActionPerformer(10),
                    'same character',
                    'wrong performer'
                );
            },

            // ---- fewest characters / turn order ----------------------------------------------

            'getPlayerControllingFewestCharacters returns sole lowest; ties null the player' => function () {
                $host = $this->host();
                $host->place(new GenericCharacter('A1'), Game::LOCATION_CITY_DOCKS, 1);
                $host->place(new GenericCharacter('A2'), Game::LOCATION_CITY_DOCKS, 1);
                $host->place(new GenericCharacter('B1'), Game::LOCATION_CITY_FORUM, 2);

                [$id, $count] = $host->getPlayerControllingFewestCharacters();
                Assert::same(2, $id, 'player 2 fewest');
                Assert::same(1, $count, 'count');

                $host->place(new GenericCharacter('B2'), Game::LOCATION_CITY_FORUM, 2);
                [$tiedId, $tiedCount] = $host->getPlayerControllingFewestCharacters();
                Assert::same(null, $tiedId, 'tie ignores player');
                Assert::same(2, $tiedCount, 'tied count');
            },

            'setNewPlayerOrder rewrites turn_order starting at first player' => function () {
                $host = $this->host();
                $orders = $host->setNewPlayerOrder(2);
                Assert::same([2 => 1, 1 => 2], $orders, 'orders');
                Assert::true(
                    str_contains($host->dbQueries[0], 'turn_order = 1')
                    && str_contains($host->dbQueries[0], 'player_id = 2'),
                    'first query'
                );
            },
        ];
    }
}
