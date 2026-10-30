<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01200;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01200;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01200_Test extends TestCase
{
    public function name(): string
    {
        return '_01200 Crystal Eye';
    }

    public function tests(): array
    {
        return [
            'constructs Unique Artifact Syrneth CityAttachment with +1 Influence' => function () {
                $eye = new _01200();
                Assert::instanceOf(CityAttachment::class, $eye, 'CityAttachment');
                Assert::instanceOf(IHasReactions::class, $eye, 'reactions');
                Assert::same(1, $eye->WealthCost, 'Wealth');
                Assert::same(1, $eye->InfluenceModifier, '+1 Influence');
                Assert::same(0, $eye->ChosenCard, 'ChosenCard');
                Assert::true($eye->hasTrait('Artifact'), 'Artifact');
                Assert::true($eye->hasTrait('Syrneth'), 'Syrneth');
                Assert::true($eye->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Reaction_01200::class, $eye->getReactions()[0], 'Reaction');
            },

            'owns Reaction_01200 when placed' => function () {
                $world = new TestWorld();
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $eye->getReactions(), 'one');
                Assert::same($eye->Id, $eye->getReactions()[0]->OwnerId, 'owner');
            },

            'Forced equip queues transition 01200' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                // WHY: Forced only queues when an opponent still has Approach cards.
                $world->placeCharacter(new GenericCharacter('Approach'), Game::LOCATION_APPROACH, 2);
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->activePlayerId = 1;

                $event = new EventAttachmentEquipped();
                $event->attachmentId = $eye->Id;
                $event->characterId = $host->Id;
                $world->fireOn($eye, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'Forced');
                Assert::same('01200', $transitions[0]->transition, 'name');
                Assert::same($eye->Id, $transitions[0]->sourceId, 'source');
                Assert::same(1, $transitions[0]->playerId, 'active');
            },

            // WHY: Day 5 / empty Approach — chooser would soft-lock; Cat's Embargo shape.
            'Forced equip skips when no opponent has Approach cards' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->activePlayerId = 1;

                $event = new EventAttachmentEquipped();
                $event->attachmentId = $eye->Id;
                $event->characterId = $host->Id;
                $world->fireOn($eye, $event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no chooser');
                Assert::count(1, $world->game->notify->messages, 'skip announced');
            },

            'equip of a different attachment does not queue 01200' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventAttachmentEquipped();
                $event->attachmentId = $other->Id;
                $event->characterId = $host->Id;
                $world->fireOn($eye, $event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'other');
            },

            'unequip clears ChosenCard and Crystal Eye Target condition' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                /** @var _01200 $eye */
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_APPROACH, 2);
                $target->addCondition(Game::CRYSTAL_EYE_TARGET);
                $eye->ChosenCard = $target->Id;
                $eye->AttachedToId = $host->Id;

                $event = new EventAttachmentUnequipped();
                $event->attachmentId = $eye->Id;
                $event->characterId = $host->Id;
                $world->fireOn($eye, $event);

                Assert::same(0, $eye->ChosenCard, 'cleared');
                Assert::false($target->hasCondition(Game::CRYSTAL_EYE_TARGET), 'condition removed');
            },

            'opponent chooser args omit empty Approach Decks' => function () {
                $world = new TestWorld();
                /** @var _01200 $eye */
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $approach = $world->placeCharacter(new GenericCharacter('Approach'), Game::LOCATION_APPROACH, 2);

                $args = $eye->argsFromCard($world->game, States::HIGH_DRAMA_PLAYER_TURN_01200, 'highDramaPhase01200', '');
                Assert::same([['id' => 2, 'name' => 'Player Two']], $args['opponents'], 'has cards');

                $approach->Location = Game::LOCATION_HAND;
                $empty = $eye->argsFromCard($world->game, States::HIGH_DRAMA_PLAYER_TURN_01200, 'highDramaPhase01200', '');
                Assert::same([], $empty['opponents'], 'empty Approach omitted');
            },

            'pass on card chooser exits when Approach Deck is empty' => function () {
                $world = new TestWorld();
                /** @var _01200 $eye */
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $world->game->activePlayerId = 1;

                $eye->actFromCardPass(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01200_2,
                    'highDramaPhase01200_2',
                    ''
                );

                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState to EVENTS');
            },

            'pass on opponent chooser exits when every Approach Deck is empty' => function () {
                $world = new TestWorld();
                /** @var _01200 $eye */
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->activePlayerId = 1;

                $eye->actFromCardPass(
                    $world->game,
                    States::HIGH_DRAMA_PLAYER_TURN_01200,
                    'highDramaPhase01200',
                    ''
                );

                Assert::same(['pass'], $world->game->gamestate->transitions, 'named pass to EVENTS');
            },

            'pass on card chooser refuses when Approach still has cards' => function () {
                $world = new TestWorld();
                /** @var _01200 $eye */
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Approach'), Game::LOCATION_APPROACH, 2);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);

                Assert::throws(
                    fn () => $eye->actFromCardPass(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01200_2,
                        'highDramaPhase01200_2',
                        ''
                    ),
                    'Approach Deck',
                    'must choose'
                );
            },

            'choosing an opponent with empty Approach is refused' => function () {
                $world = new TestWorld();
                /** @var _01200 $eye */
                $eye = $world->placeCard(new _01200(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->activePlayerId = 1;

                Assert::throws(
                    fn () => $eye->actFromCardWithId(
                        $world->game,
                        States::HIGH_DRAMA_PLAYER_TURN_01200,
                        'highDramaPhase01200',
                        '',
                        2
                    ),
                    'Approach Deck',
                    'stale client'
                );
            },

            'state constants registered for Forced chooser paths' => function () {
                Assert::same(401200, States::HIGH_DRAMA_PLAYER_TURN_01200, 'HD');
                Assert::same(4012002, States::HIGH_DRAMA_PLAYER_TURN_01200_2, 'HD_2');
                Assert::same(2201200, States::PLANNING_PHASE_APPROACH_CARDS_PLAYED_01200, 'Approach');
            },
        ];
    }
}
