<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02007;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02008;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02008_RiskClone;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02008;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02008_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02008';
    }

    /**
     * Fate's Kiss in hand; Sorcerer Strega; Risk in discard; opposing city character.
     *
     * @return array{0:_02008,1:Action_02008,2:GenericCharacter,3:_02007,4:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $kiss = $world->placeCard(new _02008(), Game::LOCATION_HAND, 1);
        $strega = $world->placeCharacter(
            new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $discarded = $world->placeCard(new _02007(), $world->game->getPlayerDiscardDeckName(1), 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
        /** @var Action_02008 $action */
        $action = $kiss->getActions()[0];
        return [$kiss, $action, $strega, $discarded, $foe];
    }

    public function tests(): array
    {
        return [
            'is a RiskAction Sorcerer ability that targets characters' => function () {
                $action = new Action_02008();
                Assert::instanceOf(RiskAction::class, $action, 'RiskAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'sorcerer');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY (journal 2026-03-16-01): Sorcerer Strega performer gates + discard Risk + opposing city.
            'available with Sorcerer Strega, discard Risk, and opposing city character' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable without Sorcerer Strega' => function () {
                $world = new TestWorld();
                $kiss = $world->placeCard(new _02008(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCard(new _02007(), $world->game->getPlayerDiscardDeckName(1), 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                /** @var Action_02008 $action */
                $action = $kiss->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no strega');
            },

            'unavailable with empty discard' => function () {
                $world = new TestWorld();
                $kiss = $world->placeCard(new _02008(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                /** @var Action_02008 $action */
                $action = $kiss->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty discard');
            },

            // WHY: opposing Home is not a legal target — only city characters count.
            'unavailable when only opposing character is at Home' => function () {
                $world = new TestWorld();
                $kiss = $world->placeCard(new _02008(), Game::LOCATION_HAND, 1);
                $world->placeCharacter(
                    new GenericCharacter('Strega', ['Sorcerer', 'Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCard(new _02007(), $world->game->getPlayerDiscardDeckName(1), 1);
                $world->placeCharacter(new GenericCharacter('Home Foe'), Game::LOCATION_PLAYER_HOME, 2);
                /** @var Action_02008 $action */
                $action = $kiss->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home only');
            },

            'performers are Sorcerer Stregas only' => function () {
                $world = new TestWorld();
                [, $action, $strega] = $this->scene($world);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);

                $ids = array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah));
                Assert::true(in_array($strega->Id, $ids, true), 'strega');
                Assert::false(in_array($plain->Id, $ids, true), 'plain');
            },

            'trigger queues transition 02008' => function () {
                $world = new TestWorld();
                [$kiss, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::same('02008', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'name');
                Assert::same($kiss->Id, $world->theah->queuedOfType(EventTransition::class)[0]->sourceId, 'source');
            },

            'args step 1 lists Risks from discard excluding Fate\'s Kiss itself' => function () {
                $world = new TestWorld();
                [$kiss, $action, , $discarded] = $this->scene($world);
                $world->game->activePlayerId = 1;
                $world->placeCard(new _02008(), $world->game->getPlayerDiscardDeckName(1), 1);

                $args = $action->getArgsFromAction(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02008,
                    'highDramaPlayerTurn_02008'
                );

                $ids = array_map(fn($c) => $c['id'], $args['cards']);
                Assert::true(in_array($discarded->Id, $ids, true), 'discarded risk');
                Assert::false(in_array($kiss->Id, $ids, true), 'kiss in hand excluded');
            },

            'act step 1 stores CHOSEN_CARD and starts sorcery' => function () {
                $world = new TestWorld();
                [, $action, $strega, $discarded] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02008,
                    'highDramaPlayerTurn_02008',
                    $discarded->Id
                );

                Assert::same($discarded->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'sorcery start');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'isValidTarget rejects own character and Home characters' => function () {
                $world = new TestWorld();
                [, $action, $strega, , $foe] = $this->scene($world);
                $homeFoe = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 2);

                [$okOwn,] = $action->isValidTargetForAbility($world->game, $strega);
                Assert::false($okOwn, 'own');
                [$okHome,] = $action->isValidTargetForAbility($world->game, $homeFoe);
                Assert::false($okHome, 'home');
                [$okFoe,] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($okFoe, 'city foe');
            },

            // WHY: TargetCharacterId wiring from Action to clone — destroy path needs this id.
            'act step 2 hides risk, places face-down RiskClone under foe with TargetCharacterId' => function () {
                $world = new TestWorld();
                [$kiss, $action, $strega, $discarded, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $discarded->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02008_2,
                    'highDramaPlayerTurn_02008_2',
                    $foe->Id
                );

                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $discarded->Location, 'original hidden');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'removed');

                $clones = array_filter(
                    $world->game->createdCardsInLocation,
                    fn($c) => $c instanceof _02008_RiskClone
                );
                Assert::count(1, $clones, 'one clone');
                /** @var _02008_RiskClone $clone */
                $clone = array_values($clones)[0];
                Assert::true($clone->FaceDown, 'face-down');
                Assert::same($discarded->Id, $clone->ClonedCardId, 'cloned id');
                Assert::same($foe->Id, $clone->TargetCharacterId, 'target wired');
                Assert::same($foe->Location, $clone->Location, 'under foe location');
                Assert::same($discarded->Name, $clone->Name, 'name copied');

                // WHY: EventFactory::createAttachmentEquippedEvent actually queues EventAttachmentEquipping.
                Assert::count(1, $world->theah->queuedOfType(EventAttachmentEquipping::class), 'equip quiet');
                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class);
                Assert::count(1, $played, 'sorcery end');
                Assert::same($kiss->Id, $played[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'act step 2 rejects own character' => function () {
                $world = new TestWorld();
                [, $action, $strega, $discarded] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $strega->Id);
                $world->game->globals->set(Game::CHOSEN_CARD, $discarded->Id);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02008_2,
                        'highDramaPlayerTurn_02008_2',
                        $strega->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },
        ];
    }
}
