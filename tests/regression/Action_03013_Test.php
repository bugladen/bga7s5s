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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03013;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03013_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03013';
    }

    /**
     * @return array{0:_03013,1:Action_03013,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $daniella = $world->placeCharacter(new _03013(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Action_03013 $action */
        $action = $daniella->getActions()[0];
        return [$daniella, $action, $foe];
    }

    private function moved(TestWorld $world, int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->theah = $world->theah;
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a continuous character action' => function () {
                $action = new Action_03013();
                Assert::instanceOf(CharacterAction::class, $action, 'character action');
                Assert::same(403013, States::HIGH_DRAMA_PLAYER_TURN_03013, 'state');
            },

            // WHY: Home is one location string for every player, so enemies at their
            // Homes are not opposing Daniella. Already-Sorcerer and uncontrolled are out.
            // Trigger grants an extra action and clears Used so the action stays up.
            'offers opposing characters at her city location who are not already Sorcerers' => function () {
                $world = new TestWorld();
                [$daniella, $action, $foe] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03013, 'highDramaPhase03013');
                Assert::same([$foe->Id], $args['ids'], 'foe');
                Assert::same($daniella->Id, $args['performerId'], 'performer is Daniella');

                $foe->addTrait($world->game, 'Sorcerer', true);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'already a sorcerer');
                $foe->removeTrait($world->game, 'Sorcerer');

                $loose = $world->placeCharacter(new GenericCharacter('Loose'), Game::LOCATION_CITY_DOCKS, 0);
                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03013, 'highDramaPhase03013');
                Assert::false(in_array($loose->Id, $args['ids'], true), 'uncontrolled');

                $daniella->Location = Game::LOCATION_PLAYER_HOME;
                $foe->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home is not a shared space');
            },

            'confirm grants Sorcerer and does not spend the action' => function () {
                $world = new TestWorld();
                [$daniella, $action, $foe] = $this->scene($world);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                Assert::same(1, $world->game->globals->get(Game::EXTRA_ACTIONS), 'extra action');
                Assert::false($action->Used, 'continuous');
                Assert::same('03013', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03013, 'x', $foe->Id);
                Assert::true($foe->hasTrait('Sorcerer'), 'granted');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'transition');
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'already tagged');
            },

            'leaving her location, a challenge, turn end, or her destruction removes the grant' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03013, 'x', $foe->Id);
                $action->handleEvent($this->moved($world, $foe->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                Assert::false($foe->hasTrait('Sorcerer'), 'moved away');

                $world = new TestWorld();
                [$daniella, $action, $foe] = $this->scene($world);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03013, 'x', $foe->Id);
                $issued = new EventChallengeIssued();
                $issued->theah = $world->theah;
                $action->handleEvent($issued);
                Assert::false($foe->hasTrait('Sorcerer'), 'challenge started');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03013, 'x', $foe->Id);
                $end = new EventPlayerTurnEnd();
                $end->theah = $world->theah;
                $action->handleEvent($end);
                Assert::false($foe->hasTrait('Sorcerer'), 'turn ended');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03013, 'x', $foe->Id);
                $action->handleEvent($this->moved($world, $daniella->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                Assert::false($foe->hasTrait('Sorcerer'), 'she left');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03013, 'x', $foe->Id);
                $destroyed = new EventCharacterDestroyed();
                $destroyed->theah = $world->theah;
                $destroyed->characterId = $daniella->Id;
                $action->handleEvent($destroyed);
                Assert::false($foe->hasTrait('Sorcerer'), 'she was destroyed');
            },

            // WHY: A crewmate can be the duel actor. Daniella only has to share that
            // location and controller. The hub write is explicit because a self-loop
            // nextState does not flush IsUpdated.
            'the duel action tags the adversary even when Daniella is not the actor' => function () {
                $world = new TestWorld();
                [$daniella, $action, $foe] = $this->scene($world);
                $crew = $world->placeCharacter(new GenericCharacter('Crew'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $crew;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::true($action->isAvailableAsDuelAction($world->theah), 'crewmate actor');
                Assert::same($action, Action_03013::findAvailableDuelAction($world->theah), 'found');

                $action->actDuelConsiderAdversarySorcerer($world->game);
                Assert::true($foe->hasTrait('Sorcerer'), 'adversary tagged');
                Assert::true(in_array($foe->Id, $world->game->updatedCardObjectIds, true), 'adversary flushed');
                Assert::true(in_array($daniella->Id, $world->game->updatedCardObjectIds, true), 'daniella flushed');
                Assert::false($action->isAvailableAsDuelAction($world->theah), 'already tagged');

                $threw = false;
                try {
                    $action->actDuelConsiderAdversarySorcerer($world->game);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'second grant');
            },

            'an illegal confirm throws' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03013, 'x', $ally->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not opposing');
                Assert::false($ally->hasTrait('Sorcerer'), 'not granted');
            },
        ];
    }
}
