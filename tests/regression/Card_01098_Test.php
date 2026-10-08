<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01098;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01099;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01098;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/**
 * Minimal BGA Deck stand-in for the two methods _01098 uses to find every copy of the embargoed card.
 * WHY: the real Deck is a DB table; the Forced / locker paths only need "cards in a hand" and
 * "cards of this printed type" lists of ['id' => int].
 */
class Cats01098FakeDeck
{
    /** @var array<int, list<int>> playerId => card ids in hand */
    public array $hands = [];

    /** @var array<string, list<int>> printed type ("01073") => every copy's card id */
    public array $byType = [];

    public function getCardsInLocation($location, $playerId = null): array
    {
        $ids = $location === Game::LOCATION_HAND ? ($this->hands[(int)$playerId] ?? []) : [];
        return array_map(fn($id) => ['id' => $id], $ids);
    }

    public function getCardsOfType($type): array
    {
        return array_map(fn($id) => ['id' => $id], $this->byType[(string)$type] ?? []);
    }

    public function insertCardOnExtremePosition($cardId, $location, $bOnTop): void
    {
    }
}

class Card_01098_Test extends TestCase
{
    public function name(): string
    {
        return '_01098 The Cat\'s Embargo';
    }

    /**
     * Embargo scheme (player 1) sits at Home; the opponent holds one Cavalier Hat. A second copy sits in
     * the opponent's other zones and a third copy belongs to the scheme controller.
     *
     * @return array{0:_01098,1:Cats01098FakeDeck,2:_01073,3:_01073,4:_01073}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01098(), Game::LOCATION_PLAYER_HOME, 1);
        $handHat = $world->placeCard(new _01073(), Game::LOCATION_HAND, 2);
        $otherTheirHat = $world->placeCard(new _01073(), Game::LOCATION_CITY_DISCARD, 2);
        $myHat = $world->placeCard(new _01073(), Game::LOCATION_HAND, 1);

        $deck = new Cats01098FakeDeck();
        $deck->hands[2] = [$handHat->Id];
        $deck->hands[1] = [$myHat->Id];
        $deck->byType['01073'] = [$handHat->Id, $otherTheirHat->Id, $myHat->Id];
        $world->game->deckOverride = $deck;
        $world->game->chosenSchemes[1] = $scheme;
        $world->game->activePlayerId = 1;

        return [$scheme, $deck, $handHat, $otherTheirHat, $myHat];
    }

    private function chooseOpponent(TestWorld $world, _01098 $scheme, int $opponentId): void
    {
        $scheme->actFromCardWithId(
            $world->game,
            States::PLANNING_PHASE_END_01098,
            'planningPhaseEnd_01098',
            '',
            $opponentId
        );
    }

    private function resolveEvent(TestWorld $world, _01098|_01099 $revealed, int $playerId = 1): EventResolveScheme
    {
        $event = new EventResolveScheme();
        $event->scheme = $revealed;
        $event->playerId = $playerId;
        $event->playerName = 'Player One';
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            // ---- construction ----
            'constructs Castille Logistics Sabotage Scheme with Initiative 75 and Panache 1' => function () {
                $scheme = new _01098();
                Assert::same(75, $scheme->Initiative, 'Initiative');
                Assert::same(1, $scheme->PanacheModifier, 'Panache modifier');
                Assert::true($scheme->hasFaction('Castille'), 'Castille');
                Assert::true($scheme->hasTrait('Logistics'), 'Logistics');
                Assert::true($scheme->hasTrait('Sabotage'), 'Sabotage');
            },

            'has one Reaction_01098 and no embargoed card yet' => function () {
                $scheme = new _01098();
                Assert::count(1, $scheme->getReactions(), 'one reaction');
                Assert::instanceOf(Reaction_01098::class, $scheme->getReactions()[0], 'reaction type');
                Assert::same(0, $scheme->EmbargoedCardId, 'nothing embargoed');
            },

            // ---- resolve: two-location Renown ----
            // WHY: the two different locations are chosen in state 01098 (planningPhaseResolveSchemes_01098),
            // so the scheme only queues that transition at MEDIUM priority; no Renown is added directly.
            'resolving queues the two-location chooser transition for the scheme controller' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01098(), Game::LOCATION_PLAYER_HOME, 1);

                $scheme->handleEvent($this->resolveEvent($world, $scheme));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01098', $transitions[0]->transition, 'transition name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source scheme');
                Assert::same(1, $transitions[0]->playerId, 'resolving player');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium priority');
                Assert::count(1, $world->theah->queuedEvents, 'no direct Renown; the chooser state adds it');
            },

            'resolving a different scheme queues nothing' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01098(), Game::LOCATION_PLAYER_HOME, 1);
                $other = $world->placeCard(new _01099(), Game::LOCATION_PLAYER_HOME, 2);

                $scheme->handleEvent($this->resolveEvent($world, $other, 2));

                Assert::count(0, $world->theah->queuedEvents, 'not this scheme');
            },

            // ---- Forced: end of Planning ----
            'planning end at Home queues the choose-opponent transition for the controller' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01098(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventPhasePlanningEnd();
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01098', $transitions[0]->transition, 'transition name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source scheme');
                Assert::same($scheme->ControllerId, $transitions[0]->playerId, 'scheme controller picks');
            },

            // WHY: only a revealed scheme (sitting at Home) has a Forced effect; a scheme elsewhere (deck, discard) must stay silent.
            'planning end does nothing when the scheme is not at Home' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01098(), Game::LOCATION_CITY_DECK, 1);

                $world->fireOn($scheme, new EventPhasePlanningEnd());

                Assert::count(0, $world->theah->queuedEvents, 'not revealed');
            },

            'chooser args list every player except the active one' => function () {
                $world = new TestWorld();
                [$scheme] = $this->scene($world);

                $result = $scheme->argsFromCard($world->game, States::PLANNING_PHASE_END_01098, 'planningPhaseEnd_01098', '');

                Assert::same([['id' => 2, 'name' => 'Player Two']], $result['args']['opponents'], 'opponents');
            },

            'choosing an opponent embargoes the revealed hand card on the scheme' => function () {
                $world = new TestWorld();
                [$scheme, , $handHat] = $this->scene($world);

                $this->chooseOpponent($world, $scheme, 2);

                Assert::same($handHat->Id, $scheme->EmbargoedCardId, 'EmbargoedCardId stamped');
                Assert::same($handHat->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'CHOSEN_CARD for the acknowledge state');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState()');
            },

            // WHY (journal 2026-04-10): the condition marks every opponent copy so Reaction_01098 can match by condition, not by name.
            'choosing stamps CATS_EMBARGO_TARGET on every opposing copy of the card, not on the controller\'s copy' => function () {
                $world = new TestWorld();
                [$scheme, , $handHat, $otherTheirHat, $myHat] = $this->scene($world);

                $this->chooseOpponent($world, $scheme, 2);

                Assert::true($handHat->hasCondition(Game::CATS_EMBARGO_TARGET), 'revealed copy stamped');
                Assert::true($otherTheirHat->hasCondition(Game::CATS_EMBARGO_TARGET), 'other opposing copy stamped');
                Assert::false($myHat->hasCondition(Game::CATS_EMBARGO_TARGET), 'own copy not stamped');
            },

            'choosing does not stamp cards of a different printed name' => function () {
                $world = new TestWorld();
                [$scheme, $deck] = $this->scene($world);
                $flint = $world->placeCard(new _01049(), Game::LOCATION_HAND, 2);
                $deck->byType['01049'] = [$flint->Id];

                $this->chooseOpponent($world, $scheme, 2);

                Assert::false($flint->hasCondition(Game::CATS_EMBARGO_TARGET), 'different name');
            },

            'choosing tells the scheme controller which copies were marked and updates the overlay' => function () {
                $world = new TestWorld();
                [$scheme, , $handHat, $otherTheirHat] = $this->scene($world);

                $this->chooseOpponent($world, $scheme, 2);

                $marked = array_values(array_filter(
                    $world->game->notify->messages,
                    fn($m) => $m['type'] === 'catsEmbargoTargetChosen'
                ));
                $markedIds = array_map(fn($m) => $m['args']['cardId'], $marked);
                sort($markedIds);
                $expected = [$handHat->Id, $otherTheirHat->Id];
                sort($expected);
                Assert::same($expected, $markedIds, 'one notification per opposing copy');
                Assert::same('player:2', $marked[0]['channel'], 'sent to the picked card\'s player');

                $overlay = array_values(array_filter(
                    $world->game->notify->messages,
                    fn($m) => $m['type'] === 'catsEmbargoUpdated'
                ));
                Assert::count(1, $overlay, 'overlay notification');
                Assert::same($scheme->Id, $overlay[0]['args']['cardId'], 'overlay on the scheme');
                Assert::same($handHat->Name, $overlay[0]['args']['embargoedCardName'], 'embargoed name');
            },

            // ---- overlay data ----
            'getCatsEmbargoData is null before an embargo is chosen' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01098(), Game::LOCATION_PLAYER_HOME, 1);
                Assert::same(null, $scheme->getCatsEmbargoData($world->game), 'no data');
            },

            'getCatsEmbargoData reports the scheme id and embargoed card name' => function () {
                $world = new TestWorld();
                [$scheme, , $handHat] = $this->scene($world);
                $this->chooseOpponent($world, $scheme, 2);

                $data = $scheme->getCatsEmbargoData($world->game);

                Assert::same($scheme->Id, $data['cardId'], 'scheme id');
                Assert::same($handHat->Name, $data['embargoedCardName'], 'embargoed name');
            },

            // ---- locker clears the stamps ----
            'sent to the locker, the scheme removes the embargo stamp from every copy' => function () {
                $world = new TestWorld();
                [$scheme, , $handHat, $otherTheirHat, $myHat] = $this->scene($world);
                $this->chooseOpponent($world, $scheme, 2);
                // Legacy stamp from before the constant was renamed must also be swept.
                $otherTheirHat->addCondition(Game::OLD_CATS_EMBARGO_TARGET);
                $world->game->notify->messages = [];

                $event = new EventCardSentToLocker();
                $event->cardId = $scheme->Id;
                $world->fireOn($scheme, $event);

                Assert::false($handHat->hasCondition(Game::CATS_EMBARGO_TARGET), 'revealed copy cleared');
                Assert::false($otherTheirHat->hasCondition(Game::CATS_EMBARGO_TARGET), 'other copy cleared');
                Assert::false($otherTheirHat->hasCondition(Game::OLD_CATS_EMBARGO_TARGET), 'legacy stamp cleared');
                Assert::false($myHat->hasCondition(Game::CATS_EMBARGO_TARGET), 'own copy untouched');

                $removed = array_values(array_filter(
                    $world->game->notify->messages,
                    fn($m) => $m['type'] === 'catsEmbargoTargetRemoved'
                ));
                Assert::count(3, $removed, 'client told to drop every copy\'s marker');
            },

            'another card being sent to the locker leaves the stamps alone' => function () {
                $world = new TestWorld();
                [$scheme, , $handHat] = $this->scene($world);
                $this->chooseOpponent($world, $scheme, 2);

                $event = new EventCardSentToLocker();
                $event->cardId = $handHat->Id;
                $world->fireOn($scheme, $event);

                Assert::true($handHat->hasCondition(Game::CATS_EMBARGO_TARGET), 'stamp survives');
            },
        ];
    }
}
