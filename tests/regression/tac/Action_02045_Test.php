<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02045;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02045;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_02045_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_02045';
    }

    /**
     * @return array{0:_02045,1:Action_02045,2:GenericCharacter}
     */
    private function scene(TestWorld $world, string $schemeLocation = Game::LOCATION_CITY_DOCKS): array
    {
        $scheme = $world->placeCard(new _02045(), $schemeLocation, 1);
        $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;
        $performer = $world->placeCharacter(
            new GenericCharacter('Sorcerer', ['Sorcerer']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        /** @var Action_02045 $action */
        $action = $scheme->getActions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        return [$scheme, $action, $performer];
    }

    public function tests(): array
    {
        return [
            'is a SchemeCityAction and a Sorcerer ability that requires a performer' => function () {
                $action = new Action_02045();
                Assert::instanceOf(SchemeCityAction::class, $action, 'SchemeCityAction');
                Assert::instanceOf(ISorcererAbility::class, $action, 'sorcery');
                Assert::true($action->RequiresPerformerSelected, 'performer');
            },

            'state constant registered' => function () {
                Assert::same(402045, States::HIGH_DRAMA_PLAYER_TURN_02045, 'search');
            },

            // WHY: Full override. SchemeAction would require Player Home, but this scheme lives in the city.
            'available to a Sorcerer at the chosen location who can pressure with Influence' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'in city');

                $scheme->Location = Game::LOCATION_HAND;
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'hand is fine once placed');

                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::count(1, $performers, 'one');
                Assert::same($performer->Id, $performers[0]->Id, 'sorcerer');
            },

            'unavailable before a location is chosen, when used, or for the other player' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);
                $scheme->ChosenLocation = '';
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not placed');

                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;
                $action->Used = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'used');

                $action->Used = false;
                $scheme->ControllerId = 2;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'other controller');
            },

            'a dashed-Influence Sorcerer or a Sorcerer elsewhere cannot perform it' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _02045(), Game::LOCATION_CITY_DOCKS, 1);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;
                $world->placeCharacter(new _02043(), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_02045 $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'Miyato dashed Influence');

                $world->placeCharacter(new GenericCharacter('Away', ['Sorcerer']), Game::LOCATION_CITY_FORUM, 1);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'still not at the chosen location');
            },

            'trigger starts the sorcery and pressures the performer location with Influence' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $starts = $world->theah->queuedOfType(EventSorcererAbilityStart::class);
                Assert::count(1, $starts, 'start');
                Assert::same($scheme->Id, $starts[0]->sourceId, 'source');
                Assert::same($action->Id, $starts[0]->abilityId, 'ability');
                Assert::same($performer->Id, $starts[0]->performerId, 'performer');

                $pressures = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $pressures, 'pressure');
                Assert::same($performer->Location, $pressures[0]->location, 'location');
                Assert::true(in_array(Game::STAT_INFLUENCE, $pressures[0]->pressureTypes, true), 'influence');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('pressureLocation', $transitions[0]->transition, 'name');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability');
                Assert::same(1, $world->game->globals->get(Game::PRESSURING_PLAYER), 'pressuring');
                Assert::same(Game::NORMAL_PRESSURE_TYPE, $world->game->globals->get(Game::PRESSURE_TYPE), 'normal');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played waits');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
            },

            // WHY: Played is deferred until the deck search finishes. Success only opens the search.
            'successful pressure opens the deck search' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);

                $event = new EventLocationPressureResult();
                $event->abilityId = $action->Id;
                $event->success = true;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'search');
                Assert::same('02045', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'not yet');
            },

            'failed pressure plays the sorcery and resolves the action' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);

                $event = new EventLocationPressureResult();
                $event->abilityId = $action->Id;
                $event->success = false;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class);
                Assert::count(1, $played, 'played');
                Assert::same($performer->Id, $played[0]->performerId, 'performer');
                Assert::same($scheme->Id, $played[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no search');
            },

            'args list Dar Matushki and Poluchatel cards in the faction deck' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $deck = $world->game->getPlayerFactionDeckName(1);
                $dar = $world->placeCard(new _02045(), $deck, 1);
                $poluchatel = $world->placeCard(new _02047(), $deck, 1);
                $world->placeCard(new _02041(), $deck, 1);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_02045, 'highDramaPlayerTurn_02045');
                $ids = array_column($args['cards'], 'id');
                Assert::same([$dar->Id, $poluchatel->Id], $ids, 'matches');
            },

            'taking a matching card reveals it into hand, shuffles, and finishes the sorcery' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);
                $deck = $world->game->getPlayerFactionDeckName(1);
                $dar = $world->placeCard(new _02045(), $deck, 1);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_02045,
                    'highDramaPlayerTurn_02045',
                    $dar->Id
                );

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerFactionDeck::class);
                $added = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::count(1, $removed, 'removed');
                Assert::same($dar->Id, $removed[0]->cardId, 'card');
                Assert::count(1, $added, 'hand');
                Assert::same($dar->Id, $added[0]->cardId, 'same');
                Assert::same([$deck], $world->game->deckShuffles, 'shuffled');
                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class);
                Assert::same($performer->Id, $played[0]->performerId, 'performer');
                Assert::same($scheme->Id, $played[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'refuses a card that is missing, outside the deck, or the wrong trait' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $deck = $world->game->getPlayerFactionDeckName(1);
                $inCity = $world->placeCard(new _02045(), Game::LOCATION_CITY_FORUM, 1);
                $flourish = $world->placeCard(new _02041(), $deck, 1);

                foreach ([9999, $inCity->Id, $flourish->Id] as $id) {
                    $threw = false;
                    try {
                        $action->actFromActionWithId(
                            $world->game,
                            States::HIGH_DRAMA_PLAYER_TURN_02045,
                            'highDramaPlayerTurn_02045',
                            $id
                        );
                    } catch (\BgaUserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, 'rejected ' . $id);
                }
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same([], $world->game->deckShuffles, 'no shuffle');
            },

            'pass is refused while a matching card is still in the deck' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $world->placeCard(new _02047(), $world->game->getPlayerFactionDeckName(1), 1);

                $threw = false;
                try {
                    $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_02045);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'must take');
                Assert::same([], $world->game->deckShuffles, 'no shuffle');
                Assert::same([], $world->game->gamestate->transitions, 'still here');
            },

            'pass shuffles and finishes the sorcery when the deck has no match' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);
                $deck = $world->game->getPlayerFactionDeckName(1);
                $world->placeCard(new _02041(), $deck, 1);

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_02045);

                Assert::same([$deck], $world->game->deckShuffles, 'shuffled');
                $played = $world->theah->queuedOfType(EventSorcererAbilityPlayed::class);
                Assert::same($performer->Id, $played[0]->performerId, 'performer');
                Assert::same($scheme->Id, $played[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'ignores a different action id and a different pressure result' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $trigger = new EventActionTriggered();
                $trigger->actionId = 'other';
                $trigger->theah = $world->theah;
                $action->handleEvent($trigger);

                $result = new EventLocationPressureResult();
                $result->abilityId = 'other';
                $result->success = true;
                $result->theah = $world->theah;
                $action->handleEvent($result);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },
        ];
    }
}
