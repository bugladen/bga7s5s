<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRisk;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericRiskReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;

/**
 * ArgumentsTrait: High Drama player-turn menu args, empty/scheme/planning helpers,
 * and choose-next reaction notify (button labels already covered in FrameworkActions_Basics).
 */
class ArgumentsTrait_PlayerTurn_Test extends TestCase
{
    public function name(): string
    {
        return 'ArgumentsTrait: player turn, planning helpers, choose-next notify';
    }

    /** An in-hand Risk whose Reaction transition is queued for choose-next. */
    private function queuedHandRisk(FrameworkActionsGame $game, int $playerId, string $name): void
    {
        $risk = new GenericRisk($name);
        $risk->addReaction(new GenericRiskReaction(), $game, false);
        $game->place($risk, Game::LOCATION_HAND, $playerId);
        $reaction = $risk->getReactions()[0];
        $event = EventFactory::createTransitionEvent($playerId, $risk->Id, 'reaction', $reaction->Id);
        $event->priority = Event::REACTION_PRIORITY;
        $game->theah->queueEvent($event);
    }

    public function tests(): array
    {
        return [
            // ---- trivial / planning -----------------------------------------------------------

            'argsEmpty returns an empty array' => function () {
                $game = new FrameworkActionsGame();
                Assert::same([], $game->argsEmpty(), 'empty');
            },

            'argAvailableDecks lists Core starter decks and singular opponent copy for 2p' => function () {
                $game = new FrameworkActionsGame();
                $args = $game->argAvailableDecks();

                Assert::true(count($args['availableDecks']) > 0, 'has decks');
                Assert::true(isset($args['availableDecks'][array_key_first($args['availableDecks'])]['id']), 'deck id');
                Assert::true(isset($args['availableDecks'][array_key_first($args['availableDecks'])]['name']), 'deck name');
                Assert::contains('opponent is selecting', $args['opponent_message'], '2p singular');
            },

            'argsPlanningPhaseResolveWhenRevealedCardsChooseOrder maps remaining cards' => function () {
                $game = new FrameworkActionsGame();
                $a = $game->place(new GenericCharacter('Scheme A'), Game::LOCATION_PLAYER_HOME, 1);
                $b = $game->place(new GenericCharacter('Scheme B'), Game::LOCATION_PLAYER_HOME, 2);
                $game->globals->set(Game::WHEN_REVEALED_REMAINING_CARDS, json_encode([
                    ['cardId' => $a->Id, 'playerId' => 1],
                    ['cardId' => $b->Id, 'playerId' => 2],
                ]));

                $args = $game->argsPlanningPhaseResolveWhenRevealedCardsChooseOrder();

                Assert::count(2, $args['whenRevealedCards'], 'two remaining');
                Assert::same($a->Id, $args['whenRevealedCards'][0]['cardId'], 'first card');
                Assert::same('Scheme A', $args['whenRevealedCards'][0]['cardName'], 'first name');
                Assert::same('Player One', $args['whenRevealedCards'][0]['playerName'], 'first player');
                Assert::same($b->Id, $args['whenRevealedCards'][1]['cardId'], 'second card');
            },

            // ---- argPlayerTurn ----------------------------------------------------------------

            'argPlayerTurn reports unlocked capability flags from Theah' => function () {
                $game = new FrameworkActionsGame();
                $mover = $game->place(new GenericCharacter('Mover'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $game->place(new GenericAttachment('Gear'), Game::LOCATION_CITY_DOCKS, 0);
                $game->place(new GenericCityCharacter('Hire'), Game::LOCATION_CITY_DOCKS, 0);

                $active = $game->argPlayerTurn()['_private']['active'];

                Assert::true($active['canMove'], 'can move');
                Assert::true($active['canChallenge'], 'can challenge');
                Assert::true($active['canEquip'], 'can equip');
                Assert::true($active['canRecruit'], 'can recruit');
                Assert::true($active['canClaim'], 'en garde non-dashed Influence can claim');
                Assert::false(isset($active['lockedPerformerId']), 'no lock key');
                Assert::false($active['hasBrutes'], 'no brutes');
                unset($mover);
            },

            'argPlayerTurn locked Extra Action performer restricts flags to that character and forces hasBrutes false' => function () {
                $game = new FrameworkActionsGame();
                $locked = $game->place(new GenericCharacter('Locked'), Game::LOCATION_CITY_DOCKS, 1);
                $locked->Engaged = true;
                $game->place(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 1);
                $game->place(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, $locked->Id);

                $active = $game->argPlayerTurn()['_private']['active'];

                Assert::same($locked->Id, $active['lockedPerformerId'], 'lock id');
                // WHY: Pattern A.2 — Pass stays available; lock only narrows performer menus.
                Assert::false($active['canMove'], 'locked engaged cannot move');
                Assert::false($active['canChallenge'], 'locked engaged cannot challenge');
                Assert::false($active['hasBrutes'], 'brutes always false under lock');
            },

            // ---- choose-next notify -----------------------------------------------------------

            'notifyAvailableReactionsForChooseNext logs fogged Risk names for the public channel' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::FIRST_PLAYER, 1);
                $this->queuedHandRisk($game, 1, 'FP Secret');
                $this->queuedHandRisk($game, 2, 'Opp Secret');

                $game->notifyAvailableReactionsForChooseNext();

                Assert::count(1, $game->notify->messages, 'one log');
                Assert::same('message', $game->notify->messages[0]['type'], 'type');
                Assert::contains('First Player is choosing', $game->notify->messages[0]['message'], 'copy');
                $list = $game->notify->messages[0]['args']['reactions_list'];
                Assert::contains('Player One - Risk Reaction', $list, 'FP risk fogged in public log');
                Assert::contains('Player Two - Risk Reaction', $list, 'opponent fogged');
                Assert::notContains('FP Secret', $list, 'no FP card name');
                Assert::notContains('Opp Secret', $list, 'no opponent card name');
            },

            'notifyAvailableReactionsForChooseNext is a no-op when nothing is queued' => function () {
                $game = new FrameworkActionsGame();
                $game->notifyAvailableReactionsForChooseNext();
                Assert::same([], $game->notify->messages, 'silent');
            },

            'argsChooseNextReaction returns the unfogged button choices for First Player' => function () {
                $game = new FrameworkActionsGame();
                $game->globals->set(Game::FIRST_PLAYER, 1);
                $this->queuedHandRisk($game, 1, 'FP Risk');
                $this->queuedHandRisk($game, 2, 'Hidden');

                $args = $game->argsChooseNextReaction();

                Assert::count(2, $args['reactions'], 'two buttons');
                Assert::contains('FP Risk', $args['reactions'][0]['label'], 'FP sees own card on button');
                Assert::same('Player Two - Risk Reaction', $args['reactions'][1]['label'], 'opponent fogged on button');
            },
        ];
    }
}
