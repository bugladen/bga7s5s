<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\DeckTraitHost;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;

/**
 * DeckTrait: location naming, twin-store moves, park, insert extremes, factionDeckCount batching.
 */
class DeckTrait_Move_Test extends TestCase
{
    public function name(): string
    {
        return 'DeckTrait: move / park / insert / deck-count batch';
    }

    private function host(): DeckTraitHost
    {
        return new DeckTraitHost();
    }

    public function tests(): array
    {
        return [
            'player pile name helpers use Faction- / Discard- / Locker-' => function () {
                $host = $this->host();
                Assert::same('Faction-7', $host->getPlayerFactionDeckName(7), 'faction');
                Assert::same('Discard-7', $host->getPlayerDiscardDeckName(7), 'discard');
                Assert::same('Locker-7', $host->getPlayerLockerName(7), 'locker');
                Assert::same($host->cards, $host->getGameDeckObject(), 'deck object');
            },

            'moveCard syncs deck row and Card->Location and notifies faction count' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $card = $host->place(new GenericRisk('Sink'), $deck, 1);

                $host->notify->messages = [];
                $moved = $host->moveCard($card->Id, Game::LOCATION_HAND, 1);

                Assert::same(Game::LOCATION_HAND, $moved->Location, 'serialized Location');
                Assert::same(Game::LOCATION_HAND, $host->cards->getCard($card->Id)['location'], 'deck row');
                $notifs = $host->notifsOfType('factionDeckCount');
                Assert::count(1, $notifs, 'one count refresh');
                Assert::same(1, $notifs[0]['args']['playerId'], 'player');
                Assert::same(0, $notifs[0]['args']['deckCount'], 'deck empty after draw to hand');
            },

            'moveCard prefers Theah instance when no card arg is passed' => function () {
                $host = $this->host();
                $card = $host->place(new GenericCharacter('In World'), Game::LOCATION_CITY_FORUM, 1);
                $fromTheah = $host->theah->getCardById($card->Id);
                Assert::true($fromTheah === $card, 'same object seeded');

                $moved = $host->moveCard($card->Id, Game::LOCATION_PLAYER_HOME, 1);
                Assert::true($moved === $card, 'returned Theah instance');
                Assert::same(Game::LOCATION_PLAYER_HOME, $card->Location, 'mutated in place');
            },

            'moveCardInDeck updates deck row only — Location stays put' => function () {
                $host = $this->host();
                $card = $host->place(new GenericRisk('Hand Risk'), Game::LOCATION_HAND, 1);
                $host->notify->messages = [];

                $host->moveCardInDeck($card->Id, Game::LOCATION_PURGATORY, 0);

                Assert::same(Game::LOCATION_HAND, $card->Location, 'Location still Hand');
                Assert::same(Game::LOCATION_PURGATORY, $host->cards->getCard($card->Id)['location'], 'deck row parked');
                Assert::count(0, $host->notifsOfType('factionDeckCount'), 'no Faction- hop');
            },

            'parkCard is moveCardInDeck into Purgatory by default' => function () {
                $host = $this->host();
                $card = $host->place(new GenericRisk('To Park'), Game::LOCATION_HAND, 1);

                $host->parkCard($card->Id);

                Assert::same(Game::LOCATION_HAND, $card->Location, 'Location untouched');
                Assert::same(Game::LOCATION_PURGATORY, $host->cards->getCard($card->Id)['location'], 'Purgatory row');
            },

            'insertCardOnPlayerFactionDeckExtreme records top/bottom and notifies count' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $a = $host->place(new GenericRisk('A'), $deck, 1);
                $b = new GenericRisk('B');
                $b->setId(9001);
                $b->Location = Game::LOCATION_HAND;
                $host->dbCards[$b->Id] = $b;

                $host->notify->messages = [];
                $host->insertCardOnPlayerFactionDeckExtreme($b->Id, 1, true);

                Assert::same(Game::LOCATION_HAND, $b->Location, 'insert does not sync Location');
                Assert::same($deck, $host->cards->getCard($b->Id)['location'], 'deck row');
                $tops = $host->cards->getCardsOnTop(1, $deck);
                Assert::same($b->Id, $tops[0]['id'], 'on top');
                Assert::count(1, $host->notifsOfType('factionDeckCount'), 'count notif');

                $host->insertCardOnPlayerFactionDeckExtreme($a->Id, 1, false);
                $bottoms = array_values($host->cards->getCardsInLocation($deck, null, 'card_location_arg'));
                Assert::same($a->Id, $bottoms[0]['id'], 'on bottom');
            },

