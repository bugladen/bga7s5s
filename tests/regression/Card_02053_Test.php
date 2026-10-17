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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02052;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02053;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02053;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_02053_Test extends TestCase
{
    public function name(): string
    {
        return '_02053 Under Cover of the Night';
    }

    /** @return _02053 */
    private function scene(TestWorld $world): _02053
    {
        /** @var _02053 $scheme */
        $scheme = $world->placeCard(new _02053(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs a Neutral Cunning Scheme with a City Reaction' => function () {
                $scheme = new _02053();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasReactions::class, $scheme, 'reactions');
                Assert::same(3, $scheme->Initiative, 'Initiative');
                Assert::same(1, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasFaction('Neutral'), 'Neutral');
                Assert::true($scheme->hasTrait('Cunning'), 'Cunning');
                Assert::true($scheme->hasTrait('Sabotage'), 'Sabotage');
                Assert::same([], $scheme->ProcessedPiles, 'no piles yet');
                Assert::instanceOf(Reaction_02053::class, $scheme->getReactions()[0], 'reaction');
                Assert::same(8002053, States::DUSK_PHASE_BEGIN_02053, 'state');
            },

            'resolve adds a Renown to the Docks' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $world->fireOn($scheme, $event);

                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $added, 'added');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added[0]->location, 'docks');
                Assert::same(1, $added[0]->amount, 'one');
                Assert::count(0, $world->theah->queuedOfType(\Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition::class), 'no chooser');
            },

            // WHY: The condition is consumed by the block. A later Home move is not stopped again.
            'a marked character moving Home loses the condition and is blocked' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $character = $world->placeCharacter(new GenericCharacter('Stay'), Game::LOCATION_CITY_DOCKS, 1);
                $character->addCondition(Game::UNDER_COVER_OF_THE_NIGHT);

                $event = new EventCardMoved();
                $event->cardId = $character->Id;
                $event->toLocation = Game::LOCATION_PLAYER_HOME;
                $event->theah = $world->theah;
                $threw = false;
                try {
                    $scheme->eventCheck($event);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'blocked');
                Assert::false($character->hasCondition(Game::UNDER_COVER_OF_THE_NIGHT), 'condition spent');

                $event->toLocation = Game::LOCATION_CITY_FORUM;
                $character->addCondition(Game::UNDER_COVER_OF_THE_NIGHT);
                $scheme->eventCheck($event);
                Assert::true($character->hasCondition(Game::UNDER_COVER_OF_THE_NIGHT), 'other destinations keep it');
            },

            'dusk end forgets which discard piles were already offered' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $scheme->ProcessedPiles = ['Discard-1', Game::LOCATION_CITY_DISCARD];
                $scheme->IsUpdated = false;
                $event = new EventDuskPhaseEnd();
                $world->fireOn($scheme, $event);
                Assert::same([], $scheme->ProcessedPiles, 'cleared');
                Assert::true($scheme->IsUpdated, 'persisted');
            },

            'args show the first non-empty discard pile in player order, then the city' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $second = $world->placeCard(new _02052(), 'Discard-2', 2);
                $city = $world->placeCard(new _02052(), Game::LOCATION_CITY_DISCARD, 0);

                $args = $scheme->argsFromCard($world->game, States::DUSK_PHASE_BEGIN_02053, 'duskPhaseBegin02053', (string) $scheme->Id);
                Assert::same([$second->Id], array_column($args['cards'], 'id'), 'player two first');
                Assert::same("Player Two's Discard Pile", $args['discardPileLabel'], 'label');

                $scheme->ProcessedPiles[] = 'Discard-2';
                $args = $scheme->argsFromCard($world->game, States::DUSK_PHASE_BEGIN_02053, 'duskPhaseBegin02053', (string) $scheme->Id);
                Assert::same([$city->Id], array_column($args['cards'], 'id'), 'city next');
                Assert::same('City Discard Pile', $args['discardPileLabel'], 'city label');
            },

            // WHY: City discard is moved immediately and does not queue a locker event.
            // A player discard only queues the remove and the locker send.
            'selecting a city discard card moves it to the City Locker now' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $city = $world->placeCard(new _02052(), Game::LOCATION_CITY_DISCARD, 0);

                $scheme->actFromCardWithId($world->game, States::DUSK_PHASE_BEGIN_02053, 'duskPhaseBegin02053', (string) $scheme->Id, $city->Id);

                Assert::same(Game::LOCATION_CITY_LOCKER, $city->Location, 'moved');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromCityDiscardPile::class), 'removed');
                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'no faction locker event');
                Assert::same([Game::LOCATION_CITY_DISCARD], $scheme->ProcessedPiles, 'pile done');
                Assert::same([''], $world->game->gamestate->transitions, 'empty transition');
            },

            'selecting a player discard card queues a locker send and leaves the row in place' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $card = $world->placeCard(new _02052(), 'Discard-1', 1);

                $scheme->actFromCardWithId($world->game, States::DUSK_PHASE_BEGIN_02053, 'duskPhaseBegin02053', (string) $scheme->Id, $card->Id);

                Assert::same('Discard-1', $card->Location, 'not moved yet');
                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                $locker = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::same($card->Id, $removed[0]->cardId, 'removed');
                Assert::same($card->OwnerId, $removed[0]->playerId, 'owner');
                Assert::same($card->Id, $locker[0]->cardId, 'locker');
                Assert::same(['Discard-1'], $scheme->ProcessedPiles, 'pile done');
            },

            'rejects a missing card or a card from a later pile' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->placeCard(new _02052(), 'Discard-1', 1);
                $later = $world->placeCard(new _02052(), 'Discard-2', 2);

                foreach ([999, $later->Id] as $id) {
                    $threw = false;
                    try {
                        $scheme->actFromCardWithId($world->game, States::DUSK_PHASE_BEGIN_02053, 'duskPhaseBegin02053', (string) $scheme->Id, $id);
                    } catch (UserException $e) {
                        $threw = true;
                    }
                    Assert::true($threw, (string) $id);
                }
                Assert::same([], $scheme->ProcessedPiles, 'not consumed');
            },

            'pass skips the current pile without sending a card' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->placeCard(new _02052(), 'Discard-1', 1);
                $scheme->actFromCardPass($world->game, States::DUSK_PHASE_BEGIN_02053, 'duskPhaseBegin02053', (string) $scheme->Id);
                Assert::same(['Discard-1'], $scheme->ProcessedPiles, 'skipped');
                Assert::count(0, $world->theah->queuedEvents, 'nothing sent');
                Assert::same([''], $world->game->gamestate->transitions, 'continue');
            },
        ];
    }
}
