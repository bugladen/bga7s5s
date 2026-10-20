<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04042;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04042;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04042_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04042';
    }

    public function tests(): array
    {
        return [
            // WHY: Approach muster is EventApproachCharacterPlayed, not EventCharacterMustered.
            // A 0-cost Artifact with a legal Home host equips immediately. Pass shuffles and
            // does not spend. Cost above 0 opens equip pay before any cards are discarded.
            'searches the city deck and equips an artifact at home' => function () {
                $world = new TestWorld();
                $kaj = $world->placeCharacter(new _04042(), Game::LOCATION_APPROACH, 1);
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_PLAYER_HOME, 1);
                $free = $world->placeCard(new _04054(), Game::LOCATION_CITY_DECK, 0);
                $free->WealthCost = 0;
                $free->addTrait($world->game, 'Artifact');
                $priced = $world->placeCard(new _04054(), Game::LOCATION_CITY_DECK, 0);
                $priced->WealthCost = 1;
                $priced->Name = 'Priced Relic';
                $priced->addTrait($world->game, 'Artifact');
                $payment = $world->placeCard(new _04054(), Game::LOCATION_HAND, 1);
                /** @var Reaction_04042 $reaction */
                $reaction = $kaj->getReactions()[0];

                $other = new EventCharacterMustered();
                $other->characterId = $host->Id;
                $world->fireOn($kaj, $other);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not kaj');

                $muster = new EventCharacterMustered();
                $muster->characterId = $kaj->Id;
                $world->fireOn($kaj, $muster);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'search');

                $reaction->performReaction($world->game, 0, '', 'search-' . $free->Id);
                $reaction->performReaction($world->game, 0, '', 'equip_' . $host->Id);
                Assert::true($reaction->Used, 'zero cost equips now');
                $equip = $world->theah->queuedOfType(EventAttachmentEquipping::class)[0];
                Assert::same($host->Id, $equip->characterId, 'home host');
                Assert::same($free->Id, $equip->attachmentId, 'free artifact');
                Assert::same(0, $equip->cost, 'no wealth');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $approach = new EventApproachCharacterPlayed();
                $approach->characterId = $kaj->Id;
                $world->fireOn($kaj, $approach);
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'approach muster');

                $reaction->performReaction($world->game, 0, '', 'search-' . $priced->Id);
                $reaction->performReaction($world->game, 0, '', 'equip_' . $host->Id);
                Assert::false($reaction->Used, 'pay comes first');
                Assert::same(Game::PAY_STATE_EQUIP_ATTACHMENT, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'equip pay');
                Assert::same($host->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'discount performer');

                $reaction->performReaction($world->game, 0, '', 'pay-' . $payment->Id);
                Assert::true($reaction->Used, 'exact wealth equips');
                Assert::true($world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0]->AsPayment, 'payment');
                Assert::same($priced->Id, $world->theah->queuedOfType(EventAttachmentEquipping::class)[0]->attachmentId, 'priced');

                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $world->fireOn($kaj, $muster);
                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass does not spend');
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentEquipping::class), 'no equip');
            },
        ];
    }
}
