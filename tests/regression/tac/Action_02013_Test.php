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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02013;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02013_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02013';
    }

    /**
     * Wilhelm in city, Relic in hand, opposing character at same location.
     *
     * @return array{0:_02013,1:Action_02013,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $wilhelm = $world->placeCharacter(new _02013(), Game::LOCATION_CITY_DOCKS, 1);
        $relic = $world->placeCharacter(new GenericCharacter('Relic', ['Relic']), Game::LOCATION_HAND, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_02013 $action */
        $action = $wilhelm->getActions()[0];
        return [$wilhelm, $action, $relic, $foe];
    }

    public function tests(): array
    {
        return [
            'is a CharacterAction that targets characters' => function () {
                $action = new Action_02013();
                Assert::instanceOf(CharacterAction::class, $action, 'CharacterAction');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $action, 'targets');
            },

            'available in city with Relic|Faith in hand and opposing at location' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'available with Faith instead of Relic' => function () {
                $world = new TestWorld();
                $wilhelm = $world->placeCharacter(new _02013(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Faith', ['Faith']), Game::LOCATION_HAND, 1);
                $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_02013 $action */
                $action = $wilhelm->getActions()[0];
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'Faith');
            },

            'unavailable at Player Home' => function () {
                $world = new TestWorld();
                [$wilhelm, $action] = $this->scene($world);
                $wilhelm->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Home');
            },

            'unavailable without Relic or Faith in hand' => function () {
                $world = new TestWorld();
                [, $action, $relic] = $this->scene($world);
                $relic->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no Relic/Faith');
            },

            'unavailable without opposing characters at location' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'no foes');
            },

            'trigger queues transition 02013' => function () {
                $world = new TestWorld();
                [$wilhelm, $action] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02013', $transitions[0]->transition, 'name');
                Assert::same($wilhelm->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY: discard choice must validate Relic|Faith server-side.
            'step 02013 refuses a non-Relic/Faith hand card' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_HAND, 1);

                $threw = false;
                try {
                    $action->actFromActionWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_02013,
                        'x',
                        $plain->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'Relic/Faith required');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'step 02013 accepts Relic and stores CHOSEN_CARD' => function () {
                $world = new TestWorld();
                [, $action, $relic] = $this->scene($world);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02013,
                    'x',
                    $relic->Id
                );

                Assert::same($relic->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['cardChosen'], $world->game->gamestate->transitions, 'next');
            },

            'isValidTarget rejects different location and own characters' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 2);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                [$okElsewhere,] = $action->isValidTargetForAbility($world->game, $elsewhere);
                Assert::false($okElsewhere, 'elsewhere');
                [$okAlly,] = $action->isValidTargetForAbility($world->game, $ally);
                Assert::false($okAlly, 'own');
            },

            'isValidTarget accepts opposing at same location' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                [$ok,] = $action->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'valid');
            },

            // WHY: City Action treats target as Sorcerer for the Combat challenge, then
            // clears the granted trait when ChallengeIssued fires for that defender.
            'step 02013_2 discards, grants Sorcerer, sets challenge globals, transitions' => function () {
                $world = new TestWorld();
                [, $action, $relic, $foe] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $relic->Id);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02013_2,
                    'x',
                    $foe->Id
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'discard');
                Assert::true($foe->hasTrait('Sorcerer'), 'granted Sorcerer');
                Assert::same($foe->Id, $action->TargetedCharacterId, 'targeted');
                Assert::same(
                    Game::WILHELM_DUNST_CHALLENGE_TYPE,
                    $world->game->globals->get(Game::CHALLENGE_TYPE),
                    'challenge type'
                );
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'Combat');
                Assert::same('02013_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, '02013_2');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'EventChallengeIssued on TargetedCharacterId removes Sorcerer and clears target' => function () {
                $world = new TestWorld();
                [, $action, , $foe] = $this->scene($world);
                $foe->addTrait($world->game, 'Sorcerer');
                $action->TargetedCharacterId = $foe->Id;

                $event = new EventChallengeIssued();
                $event->challengerId = 0;
                $event->defenderId = $foe->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::false($foe->hasTrait('Sorcerer'), 'cleared');
                Assert::same(null, $action->TargetedCharacterId, 'target cleared');
            },
        ];
    }
}
