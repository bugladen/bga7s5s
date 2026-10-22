<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\Action;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\StatesTraitGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngarded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventHighDramaPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressured;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseHighDrama;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;

/** StatesTrait: High Drama phase bookkeeping, action dispatch, pressure, recruit helpers, next player. */
class StatesTrait_HighDrama_Test extends TestCase
{
    public function name(): string
    {
        return 'StatesTrait: high drama phase';
    }

    /** Dirty every per-turn global so reset states can prove they clean them. */
    private function dirtyTurnGlobals(StatesTraitGame $game): void
    {
        $game->globals->set(Game::PRESSURE_TYPE, Game::CASTILLIAN_CAPER_PRESSURE_TYPE);
        $game->globals->set(Game::PRESSURE_STAT, Game::STAT_FINESSE);
        $game->globals->set(Game::RECRUIT_TYPE, Game::KASPAR_RECRUIT_TYPE);
        $game->globals->set(Game::EQUIP_TYPE, Game::SMUGGLED_ITEM_EQUIP_TYPE);
        $game->globals->set(Game::CHALLENGE_TYPE, Game::TRISKELION_CHALLENGE_TYPE);
        $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_INFLUENCE);
        $game->globals->set(Game::IS_BASIC_CLAIM_ACTION, true);
        $game->globals->set(Game::ABNORMAL_FLOW, true);
    }

    private function assertTurnGlobalsReset(StatesTraitGame $game): void
    {
        Assert::same(Game::NORMAL_PRESSURE_TYPE, $game->globals->get(Game::PRESSURE_TYPE), 'pressure type');
        Assert::false($game->globals->has(Game::PRESSURE_STAT), 'pressure stat deleted');
        Assert::same(Game::NORMAL_RECRUIT_TYPE, $game->globals->get(Game::RECRUIT_TYPE), 'recruit type');
        Assert::same(Game::NORMAL_EQUIP_TYPE, $game->globals->get(Game::EQUIP_TYPE), 'equip type');
        Assert::same(Game::NORMAL_CHALLENGE_TYPE, $game->globals->get(Game::CHALLENGE_TYPE), 'challenge type');
        Assert::same(Game::STAT_COMBAT, $game->globals->get(Game::CHALLENGE_STAT), 'challenge stat');
    }

    private function action(string $id, bool $requiresPerformer): Action
    {
        return new class($id, $requiresPerformer) extends Action {
            public string $Id;

            public function __construct(string $id, bool $requiresPerformer)
            {
                parent::__construct();
                $this->Id = $id;
                $this->RequiresPerformerSelected = $requiresPerformer;
            }
        };
    }

    public function tests(): array
    {
        return [
            'stHighDramaBeginning enters High Drama, zeroes the pass count and queues PhaseHighDrama' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::PASS_COUNT, 3);
                $game->stHighDramaBeginning();

                Assert::same(Game::HIGH_DRAMA, $game->getGameStateValue(Game::TURN_PHASE), 'phase');
                Assert::same(0, $game->globals->get(Game::PASS_COUNT), 'pass count');
                Assert::count(1, $game->queued(EventPhaseHighDrama::class), 'event');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stHighDramaPhase hands the turn to the first player and resets per-turn globals' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::FIRST_PLAYER, 2);
                $this->dirtyTurnGlobals($game);

                $game->stHighDramaPhase();

                Assert::same([2], $game->gamestate->activePlayerChanges, 'first player active');
                $this->assertTurnGlobalsReset($game);
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stHighDramaPlayerTurn resets per-turn globals and the basic-claim / abnormal-flow flags' => function () {
                $game = new StatesTraitGame();
                $this->dirtyTurnGlobals($game);

                $game->stHighDramaPlayerTurn();

                $this->assertTurnGlobalsReset($game);
                Assert::false($game->globals->has(Game::IS_BASIC_CLAIM_ACTION), 'basic claim flag deleted');
                Assert::false($game->globals->has(Game::ABNORMAL_FLOW), 'abnormal flow deleted');
                Assert::same([], $game->gamestate->transitions, 'activeplayer state: no auto transition');
            },

            'stHighDramaEnd queues HighDramaPhaseEnd and continues' => function () {
                $game = new StatesTraitGame();
                $game->stHighDramaEnd();
                Assert::count(1, $game->queued(EventHighDramaPhaseEnd::class), 'event');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState');
            },

            // ---- action dispatch -----------------------------------------------------

            'stHighDramaInPlayActionDispatch re-seats CURRENT_PLAYER and queues ActionTriggered from Theah' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::CURRENT_PLAYER, 2);
                $game->globals->set(Game::CHOSEN_ACTION, 'BasicThing');
                $game->theah->registerTheahAction($this->action('BasicThing', false));

                $game->stHighDramaInPlayActionDispatch();

                Assert::same([2], $game->gamestate->activePlayerChanges, 'active player reset to CURRENT_PLAYER');
                $events = $game->queued(EventActionTriggered::class);
                Assert::count(1, $events, 'event');
                Assert::same(2, $events[0]->playerId, 'player');
                Assert::same(Game::THEAH_ID, $events[0]->performerId, 'Theah actions have no performer');
                Assert::same(Game::THEAH_ID, $events[0]->sourceId, 'source is Theah');
                Assert::same('BasicThing', $events[0]->actionId, 'action id');
                Assert::same(['inPlayActionChosen'], $game->gamestate->transitions, 'transition');
            },

            'stHighDramaInPlayActionDispatch routes to performer selection when the action needs one' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::CURRENT_PLAYER, 1);
                $game->globals->set(Game::CHOSEN_ACTION, 'NeedsPerformer');
                $game->theah->registerTheahAction($this->action('NeedsPerformer', true));

                $game->stHighDramaInPlayActionDispatch();

                Assert::count(1, $game->queued(EventActionTriggered::class), 'event still queued');
                Assert::same(1, $game->globals->get(Game::IN_PLAY_ACTION_TRIGGER_PENDING), 'trigger still waiting on the performer');
                Assert::same(['requiresPerformerSelected'], $game->gamestate->transitions, 'transition');
            },

            'stHighDramaInPlayActionDispatch with an unknown action still queues the event and proceeds' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::CURRENT_PLAYER, 1);
                $game->globals->set(Game::CHOSEN_ACTION, 'DoesNotExist');

                $game->stHighDramaInPlayActionDispatch();

                Assert::count(1, $game->queued(EventActionTriggered::class), 'event');
                Assert::same(['inPlayActionChosen'], $game->gamestate->transitions, 'transition');
            },

            // ---- pressure ---------------------------------------------------------

            'stHighDramaPressureLocation pressures the performer location and queues LocationPressured' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Pressurer'), Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::PRESSURING_PLAYER, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $game->globals->set(Game::TRANSITION_INTERNAL_ID, '01027_2');
                $game->pressureLocationResult = [true, 'totals text', 2];
                $game->pressureStatsResult = ['Influence', 'Finesse'];

                $game->stHighDramaPressureLocation();

                Assert::same([1], $game->gamestate->activePlayerChanges, 'pressuring player active');
                Assert::count(1, $game->pressureLocationCalls, 'pressure computed once');
                $call = $game->pressureLocationCalls[0];
                Assert::same(1, $call['playerId'], 'pressuring player');
                Assert::same($performer->Id, $call['performerId'], 'performer');
                Assert::same(Game::LOCATION_CITY_FORUM, $call['location'], 'performer location wins over captured');
                Assert::same(Game::STAT_INFLUENCE, $call['pressureType'], 'Influence by default');

                $events = $game->queued(EventLocationPressured::class);
                Assert::count(1, $events, 'event');
                Assert::same(1, $events[0]->playerId, 'event player');
                Assert::same($performer->Id, $events[0]->performerId, 'event performer');
                Assert::same(Game::LOCATION_CITY_FORUM, $events[0]->location, 'event location');
                Assert::same('Influence, Finesse', $events[0]->pressureType, 'stats joined');
                Assert::true($events[0]->success, 'success');
                Assert::same('totals text', $events[0]->totalsExplanation, 'explanation');
                Assert::same(2, $events[0]->difference, 'difference');
                Assert::same('01027_2', $events[0]->abilityId, 'ability id from transition id');
                Assert::false($events[0]->highDramaBasicAction, 'not a basic claim');
                Assert::same([null], $game->gamestate->transitions, 'bare nextState (sole transition)');
            },

            'stHighDramaPressureLocation without a performer uses the captured location' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::PRESSURING_PLAYER, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, 0);
                $game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_BAZAAR);

                $game->stHighDramaPressureLocation();

                Assert::same(null, $game->pressureLocationCalls[0]['performerId'], 'no performer');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $game->pressureLocationCalls[0]['location'], 'captured location');
                $events = $game->queued(EventLocationPressured::class);
                Assert::same(null, $events[0]->performerId, 'event has no performer');
            },

            'stHighDramaPressureLocation honours a PRESSURE_STAT override' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::PRESSURING_PLAYER, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, 0);
                $game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $game->globals->set(Game::PRESSURE_STAT, Game::STAT_RESOLVE);

                $game->stHighDramaPressureLocation();

                Assert::same(Game::STAT_RESOLVE, $game->pressureLocationCalls[0]['pressureType'], 'stat passed through');
            },

            'stHighDramaPressureLocation on a basic claim zeroes the pass count and flags the event' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::PRESSURING_PLAYER, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, 0);
                $game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_DOCKS);
                $game->globals->set(Game::IS_BASIC_CLAIM_ACTION, true);
                $game->globals->set(Game::PASS_COUNT, 2);

                $game->stHighDramaPressureLocation();

                Assert::same(0, $game->globals->get(Game::PASS_COUNT), 'pass count reset');
                Assert::true($game->queued(EventLocationPressured::class)[0]->highDramaBasicAction, 'flagged as basic action');
            },

            'stHighDramaPressureLocation on a basic claim keeps the city site when the performer left play' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Dead'), 'Discard-1', 1);
                $game->discardOrLockerByCardId[$performer->Id] = true;
                $game->globals->set(Game::PRESSURING_PLAYER, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::CHOSEN_LOCATION, Game::LOCATION_CITY_BAZAAR);
                $game->globals->set(Game::IS_BASIC_CLAIM_ACTION, true);

                $game->stHighDramaPressureLocation();

                Assert::same(Game::LOCATION_CITY_BAZAAR, $game->pressureLocationCalls[0]['location'], 'captured city site');
            },

            // ---- recruit ------------------------------------------------------------

            'stHighDramaRecruitActionParleyable: a negotiable mercenary at the site makes Parley an option' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCityCharacter('Negotiable Merc', true), Game::LOCATION_CITY_DOCKS, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $game->stHighDramaRecruitActionParleyable();

                Assert::same(['parleyable'], $game->gamestate->transitions, 'parleyable');
                Assert::false($game->globals->has(Game::DISCOUNT), 'discount untouched until the choice is made');
            },

            'stHighDramaRecruitActionParleyable: no negotiable mercenary skips the prompt with a zero discount' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCityCharacter('Stubborn Merc', false), Game::LOCATION_CITY_DOCKS, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $game->stHighDramaRecruitActionParleyable();

                Assert::same(['notParleyable'], $game->gamestate->transitions, 'notParleyable');
                Assert::same(0, $game->globals->get(Game::DISCOUNT), 'no discount');
                Assert::false($game->globals->get(Game::PERFORMER_PARLEYED), 'not parleyed');
            },

            'stHighDramaRecruitActionParleyable: a mercenary elsewhere does not count' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCityCharacter('Far Merc', true), Game::LOCATION_CITY_FORUM, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $game->stHighDramaRecruitActionParleyable();

                Assert::same(['notParleyable'], $game->gamestate->transitions, 'notParleyable');
            },

            'stHighDramaRecruitActionParleyable: an engaged performer cannot parley' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Tired'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Engaged = true;
                $game->place(new GenericCityCharacter('Negotiable Merc', true), Game::LOCATION_CITY_DOCKS, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $game->stHighDramaRecruitActionParleyable();

                Assert::same(['notParleyable'], $game->gamestate->transitions, 'notParleyable');
            },

            'stHighDramaRecruitActionParleyable: a Mercenary performer cannot parley' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Hired Gun', ['Mercenary']), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCityCharacter('Negotiable Merc', true), Game::LOCATION_CITY_DOCKS, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $game->stHighDramaRecruitActionParleyable();

                Assert::same(['notParleyable'], $game->gamestate->transitions, 'notParleyable');
            },

            'stHighDramaRecruitActionParleyable: a mercenary already controlled is not a recruit target' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCityCharacter('Owned Merc', true), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $game->stHighDramaRecruitActionParleyable();

                Assert::same(['notParleyable'], $game->gamestate->transitions, 'notParleyable');
            },

            'stRecruitComputeDiscount: parleying with a negotiable recruit discounts by the performer Influence' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $game->place(new GenericCityCharacter('Negotiable Merc', true), Game::LOCATION_CITY_DOCKS, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::CHOSEN_CARD, $merc->Id);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);

                $game->stRecruitComputeDiscount();

                Assert::same($performer->ModifiedInfluence, $game->globals->get(Game::DISCOUNT), 'discount equals Influence');
                Assert::true($performer->ModifiedInfluence > 0, 'fixture has Influence');
                Assert::contains('for Influence', (string) $game->globals->get(Game::DISCOUNT_EXPLAINATIONS), 'explained');
                $private = array_values(array_filter($game->notify->messages, fn($m) => $m['channel'] === 'player:1'));
                Assert::count(1, $private, 'private explanation to the performer controller');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stRecruitComputeDiscount: a Yes to Parley does not apply to a non-negotiable recruit' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $game->place(new GenericCityCharacter('Stubborn Merc', false), Game::LOCATION_CITY_DOCKS, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::CHOSEN_CARD, $merc->Id);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);

                $game->stRecruitComputeDiscount();

                Assert::same(0, $game->globals->get(Game::DISCOUNT), 'no discount');
                Assert::same([], $game->notify->messages, 'nothing to explain');
            },

            'stRecruitComputeDiscount: no parley means no discount' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $merc = $game->place(new GenericCityCharacter('Negotiable Merc', true), Game::LOCATION_CITY_DOCKS, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::CHOSEN_CARD, $merc->Id);

                $game->stRecruitComputeDiscount();

                Assert::same(0, $game->globals->get(Game::DISCOUNT), 'no discount');
            },

            'stRecruitUndoEngage: a parleyed, engaged performer is re-engarded without transitions' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Engaged = true;
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);

                $game->stRecruitUndoEngage();

                $events = $game->queued(EventCardEngarded::class);
                Assert::count(1, $events, 'engarde queued');
                Assert::same($performer->Id, $events[0]->cardId, 'performer');
                Assert::same(1, $events[0]->playerId, 'controller');
                Assert::same([true], $game->theah->runEventsCalls, 'ran with skipTransitions');
                Assert::false($game->globals->get(Game::PERFORMER_PARLEYED), 'flag cleared');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stRecruitUndoEngage: nothing to undo when the performer did not parley' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Engaged = true;
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::PERFORMER_PARLEYED, false);

                $game->stRecruitUndoEngage();

                Assert::count(0, $game->queued(EventCardEngarded::class), 'no engarde');
                Assert::same([], $game->theah->runEventsCalls, 'no event run');
                Assert::false($game->globals->get(Game::PERFORMER_PARLEYED), 'flag stays false');
            },

            'stRecruitUndoEngage: a parleyed performer that is already en garde is left alone' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);

                $game->stRecruitUndoEngage();

                Assert::count(0, $game->queued(EventCardEngarded::class), 'no engarde');
                Assert::false($game->globals->get(Game::PERFORMER_PARLEYED), 'flag cleared');
            },

            'stRecruitUndoEngageToMerc: undoes the engage and recomputes the discount for a parleyed performer' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $performer->Engaged = true;
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::PERFORMER_PARLEYED, true);

                $game->stRecruitUndoEngageToMerc();

                Assert::count(1, $game->queued(EventCardEngarded::class), 'engarde queued');
                Assert::same([true], $game->theah->runEventsCalls, 'ran with skipTransitions');
                Assert::same(2, $game->theah->buildCityCalls, 'city rebuilt before and after the undo');
                Assert::same($performer->ModifiedInfluence, $game->globals->get(Game::DISCOUNT), 'parley discount restored');
                Assert::same([''], $game->gamestate->transitions, 'continues');
            },

            'stRecruitUndoEngageToMerc: a non-parleyed performer keeps a zero discount' => function () {
                $game = new StatesTraitGame();
                $performer = $game->place(new GenericCharacter('Recruiter'), Game::LOCATION_CITY_DOCKS, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

                $game->stRecruitUndoEngageToMerc();

                Assert::count(0, $game->queued(EventCardEngarded::class), 'no engarde');
                Assert::same(0, $game->globals->get(Game::DISCOUNT), 'no discount');
            },

            // ---- next player ----------------------------------------------------------

            'stNextPlayer clears every per-action global' => function () {
                $game = new StatesTraitGame();
                foreach ([
                    Game::CHOSEN_OPPONENT, Game::CHOSEN_CARD, Game::CHOSEN_CARD_COST, Game::NEXT_COMBAT_CARD,
                    Game::CHOSEN_LOCATION, Game::CHOSEN_PERFORMER, Game::PERFORMER_PARLEYED, Game::CHOSEN_ATTACHMENT,
                    Game::CHOSEN_ACTION, Game::CHOSEN_TARGET, Game::CHOSEN_TECHNIQUE_IS_MAIN, Game::CHOSEN_TECHNIQUE,
                    Game::CHOSEN_MANEUVER, Game::TRANSITION_SOURCE_ID, Game::TRANSITION_INTERNAL_ID, Game::REACTION_ID,
                    Game::REVEALED_CARDS, Game::ABNORMAL_FLOW, Game::DISCOUNT, Game::PRESSURE_BONUS, Game::LOYAL_PLAYER_ID,
                    Game::VANTAGE_POINT_PLAYER_ID, Game::MEETING_OF_THE_MINDS_PLAYER_ID, Game::SMUGGLED_ITEM_ATTACHMENT_ID,
                    Game::IN_PLAY_ACTION_TRIGGER_PENDING, Game::IN_PLAY_ACTION_ENTRY_TRANSITION, Game::IN_PLAY_ACTION_ENTRY_PLAYER_ID,
                ] as $key) {
                    $game->globals->set($key, 'dirty');
                }
                $this->dirtyTurnGlobals($game);
                $game->globals->set(Game::CURRENT_PLAYER, 1);

                $game->stNextPlayer();

                foreach ([
                    Game::CHOSEN_OPPONENT, Game::CHOSEN_CARD, Game::CHOSEN_CARD_COST, Game::NEXT_COMBAT_CARD,
                    Game::CHOSEN_LOCATION, Game::CHOSEN_PERFORMER, Game::PERFORMER_PARLEYED, Game::CHOSEN_ATTACHMENT,
                    Game::CHOSEN_ACTION, Game::CHOSEN_TARGET, Game::CHOSEN_TECHNIQUE_IS_MAIN, Game::CHOSEN_TECHNIQUE,
                    Game::CHOSEN_MANEUVER, Game::TRANSITION_SOURCE_ID, Game::TRANSITION_INTERNAL_ID, Game::REACTION_ID,
                    Game::REVEALED_CARDS, Game::ABNORMAL_FLOW, Game::DISCOUNT, Game::PRESSURE_BONUS, Game::LOYAL_PLAYER_ID,
                    Game::VANTAGE_POINT_PLAYER_ID, Game::MEETING_OF_THE_MINDS_PLAYER_ID, Game::SMUGGLED_ITEM_ATTACHMENT_ID,
                    Game::IN_PLAY_ACTION_TRIGGER_PENDING, Game::IN_PLAY_ACTION_ENTRY_TRANSITION, Game::IN_PLAY_ACTION_ENTRY_PLAYER_ID,
                ] as $key) {
                    Assert::false($game->globals->has($key), "{$key} deleted");
                }
                $this->assertTurnGlobalsReset($game);
            },

            'stNextPlayer passes the turn and queues PlayerTurnEnd when no extra actions remain' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::CURRENT_PLAYER, 1);
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 99);

                $game->stNextPlayer();

                Assert::same(2, $game->globals->get(Game::CURRENT_PLAYER), 'turn passes');
                Assert::false($game->globals->has(Game::EXTRA_ACTION_PERFORMER), 'extra action performer cleared');
                $events = $game->queued(EventPlayerTurnEnd::class);
                Assert::count(1, $events, 'PlayerTurnEnd');
                Assert::same(1, $events[0]->playerId, 'ended the outgoing player turn');
                Assert::same([1], $game->extraTimeGiven, 'outgoing player got extra time');
                Assert::same(['nextPlayer'], $game->gamestate->transitions, 'transition');
            },

            'stNextPlayer wraps the turn from the last player to the first' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::CURRENT_PLAYER, 2);
                $game->stNextPlayer();
                Assert::same(1, $game->globals->get(Game::CURRENT_PLAYER), 'wraps');
            },

            'stNextPlayer spends an extra action instead of passing the turn' => function () {
                $game = new StatesTraitGame();
                $game->globals->set(Game::CURRENT_PLAYER, 1);
                $game->globals->set(Game::EXTRA_ACTIONS, 2);
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 99);

                $game->stNextPlayer();

                Assert::same(1, $game->globals->get(Game::CURRENT_PLAYER), 'same player keeps the turn');
                Assert::same(1, $game->globals->get(Game::EXTRA_ACTIONS), 'one extra action spent');
                Assert::same(99, $game->globals->get(Game::EXTRA_ACTION_PERFORMER), 'performer lock survives for the extra action');
                Assert::count(0, $game->queued(EventPlayerTurnEnd::class), 'turn did not end');
                Assert::same(2, $game->notify->messages[0]['args']['extra_actions'], 'announces the count before spending');
                Assert::same(['nextPlayer'], $game->gamestate->transitions, 'still loops through nextPlayer');
            },
        ];
    }
}
