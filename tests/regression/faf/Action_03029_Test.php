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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03029;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03029;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;

class Action_03029_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03029';
    }

    /**
     * @return array{0:_03029,1:Action_03029,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _03029(), Game::LOCATION_PLAYER_HOME, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Witch', ['Sorcerer', 'Hero']), Game::LOCATION_CITY_DOCKS, 1);
        $away = $world->placeCharacter(new GenericCharacter('Away', ['Hero', 'Spy']), Game::LOCATION_CITY_FORUM, 1);
        $performer->Engaged = true;
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        /** @var Action_03029 $action */
        $action = $scheme->getActions()[0];
        return [$scheme, $action, $performer, $away];
    }

    public function tests(): array
    {
        return [
            'is a sorcerer city action on a scheme' => function () {
                $action = new Action_03029();
                Assert::instanceOf(SchemeCityAction::class, $action, 'scheme city action');
                Assert::instanceOf(ISorcererAbility::class, $action, 'sorcery');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            // WHY: Performers are city characters with Sorcerer. Engage is not a cost.
            // Mode 1 needs someone who is not already there. Mode 2 includes the performer,
            // because a city character always has another destination.
            'an engaged Sorcerer can pull someone in or send someone out' => function () {
                $world = new TestWorld();
                [, $action, $performer, $away] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'available');
                Assert::same([$performer->Id], array_map(fn($c) => $c->Id, $action->getPerformersForAction(1, $world->theah)), 'sorcerer only');

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_03029, 'x');
                Assert::true($args['optionToPerformerAvailable'], 'someone is elsewhere');
                Assert::true($args['optionFromPerformerAvailable'], 'the performer can leave');

                $action->MoveMode = 1;
                [$ok] = $action->isValidTargetForAbility($world->game, $away);
                Assert::true($ok, 'not already there');
                [$ok] = $action->isValidTargetForAbility($world->game, $performer);
                Assert::false($ok, 'already at the performer');

                $action->MoveMode = 2;
                [$ok] = $action->isValidTargetForAbility($world->game, $performer);
                Assert::true($ok, 'can leave');
                [$ok] = $action->isValidTargetForAbility($world->game, $away);
                Assert::false($ok, 'not at the performer');

                $action->MoveMode = 0;
                [$ok] = $action->isValidTargetForAbility($world->game, $away);
                Assert::false($ok, 'no mode chosen');

                $scheme = $world->theah->getCardById($action->OwnerId);
                $scheme->Location = Game::LOCATION_CITY_DOCKS;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'scheme is not home');
            },

            // WHY: The wound is paid inside the move, after the sorcery start. Mode 1
            // resolves immediately. Mode 2 only remembers the character.
            'moving to the performer wounds them and moves the other character' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer, $away] = $this->scene($world);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03029, 'x', 1);
                Assert::same(1, $action->MoveMode, 'to the performer');
                Assert::same(['optionChosen'], $world->game->gamestate->transitions, 'option');
                Assert::count(0, $world->theah->queuedEvents, 'no move yet');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03029_2, 'x', $away->Id);
                Assert::same($performer->Id, $world->theah->queuedOfType(EventSorcererAbilityStart::class)[0]->performerId, 'start');
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0]->characterId, 'wound the performer');
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($away->Id, $move->cardId, 'the other character');
                Assert::same($performer->Location, $move->toLocation, 'to the performer');
                Assert::false($move->engage, 'does not engage');
                Assert::same($performer->Location, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class)[0]->targetLocation, 'played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(0, $action->MoveMode, 'cleared');
                Assert::same($scheme->Id, $world->theah->queuedOfType(EventSorcererAbilityStart::class)[0]->sourceId, 'scheme');
            },

            'moving away stores the character, then rejects a location they already occupy' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03029, 'x', 2);
                $world->game->gamestate->transitions = [];
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03029_2, 'x', $performer->Id);
                Assert::same($performer->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'stored');
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'location step next');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'not yet');

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03029_3, 'x', [$performer->Location]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'already there');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03029_3, 'x', [Game::LOCATION_PLAYER_HOME]);
                Assert::same(Game::LOCATION_PLAYER_HOME, $world->theah->queuedOfType(EventCardMoving::class)[0]->toLocation, 'home');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_CARD), 'cleared');
                Assert::same(0, $action->MoveMode, 'cleared');
                Assert::same(['characterChosen', 'locationChosen'], $world->game->gamestate->transitions, 'location chosen');
            },
        ];
    }
}
