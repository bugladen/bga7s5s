<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01062;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01178;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd09;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneParry;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\AbilityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLocationAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeAccepted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;

/** FrameworkActionsTrait: the basic Challenge action, from start through accept / refuse / intervene. */
class FrameworkActions_Challenge_Test extends TestCase
{
    public function name(): string
    {
        return 'FrameworkActionsTrait: challenge';
    }

    /**
     * Player 1's challenger and player 2's defender, both at the Docks, with the globals the
     * flow has set by the time a target is chosen.
     *
     * @return array{0:FrameworkActionsGame,1:GenericCharacter,2:GenericCharacter}
     */
    private function challengeScene(int $type = Game::NORMAL_CHALLENGE_TYPE): array
    {
        $game = new FrameworkActionsGame();
        $challenger = $game->place(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
        $defender = $game->place(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
        $game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
        $game->globals->set(Game::CHOSEN_TARGET, $defender->Id);
        $game->globals->set(Game::CHALLENGE_TYPE, $type);
        $game->globals->set(Game::CURRENT_PLAYER, 1);
        return [$game, $challenger, $defender];
    }

    private function registerBasicChallenge(FrameworkActionsGame $game): GenericLocationAction
    {
        $action = new class($game) extends GenericLocationAction {
            public function __construct($game)
            {
                parent::__construct($game);
                $this->Id = 'BasicChallenge';
            }
        };
        $game->theah->registerTheahAction($action);
        return $action;
    }

    /** @return array<string, int> challenge types a character may never refuse, by label */
    private function unrefusableTypes(): array
    {
        return [
            'Épée Sanglante' => Game::EPEE_SANGLANTE_CHALLENGE_TYPE,
            'Unsanctioned Duel' => Game::UNSANCTIONED_DUEL_CHALLENGE_TYPE,
            'Stand Your Ground' => Game::STAND_YOUR_GROUND_CHALLENGE_TYPE,
            'Rage' => Game::RAGE_CHALLENGE_TYPE,
        ];
    }

    public function tests(): array
    {
        return [
            // ---- Start ---------------------------------------------------------------------------------

            'actHighDramaChallengeActionStart sets the default challenge and picks the Basic Challenge action' => function () {
                [$game] = $this->challengeScene();
                $this->registerBasicChallenge($game);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE); // stale
                $game->globals->set(Game::CHALLENGE_TYPE, Game::RAGE_CHALLENGE_TYPE); // stale

                $game->actHighDramaChallengeActionStart();

                Assert::same(Game::STAT_COMBAT, $game->globals->get(Game::CHALLENGE_STAT), 'Combat challenge');
                Assert::same(Game::NORMAL_CHALLENGE_TYPE, $game->globals->get(Game::CHALLENGE_TYPE), 'normal challenge');
                Assert::same('BasicChallenge', $game->globals->get(Game::CHOSEN_ACTION), 'basic challenge action');
                Assert::same('BasicChallenge', $game->globals->get(Game::TRANSITION_INTERNAL_ID), 'transition id');
                Assert::same(['challengeActionStart'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaChallengeActionStart is refused when no one can challenge' => function () {
                $game = new FrameworkActionsGame();
                $this->registerBasicChallenge($game);
                $engaged = $game->place(new GenericCharacter('Engaged'), Game::LOCATION_CITY_DOCKS, 1);
                $engaged->Engaged = true;
                $dashed = $game->place(new GenericCharacter('Dashed'), Game::LOCATION_CITY_FORUM, 1);
                $dashed->DashedCombat = true;
                $lonely = $game->place(new GenericCharacter('Lonely'), Game::LOCATION_CITY_BAZAAR, 1); // nobody to challenge there
                $game->place(new GenericCharacter('Theirs'), Game::LOCATION_CITY_DOCKS, 2);
                $game->place(new GenericCharacter('Theirs, too'), Game::LOCATION_CITY_FORUM, 2);

                Assert::throws(fn() => $game->actHighDramaChallengeActionStart(), 'Challenge Action is not allowed', 'refused');
                Assert::same([], $game->gamestate->transitions, 'no transition');
                Assert::false($game->globals->has(Game::CHALLENGE_STAT), 'nothing set');
            },

            // ---- Performer -----------------------------------------------------------------------------

            'actHighDramaChallengeActionPerformerChosen accepts a ready character with someone to challenge' => function () {
                [$game, $challenger] = $this->challengeScene();

                $game->actHighDramaChallengeActionPerformerChosen($challenger->Id);

                Assert::same($challenger->Id, $game->globals->get(Game::CHOSEN_PERFORMER), 'recorded');
                Assert::same(['performerChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaChallengeActionPerformerChosen refuses engaged, Dashed Combat, Home and opposed-by-nobody performers' => function () {
                $game = new FrameworkActionsGame();
                $engaged = $game->place(new GenericCharacter('Engaged'), Game::LOCATION_CITY_DOCKS, 1);
                $engaged->Engaged = true;
                $dashed = $game->place(new GenericCharacter('Dashed'), Game::LOCATION_CITY_FORUM, 1);
                $dashed->DashedCombat = true;
                $home = $game->place(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);
                $lonely = $game->place(new GenericCharacter('Lonely'), Game::LOCATION_CITY_BAZAAR, 1);
                $game->place(new GenericCharacter('Theirs'), Game::LOCATION_CITY_DOCKS, 2);
                $game->place(new GenericCharacter('Theirs, too'), Game::LOCATION_CITY_FORUM, 2);

                Assert::throws(fn() => $game->actHighDramaChallengeActionPerformerChosen($engaged->Id), 'Performer cannot Challenge', 'engaged');
                Assert::throws(fn() => $game->actHighDramaChallengeActionPerformerChosen($dashed->Id), 'Dashed Combat', 'dashed');
                Assert::throws(fn() => $game->actHighDramaChallengeActionPerformerChosen($home->Id), 'not in the City', 'home');
                Assert::throws(fn() => $game->actHighDramaChallengeActionPerformerChosen($lonely->Id), 'No Challengable Characters', 'nobody there');
                Assert::false($game->globals->has(Game::CHOSEN_PERFORMER), 'nothing recorded');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaChallengeActionPerformerChosen ignores uncontrolled and own characters at the site' => function () {
                $game = new FrameworkActionsGame();
                $challenger = $game->place(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCharacter('Free Mercenary'), Game::LOCATION_CITY_DOCKS, 0);

                Assert::throws(fn() => $game->actHighDramaChallengeActionPerformerChosen($challenger->Id), 'No Challengable Characters', 'refused');
            },

            'actHighDramaChallengeActionPerformerChosen: Carmella may challenge while engaged until she has used her ability' => function () {
                $game = new FrameworkActionsGame();
                $carmella = $game->place(new _01178(), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericCharacter('Theirs'), Game::LOCATION_CITY_DOCKS, 2);
                $carmella->Engaged = true;
                $carmella->AbilityUsed = false;

                $game->actHighDramaChallengeActionPerformerChosen($carmella->Id);
                Assert::same(['performerChosen'], $game->gamestate->transitions, 'engaged but ability unused: allowed');

                $carmella->AbilityUsed = true;
                Assert::throws(fn() => $game->actHighDramaChallengeActionPerformerChosen($carmella->Id), 'Performer cannot Challenge', 'engaged and used');
            },

            'actHighDramaChallengeActionPerformerChosen honours the Extra Action performer lock' => function () {
                [$game, $challenger] = $this->challengeScene();
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 4242);

                Assert::throws(fn() => $game->actHighDramaChallengeActionPerformerChosen($challenger->Id), 'same character', 'locked elsewhere');
            },

            // ---- Target --------------------------------------------------------------------------------

            'actHighDramaChallengeActionTargetChosen records the target and moves on' => function () {
                [$game, $challenger, $defender] = $this->challengeScene();
                $game->globals->set(Game::CHOSEN_TARGET, 0);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_COMBAT);
                $this->registerBasicChallenge($game);
                $game->globals->set(Game::CHOSEN_ACTION, 'BasicChallenge');

                $game->actHighDramaChallengeActionTargetChosen($defender->Id);

                Assert::same($defender->Id, $game->globals->get(Game::CHOSEN_TARGET), 'recorded');
                Assert::count(0, $game->queued(EventCharacterTargeted::class), 'a plain challenge does not target through an ability');
                Assert::same(['targetChosen'], $game->gamestate->transitions, 'transition');
                // WHY: Unyielding Loyalty opens on the next state, before the challenge is
                // issued. The log has to name the challenger, the target, and the stat now.
                Assert::count(1, $game->notify->messages, 'one log line');
                Assert::contains('has chosen', $game->notify->messages[0]['message'], 'chosen');
                Assert::contains('to Challenge', $game->notify->messages[0]['message'], 'challenge');
                Assert::contains('Combat stat', $game->notify->messages[0]['message'], 'duel stat');
                Assert::same('message', $game->notify->messages[0]['type'], 'log only, no duel markers');
                Assert::same($challenger->getInjectCode(), $game->notify->messages[0]['args']['challenger_inject_code'], 'challenger');
                Assert::same($defender->getInjectCode(), $game->notify->messages[0]['args']['defender_inject_code'], 'defender');
            },

            'actHighDramaChallengeActionTargetChosen refuses an unknown character' => function () {
                [$game] = $this->challengeScene();
                $this->registerBasicChallenge($game);
                $game->globals->set(Game::CHOSEN_ACTION, 'BasicChallenge');

                Assert::throws(fn() => $game->actHighDramaChallengeActionTargetChosen(99999), 'Character not found', 'unknown');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaChallengeActionTargetChosen: a targeting ability validates the target and announces the targeting' => function () {
                [$game, $challenger, $defender] = $this->challengeScene();
                $game->globals->set(Game::CHOSEN_TARGET, 0);
                $owner = new class('Ability owner') extends AbilityCharacter {
                };
                $targeting = new class extends GenericCharacterAction implements IAbilityThatTargetsCharacters {
                    public array $verdict = [true, ''];

                    public function isValidTargetForAbility(Game $game, Character $character): array
                    {
                        return $this->verdict;
                    }
                };
                $owner->addAction($targeting, $game, false);
                $game->place($owner, Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::CHOSEN_ACTION, $targeting->Id);
                $game->globals->set(Game::CHALLENGE_STAT, Game::STAT_FINESSE);

                $targeting->verdict = [false, 'That target is not allowed.'];
                Assert::throws(fn() => $game->actHighDramaChallengeActionTargetChosen($defender->Id), 'That target is not allowed.', 'invalid target');
                Assert::same(0, $game->globals->get(Game::CHOSEN_TARGET), 'not recorded');
                Assert::count(0, $game->queued(EventCharacterTargeted::class), 'not announced');
                Assert::count(0, $game->notify->messages, 'a refused target is not logged');

                $targeting->verdict = [true, ''];
                $game->actHighDramaChallengeActionTargetChosen($defender->Id);

                Assert::same($defender->Id, $game->globals->get(Game::CHOSEN_TARGET), 'recorded');
                $targeted = $game->queued(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'CharacterTargeted announced');
                Assert::same(1, $targeted[0]->playerId, 'player');
                Assert::same($defender->Id, $targeted[0]->targetId, 'target');
                Assert::same($challenger->Id, $targeted[0]->sourceId, 'source falls back to the performer');
                Assert::same($targeting->Id, $targeted[0]->abilityId, 'ability');
                Assert::same(1, $targeted[0]->batchId, 'own event batch');
                Assert::same(['targetChosen'], $game->gamestate->transitions, 'transition');
                Assert::contains('Finesse stat', $game->notify->messages[0]['message'], 'duel stat before reactions');
            },

            'actHighDramaChallengeActionTargetChosen prefers the transition source over the performer' => function () {
                [$game, , $defender] = $this->challengeScene();
                $owner = new AbilityCharacter('Ability owner');
                $targeting = new class extends GenericCharacterAction implements IAbilityThatTargetsCharacters {
                    public function isValidTargetForAbility(Game $game, Character $character): array
                    {
                        return [true, ''];
                    }
                };
                $owner->addAction($targeting, $game, false);
                $game->place($owner, Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::CHOSEN_ACTION, $targeting->Id);
                $game->globals->set(Game::TRANSITION_SOURCE_ID, 777);

                $game->actHighDramaChallengeActionTargetChosen($defender->Id);

                Assert::same(777, $game->queued(EventCharacterTargeted::class)[0]->sourceId, 'transition source');
            },

            'actHighDramaChallengeActionTargetChosen: Defending Honor does not validate or announce the ability target' => function () {
                [$game, , $defender] = $this->challengeScene(Game::DEFENDING_HONOR_CHALLENGE_TYPE);
                $owner = new AbilityCharacter('Ability owner');
                $targeting = new class extends GenericCharacterAction implements IAbilityThatTargetsCharacters {
                    public function isValidTargetForAbility(Game $game, Character $character): array
                    {
                        return [false, 'Would be refused.'];
                    }
                };
                $owner->addAction($targeting, $game, false);
                $game->place($owner, Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::CHOSEN_ACTION, $targeting->Id);

                $game->actHighDramaChallengeActionTargetChosen($defender->Id);

                Assert::same($defender->Id, $game->globals->get(Game::CHOSEN_TARGET), 'recorded');
                Assert::count(0, $game->queued(EventCharacterTargeted::class), 'not announced');
                Assert::same(['targetChosen'], $game->gamestate->transitions, 'transition');
                Assert::contains('has chosen', $game->notify->messages[0]['message'], 'challenge intent still logged');
            },

            // ---- Technique -----------------------------------------------------------------------------

            'actHighDramaChallengeActionTechniqueActivated activates the Technique and issues the challenge' => function () {
                $game = new FrameworkActionsGame();
                $challenger = new AbilityCharacter('Challenger');
                $challenger->addTechnique(new Technique_PlusOneParry(), $game, false);
                $game->place($challenger, Game::LOCATION_CITY_DOCKS, 1);
                $defender = $game->place(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $defender->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $game->globals->set(Game::CURRENT_PLAYER, 1);
                $technique = $challenger->getTechniques()[0];

                $game->actHighDramaChallengeActionTechniqueActivated($technique->Id);

                Assert::same($technique->Id, $game->globals->get(Game::CHOSEN_TECHNIQUE), 'chosen technique');
                Assert::same($technique->Id, $game->globals->get(Game::TRANSITION_INTERNAL_ID), 'transition id');
                Assert::same(
                    [EventTechniqueActivated::class, EventChallengeIssued::class, EventCardEngaged::class],
                    array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents),
                    'activation, then the issued challenge, then the performer engages'
                );
                $activated = $game->queued(EventTechniqueActivated::class)[0];
                Assert::same(1, $activated->playerId, 'player');
                Assert::same($challenger->Id, $activated->ownerId, 'owner');
                $issued = $game->queued(EventChallengeIssued::class)[0];
                Assert::same($challenger->Id, $issued->challengerId, 'challenger');
                Assert::same($defender->Id, $issued->defenderId, 'defender');
                Assert::same($technique->Id, $issued->activatedTechniqueId, 'technique recorded on the challenge');
                Assert::false($game->globals->get(Game::CHALLENGE_ACCEPTED), 'acceptance reset');
                Assert::false($game->globals->get(Game::CHALLENGE_CANCELLED), 'cancellation reset');
                Assert::same(['techniqueActivated'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaChallengeActionTechniqueActivated refuses an unknown Technique' => function () {
                [$game] = $this->challengeScene();

                Assert::throws(fn() => $game->actHighDramaChallengeActionTechniqueActivated('nope'), 'Technique not found', 'unknown');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::false($game->globals->has(Game::CHOSEN_TECHNIQUE), 'nothing recorded');
            },

            'actHighDramaChallengeActionActivateTechnique_Pass issues the challenge without a Technique' => function () {
                [$game, $challenger, $defender] = $this->challengeScene();

                $game->actHighDramaChallengeActionActivateTechnique_Pass();

                $issued = $game->queued(EventChallengeIssued::class);
                Assert::count(1, $issued, 'challenge issued');
                Assert::same('', $issued[0]->activatedTechniqueId, 'no technique');
                Assert::count(0, $game->queued(EventTechniqueActivated::class), 'no activation');
                Assert::count(1, $game->queued(EventCardEngaged::class), 'performer engages for a normal challenge');
                Assert::same(['pass'], $game->gamestate->transitions, 'transition');
            },

            'a challenge that engages nobody (Defending Honor) issues without an engage' => function () {
                [$game] = $this->challengeScene(Game::DEFENDING_HONOR_CHALLENGE_TYPE);

                $game->actHighDramaChallengeActionActivateTechnique_Pass();

                Assert::count(1, $game->queued(EventChallengeIssued::class), 'challenge issued');
                Assert::count(0, $game->queued(EventCardEngaged::class), 'no engage');
            },

            // ---- Accept --------------------------------------------------------------------------------

            'actHighDramaChallengeActionAccept queues the acceptance and records it' => function () {
                [$game, $challenger, $defender] = $this->challengeScene();

                $game->actHighDramaChallengeActionAccept();

                $accepted = $game->queued(EventChallengeAccepted::class);
                Assert::count(1, $accepted, 'accepted event');
                Assert::same($challenger->Id, $accepted[0]->challengerId, 'challenger');
                Assert::same($defender->Id, $accepted[0]->targetId, 'target');
                Assert::count(1, $game->theah->checkedEvents, 'offered to the cards');
                Assert::contains('ACCEPTS The Challenge', $game->notify->messages[0]['message'], 'announced');
                Assert::true($game->globals->get(Game::CHALLENGE_ACCEPTED), 'recorded');
                Assert::same(['proceed'], $game->gamestate->transitions, 'transition');
            },

            // ---- Refuse --------------------------------------------------------------------------------

            'actHighDramaChallengeActionReject queues the refusal and records it' => function () {
                [$game, $challenger, $defender] = $this->challengeScene();

                $game->actHighDramaChallengeActionReject();

                $rejected = $game->queued(EventChallengeRejected::class);
                Assert::count(1, $rejected, 'rejected event');
                Assert::same($challenger->Id, $rejected[0]->challengerId, 'challenger');
                Assert::same($defender->Id, $rejected[0]->targetId, 'target');
                Assert::count(1, $game->theah->checkedEvents, 'offered to the cards');
                Assert::false($game->globals->get(Game::CHALLENGE_ACCEPTED), 'recorded as not accepted');
                Assert::same(['proceed'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaChallengeActionReject: some challenge types may never be refused' => function () {
                foreach ($this->unrefusableTypes() as $label => $type) {
                    [$game] = $this->challengeScene($type);

                    Assert::throws(fn() => $game->actHighDramaChallengeActionReject(), 'Refusing a Challenge is not allowed', $label);
                    Assert::same([], $game->theah->queuedEvents, "$label: nothing queued");
                    Assert::same([], $game->gamestate->transitions, "$label: no transition");
                }
            },

            'actHighDramaChallengeActionReject: Aja challenges can only be refused with 3 Finesse or more' => function () {
                [$game, , $defender] = $this->challengeScene(Game::AJA_CHALLENGE_TYPE);
                $defender->ModifiedFinesse = 2;
                Assert::throws(fn() => $game->actHighDramaChallengeActionReject(), '3 Finesse or more', 'too clumsy');

                $defender->ModifiedFinesse = 3;
                $game->actHighDramaChallengeActionReject();
                Assert::count(1, $game->queued(EventChallengeRejected::class), 'refused with enough Finesse');
            },

            'actHighDramaChallengeActionReject: Daichi cannot be refused by (or refuse) a character with greater Combat' => function () {
                $game = new FrameworkActionsGame();
                $daichi = $game->place(new _03050(), Game::LOCATION_CITY_DOCKS, 1);
                $brawler = $game->place(new GenericCharacter('Brawler'), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, $daichi->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $brawler->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);

                $daichi->ModifiedCombat = 2;
                $brawler->ModifiedCombat = 3;
                Assert::throws(fn() => $game->actHighDramaChallengeActionReject(), 'cannot be refused (greater Combat)', 'Daichi challenges a stronger fighter');

                $brawler->ModifiedCombat = 2;
                $game->actHighDramaChallengeActionReject();
                Assert::same(['proceed'], $game->gamestate->transitions, 'equal Combat may refuse');
            },

            'actHighDramaChallengeActionReject: Knives Out stops anyone at its location refusing' => function () {
                [$game, , $defender] = $this->challengeScene();
                $knives = $game->place(new _04cd09(), Game::LOCATION_CITY_DOCKS, 0);

                Assert::throws(fn() => $game->actHighDramaChallengeActionReject(), 'Knives Out', 'blocked at its location');

                $knives->Location = Game::LOCATION_CITY_FORUM;
                $game->actHighDramaChallengeActionReject();
                Assert::same(['proceed'], $game->gamestate->transitions, 'allowed elsewhere');
            },

            'actHighDramaChallengeActionReject: When Least Expected — a Duelist challenger forces a discard instead' => function () {
                [$game, $challenger, $defender] = $this->challengeScene(Game::WHEN_LEAST_EXPECTED_CHALLENGE_TYPE);
                $challenger->Traits = ['Duelist'];

                Assert::throws(fn() => $game->actHighDramaChallengeActionReject(), 'you have no cards in hand', 'empty hand: cannot refuse');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');

                $game->place(new GenericCharacter('Discard fodder'), Game::LOCATION_HAND, 2);
                $game->actHighDramaChallengeActionReject();

                Assert::same(['03042'], $game->gamestate->transitions, 'routed to the discard picker');
                Assert::count(0, $game->queued(EventChallengeRejected::class), 'not refused yet');
            },

            'actHighDramaChallengeActionReject: When Least Expected from a non-Duelist is an ordinary refusal' => function () {
                [$game] = $this->challengeScene(Game::WHEN_LEAST_EXPECTED_CHALLENGE_TYPE);

                $game->actHighDramaChallengeActionReject();

                Assert::count(1, $game->queued(EventChallengeRejected::class), 'refused');
                Assert::same(['proceed'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaChallengeActionReject uses the last-known snapshot of a participant who already died' => function () {
                $game = new FrameworkActionsGame();
                $daichiAlive = new _03050();
                $daichi = $game->place($daichiAlive, Game::LOCATION_CITY_DOCKS, 1);
                $brawler = $game->place(new GenericCharacter('Brawler'), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, $daichi->Id);
                $game->globals->set(Game::CHOSEN_TARGET, $brawler->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $brawler->ModifiedCombat = 3;
                $daichi->ModifiedCombat = 2;

                // Daichi died to a ChallengeIssued reaction; his Locker corpse is a bare shell,
                // but the rules that applied when the challenge was issued still apply.
                $snapshot = clone $daichi;
                $daichi->Location = 'Locker-1';
                $game->lastKnownCharacters[$daichi->Id] = $snapshot;

                Assert::throws(fn() => $game->actHighDramaChallengeActionReject(), 'cannot be refused (greater Combat)', 'snapshot rules apply');
            },

            // ---- Intervene -----------------------------------------------------------------------------

            'actHighDramaChallengeActionIntervene swaps the defender, engages the intervener and counts as accepted' => function () {
                [$game, , $defender] = $this->challengeScene();
                $hero = $game->place(new GenericCharacter('Bodyguard'), Game::LOCATION_CITY_DOCKS, 2);
                $defender->addCondition(Game::DUEL_DEFENDER);

                $game->actHighDramaChallengeActionIntervene($hero->Id);

                Assert::false($defender->hasCondition(Game::DUEL_DEFENDER), 'old defender no longer defends');
                Assert::true($hero->hasCondition(Game::DUEL_DEFENDER), 'intervener defends');
                Assert::same($hero->Id, $game->globals->get(Game::CHOSEN_TARGET), 'new target');
                Assert::true($game->globals->get(Game::CHALLENGE_ACCEPTED), 'accepted');

                Assert::same(
                    [EventCharacterIntervened::class, EventCardEngaged::class],
                    array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents),
                    'intervention, then engage'
                );
                $event = $game->queued(EventCharacterIntervened::class)[0];
                Assert::same($defender->Id, $event->oldTargetId, 'old target');
                Assert::same($hero->Id, $event->newTargetId, 'new target');
                Assert::same($hero->Id, $game->queued(EventCardEngaged::class)[0]->cardId, 'the intervener engages');
                Assert::same(['proceed'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaChallengeActionIntervene refuses a character elsewhere or already engaged' => function () {
                [$game] = $this->challengeScene();
                $far = $game->place(new GenericCharacter('Far away'), Game::LOCATION_CITY_FORUM, 2);
                $tired = $game->place(new GenericCharacter('Tired'), Game::LOCATION_CITY_DOCKS, 2);
                $tired->Engaged = true;

                Assert::throws(fn() => $game->actHighDramaChallengeActionIntervene($far->Id), 'not at the same location', 'elsewhere');
                Assert::throws(fn() => $game->actHighDramaChallengeActionIntervene($tired->Id), 'cannot Intervene', 'engaged');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaChallengeActionIntervene: Carmella may intervene while engaged until her ability is used' => function () {
                [$game] = $this->challengeScene();
                $carmella = $game->place(new _01178(), Game::LOCATION_CITY_DOCKS, 2);
                $carmella->Engaged = true;
                $carmella->AbilityUsed = true;
                Assert::throws(fn() => $game->actHighDramaChallengeActionIntervene($carmella->Id), 'cannot Intervene', 'engaged and used');

                $carmella->AbilityUsed = false;
                $game->actHighDramaChallengeActionIntervene($carmella->Id);
                Assert::same(['proceed'], $game->gamestate->transitions, 'engaged but unused: allowed');
            },

            'actHighDramaChallengeActionIntervene: challenge-type restrictions on who may step in' => function () {
                $cases = [
                    'Legendary Reputation' => [Game::LEGENDARY_REPUTATION_CHALLENGE_TYPE, 'Only Leaders'],
                    'Sworn Swords' => [Game::SWORN_SWORDS_CHALLENGE_TYPE, 'Only Duelists'],
                    'Valeri Mikhailov' => [Game::VALERI_MIKHAILOV_CHALLENGE_TYPE, 'No Characters can Intervene'],
                    'Torvo Espada' => [Game::TORVO_ESPADA_CHALLENGE_TYPE, 'No characters can intervene'],
                    'Raven' => [Game::RAVEN_CHALLENGE_TYPE, 'No characters can intervene'],
                    'Aja' => [Game::AJA_CHALLENGE_TYPE, '3 Finesse or more'],
                    'Celerity' => [Game::CELERITY_CHALLENGE_TYPE, '3 Finesse or more'],
                ];
                foreach ($cases as $label => [$type, $needle]) {
                    [$game] = $this->challengeScene($type);
                    $hero = $game->place(new GenericCharacter('Bodyguard'), Game::LOCATION_CITY_DOCKS, 2);
                    $hero->ModifiedFinesse = 2;

                    Assert::throws(fn() => $game->actHighDramaChallengeActionIntervene($hero->Id), $needle, $label);
                    Assert::same([], $game->theah->queuedEvents, "$label: nothing queued");
                    Assert::same([], $game->gamestate->transitions, "$label: no transition");
                }
            },

            'actHighDramaChallengeActionIntervene: the restrictions admit the right characters' => function () {
                [$game] = $this->challengeScene(Game::LEGENDARY_REPUTATION_CHALLENGE_TYPE);
                $leader = $game->place(new GenericLeader(), Game::LOCATION_CITY_DOCKS, 2);
                $game->actHighDramaChallengeActionIntervene($leader->Id);
                Assert::same(['proceed'], $game->gamestate->transitions, 'a Leader may step in for Legendary Reputation');

                [$game] = $this->challengeScene(Game::SWORN_SWORDS_CHALLENGE_TYPE);
                $duelist = $game->place(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 2);
                $game->actHighDramaChallengeActionIntervene($duelist->Id);
                Assert::same(['proceed'], $game->gamestate->transitions, 'a Duelist may step in for Sworn Swords');

                [$game] = $this->challengeScene(Game::AJA_CHALLENGE_TYPE);
                $nimble = $game->place(new GenericCharacter('Nimble'), Game::LOCATION_CITY_DOCKS, 2);
                $nimble->ModifiedFinesse = 3;
                $game->actHighDramaChallengeActionIntervene($nimble->Id);
                Assert::same(['proceed'], $game->gamestate->transitions, '3 Finesse may step in for Aja');
            },

            'actHighDramaChallengeActionIntervene: a Musketeer intervening for Odette does not engage' => function () {
                $game = new FrameworkActionsGame();
                $challenger = $game->place(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $odette = $game->place(new _01062(), Game::LOCATION_CITY_DOCKS, 2);
                $musketeer = $game->place(new GenericCharacter('Musketeer', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 2);
                $plain = $game->place(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, $challenger->Id);
                $game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);

                $game->globals->set(Game::CHOSEN_TARGET, $odette->Id);
                $game->actHighDramaChallengeActionIntervene($musketeer->Id);
                Assert::count(1, $game->queued(EventCharacterIntervened::class), 'intervened');
                Assert::count(0, $game->queued(EventCardEngaged::class), 'Musketeer does not engage');
                Assert::contains('does not need to engage', $game->notify->messages[0]['message'], 'explained');

                $game->globals->set(Game::CHOSEN_TARGET, $odette->Id);
                $game->actHighDramaChallengeActionIntervene($plain->Id);
                Assert::count(1, $game->queued(EventCardEngaged::class), 'anyone else still engages');
            },

            'actHighDramaChallengeActionIntervene: Rena with an en garde weapon defers her engage to her reaction' => function () {
                [$game] = $this->challengeScene();
                $rena = $game->place(new _01040(), Game::LOCATION_CITY_DOCKS, 2);
                $weapon = $game->place(new GenericAttachment('Rapier'), Game::LOCATION_CITY_DOCKS, 2);
                $weapon->Traits = ['Weapon'];
                $rena->Attachments = [$weapon->Id];
                $game->actHighDramaChallengeActionIntervene($rena->Id);

                Assert::count(1, $game->queued(EventCharacterIntervened::class), 'intervened');
                Assert::count(0, $game->queued(EventCardEngaged::class), 'engage deferred to the reaction');

                // an engaged weapon no longer qualifies: she is an ordinary intervener again
                [$game] = $this->challengeScene();
                $rena = $game->place(new _01040(), Game::LOCATION_CITY_DOCKS, 2);
                $weapon = $game->place(new GenericAttachment('Rapier'), Game::LOCATION_CITY_DOCKS, 2);
                $weapon->Traits = ['Weapon'];
                $rena->Attachments = [$weapon->Id];
                $weapon->Engaged = true;
                $game->actHighDramaChallengeActionIntervene($rena->Id);
                Assert::count(1, $game->queued(EventCardEngaged::class), 'ordinary engage');
            },
        ];
    }
}
