<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;

/** FrameworkActionsTrait: Move action, Recruit action (Parley, mercenary pick, payment) and actRecruitMercenary. */
class FrameworkActions_MoveRecruit_Test extends TestCase
{
    public function name(): string
    {
        return 'FrameworkActionsTrait: move & recruit';
    }

    /** @return array{0:FrameworkActionsGame,1:GenericCharacter,2:GenericCityCharacter} performer + available merc at the Docks */
    private function recruitScene(bool $negotiable = true): array
    {
        $game = new FrameworkActionsGame();
        $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
        $merc = $game->place(new GenericCityCharacter('Merc', $negotiable), Game::LOCATION_CITY_DOCKS, 0);
        $merc->WealthCost = 2;
        $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        return [$game, $performer, $merc];
    }

    /** @return list<int> ids of fresh hand cards for player 1; Wealth-trait cards when $wealth */
    private function hand(FrameworkActionsGame $game, int $count, bool $wealth = false): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $game->place(new GenericCharacter("Pay $i", $wealth ? ['Wealth'] : []), Game::LOCATION_HAND, 1)->Id;
        }
        return $ids;
    }

    public function tests(): array
    {
        return [
            // ---- Move: start ----------------------------------------------------------------

            'actHighDramaMoveActionStart proceeds when the player has an en garde character in play' => function () {
                $game = new FrameworkActionsGame();
                $game->place(new GenericCharacter('Mover'), Game::LOCATION_CITY_DOCKS, 1);

                $game->actHighDramaMoveActionStart();

                Assert::same(['moveActionStart'], $game->gamestate->transitions, 'transition');
                Assert::true($game->theah->buildCityCalls > 0, 'city rebuilt before judging legality');
            },

            'actHighDramaMoveActionStart counts a character waiting Home' => function () {
                $game = new FrameworkActionsGame();
                $game->place(new GenericCharacter('Homebody'), Game::LOCATION_PLAYER_HOME, 1);
                $game->actHighDramaMoveActionStart();
                Assert::same(['moveActionStart'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaMoveActionStart is refused when every character is engaged' => function () {
                $game = new FrameworkActionsGame();
                $mover = $game->place(new GenericCharacter('Tired'), Game::LOCATION_CITY_DOCKS, 1);
                $mover->Engaged = true;

                Assert::throws(fn() => $game->actHighDramaMoveActionStart(), 'Moving is not allowed', 'refused');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaMoveActionStart ignores the opponent characters and is refused with none of your own' => function () {
                $game = new FrameworkActionsGame();
                $game->place(new GenericCharacter('Theirs'), Game::LOCATION_CITY_DOCKS, 2);
                Assert::throws(fn() => $game->actHighDramaMoveActionStart(), 'Moving is not allowed', 'refused');
            },

            // ---- Move: performer --------------------------------------------------------------

            'actHighDramaMoveActionPerformerChosen records the performer in CHOSEN_CARD' => function () {
                $game = new FrameworkActionsGame();
                $mover = $game->place(new GenericCharacter('Mover'), Game::LOCATION_CITY_DOCKS, 1);

                $game->actHighDramaMoveActionPerformerChosen($mover->Id);

                Assert::same($mover->Id, $game->globals->get(Game::CHOSEN_CARD), 'chosen');
                Assert::same(['performerChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaMoveActionPerformerChosen honours the Extra Action performer lock' => function () {
                $game = new FrameworkActionsGame();
                $locked = $game->place(new GenericCharacter('Locked'), Game::LOCATION_CITY_DOCKS, 1);
                $other = $game->place(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, $locked->Id);

                Assert::throws(fn() => $game->actHighDramaMoveActionPerformerChosen($other->Id), 'same character', 'wrong performer');
                Assert::false($game->globals->has(Game::CHOSEN_CARD), 'nothing recorded');
                Assert::same([], $game->gamestate->transitions, 'no transition');

                $game->actHighDramaMoveActionPerformerChosen($locked->Id);
                Assert::same(['performerChosen'], $game->gamestate->transitions, 'the locked performer is accepted');
            },

            // ---- Move: destination -------------------------------------------------------------

            'actHighDramaMoveActionDestinationChosen queues the move (engaging) and the action resolution' => function () {
                $game = new FrameworkActionsGame();
                $game->activePlayerId = 1;
                $mover = $game->place(new GenericCharacter('Mover'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_CARD, $mover->Id);
                $game->globals->set(Game::PASS_COUNT, 3);

                $game->actHighDramaMoveActionDestinationChosen(json_encode([Game::LOCATION_CITY_FORUM]));

                $moves = $game->queued(EventCardMoving::class);
                Assert::count(1, $moves, 'one move');
                Assert::same($mover->Id, $moves[0]->cardId, 'card');
                Assert::same(1, $moves[0]->initiatingPlayerId, 'initiated by the controller');
                Assert::same(Game::LOCATION_CITY_DOCKS, $moves[0]->fromLocation, 'from');
                Assert::same(Game::LOCATION_CITY_FORUM, $moves[0]->toLocation, 'to');
                Assert::true($moves[0]->engage, 'a Move Action engages the mover');
                Assert::count(1, $game->queued(EventActionResolved::class), 'action resolved');
                Assert::same(0, $game->globals->get(Game::PASS_COUNT), 'a real action resets the consecutive-pass count');
                Assert::same('${player_name} performed a Move Action.', $game->notify->messages[0]['message'], 'announced');
                Assert::same(['destinationChosen'], $game->gamestate->transitions, 'transition');
                Assert::count(1, array_filter($game->theah->checkedEvents, fn($e) => $e instanceof EventCardMoving), 'move offered to the cards');
            },

            'actHighDramaMoveActionDestinationChosen: leaving Player Home does not engage' => function () {
                $game = new FrameworkActionsGame();
                $mover = $game->place(new GenericCharacter('Homebody'), Game::LOCATION_PLAYER_HOME, 1);
                $game->globals->set(Game::CHOSEN_CARD, $mover->Id);

                $game->actHighDramaMoveActionDestinationChosen(json_encode([Game::LOCATION_CITY_DOCKS]));

                $moves = $game->queued(EventCardMoving::class);
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->fromLocation, 'from Home');
                Assert::false($moves[0]->engage, 'muster-style move out of Home is free');
            },

            'actHighDramaMoveActionDestinationChosen: Player Home is a legal destination' => function () {
                // WHY: Home is adjacent from every city site but is not in the city map; the handler must not
                // route it through getCityLocation (which throws).
                $game = new FrameworkActionsGame();
                $mover = $game->place(new GenericCharacter('Mover'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_CARD, $mover->Id);

                $game->actHighDramaMoveActionDestinationChosen(json_encode([Game::LOCATION_PLAYER_HOME]));

                $moves = $game->queued(EventCardMoving::class);
                Assert::count(1, $moves, 'moved');
                Assert::same(Game::LOCATION_PLAYER_HOME, $moves[0]->toLocation, 'to Home');
                Assert::true($moves[0]->engage, 'moving Home from the city still engages');
            },

            'actHighDramaMoveActionDestinationChosen refuses a non-adjacent location' => function () {
                $game = new FrameworkActionsGame();
                $mover = $game->place(new GenericCharacter('Mover'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_CARD, $mover->Id);
                $game->globals->set(Game::PASS_COUNT, 2);

                Assert::throws(
                    fn() => $game->actHighDramaMoveActionDestinationChosen(json_encode([Game::LOCATION_CITY_BAZAAR])),
                    'not an adjacent location',
                    'Docks and Bazaar are not neighbours in a 2-player city'
                );

                Assert::count(0, $game->queued(EventCardMoving::class), 'nothing queued');
                Assert::same(2, $game->globals->get(Game::PASS_COUNT), 'pass count untouched');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaMoveActionDestinationChosen refuses a location that is not in the city' => function () {
                $game = new FrameworkActionsGame();
                $mover = $game->place(new GenericCharacter('Mover'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_CARD, $mover->Id);

                Assert::throws(fn() => $game->actHighDramaMoveActionDestinationChosen(json_encode(['Atlantis'])), 'does not exist', 'unknown');
                Assert::count(0, $game->queued(EventCardMoving::class), 'nothing queued');
            },

            'actHighDramaMoveActionDestinationChosen refuses an empty destination' => function () {
                $game = new FrameworkActionsGame();
                $mover = $game->place(new GenericCharacter('Mover'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_CARD, $mover->Id);

                Assert::throws(fn() => $game->actHighDramaMoveActionDestinationChosen(json_encode([null])), 'Location is not valid', 'null');
            },

            'actHighDramaMoveActionDestinationChosen: a card veto stops the move and the resolution' => function () {
                $game = new FrameworkActionsGame();
                $mover = $game->place(new GenericCharacter('Mover'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_CARD, $mover->Id);
                $game->globals->set(Game::PASS_COUNT, 2);
                $game->theah->eventCheckHook = function (Event $event) {
                    if ($event instanceof EventCardMoving) {
                        throw new \Exception('Shackled characters cannot move');
                    }
                };

                Assert::throws(
                    fn() => $game->actHighDramaMoveActionDestinationChosen(json_encode([Game::LOCATION_CITY_FORUM])),
                    'cannot move',
                    'veto propagates'
                );

                Assert::count(0, $game->queued(EventCardMoving::class), 'move not queued');
                Assert::count(0, $game->queued(EventActionResolved::class), 'no resolution for a move that did not happen');
                Assert::same(2, $game->globals->get(Game::PASS_COUNT), 'pass count untouched');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            // ---- Recruit: start / performer ------------------------------------------------------

            'actHighDramaRecruitActionStart resets the recruit type to normal and proceeds' => function () {
                [$game] = $this->recruitScene();
                $game->globals->set(Game::RECRUIT_TYPE, Game::KASPAR_RECRUIT_TYPE); // stale from a previous action

                $game->actHighDramaRecruitActionStart();

                Assert::same(Game::NORMAL_RECRUIT_TYPE, $game->globals->get(Game::RECRUIT_TYPE), 'normal recruit');
                Assert::same(['recruitActionStart'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaRecruitActionStart is refused without a character next to an available mercenary' => function () {
                $game = new FrameworkActionsGame();
                $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCityCharacter('Far Merc'), Game::LOCATION_CITY_FORUM, 0);

                Assert::throws(fn() => $game->actHighDramaRecruitActionStart(), 'Recruiting is not allowed', 'refused');
                Assert::same([], $game->gamestate->transitions, 'no transition');
                Assert::false($game->globals->has(Game::RECRUIT_TYPE), 'recruit type untouched');
            },

            'actHighDramaRecruitActionPerformerChosen accepts a city character standing with a mercenary' => function () {
                [$game, $performer] = $this->recruitScene();
                $game->globals->set(Game::CHOSEN_PERFORMER, 0);

                $game->actHighDramaRecruitActionPerformerChosen($performer->Id);

                Assert::same($performer->Id, $game->globals->get(Game::CHOSEN_PERFORMER), 'performer recorded');
                Assert::same(['performerChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaRecruitActionPerformerChosen refuses a character waiting at Home' => function () {
                $game = new FrameworkActionsGame();
                $homebody = $game->place(new GenericCharacter('Homebody'), Game::LOCATION_PLAYER_HOME, 1);

                Assert::throws(fn() => $game->actHighDramaRecruitActionPerformerChosen($homebody->Id), 'not in the City', 'refused');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaRecruitActionPerformerChosen refuses a city character with no mercenary at their location' => function () {
                $game = new FrameworkActionsGame();
                $lonely = $game->place(new GenericCharacter('Lonely'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCityCharacter('Far Merc'), Game::LOCATION_CITY_FORUM, 0);

                Assert::throws(fn() => $game->actHighDramaRecruitActionPerformerChosen($lonely->Id), 'not in a state to recruit', 'refused');
                Assert::false($game->globals->has(Game::CHOSEN_PERFORMER), 'nothing recorded');
            },

            'actHighDramaRecruitActionPerformerChosen honours the Extra Action performer lock' => function () {
                [$game, $performer] = $this->recruitScene();
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 4242);

                Assert::throws(fn() => $game->actHighDramaRecruitActionPerformerChosen($performer->Id), 'same character', 'locked to someone else');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            // ---- Recruit: Parley -------------------------------------------------------------------

            'actHighDramaRecruitActionParleyYes discounts by Influence, explains it privately and marks the parley' => function () {
                [$game, $performer] = $this->recruitScene();

                $game->actHighDramaRecruitActionParleyYes();

                Assert::true($performer->ModifiedInfluence > 0, 'fixture has Influence');
                Assert::same($performer->ModifiedInfluence, $game->globals->get(Game::DISCOUNT), 'discount = Influence');
                Assert::contains('for Influence', $game->globals->get(Game::DISCOUNT_EXPLAINATIONS), 'explained');
                Assert::true($game->globals->get(Game::PERFORMER_PARLEYED), 'parley flag');
                $private = array_values(array_filter($game->notify->messages, fn($m) => $m['channel'] === 'player:1'));
                Assert::count(1, $private, 'private explanation to the performer controller');
                Assert::same(['parleyChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaRecruitActionParleyNo records no discount and says nothing' => function () {
                [$game] = $this->recruitScene();

                $game->actHighDramaRecruitActionParleyNo();

                Assert::same(0, $game->globals->get(Game::DISCOUNT), 'no discount');
                Assert::false($game->globals->get(Game::PERFORMER_PARLEYED), 'not parleyed');
                Assert::same([], $game->notify->messages, 'nothing to explain');
                Assert::same(['parleyChosen'], $game->gamestate->transitions, 'same transition as Yes');
            },

            // ---- Recruit: mercenary pick ---------------------------------------------------------------

            'actHighDramaRecruitActionMercenaryChosen moves to payment without engaging when nobody parleyed' => function () {
                [$game, $performer, $merc] = $this->recruitScene();
                $game->globals->set(Game::RECRUIT_TYPE, Game::NORMAL_RECRUIT_TYPE);
                $game->globals->set(Game::PERFORMER_PARLEYED, false);

                $game->actHighDramaRecruitActionMercenaryChosen($merc->Id);

                Assert::same($merc->Id, $game->globals->get(Game::CHOSEN_CARD), 'mercenary recorded');
                $pay = $game->queued(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay state entered');
                Assert::same($merc->Id, $pay[0]->cardId, 'for the mercenary');
                Assert::same(1, $pay[0]->playerId, 'by the active player');
                Assert::same(Game::PAY_STATE_RECRUIT_MERCENARY, $pay[0]->payStateType, 'recruit payment');
                Assert::count(0, $game->queued(EventCardEngaged::class), 'no engage');
                Assert::same(['mercenaryChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaRecruitActionMercenaryChosen engages a parleying performer (normal recruit)' => function () {
                [$game, $performer, $merc] = $this->recruitScene(true);
                $game->globals->set(Game::RECRUIT_TYPE, Game::NORMAL_RECRUIT_TYPE);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);

                $game->actHighDramaRecruitActionMercenaryChosen($merc->Id);

                $engage = $game->queued(EventCardEngaged::class);
                Assert::count(1, $engage, 'engaged');
                Assert::same($performer->Id, $engage[0]->cardId, 'the performer');
                Assert::same(1, $engage[0]->playerId, 'by the active player');
                Assert::count(1, array_filter($game->theah->checkedEvents, fn($e) => $e instanceof EventCardEngaged), 'engage offered to the cards');
            },

            'actHighDramaRecruitActionMercenaryChosen does not engage an already-engaged parleying performer' => function () {
                [$game, $performer, $merc] = $this->recruitScene(true);
                $performer->Engaged = true;
                $game->globals->set(Game::RECRUIT_TYPE, Game::NORMAL_RECRUIT_TYPE);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);

                $game->actHighDramaRecruitActionMercenaryChosen($merc->Id);

                Assert::count(0, $game->queued(EventCardEngaged::class), 'no second engage');
            },

            'actHighDramaRecruitActionMercenaryChosen refuses a Parley Yes against a non-negotiable mercenary' => function () {
                // WHY: Parley is chosen before the target; the Yes must not stick to a mercenary that cannot be parleyed.
                [$game, , $merc] = $this->recruitScene(false);
                $game->globals->set(Game::RECRUIT_TYPE, Game::NORMAL_RECRUIT_TYPE);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);

                Assert::throws(fn() => $game->actHighDramaRecruitActionMercenaryChosen($merc->Id), 'cannot Parley', 'refused');

                Assert::false($game->globals->has(Game::CHOSEN_CARD), 'nothing recorded');
                Assert::count(0, $game->queued(EventEnteringPayState::class), 'no pay state');
                Assert::count(0, $game->queued(EventCardEngaged::class), 'no engage');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaRecruitActionMercenaryChosen: Silver Tongue parleys without engaging, with the same negotiable gate' => function () {
                [$game, , $merc] = $this->recruitScene(true);
                $game->globals->set(Game::RECRUIT_TYPE, Game::SILVER_TONGUE_RECRUIT_TYPE);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);

                $game->actHighDramaRecruitActionMercenaryChosen($merc->Id);

                Assert::count(0, $game->queued(EventCardEngaged::class), 'Silver Tongue does not engage');
                Assert::same(['mercenaryChosen'], $game->gamestate->transitions, 'transition');

                [$game, , $stubborn] = $this->recruitScene(false);
                $game->globals->set(Game::RECRUIT_TYPE, Game::SILVER_TONGUE_RECRUIT_TYPE);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);
                Assert::throws(fn() => $game->actHighDramaRecruitActionMercenaryChosen($stubborn->Id), 'cannot Parley', 'still gated on Negotiable');
            },

            'actHighDramaRecruitActionMercenaryChosen: Kaspar and Cirilo recruits skip the Parley gate and the engage' => function () {
                foreach ([Game::KASPAR_RECRUIT_TYPE, Game::CIRILO_RECRUIT_TYPE] as $type) {
                    [$game, , $merc] = $this->recruitScene(false);
                    $game->globals->set(Game::RECRUIT_TYPE, $type);
                    $game->globals->set(Game::PERFORMER_PARLEYED, true);

                    $game->actHighDramaRecruitActionMercenaryChosen($merc->Id);

                    Assert::count(0, $game->queued(EventCardEngaged::class), "type $type: no engage");
                    Assert::same(['mercenaryChosen'], $game->gamestate->transitions, "type $type: allowed");
                }
            },

            'actHighDramaRecruitActionMercenaryChosen refuses a mercenary at another location or a non-mercenary' => function () {
                [$game, $performer] = $this->recruitScene();
                $far = $game->place(new GenericCityCharacter('Far Merc'), Game::LOCATION_CITY_FORUM, 0);
                $notMerc = $game->place(new GenericCharacter('Bystander'), Game::LOCATION_CITY_DOCKS, 2);

                Assert::throws(fn() => $game->actHighDramaRecruitActionMercenaryChosen($far->Id), "not a Mercenary at the Performer's Location", 'far');
                Assert::throws(fn() => $game->actHighDramaRecruitActionMercenaryChosen($notMerc->Id), "not a Mercenary at the Performer's Location", 'not a mercenary');
                Assert::count(0, $game->queued(EventEnteringPayState::class), 'no pay state');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            // ---- Recruit: payment ----------------------------------------------------------------------------

            'actHighDramaRecruitActionPayForMercenary announces, resolves, recruits and discards the payment' => function () {
                [$game, $performer, $merc] = $this->recruitScene();
                $game->globals->set(Game::CHOSEN_CARD, $merc->Id);
                $game->globals->set(Game::DISCOUNT, 0);
                $game->globals->set(Game::PASS_COUNT, 2);
                $pay = $this->hand($game, 2);

                $game->actHighDramaRecruitActionPayForMercenary(json_encode($pay));

                Assert::contains('to perform a Recruit Action', $game->notify->messages[0]['message'], 'announced');
                Assert::count(1, $game->notify->messages, 'no Parley line when nobody parleyed');
                Assert::count(1, $game->queued(EventActionResolved::class), 'action resolved');
                Assert::same(
                    EventActionResolved::class,
                    end($game->theah->queuedEvents)::class,
                    'resolution is queued last, after the payment was accepted'
                );
                $recruited = $game->queued(EventCharacterRecruited::class);
                Assert::count(1, $recruited, 'recruited');
                Assert::same($merc->Id, $recruited[0]->characterId, 'mercenary');
                Assert::same(1, $recruited[0]->playerId, 'recruiter');
                Assert::same(2, $recruited[0]->cost, 'full printed cost');
                Assert::same(0, $recruited[0]->discount, 'no discount');
                $discards = $game->queued(EventCardDiscardedFromHand::class);
                Assert::same($pay, array_map(fn($e) => $e->cardId, $discards), 'payment discarded in order');
                foreach ($discards as $discard) {
                    Assert::true($discard->AsPayment, 'flagged as payment');
                    Assert::same(0, $discard->sourceId, 'framework-caused');
                }
                Assert::same(0, $game->globals->get(Game::PASS_COUNT), 'pass count reset');
                Assert::same(['mercenaryPaidFor'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaRecruitActionPayForMercenary adds a Parley announcement when the performer parleyed' => function () {
                [$game, , $merc] = $this->recruitScene();
                $game->globals->set(Game::CHOSEN_CARD, $merc->Id);
                $game->globals->set(Game::DISCOUNT, 1);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);
                $pay = $this->hand($game, 1);

                $game->actHighDramaRecruitActionPayForMercenary(json_encode($pay));

                Assert::count(2, $game->notify->messages, 'recruit + parley lines');
                Assert::contains('chose to Parley', $game->notify->messages[1]['message'], 'parley announced');
                $recruited = $game->queued(EventCharacterRecruited::class);
                Assert::same(1, $recruited[0]->cost, 'cost reduced by the discount');
                Assert::same(1, $recruited[0]->discount, 'discount recorded');
            },

            'actHighDramaRecruitActionPayForMercenary rejects underpayment' => function () {
                [$game, , $merc] = $this->recruitScene();
                $game->globals->set(Game::CHOSEN_CARD, $merc->Id);
                $game->globals->set(Game::DISCOUNT, 0);
                $pay = $this->hand($game, 1);

                Assert::throws(
                    fn() => $game->actHighDramaRecruitActionPayForMercenary(json_encode($pay)),
                    'Cost of Mercenary is 2. You selected 1 Wealth',
                    'underpaid'
                );

                Assert::count(0, $game->queued(EventCharacterRecruited::class), 'not recruited');
                Assert::count(0, $game->queued(EventCardDiscardedFromHand::class), 'nothing discarded');
                // WHY: the resolution must not be queued before the payment is validated.
                Assert::count(0, $game->queued(EventActionResolved::class), 'action not resolved');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued at all');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            // ---- actRecruitMercenary -----------------------------------------------------------------------

            'actRecruitMercenary rejects a missing or non-City character' => function () {
                [$game] = $this->recruitScene();
                $plain = $game->place(new GenericCharacter('Not a city card'), Game::LOCATION_CITY_DOCKS, 0);

                Assert::throws(fn() => $game->actRecruitMercenary(99999, '[]'), 'Character not found', 'missing');
                Assert::throws(fn() => $game->actRecruitMercenary($plain->Id, '[]'), 'not a City Character', 'wrong type');
            },

            'actRecruitMercenary takes the printed cost minus the discount, never below zero' => function () {
                [$game, , $merc] = $this->recruitScene();
                $game->globals->set(Game::DISCOUNT, 5); // more than the cost of 2

                $game->actRecruitMercenary($merc->Id, '[]');

                $recruited = $game->queued(EventCharacterRecruited::class);
                Assert::same(0, $recruited[0]->cost, 'floored at zero');
                Assert::same(5, $recruited[0]->discount, 'discount recorded as given');
            },

            'actRecruitMercenary passes the discount explanations through to the event' => function () {
                [$game, , $merc] = $this->recruitScene();
                $game->globals->set(Game::DISCOUNT, 1);
                $game->globals->set(Game::DISCOUNT_EXPLAINATIONS, 'because of reasons');

                $game->actRecruitMercenary($merc->Id, json_encode($this->hand($game, 1)));

                Assert::same('because of reasons', $game->queued(EventCharacterRecruited::class)[0]->explanations, 'explanations');
            },

            'actRecruitMercenary: a Cirilo recruit costs exactly 1 whatever the printed cost' => function () {
                [$game, , $merc] = $this->recruitScene();
                $merc->WealthCost = 4;
                $game->globals->set(Game::RECRUIT_TYPE, Game::CIRILO_RECRUIT_TYPE);

                $game->actRecruitMercenary($merc->Id, json_encode($this->hand($game, 1)));

                Assert::same(1, $game->queued(EventCharacterRecruited::class)[0]->cost, 'cost 1');
            },

            'actRecruitMercenary: a Wealth card may overpay by one, a plain card may not' => function () {
                [$game, , $merc] = $this->recruitScene();
                $merc->WealthCost = 1;

                $game->actRecruitMercenary($merc->Id, json_encode($this->hand($game, 1, true))); // one Wealth card = 2
                Assert::count(1, $game->queued(EventCharacterRecruited::class), 'accepted');

                [$game, , $merc] = $this->recruitScene();
                $merc->WealthCost = 1;
                Assert::throws(
                    fn() => $game->actRecruitMercenary($merc->Id, json_encode($this->hand($game, 2))),
                    'Cost of Mercenary is 1. You selected 2 Wealth',
                    'plain overpay refused'
                );
            },

            'actRecruitMercenary rejects a payment card that does not exist' => function () {
                [$game, , $merc] = $this->recruitScene();
                Assert::throws(fn() => $game->actRecruitMercenary($merc->Id, json_encode([99999])), 'Card #99999 not found', 'ghost card');
                Assert::count(0, $game->queued(EventCharacterRecruited::class), 'not recruited');
            },

            'actRecruitMercenary: a card veto on the recruit event stops the payment from being discarded' => function () {
                [$game, , $merc] = $this->recruitScene();
                $pay = $this->hand($game, 2);
                $game->theah->eventCheckHook = function (Event $event) {
                    if ($event instanceof EventCharacterRecruited) {
                        throw new \Exception('Cannot recruit that mercenary');
                    }
                };

                Assert::throws(fn() => $game->actRecruitMercenary($merc->Id, json_encode($pay)), 'Cannot recruit', 'veto propagates');

                Assert::count(0, $game->queued(EventCharacterRecruited::class), 'not recruited');
                Assert::count(0, $game->queued(EventCardDiscardedFromHand::class), 'payment kept');
            },
        ];
    }
}
