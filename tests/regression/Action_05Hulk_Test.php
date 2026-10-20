<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Hulk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Hulk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05Hulk_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Hulk';
    }

    public function tests(): array
    {
        return [
            // WHY: Engage and the non-Berserker wound are costs paid at announce.
            // A Berserker skips the wound. An already-engaged performer is not engaged again.
            'engages and wounds at announce, unless the performer is a berserker' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Hulk(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Brute'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_05Hulk $action */
                $action = $risk->getActions()[0];
                Assert::true($risk->effectsCannotBeCancelled(), 'cannot cancel');
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'city performer with a target');

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $action->announceAction($world->game);
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage cost');
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wound cost');

                $performer->addTrait($world->game, 'Berserker', true);
                $world->theah->takeQueuedEvents();
                $action->announceAction($world->game);
                Assert::count(1, $world->theah->queuedOfType(EventCardEngaged::class), 'berserker still engages');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'berserker skips the wound');

                $world->theah->takeQueuedEvents();
                $performer->Engaged = true;
                $action->announceAction($world->game);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'already engaged');
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged hides it');
            },

            'hides performers who cannot issue a combat challenge' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Hulk(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Brute'), Game::LOCATION_CITY_DOCKS, 1);
                $home = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_05Hulk $action */
                $action = $risk->getActions()[0];

                $performer->DashedCombat = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'dashed combat');
                $performer->DashedCombat = false;

                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no opposing character');
                $foe->Location = Game::LOCATION_CITY_DOCKS;

                $ids = array_map(fn($character) => $character->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::same([$performer->Id], array_values($ids), 'home stays home');
                Assert::false(in_array($home->Id, $ids, true), 'home not a city performer');

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                [$ok] = $action->isValidTargetForAbility($world->game, $performer);
                Assert::false($ok, 'cannot target your own character');
                $foe->Location = Game::LOCATION_CITY_FORUM;
                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::false($ok, 'must share the location');
                $foe->Location = Game::LOCATION_CITY_DOCKS;
                [$ok] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'opposing at the location');
            },

            // WHY: The type is Rage (combat, unrefusable) and the action is not resolved
            // at trigger. The -2 Parry arms when the challenge is issued, follows an
            // intervener, and applies only to that character's first combat card.
            'issues an unrefusable combat challenge and cuts the adversary first parry' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _05Hulk(), Game::LOCATION_HAND, 1);
                $spare = $world->placeCard(new _05Hulk(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Brute'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $intervener = $world->placeCharacter(new GenericCharacter('Intervener'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_05Hulk $action */
                $action = $risk->getActions()[0];
                /** @var Action_05Hulk $other */
                $other = $spare->getActions()[0];

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($risk, $triggered);
                Assert::same(Game::RAGE_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'rage');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'combat');
                Assert::same('05Hulk', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'issue');

                $world->game->globals->set(Game::CHOSEN_ACTION, $action->Id);
                $issued = new EventChallengeIssued();
                $issued->defenderId = $foe->Id;
                $issued->theah = $world->theah;
                $action->handleEvent($issued);
                $other->handleEvent($issued);
                Assert::same($foe->Id, $action->FirstCombatParryCharacterId, 'adversary armed');
                Assert::same(0, $other->FirstCombatParryCharacterId, 'the other copy stays quiet');

                $steppedIn = new EventCharacterIntervened();
                $steppedIn->oldTargetId = $foe->Id;
                $steppedIn->newTargetId = $intervener->Id;
                $steppedIn->theah = $world->theah;
                $action->handleEvent($steppedIn);
                Assert::same($intervener->Id, $action->FirstCombatParryCharacterId, 'intervener is the adversary');

                $mine = new EventDuelCalculateCombatCardStats();
                $mine->actorId = $performer->Id;
                $mine->theah = $world->theah;
                $action->handleEvent($mine);
                Assert::same(0, $mine->parry, 'performer is not penalized');
                Assert::same($intervener->Id, $action->FirstCombatParryCharacterId, 'still armed');

                $calc = new EventDuelCalculateCombatCardStats();
                $calc->actorId = $intervener->Id;
                $calc->theah = $world->theah;
                $action->handleEvent($calc);
                Assert::same(-2, $calc->parry, 'minus two');
                Assert::same(0, $action->FirstCombatParryCharacterId, 'spent on the first card');

                $action->FirstCombatParryCharacterId = $intervener->Id;
                $world->game->globals->set(Game::IN_DUEL, true);
                $resolved = new EventActionResolved();
                $resolved->theah = $world->theah;
                $action->handleEvent($resolved);
                Assert::same($intervener->Id, $action->FirstCombatParryCharacterId, 'mid-duel resolve keeps it');
                $world->game->globals->set(Game::IN_DUEL, false);
                $action->handleEvent($resolved);
                Assert::same(0, $action->FirstCombatParryCharacterId, 'outside a duel it clears');

                $action->FirstCombatParryCharacterId = $intervener->Id;
                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $action->handleEvent($end);
                Assert::same(0, $action->FirstCombatParryCharacterId, 'duel end');
            },
        ];
    }
}
