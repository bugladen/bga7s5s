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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02005;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_02005_Test extends TestCase
{
    public function name(): string
    {
        return '_02005 Decipher the Strands';
    }

    /**
     * @return _02005
     */
    private function scene(TestWorld $world): _02005
    {
        /** @var _02005 $scheme */
        $scheme = $world->placeCard(new _02005(), Game::LOCATION_PLAYER_HOME, 1);
        return $scheme;
    }

    public function tests(): array
    {
        return [
            'constructs Vodacce Scheme with no composites' => function () {
                $scheme = new _02005();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::same(47, $scheme->Initiative, 'Initiative');
                Assert::same(1, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Sorte'), 'Sorte');
                Assert::true($scheme->hasTrait('Weave'), 'Weave');
                Assert::true($scheme->hasFaction('Vodacce'), 'Vodacce');
            },

            'state constants registered' => function () {
                Assert::same(2602005, States::PLANNING_PHASE_RESOLVE_SCHEMES_02005, '02005');
                Assert::same(26020052, States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_2, '02005_2');
                Assert::same(26020053, States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_3, '02005_3');
                Assert::same(26020054, States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_4, '02005_4');
                Assert::same(26020055, States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_5, '02005_5');
            },

            // WHY: EventResolveScheme → transition "02005" (multi-state planning flow on card).
            'resolve queues transition 02005' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02005', $transitions[0]->transition, 'name');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
            },

            'first renown choice queues add + transition 02005_2' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02005,
                    'x',
                    (string) $scheme->Id,
                    [Game::LOCATION_CITY_DOCKS]
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'renown');
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown[0]->location, 'docks');
                Assert::same('02005_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'next');
            },

            'second renown rejects location that already has Renown' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_2,
                        'x',
                        (string) $scheme->Id,
                        [Game::LOCATION_CITY_FORUM]
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'has renown');
            },

            'pass on step 2 refuses while zero-renown locations remain' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);

                $threw = false;
                try {
                    $scheme->actFromCardPass(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_2,
                        'x',
                        (string) $scheme->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'must place');
            },

            'opponent args exclude scheme controller' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $args = $scheme->argsFromCard(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_3,
                    'x',
                    (string) $scheme->Id
                );
                Assert::count(1, $args['opponents'], 'one opponent');
                Assert::same(2, $args['opponents'][0]['id'], 'p2');
            },

            // WHY: peek size = 3 + count of controlled Strega in play.
            'choosing opponent peeks 3 plus Strega count' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->placeCharacter(
                    new GenericCharacter('Strega A', ['Strega']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $world->placeCharacter(
                    new GenericCharacter('Strega B', ['Strega']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                $ids = [];
                for ($i = 0; $i < 5; $i++) {
                    $card = new GenericCharacter('Top' . $i);
                    $card->setId(700 + $i);
                    $world->game->registerDbCard($card);
                    $ids[] = ['id' => 700 + $i];
                }
                $world->game->topFactionCards = $ids;

                $scheme->actFromCardWithId(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_3,
                    'x',
                    (string) $scheme->Id,
                    2
                );

                Assert::same(2, $world->game->globals->get(Game::CHOSEN_OPPONENT), 'opponent');
                $cards = json_decode($world->game->globals->get(Game::CHOSEN_CARD));
                Assert::count(5, $cards, '3 + 2 Strega');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
            },

            'source uses 3 + Strega count for peek' => function () {
                $src = file_get_contents(dirname(__DIR__, 2) . '/modules/php/cards/tac/_02005.php');
                Assert::contains('$count = 3 + count($characters)', $src, 'peek formula');
                Assert::contains('getCardsOnTopOfPlayerFactionDeck($id, $count)', $src, 'deck peek');
                Assert::contains('You must sink at least one card.', $src, 'sink ≥1');
            },

            // WHY: sink requires ≥1 card — empty selection throws before deck writes.
            'sink step refuses empty selection' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([(object)['id' => 801]]));

                $threw = false;
                try {
                    $scheme->actFromCardWithIds(
                        $world->game,
                        States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_4,
                        'x',
                        (string) $scheme->Id,
                        []
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'must sink ≥1');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'sinking all remaining cards queues Played and Resolved' => function () {
                $world = new TestWorld();
                $scheme = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_OPPONENT, 2);
                $world->game->globals->set(Game::CHOSEN_CARD, json_encode([(object)['id' => 901]]));

                $deck = $world->game->getPlayerFactionDeckName(2);
                $card = new GenericCharacter('SinkMe');
                $card->setId(901);
                $card->ControllerId = 2;
                $card->Location = $deck;
                $world->game->registerDbCard($card);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_02005_4,
                    'x',
                    (string) $scheme->Id,
                    [901]
                );

                Assert::same(
                    [['id' => 901, 'deck' => $deck, 'onTop' => false]],
                    $world->game->deckInserts,
                    'sunk to bottom'
                );
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same(['allSunk'], $world->game->gamestate->transitions, 'allSunk');
            },
        ];
    }
}
