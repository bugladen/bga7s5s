<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03028;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterCombatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;

class Card_03028_Test extends TestCase
{
    public function name(): string
    {
        return '_03028 Térence Rois';
    }

    public function tests(): array
    {
        return [
            'constructs the Montaigne character' => function () {
                $terence = new _03028();
                Assert::instanceOf(Character::class, $terence, 'character');
                Assert::instanceOf(IHasReactions::class, $terence, 'reactions');
                Assert::same(4, $terence->Resolve, 'resolve');
                Assert::same(0, $terence->Combat, 'combat');
                Assert::same(3, $terence->Finesse, 'finesse');
                Assert::same(2, $terence->Influence, 'influence');
                Assert::true($terence->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($terence->hasTrait('Merchant'), 'Merchant');
                Assert::true($terence->hasTrait('Aristocrat'), 'Aristocrat');
                Assert::instanceOf(Reaction_03028::class, $terence->getReactions()[0], 'reaction');
            },

            // WHY: The combat ban lives in eventCheck, which this method does not gate on
            // the blank. Theah is what skips the call. Other stats are allowed.
            'cannot issue a Combat challenge' => function () {
                $world = new TestWorld();
                $terence = $world->placeCharacter(new _03028(), Game::LOCATION_CITY_BAZAAR, 1);
                $issued = new EventChallengeIssued();
                $issued->theah = $world->theah;
                $issued->challengerId = $terence->Id;
                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
                $terence->addCondition(Game::FATES_SILENCE_CONDITION);
                $threw = false;
                try {
                    $terence->eventCheck($issued);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'combat, even while the condition is stamped');

                $world->game->globals->set(Game::CHALLENGE_STAT, Game::STAT_INFLUENCE);
                $terence->eventCheck($issued);
                Assert::true(true, 'influence is allowed');
            },

            // WHY: At the Grand Bazaar, as challenger or defender, Combat is set to Influence.
            // The restore event is queued only when ModifiedCombat differs from the stored
            // value, and the combat event itself is not applied by the queue.
            'dueling at the Grand Bazaar sets Combat equal to Influence' => function () {
                $world = new TestWorld();
                $terence = $world->placeCharacter(new _03028(), Game::LOCATION_CITY_BAZAAR, 1);
                $started = new EventDuelStarted();
                $started->theah = $world->theah;
                $started->challengerId = $terence->Id;
                $started->defenderId = 99;
                $terence->handleEvent($started);
                $combat = $world->theah->queuedOfType(EventCharacterCombatModified::class)[0];
                Assert::same(0, $combat->OldCombat, 'printed combat');
                Assert::same(2, $combat->NewCombat, 'influence');
                Assert::true($terence->DuelCombatEqualsInfluenceApplied, 'flag');
                Assert::same(0, $terence->CombatBeforeDuelOverride, 'stored');

                $terence->ModifiedCombat = 2;
                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $world->theah->takeQueuedEvents();
                $terence->handleEvent($end);
                Assert::same(0, $world->theah->queuedOfType(EventCharacterCombatModified::class)[0]->NewCombat, 'restored');
                Assert::false($terence->DuelCombatEqualsInfluenceApplied, 'cleared');

                $terence->Location = Game::LOCATION_CITY_DOCKS;
                $world->theah->takeQueuedEvents();
                $terence->handleEvent($started);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterCombatModified::class), 'not the bazaar');
            },

            'blanking clears the override, and unblanking reapplies it during the duel' => function () {
                $world = new TestWorld();
                $terence = $world->placeCharacter(new _03028(), Game::LOCATION_CITY_BAZAAR, 1);
                $terence->DuelCombatEqualsInfluenceApplied = true;
                $terence->CombatBeforeDuelOverride = 0;
                $terence->ModifiedCombat = 2;
                $terence->onAbilitiesBlanked($world->theah);
                Assert::false($terence->DuelCombatEqualsInfluenceApplied, 'blank clears');
                Assert::same(0, $world->theah->queuedOfType(EventCharacterCombatModified::class)[0]->NewCombat, 'restored');

                $world->game->globals->set(Game::IN_DUEL, true);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_BAZAAR, 2);
                $world->theah->duelActor = $terence;
                $world->theah->duelOpponent = $foe;
                $terence->ModifiedCombat = 0;
                $world->theah->takeQueuedEvents();
                $terence->onAbilitiesUnblanked($world->theah);
                Assert::true($terence->DuelCombatEqualsInfluenceApplied, 'reapplied');
                Assert::same(2, $world->theah->queuedOfType(EventCharacterCombatModified::class)[0]->NewCombat, 'back to influence');
            },
        ];
    }
}