            'factionDeckCount batch collapses N notifies into one absolute count' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(1);
                $discard = $host->getPlayerDiscardDeckName(1);
                $c1 = $host->place(new GenericRisk('D1'), $discard, 1);
                $c2 = $host->place(new GenericRisk('D2'), $discard, 1);

                $host->notify->messages = [];
                $host->beginFactionDeckCountBatch();
                $host->moveCard($c1->Id, $deck, 0);
                $host->moveCard($c2->Id, $deck, 0);
                Assert::count(0, $host->notifsOfType('factionDeckCount'), 'suppressed while open');
                $host->endFactionDeckCountBatch();

                $notifs = $host->notifsOfType('factionDeckCount');
                Assert::count(1, $notifs, 'single flush');
                Assert::same(2, $notifs[0]['args']['deckCount'], 'absolute final size');
            },

            'nested factionDeckCount batches only flush at outermost end' => function () {
                $host = $this->host();
                $deck = $host->getPlayerFactionDeckName(2);
                $card = $host->place(new GenericRisk('Nest'), Game::LOCATION_HAND, 2);

                $host->notify->messages = [];
                $host->beginFactionDeckCountBatch();
                $host->beginFactionDeckCountBatch();
                $host->moveCard($card->Id, $deck, 0);
                $host->endFactionDeckCountBatch();
                Assert::count(0, $host->notifsOfType('factionDeckCount'), 'inner end keeps batch');
                $host->endFactionDeckCountBatch();
                Assert::count(1, $host->notifsOfType('factionDeckCount'), 'outer flush');
            },

            'endFactionDeckCountBatch is a no-op when depth is already zero' => function () {
                $host = $this->host();
                $host->notify->messages = [];
                $host->endFactionDeckCountBatch();
                Assert::count(0, $host->notify->messages, 'no spurious notif');
            },

            'notifyFactionDeckCount outside a batch fires immediately' => function () {
                $host = $this->host();
                $host->place(new GenericRisk('One'), $host->getPlayerFactionDeckName(1), 1);
                $host->notify->messages = [];
                $host->notifyFactionDeckCount(1);
                $notifs = $host->notifsOfType('factionDeckCount');
                Assert::count(1, $notifs, 'immediate');
                Assert::same(1, $notifs[0]['args']['deckCount'], 'count');
            },

            'getCardPropertiesInLocation filters by controller when playerId set' => function () {
                $host = $this->host();
                $mine = $host->place(new GenericRisk('Mine'), Game::LOCATION_HAND, 1);
                $theirs = $host->place(new GenericRisk('Theirs'), Game::LOCATION_HAND, 2);

                $mineOnly = $host->getCardPropertiesInLocation(Game::LOCATION_HAND, 1);
                Assert::count(1, $mineOnly, 'filtered');
                Assert::same($mine->Id, $mineOnly[0]['id'], 'mine');

                $all = $host->getCardPropertiesInLocation(Game::LOCATION_HAND);
                Assert::count(2, $all, 'unfiltered');
                unset($theirs);
            },

            'createCardInLocation inserts, instantiates, and stamps Owner/Controller/Location' => function () {
                $host = $this->host();
                $card = $host->createCardInLocation('01045', Game::LOCATION_HAND, 1, 1);

                Assert::same(Game::LOCATION_HAND, $card->Location, 'Location');
                Assert::same(1, $card->OwnerId, 'owner');
                Assert::same(1, $card->ControllerId, 'controller');
                Assert::true(isset($host->dbCards[$card->Id]), 'registered in RAM');
                Assert::true(
                    count(array_filter($host->dbQueries, fn(string $sql) => stripos($sql, 'INSERT INTO card') !== false)) >= 1,
                    'INSERT recorded'
                );
            },
        ];
    }
}
