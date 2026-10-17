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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03012;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03024;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03026;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_03026_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_03026';
    }

    /**
     * @return array{0:_03026,1:Action_03026}
     */
    private function scene(TestWorld $world): array
    {
        $angeline = $world->placeCharacter(new _03026(), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Action_03026 $action */
        $action = $angeline->getActions()[0];
        return [$angeline, $action];
    }

    public function tests(): array
    {
        return [
            'is a character action, not a city-only action' => function () {
                Assert::instanceOf(CharacterAction::class, new Action_03026(), 'character action');
            },

            // WHY: She needs a hand card and an adjacent city. Home is a legal origin
            // (includeHome is false only for the destination). A blanked text box cannot act.
            'needs a card in hand and an adjacent city' => function () {
                $world = new TestWorld();
                [$angeline, $action] = $this->scene($world);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'empty hand');

                $world->placeCard(new _03024(), Game::LOCATION_HAND, 1);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'docks to forum');

                $angeline->Location = Game::LOCATION_PLAYER_HOME;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'home can move to a city');

                $angeline->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            // WHY: Discard is an effect, not a payment or a play. A card that is not in
            // hand throws before anything is queued. The location step clears CHOSEN_CARD.
            'discard then move, and a Sorcery discard opens the wound step' => function () {
                $world = new TestWorld();
                [$angeline, $action] = $this->scene($world);
                $plain = $world->placeCard(new _03024(), Game::LOCATION_HAND, 1);
                $sorcery = $world->placeCard(new _03012(), Game::LOCATION_HAND, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);

                $plain->Location = Game::LOCATION_CITY_DOCKS;
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03026, 'x', $plain->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                $plain->Location = Game::LOCATION_HAND;

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03026, 'x', $sorcery->Id);
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0];
                Assert::same($sorcery->Id, $discard->cardId, 'sorcery');
                Assert::true($discard->asEffect, 'effect');
                Assert::false($discard->AsPayment, 'not a payment');
                Assert::false($discard->AsPlayed, 'not played');
                Assert::same($sorcery->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'remembered');
                Assert::same('03026_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'location step');
                Assert::same(['cardDiscarded'], $world->game->gamestate->transitions, 'named transition');

                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];
                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03026_2, 'x', [Game::LOCATION_CITY_BAZAAR]);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'bazaar is not adjacent to docks');
                Assert::count(0, $world->theah->queuedOfType(EventCardMoving::class), 'no move');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03026_2, 'x', [Game::LOCATION_CITY_FORUM]);
                $move = $world->theah->queuedOfType(EventCardMoving::class)[0];
                Assert::same($angeline->Id, $move->cardId, 'angeline');
                Assert::same(Game::LOCATION_CITY_FORUM, $move->toLocation, 'forum');
                Assert::false($move->engage, 'does not engage');
                Assert::same('03026_3', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'sorcery opens the wound');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved yet');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_CARD), 'cleared');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                unset($foe);
            },

            'a non-Sorcery discard resolves on the move, and the wound step rejects a friend' => function () {
                $world = new TestWorld();
                [$angeline, $action] = $this->scene($world);
                $plain = $world->placeCard(new _03024(), Game::LOCATION_HAND, 1);
                $friend = $world->placeCharacter(new GenericCharacter('Friend'), Game::LOCATION_CITY_FORUM, 1);
                $world->game->globals->set(Game::CHOSEN_CARD, $plain->Id);

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_03026_2, 'x', [Game::LOCATION_CITY_FORUM]);
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no wound step');

                $angeline->Location = Game::LOCATION_CITY_FORUM;
                $world->theah->takeQueuedEvents();
                $world->game->gamestate->transitions = [];
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03026_3, 'x', $friend->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not opposing');

                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_03026_3, 'x', $foe->Id);
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($foe->Id, $wound->characterId, 'foe');
                Assert::same($angeline->Id, $wound->sourceId, 'angeline');
                Assert::same($action->Id, $wound->abilityId, 'the action');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved after the wound');
                Assert::same(['targetChosen'], $world->game->gamestate->transitions, 'target chosen');
            },

            'the trigger opens the discard step' => function () {
                $world = new TestWorld();
                [$angeline, $action] = $this->scene($world);
                $triggered = new EventActionTriggered();
                $triggered->theah = $world->theah;
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $action->handleEvent($triggered);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('03026', $transition->transition, 'discard step');
                Assert::same($angeline->Id, $transition->sourceId, 'angeline');
            },
        ];
    }
}
