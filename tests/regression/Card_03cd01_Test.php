<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd05;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;

class Card_03cd01_Test extends TestCase
{
    public function name(): string
    {
        return '_03cd01 Penya';
    }

    public function tests(): array
    {
        return [
            'constructs a dashed Mercenary Hero with the City Action' => function () {
                $penya = new _03cd01();
                Assert::instanceOf(CityCharacter::class, $penya, 'CityCharacter');
                Assert::instanceOf(IHasActions::class, $penya, 'actions');
                Assert::same(1, $penya->CityCardNumber, 'city number');
                Assert::same(1, $penya->Resolve, 'Resolve');
                Assert::same(2, $penya->Finesse, 'Finesse');
                Assert::true($penya->DashedCombat, 'dashed Combat');
                Assert::true($penya->DashedInfluence, 'dashed Influence');
                Assert::false($penya->Negotiable, 'not negotiable');
                Assert::true($penya->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($penya->hasTrait('Hero'), 'Hero');
                Assert::true($penya->hasTrait('Swindler'), 'Swindler');
                Assert::true($penya->hasTrait('Orphan'), 'Orphan');
                Assert::false($penya->canIntervene(), 'cannot intervene');
                Assert::count(1, $penya->getActions(), 'one Action');
                Assert::instanceOf(Action_03cd01::class, $penya->getActions()[0], 'Action_03cd01');
            },

            // WHY: Printed dashed Combat is enforced in Character::eventCheckCore, which
            // still runs when the text box is blanked. Penya opts in via DashedCombat.
            'Combat challenge issued by Penya is rejected' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);

                $issued = new EventChallengeIssued();
                $issued->challengerId = $penya->Id;
                $issued->theah = $world->theah;

                $threw = false;
                try {
                    $penya->eventCheck($issued);
                } catch (UserException $e) {
                    $threw = true;
                    Assert::contains('dashed Combat', $e->getMessage(), 'message');
                }
                Assert::true($threw, 'rejected');
            },

            'intervening as Penya is rejected' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);

                $intervened = new EventCharacterIntervened();
                $intervened->newTargetId = $penya->Id;
                $intervened->theah = $world->theah;

                $threw = false;
                try {
                    $penya->eventCheck($intervened);
                } catch (UserException $e) {
                    $threw = true;
                    Assert::contains('cannot intervene', $e->getMessage(), 'message');
                }
                Assert::true($threw, 'rejected');

                $intervened->newTargetId = $other->Id;
                $penya->eventCheck($intervened);
            },

            // WHY: Forced puts the top city card at his current location, unequips first
            // (so recreate does not orphan attachments), then removes him to the City Deck.
            'a wound in the city cancels and shuffles him away with his attachment' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_CITY_DOCKS, 2);
                $bones = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 2);
                $penya->Attachments[] = $bones->Id;
                $bones->AttachedToId = $penya->Id;
                $top = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DECK, 0);
                $world->game->topCityCards = [['id' => $top->Id]];

                $wound = new EventCharacterBeingWounded();
                $wound->characterId = $penya->Id;
                $wound->theah = $world->theah;
                $penya->handleEvent($wound);

                Assert::true($wound->canceled, 'wound canceled');
                $added = $world->theah->queuedOfType(EventCityCardAddedToLocation::class);
                Assert::count(1, $added, 'top card');
                Assert::same($top->Id, $added[0]->cardId, 'top id');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'his location');

                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequip');
                Assert::same($bones->Id, $unequip[0]->attachmentId, 'bones');
                $discard = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discard, 'city attachment leaves via city discard');
                Assert::same($bones->Id, $discard[0]->cardId, 'bones discarded');

                $remove = $world->theah->queuedOfType(EventCardRemovedFromPlay::class);
                Assert::count(1, $remove, 'removed');
                Assert::same($penya->Id, $remove[0]->cardId, 'penya');
                Assert::same(2, $remove[0]->playerId, 'controller');
                Assert::same(Game::LOCATION_CITY_DECK, $remove[0]->toLocation, 'city deck');
            },

            'an empty City Deck still removes him' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_CITY_FORUM, 1);

                $wound = new EventCharacterBeingWounded();
                $wound->characterId = $penya->Id;
                $wound->theah = $world->theah;
                $penya->handleEvent($wound);

                Assert::true($wound->canceled, 'canceled');
                Assert::count(0, $world->theah->queuedOfType(EventCityCardAddedToLocation::class), 'no top card');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlay::class), 'removed');
            },

            'a wound at Home is not the Forced' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_PLAYER_HOME, 1);

                $wound = new EventCharacterBeingWounded();
                $wound->characterId = $penya->Id;
                $wound->theah = $world->theah;
                $penya->handleEvent($wound);

                Assert::false($wound->canceled, 'stands');
                Assert::count(0, $world->theah->queuedEvents, 'no forced');
            },

            'a wound on someone else is ignored' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);

                $wound = new EventCharacterBeingWounded();
                $wound->characterId = $other->Id;
                $wound->theah = $world->theah;
                $penya->handleEvent($wound);

                Assert::false($wound->canceled, 'stands');
            },

            'joining a duel in the city triggers the same Forced' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_CITY_BAZAAR, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_BAZAAR, 2);

                $started = new EventDuelStarted();
                $started->defenderId = $penya->Id;
                $started->challengerId = $foe->Id;
                $started->theah = $world->theah;
                $penya->handleEvent($started);

                $remove = $world->theah->queuedOfType(EventCardRemovedFromPlay::class);
                Assert::count(1, $remove, 'removed');
                Assert::same(Game::LOCATION_CITY_DECK, $remove[0]->toLocation, 'city deck');
            },

            'a duel he is not in does not trigger' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_CITY_DOCKS, 1);
                $a = $world->placeCharacter(new GenericCharacter('A'), Game::LOCATION_CITY_DOCKS, 1);
                $b = $world->placeCharacter(new GenericCharacter('B'), Game::LOCATION_CITY_DOCKS, 2);

                $started = new EventDuelStarted();
                $started->challengerId = $a->Id;
                $started->defenderId = $b->Id;
                $started->theah = $world->theah;
                $penya->handleEvent($started);

                Assert::count(0, $world->theah->queuedEvents, 'silent');
            },

            'a duel while he is at Home does not trigger' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_PLAYER_HOME, 1);

                $started = new EventDuelStarted();
                $started->challengerId = $penya->Id;
                $started->theah = $world->theah;
                $penya->handleEvent($started);

                Assert::count(0, $world->theah->queuedEvents, 'silent');
            },

            'arriving in the City Deck shuffles that deck' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_CITY_DOCKS, 1);

                $removed = new EventCardRemovedFromPlay();
                $removed->cardId = $penya->Id;
                $removed->toLocation = Game::LOCATION_CITY_DECK;
                $removed->theah = $world->theah;
                $penya->handleEvent($removed);

                Assert::same([Game::LOCATION_CITY_DECK], $world->game->deckShuffles, 'shuffled');
            },

            'removal to anywhere else does not shuffle' => function () {
                $world = new TestWorld();
                $penya = $world->placeCharacter(new _03cd01(), Game::LOCATION_CITY_DOCKS, 1);

                $removed = new EventCardRemovedFromPlay();
                $removed->cardId = $penya->Id;
                $removed->toLocation = Game::LOCATION_CITY_DISCARD;
                $removed->theah = $world->theah;
                $penya->handleEvent($removed);

                Assert::count(0, $world->game->deckShuffles, 'no shuffle');
            },
        ];
    }
}
