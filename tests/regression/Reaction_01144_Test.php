<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01144;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01144;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseHighDrama;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01144_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01144';
    }

    /**
     * Scheme at Home; controller has unique fewest characters; available Mercenary in city.
     *
     * @return array{0:_01144,1:Reaction_01144,2:GenericCharacter,3:GenericLeader}
     */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01144(), Game::LOCATION_PLAYER_HOME, 1);
        $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_PLAYER_HOME, 1);
        $leader->ModifiedCombat = 3;
        $leader->ModifiedFinesse = 1;
        $leader->ModifiedInfluence = 2;
        $world->theah->leadersByPlayerId[1] = $leader;
        // Player 2 has more characters so player 1 is unique fewest.
        $world->placeCharacter(new GenericCharacter('Foe A'), Game::LOCATION_CITY_FORUM, 2);
        $world->placeCharacter(new GenericCharacter('Foe B'), Game::LOCATION_CITY_BAZAAR, 2);
        $merc = $world->placeCharacter(
            new GenericCharacter('Hireling', ['Mercenary']),
            Game::LOCATION_CITY_DOCKS,
            0
        );
        /** @var Reaction_01144 $reaction */
        $reaction = $scheme->getReactions()[0];
        return [$scheme, $reaction, $merc, $leader];
    }

    public function tests(): array
    {
        return [
            'is a CardReaction' => function () {
                Assert::instanceOf(CardReaction::class, new Reaction_01144(), 'CardReaction');
            },

            'buttons offer Fill Ranks and Decline' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['fillRanks', 'decline'], $ids, 'buttons');
            },

            // WHY (journal 2026-04-13-06): availability requires Mercenary trait, not any city character.
            'High Drama beginning offers when fewest characters and a Mercenary is available' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);

                $event = new EventPhaseHighDrama();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same('reaction', $transitions[0]->transition, 'reaction');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'scheme');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
            },

            // WHY (journal 2026-04-13-06): must require Mercenary — any uncontrolled city character used to enable.
            'does not offer when only a non-Mercenary is available in the city' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01144(), Game::LOCATION_PLAYER_HOME, 1);
                $leader = $world->placeCharacter(new GenericLeader('Leader'), Game::LOCATION_PLAYER_HOME, 1);
                $world->theah->leadersByPlayerId[1] = $leader;
                $world->placeCharacter(new GenericCharacter('Foe A'), Game::LOCATION_CITY_FORUM, 2);
                $world->placeCharacter(new GenericCharacter('Foe B'), Game::LOCATION_CITY_BAZAAR, 2);
                $world->placeCharacter(new GenericCharacter('Civilian'), Game::LOCATION_CITY_DOCKS, 0);
                /** @var Reaction_01144 $reaction */
                $reaction = $scheme->getReactions()[0];

                $event = new EventPhaseHighDrama();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'no merc');
            },

            'does not offer when character counts are tied for fewest' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                // Scene: p1 has Leader (1), p2 has 2 foes. Add a buddy so both sit at 2.
                $world->placeCharacter(new GenericCharacter('Buddy'), Game::LOCATION_CITY_FORUM, 1);

                $event = new EventPhaseHighDrama();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'tied fewest');
            },

            'does not offer when the scheme is not at Player Home' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);
                $scheme->Location = Game::LOCATION_CITY_FORUM;

                $event = new EventPhaseHighDrama();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'not Home');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->Used = true;

                $event = new EventPhaseHighDrama();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            'fillRanks sets discount from Leader max stat, marks Used, and queues 01144' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, , $leader] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'fillRanks');

                Assert::same(3, $world->game->globals->get(Game::DISCOUNT), 'Combat max');
                Assert::true($reaction->Used, 'used');
                $chooser = array_values(array_filter(
                    $world->theah->queuedOfType(EventTransition::class),
                    fn($t) => $t->transition === '01144'
                ));
                Assert::count(1, $chooser, '01144 chooser');
                Assert::same($scheme->Id, $chooser[0]->sourceId, 'scheme source');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
                Assert::same($leader->Id, $world->theah->getLeaderByPlayerId(1)->Id, 'leader wired');
            },

            'decline finishes without marking Used or setting discount' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::false($reaction->Used, 'not used');
                Assert::same(null, $world->game->globals->get(Game::DISCOUNT), 'no discount');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'args expose discount for the mercenary chooser' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $world->game->globals->set(Game::DISCOUNT, 4);

                $args = $reaction->getArgsFromReaction(
                    $world->game,
                    States::HIGH_DRAMA_BEGINNING_01144,
                    'highDramaBeginning_01144'
                );

                Assert::same(4, $args['discount'], 'discount');
            },

            'act chooses a valid Mercenary' => function () {
                $world = new TestWorld();
                [, $reaction, $merc] = $this->scene($world);

                $reaction->actFromReactionWithId(
                    $world->game,
                    States::HIGH_DRAMA_BEGINNING_01144,
                    'x',
                    $merc->Id
                );

                Assert::same($merc->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['mercenaryChosen'], $world->game->gamestate->transitions, 'next');
            },

            'act refuses a non-Mercenary' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $civ = $world->placeCharacter(new GenericCharacter('Civ'), Game::LOCATION_CITY_DOCKS, 0);

                $threw = false;
                try {
                    $reaction->actFromReactionWithId(
                        $world->game,
                        States::HIGH_DRAMA_BEGINNING_01144,
                        'x',
                        $civ->Id
                    );
                } catch (\BgaUserException | UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'refused');
            },

            'act refuses an already controlled character' => function () {
                $world = new TestWorld();
                [, $reaction, $merc] = $this->scene($world);
                $merc->ControllerId = 2;

                $threw = false;
                try {
                    $reaction->actFromReactionWithId(
                        $world->game,
                        States::HIGH_DRAMA_BEGINNING_01144,
                        'x',
                        $merc->Id
                    );
                } catch (\BgaUserException | UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'controlled');
            },

            'pay step recruits and moves the Mercenary Home unstoppably' => function () {
                $world = new TestWorld();
                [$scheme, $reaction, $merc] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_CARD, $merc->Id);
                $payIds = [9001, 9002];

                $reaction->actFromReactionWithIds(
                    $world->game,
                    States::HIGH_DRAMA_BEGINNING_01144_2,
                    'x',
                    $payIds
                );

                Assert::count(1, $world->game->recruitMercenaryCalls, 'recruit called');
                Assert::same($merc->Id, $world->game->recruitMercenaryCalls[0]['recruitId'], 'recruit id');
                Assert::same(json_encode($payIds), $world->game->recruitMercenaryCalls[0]['payWithCards'], 'pay');

                $moves = $world->theah->queuedOfType(EventCardMoving::class);
                Assert::count(1, $moves, 'move Home');
                Assert::same($merc->Id, $moves[0]->cardId, 'merc');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'Home');
                Assert::true($moves[0]->unstoppable, 'unstoppable');
                Assert::same($scheme->Id, $moves[0]->sourceId, 'scheme source');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },
        ];
    }
}
