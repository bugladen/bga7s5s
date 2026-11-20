<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericFactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Rum;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Rum;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_05Rum_Test extends TestCase
{
    public function name(): string
    {
        return '_05Rum X Marks the Spot';
    }

    /**
     * @return array{0:_05Rum,1:Action_05Rum,2:GenericCharacter}
     */
    private function scene(TestWorld $world, string $schemeLocation = Game::LOCATION_CITY_DOCKS): array
    {
        /** @var _05Rum $scheme */
        $scheme = $world->placeCard(new _05Rum(), $schemeLocation, 1);
        $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;
        $performer = $world->placeCharacter(
            new GenericCharacter('Corsair', ['Pirate']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        /** @var Action_05Rum $action */
        $action = $scheme->getActions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        return [$scheme, $action, $performer];
    }

    public function tests(): array
    {
        return [
            'constructs Daring Camaraderie scheme with Pirate City Action' => function () {
                $scheme = new _05Rum();
                Assert::same('X Marks the Spot', $scheme->Name, 'name');
                Assert::same(6, $scheme->Initiative, 'initiative');
                Assert::same(1, $scheme->PanacheModifier, 'panache');
                Assert::true($scheme->hasTrait('Daring'), 'Daring');
                Assert::true($scheme->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::false($scheme->hasTrait('Fortune'), 'not Fortune');
                Assert::instanceOf(Action_05Rum::class, $scheme->getActions()[0], 'action');
                Assert::instanceOf(SchemeCityAction::class, $scheme->getActions()[0], 'SchemeCityAction');
            },

            'state constants avoid CardNumber-24 collisions' => function () {
                Assert::same(2605241, States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM, 'resolve');
                Assert::same(26052412, States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM_2, 'resolve2');
                Assert::same(405241, States::HIGH_DRAMA_PLAYER_TURN_05RUM, 'search');
                Assert::same(4052412, States::HIGH_DRAMA_PLAYER_TURN_05RUM_2, 'pay');
            },

            'resolve queues city-location pick' => function () {
                $world = new TestWorld();
                /** @var _05Rum $scheme */
                $scheme = $world->placeCard(new _05Rum(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->playerId = 1;
                $event->scheme = $scheme;
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('05Rum', $transitions[0]->transition, 'pick key');
            },

            'placing chosen location then Renown elsewhere queues scheme move' => function () {
                $world = new TestWorld();
                /** @var _05Rum $scheme */
                $scheme = $world->placeCard(new _05Rum(), Game::LOCATION_PLAYER_HOME, 1);
                $world->game->gamestate->currentMainStateId = States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM;

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM,
                    'planningPhaseResolveSchemes_05Rum',
                    '',
                    [Game::LOCATION_CITY_FORUM]
                );

                Assert::same(Game::LOCATION_CITY_FORUM, $scheme->ChosenLocation, 'chosen');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'to renown');

                $world->game->gamestate->transitions = [];
                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_05RUM_2,
                    'planningPhaseResolveSchemes_05Rum_2',
                    '',
                    [Game::LOCATION_CITY_DOCKS]
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'renown');
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown[0]->location, 'docks');

                $moves = $world->theah->queuedOfType(EventSchemeMovedToCity::class);
                Assert::count(1, $moves, 'scheme move');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->location, 'forum');
                Assert::same(['locationChosen'], $world->game->gamestate->transitions, 'done');
            },

            // WHY: Full override. SchemeAction would require Player Home, but this scheme lives in the city.
            'available to a Pirate at the chosen location who can pressure with Finesse' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'in city');

                $performers = $action->getPerformersForAction(1, $world->theah);
                Assert::count(1, $performers, 'one');
                Assert::same($performer->Id, $performers[0]->Id, 'pirate');

                $scheme->ChosenLocation = '';
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not placed');
            },

            'a non-Pirate at the chosen location cannot perform it' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _05Rum(), Game::LOCATION_CITY_DOCKS, 1);
                $scheme->ChosenLocation = Game::LOCATION_CITY_DOCKS;
                $world->placeCharacter(new GenericCharacter('Scholar', ['Academic']), Game::LOCATION_CITY_DOCKS, 1);
                /** @var Action_05Rum $action */
                $action = $scheme->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not pirate');
            },

            'trigger pressures the performer location with Finesse' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);

                $event = new EventActionTriggered();
                $event->actionId = $action->Id;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $pressures = $world->theah->queuedOfType(EventPressureOccuring::class);
                Assert::count(1, $pressures, 'pressure');
                Assert::same($performer->Location, $pressures[0]->location, 'location');
                Assert::true(in_array(Game::STAT_FINESSE, $pressures[0]->pressureTypes, true), 'finesse');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('pressureLocation', $transitions[0]->transition, 'name');
                Assert::count(0, $world->theah->queuedOfType(EventActionResolved::class), 'not resolved');
            },

            'successful pressure opens the Unique Attachment search' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);

                $event = new EventLocationPressureResult();
                $event->abilityId = $action->Id;
                $event->success = true;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'search');
                Assert::same('05Rum', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same($action->Id, $transitions[0]->internalId, 'ability');
            },

            'failed pressure resolves the action' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);

                $event = new EventLocationPressureResult();
                $event->abilityId = $action->Id;
                $event->success = false;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $action->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no search');
            },

            'args list equippable Unique Attachments in the faction deck' => function () {
                $world = new TestWorld();
                [, $action, $performer] = $this->scene($world);
                $deck = $world->game->getPlayerFactionDeckName(1);

                $unique = new GenericFactionAttachment('Unique Blade', 0);
                $unique->Traits = ['Unique', 'Weapon'];
                $unique = $world->placeCard($unique, $deck, 1);

                $plain = new GenericFactionAttachment('Plain Blade', 0);
                $plain->Traits = ['Weapon'];
                $world->placeCard($plain, $deck, 1);

                $args = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05RUM, 'highDramaPhase05Rum');
                $ids = array_column($args['cards'], 'id');
                Assert::same([$unique->Id], $ids, 'unique only');
                Assert::same($performer->Id, $args['performerId'], 'performer');
            },

            'taking a Unique Attachment queues deck remove, hand add, pay, and shuffle' => function () {
                $world = new TestWorld();
                [$scheme, $action] = $this->scene($world);
                $deck = $world->game->getPlayerFactionDeckName(1);

                $unique = new GenericFactionAttachment('Unique Blade', 0);
                $unique->Traits = ['Unique', 'Weapon'];
                $unique = $world->placeCard($unique, $deck, 1);

                $action->actFromActionWithId(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05RUM,
                    'highDramaPhase05Rum',
                    $unique->Id
                );

                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlayerFactionDeck::class), 'removed');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToHand::class), 'hand');
                Assert::count(1, $world->theah->queuedOfType(EventEnteringPayState::class), 'pay');
                Assert::same([$deck], $world->game->deckShuffles, 'shuffled');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'pay transition');
                Assert::same('05Rum_2', $transitions[0]->transition, 'pay key');
                Assert::same(['attachmentChosen'], $world->game->gamestate->transitions, 'named');
            },

            'pass is refused while an equippable Unique Attachment remains' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $unique = new GenericFactionAttachment('Unique Blade', 0);
                $unique->Traits = ['Unique', 'Weapon'];
                $world->placeCard($unique, $world->game->getPlayerFactionDeckName(1), 1);

                $threw = false;
                try {
                    $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_05RUM);
                } catch (\Bga\GameFramework\UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'must take');
                Assert::same([], $world->game->deckShuffles, 'no shuffle');
            },

            'pass shuffles and resolves when no equippable Unique Attachment exists' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                $plain = new GenericFactionAttachment('Plain Blade', 0);
                $plain->Traits = ['Weapon'];
                $world->placeCard($plain, $world->game->getPlayerFactionDeckName(1), 1);

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_05RUM);

                Assert::same([$world->game->getPlayerFactionDeckName(1)], $world->game->deckShuffles, 'shuffled');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['pass'], $world->game->gamestate->transitions, 'pass');
            },

            'paying equips the Unique Attachment and resolves' => function () {
                $world = new TestWorld();
                [$scheme, $action, $performer] = $this->scene($world);

                $unique = new GenericFactionAttachment('Unique Blade', 0);
                $unique->Traits = ['Unique', 'Weapon'];
                $unique = $world->placeCard($unique, Game::LOCATION_HAND, 1);
                $unique->ControllerId = 1;

                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $unique->Id);
                $world->game->globals->set(Game::CHOSEN_CARD_COST, 0);
                $world->game->globals->set(Game::DISCOUNT, 0);

                $action->actFromActionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_05RUM_2,
                    'highDramaPhase05Rum_2',
                    []
                );

                $equips = $world->theah->queuedOfType(EventAttachmentEquipping::class);
                Assert::count(1, $equips, 'equip');
                Assert::same($unique->Id, $equips[0]->attachmentId, 'attachment');
                Assert::same($performer->Id, $equips[0]->characterId, 'performer');
                Assert::same($scheme->Id, $equips[0]->sourceId, 'source');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },
        ];
    }
}
