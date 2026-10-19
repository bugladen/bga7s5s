<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04038;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;

class Card_04038_Test extends TestCase
{
    public function name(): string
    {
        return '_04038 Panacea';
    }

    public function tests(): array
    {
        return [
            'is an alquimia invigorant' => function () {
                $card = new _04038();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(2, $card->WealthCost, 'cost');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::true($card->hasTrait('Alquimia'), 'Alquimia');
                Assert::true($card->hasTrait('Invigorant'), 'Invigorant');
                Assert::count(1, $card->getActions(), 'action');
                Assert::count(1, $card->getManeuvers(), 'maneuver');
            },

            // WHY: Paying for Panacea grants Wealth to other Alquimia and Discovery cards
            // in hand. The card being paid for, and cards that already have Wealth, are
            // skipped. A nested in-hand reaction must not strip the grant. Paying for
            // something else, discarding the granted card, or ending the turn does.
            'grants wealth while paying, then revokes it' => function () {
                $world = new TestWorld();
                $panacea = $world->placeCard(new _04038(), Game::LOCATION_HAND, 1);
                $alquimia = $world->placeCard(new _04036(), Game::LOCATION_HAND, 1);
                $discovery = $world->placeCard(new _04037(), Game::LOCATION_HAND, 1);
                $plain = $world->placeCard(new _04027(), Game::LOCATION_HAND, 1);
                $already = $world->placeCard(new _04037(), Game::LOCATION_HAND, 1);
                $already->addTrait($world->game, 'Wealth');

                $pay = new EventEnteringPayState();
                $pay->cardId = $panacea->Id;
                $pay->playerId = 1;
                $pay->payStateType = Game::PAY_STATE_IN_HAND_ACTION;
                $world->fireOn($panacea, $pay);
                Assert::true($alquimia->hasTrait('Wealth'), 'alquimia');
                Assert::true($discovery->hasTrait('Wealth'), 'discovery');
                Assert::false($plain->hasTrait('Wealth'), 'bureaucracy');
                Assert::false($panacea->hasTrait('Wealth'), 'not itself');
                Assert::same([$alquimia->Id, $discovery->Id], $panacea->GrantedWealthCardIds, 'granted once');

                $world->fireOn($panacea, $pay);
                Assert::same([$alquimia->Id, $discovery->Id], $panacea->GrantedWealthCardIds, 're-entering pay does not double');

                $nested = new EventEnteringPayState();
                $nested->cardId = $plain->Id;
                $nested->playerId = 1;
                $nested->payStateType = Game::PAY_STATE_IN_HAND_REACTION;
                $world->fireOn($panacea, $nested);
                Assert::true($alquimia->hasTrait('Wealth'), 'nested reaction keeps the grant');

                $other = new EventEnteringPayState();
                $other->cardId = $plain->Id;
                $other->playerId = 1;
                $other->payStateType = Game::PAY_STATE_EQUIP_ATTACHMENT;
                $world->fireOn($panacea, $other);
                Assert::false($alquimia->hasTrait('Wealth'), 'other payment revokes');
                Assert::same([], $panacea->GrantedWealthCardIds, 'list cleared');

                $world->fireOn($panacea, $pay);
                $discard = new EventCardDiscardedFromHand();
                $discard->cardId = $alquimia->Id;
                $discard->ownerId = 1;
                $discard->AsPayment = true;
                $world->fireOn($panacea, $discard);
                Assert::same($alquimia->Id, $world->theah->queuedOfType(EventCardSentToLocker::class)[0]->cardId, 'locker');
                Assert::false(in_array($alquimia->Id, $panacea->GrantedWealthCardIds, true), 'removed from the list');
                Assert::true(in_array($discovery->Id, $panacea->GrantedWealthCardIds, true), 'the other grant remains');

                $end = new EventPlayerTurnEnd();
                $world->fireOn($panacea, $end);
                Assert::false($discovery->hasTrait('Wealth'), 'turn end revokes');
                Assert::same([], $panacea->GrantedWealthCardIds, 'empty');
            },
        ];
    }
}
