<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05FunkyCheese;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05FunkyCheese;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;

class Card_05FunkyCheese_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05FunkyCheese';
    }

    public function tests(): array
    {
        return [
            'constructs an Eisen Villain Priest with Covert Reaction' => function () {
                $card = new _05FunkyCheese();
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::same('Eisen', $card->Factions[0], 'faction');
                Assert::same(5, $card->Resolve, 'resolve');
                Assert::same(2, $card->Combat, 'combat');
                Assert::same(2, $card->Finesse, 'finesse');
                Assert::same(2, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Priest'), 'Priest');
                Assert::true($card->hasTrait('Zealot'), 'Zealot');
                Assert::false($card->LeaderWoundInfluenceBonusApplied, 'flag clear');
                Assert::instanceOf(Reaction_05FunkyCheese::class, $card->getReactions()[0], 'reaction');
            },

            // WHY: Leader wounds > Leichenberg wounds → +1 Influence once. Equal/less clears.
            // Leader wound events recompute even when Leichenberg's own Wounds are unchanged.
            // Muster against a wounded Leader applies without a self-wound. Silence clears/restores.
            'gains +1 Influence while Leader has more wounds' => function () {
                $world = new TestWorld();
                $leader = $world->placeCharacter(new GenericLeader(), Game::LOCATION_CITY_FORUM, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                $leichenberg = $world->placeCharacter(new _05FunkyCheese(), Game::LOCATION_CITY_DOCKS, 1);

                $leader->Wounds = 2;
                $leichenberg->Wounds = 0;
                $wounded = new EventCharacterWounded();
                $wounded->theah = $world->theah;
                $wounded->characterId = $leader->Id;
                $wounded->wounds = 0;
                $wounded->characterHandled = true;
                $leichenberg->handleEvent($wounded);

                $inf = $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0];
                Assert::same(2, $inf->OldInfluence, 'printed');
                Assert::same(3, $inf->NewInfluence, 'plus one');
                Assert::true($leichenberg->LeaderWoundInfluenceBonusApplied, 'flag');

                $world->theah->takeQueuedEvents();
                $leichenberg->handleEvent($wounded);
                Assert::count(0, $world->theah->queuedEvents, 'does not stack');

                $leichenberg->Wounds = 2;
                $selfWound = new EventCharacterWounded();
                $selfWound->theah = $world->theah;
                $selfWound->characterId = $leichenberg->Id;
                $selfWound->wounds = 0;
                $selfWound->characterHandled = true;
                $leichenberg->handleEvent($selfWound);
                // WHY: Queued InfluenceModified never mutates ModifiedInfluence in the harness —
                // clear is relative to the still-printed chip (Ise Combat sibling).
                $cleared = $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0];
                Assert::same(1, $cleared->NewInfluence, 'equal wounds clears');
                Assert::false($leichenberg->LeaderWoundInfluenceBonusApplied, 'cleared');

                $world->theah->takeQueuedEvents();
                $leichenberg->Wounds = 0;
                $leader->Wounds = 3;
                $muster = new EventCharacterMustered();
                $muster->theah = $world->theah;
                $muster->characterId = $leichenberg->Id;
                $leichenberg->handleEvent($muster);
                Assert::true($leichenberg->LeaderWoundInfluenceBonusApplied, 'muster applies');

                $world->theah->takeQueuedEvents();
                $leichenberg->onAbilitiesBlanked($world->theah);
                Assert::false($leichenberg->LeaderWoundInfluenceBonusApplied, 'blank removes');

                $world->theah->takeQueuedEvents();
                $leichenberg->onAbilitiesUnblanked($world->theah);
                Assert::true($leichenberg->LeaderWoundInfluenceBonusApplied, 'unblank restores');

                $world->theah->takeQueuedEvents();
                $approach = new EventApproachCharacterPlayed();
                $approach->theah = $world->theah;
                $approach->characterId = $leichenberg->Id;
                $leichenberg->LeaderWoundInfluenceBonusApplied = false;
                $leichenberg->handleEvent($approach);
                Assert::true($leichenberg->LeaderWoundInfluenceBonusApplied, 'approach applies');

                $world->theah->takeQueuedEvents();
                $leichenberg->Wounds = 1;
                $leader->Wounds = 0;
                $healed = new EventCharacterHealed();
                $healed->theah = $world->theah;
                $healed->characterId = $leader->Id;
                $healed->wounds = 0;
                $healed->characterHandled = true;
                $leichenberg->handleEvent($healed);
                Assert::false($leichenberg->LeaderWoundInfluenceBonusApplied, 'leader healed clears');
            },
        ];
    }
}
