<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01090;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCombatCardAnnounced;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_01090_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01090';
    }

    /**
     * Lorenzo (P1) duels Foe (P2) at Docks; the top of P2's faction deck is `Revealed`.
     *
     * @return array{0:_01090,1:Character,2:Technique_01090,3:Character}
     */
    private function duel(TestWorld $world): array
    {
        $lorenzo = $world->placeCharacter(new _01090(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $revealed = $world->placeCharacter(new GenericCharacter('Revealed'), $world->game->getPlayerFactionDeckName(2), 2);
        $world->game->topFactionCards = [['id' => $revealed->Id]];
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $lorenzo;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_01090 $technique */
        $technique = $lorenzo->getTechniques()[0];
        return [$lorenzo, $foe, $technique, $revealed];
    }

    private function resolve(TestWorld $world, Technique_01090 $technique, bool $inDuel = true, int $actorId = 0, int $adversaryId = 0): EventResolveTechnique
    {
        $event = new EventResolveTechnique();
        $event->techniqueId = $technique->Id;
        $event->inDuel = $inDuel;
        $event->actorId = $actorId;
        $event->adversaryId = $adversaryId;
        $event->theah = $world->theah;
        return $event;
    }

    private function threat(TestWorld $world, Technique_01090 $technique, bool $preview = false, int $actorId = 0, int $adversaryId = 0): EventGenerateChallengeThreat
    {
        $event = new EventGenerateChallengeThreat();
        $event->techniqueId = $technique->Id;
        $event->preview = $preview;
        $event->actorId = $actorId;
        $event->adversaryId = $adversaryId;
        $event->theah = $world->theah;
        return $event;
    }

    private function newRound(TestWorld $world): EventDuelNewRound
    {
        $event = new EventDuelNewRound();
        $event->theah = $world->theah;
        return $event;
    }

    /** Reveal, then flip the duel round to the adversary's turn (actor becomes Foe). */
    private function revealThenAdversaryRound(TestWorld $world, Technique_01090 $technique, Character $foe): void
    {
        $technique->handleEvent($this->resolve($world, $technique));
        $world->theah->takeQueuedEvents();
        $world->theah->duelActor = $foe;
    }

    private function transitionsNamed(TestWorld $world, string $name): array
    {
        return array_values(array_filter(
            $world->theah->queuedOfType(EventTransition::class),
            fn($t) => $t->transition === $name
        ));
    }

    public function tests(): array
    {
        return [
            'available in the normal case' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when Fate\'s Silence blanks Lorenzo' => function () {
                $world = new TestWorld();
                [$lorenzo, , $technique] = $this->duel($world);
                $lorenzo->addCondition(Game::FATES_SILENCE_CONDITION);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'blanked');
            },

            // WHY (journal 2026-03-27-01): reveal the ADVERSARY's deck, not Lorenzo's controller's.
            'in-duel resolve reveals the adversary deck top and queues the acknowledge transition' => function () {
                $world = new TestWorld();
                [$lorenzo, , $technique, $revealed] = $this->duel($world);

                $technique->handleEvent($this->resolve($world, $technique));

                $transitions = $this->transitionsNamed($world, '01090');
                Assert::count(1, $transitions, 'acknowledge transition');
                Assert::same($technique->Id, $transitions[0]->internalId, 'technique internal id');
                Assert::same($lorenzo->Id, $transitions[0]->sourceId, 'source Lorenzo');
                Assert::same(1, $transitions[0]->playerId, 'Lorenzo\'s controller acknowledges');
                $messages = array_filter($world->game->notify->messages, fn($m) => $m['type'] === 'message');
                Assert::count(1, $messages, 'reveal announced');

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_01090, 'duelChooseTechnique_01090');
                Assert::same('Player Two', $args['opponentName'], 'adversary deck owner');
                Assert::same($revealed->Id, $args['card']['id'], 'revealed card in args');
            },

            'resolve for another technique id is ignored' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $event = $this->resolve($world, $technique);
                $event->techniqueId = 'someOtherTechnique';

                $technique->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            // WHY (journal 2026-09-18-04): Challenge Resolve runs before Accept/Refuse - revealing there would peek on Refuse.
            'resolve outside a duel does not reveal (challenge defers to GenerateThreat)' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);

                $technique->handleEvent($this->resolve($world, $technique, false));

                Assert::count(0, $world->theah->queuedEvents, 'no reveal');
            },

            'resolve in duel with explicit actor/adversary ids uses them over the duel round' => function () {
                $world = new TestWorld();
                [$lorenzo, , $technique] = $this->duel($world);
                $other = $world->placeCharacter(new GenericCharacter('Other Foe'), Game::LOCATION_CITY_FORUM, 3);
                $otherTop = $world->placeCharacter(new GenericCharacter('Other Top'), $world->game->getPlayerFactionDeckName(3), 3);
                $world->game->topFactionCards = [['id' => $otherTop->Id]];
                $world->game->playerNames[3] = 'Player Three';

                $technique->handleEvent($this->resolve($world, $technique, true, $lorenzo->Id, $other->Id));

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_01090, 'x');
                Assert::same('Player Three', $args['opponentName'], 'event adversary id wins');
            },

            // WHY (journal 2026-09-18-04): getAdversary reads CHOSEN_TARGET outside duels; getDuelRoundOpponent would fatal.
            'accepted challenge reveals using CHOSEN_TARGET and CHOSEN_PERFORMER' => function () {
                $world = new TestWorld();
                [$lorenzo, $foe, $technique, $revealed] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->theah->duelActor = null;
                $world->theah->duelOpponent = null;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $lorenzo->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $technique->handleEvent($this->threat($world, $technique));

                Assert::count(1, $this->transitionsNamed($world, '01090'), 'acknowledge transition');
                $args = $technique->getArgsFromTechnique(
                    $world->game,
                    States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01090,
                    'highDramaChallengeActionResolveTechnique_01090'
                );
                Assert::same($revealed->Id, $args['card']['id'], 'revealed');
                Assert::same('Player Two', $args['opponentName'], 'opponent name from deck owner, not duel opponent');
            },

            'Intervene retarget: event adversary id overrides CHOSEN_TARGET' => function () {
                $world = new TestWorld();
                [$lorenzo, $foe, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $lorenzo->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);
                $interloper = $world->placeCharacter(new GenericCharacter('Interloper'), Game::LOCATION_CITY_DOCKS, 3);
                $top = $world->placeCharacter(new GenericCharacter('Top 3'), $world->game->getPlayerFactionDeckName(3), 3);
                $world->game->topFactionCards = [['id' => $top->Id]];
                $world->game->playerNames[3] = 'Player Three';

                $technique->handleEvent($this->threat($world, $technique, false, 0, $interloper->Id));

                $args = $technique->getArgsFromTechnique($world->game, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01090, 'x');
                Assert::same('Player Three', $args['opponentName'], 'retargeted deck');
            },

            // WHY: GENERATE_THREAT also runs on Refuse (wound threat); CHALLENGE_ACCEPTED gates the reveal.
            'refused challenge (CHALLENGE_ACCEPTED unset) does not reveal' => function () {
                $world = new TestWorld();
                [$lorenzo, $foe, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $lorenzo->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $technique->handleEvent($this->threat($world, $technique));

                Assert::count(0, $world->theah->queuedEvents, 'no peek on Refuse');
            },

            'preview (Accept args dry-run) never reveals' => function () {
                $world = new TestWorld();
                [$lorenzo, $foe, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $lorenzo->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $technique->handleEvent($this->threat($world, $technique, true));

                Assert::count(0, $world->theah->queuedEvents, 'preview is side-effect free');
            },

            // WHY: getAdversary() must be null-safe on challenges - no CHOSEN_TARGET means no reveal, not a fatal.
            'accepted challenge with no chosen target does not crash or reveal' => function () {
                $world = new TestWorld();
                [$lorenzo, , $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $lorenzo->Id);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $technique->handleEvent($this->threat($world, $technique));

                Assert::count(0, $world->theah->queuedEvents, 'no adversary');
            },

            'accepted challenge with no chosen performer does not crash or reveal' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $technique->handleEvent($this->threat($world, $technique));

                Assert::count(0, $world->theah->queuedEvents, 'no actor');
            },

            'threat event for another technique is ignored' => function () {
                $world = new TestWorld();
                [$lorenzo, $foe, $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $lorenzo->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);
                $event = $this->threat($world, $technique);
                $event->techniqueId = 'someOtherTechnique';

                $technique->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            'adversary new round after a reveal queues the play-or-wound prompt for the adversary' => function () {
                $world = new TestWorld();
                [$lorenzo, $foe, $technique] = $this->duel($world);
                $this->revealThenAdversaryRound($world, $technique, $foe);

                $technique->handleEvent($this->newRound($world));

                $transitions = $this->transitionsNamed($world, '01090');
                Assert::count(1, $transitions, 'prompt');
                Assert::same(2, $transitions[0]->playerId, 'adversary controller is prompted');
                Assert::same($lorenzo->Id, $transitions[0]->sourceId, 'source Lorenzo');
                Assert::same($technique->Id, $transitions[0]->internalId, 'technique id');
            },

            'no prompt before anything was revealed' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $world->theah->duelActor = $foe;

                $technique->handleEvent($this->newRound($world));

                Assert::count(0, $world->theah->queuedEvents, 'nothing revealed');
            },

            'no prompt when the new round belongs to Lorenzo\'s own side' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $technique->handleEvent($this->resolve($world, $technique));
                $world->theah->takeQueuedEvents();
                // Actor is still Lorenzo (P1); the revealed deck belongs to P2.

                $technique->handleEvent($this->newRound($world));

                Assert::count(0, $world->theah->queuedEvents, 'only the deck owner\'s round');
            },

            'duel end clears the pending reveal' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->revealThenAdversaryRound($world, $technique, $foe);

                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $technique->handleEvent($end);
                $technique->handleEvent($this->newRound($world));

                Assert::count(0, $this->transitionsNamed($world, '01090'), 'cleared');
            },

            'cancelling the technique clears the pending reveal' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->revealThenAdversaryRound($world, $technique, $foe);

                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = $technique->Id;
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);
                $technique->handleEvent($this->newRound($world));

                Assert::count(0, $this->transitionsNamed($world, '01090'), 'cleared');
            },

            'cancelling a different technique keeps the pending reveal' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $this->revealThenAdversaryRound($world, $technique, $foe);

                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = 'someOtherTechnique';
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);
                $technique->handleEvent($this->newRound($world));

                Assert::count(1, $this->transitionsNamed($world, '01090'), 'still pending');
            },

            'a second reveal replaces the first' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $second = $world->placeCharacter(new GenericCharacter('Second'), $world->game->getPlayerFactionDeckName(2), 2);
                $technique->handleEvent($this->resolve($world, $technique));
                $world->game->topFactionCards = [['id' => $second->Id]];
                $technique->handleEvent($this->resolve($world, $technique));

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_NEW_ROUND_01090, 'duelNewRound_01090');
                Assert::same($second->Id, $args['card']['id'], 'latest reveal');
            },

            'args are only built for the three 01090 states' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $technique->handleEvent($this->resolve($world, $technique));

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_01093, 'x');

                Assert::same([], $args, 'unrelated state');
            },

            'new round choice 0: wounds the adversary actor, clears the prompt and moves on' => function () {
                $world = new TestWorld();
                [$lorenzo, $foe, $technique] = $this->duel($world);
                $this->revealThenAdversaryRound($world, $technique, $foe);

                $technique->actFromTechniqueWithId($world->game, States::DUEL_NEW_ROUND_01090, 'duelNewRound_01090', 0);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'one wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary wounded');
                Assert::same(1, $wounds[0]->wounds, 'one wound');
                Assert::same($lorenzo->Id, $wounds[0]->sourceId, 'source Lorenzo');
                Assert::same($technique->Id, $wounds[0]->abilityId, 'ability id');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');

                // WHY: text says "next round" (once) - leaving CardPlayerId set would re-prompt every adversary round.
                $world->theah->takeQueuedEvents();
                $technique->handleEvent($this->newRound($world));
                Assert::count(0, $this->transitionsNamed($world, '01090'), 'no re-prompt');
            },

            'new round choice card: discards it, plays the revealed card as combat card' => function () {
                $world = new TestWorld();
                [$lorenzo, $foe, $technique, $revealed] = $this->duel($world);
                $handCard = $world->placeCharacter(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 2);
                $this->revealThenAdversaryRound($world, $technique, $foe);

                $technique->actFromTechniqueWithId($world->game, States::DUEL_NEW_ROUND_01090, 'duelNewRound_01090', $handCard->Id);

                $discards = $world->theah->queuedOfType(EventCardDiscardedFromHand::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($handCard->Id, $discards[0]->cardId, 'discarded hand card');
                Assert::same(2, $discards[0]->ownerId, 'adversary discards');
                Assert::same($lorenzo->Id, $discards[0]->sourceId, 'source Lorenzo');
                Assert::true($discards[0]->asEffect, 'as effect');
                Assert::same($revealed->Id, $world->game->globals->get(Game::CHOSEN_CARD), 'chosen card is the revealed card');
                Assert::same(Game::LOCATION_DUELING_LINE, $revealed->Location, 'on the dueling line');
                Assert::count(1, $world->theah->queuedOfType(EventCombatCardAnnounced::class), 'combat card announced');
                $follow = $this->transitionsNamed($world, '01090_2');
                Assert::count(1, $follow, 'follow-up transition');
                Assert::same(2, $follow[0]->playerId, 'adversary controller');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::same([null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'new round choice card: discard is queued before the combat card is announced' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $handCard = $world->placeCharacter(new GenericCharacter('Hand Card'), Game::LOCATION_HAND, 2);
                $this->revealThenAdversaryRound($world, $technique, $foe);

                $technique->actFromTechniqueWithId($world->game, States::DUEL_NEW_ROUND_01090, 'x', $handCard->Id);

                Assert::instanceOf(EventCardDiscardedFromHand::class, $world->theah->queuedEvents[0], 'discard first');
                Assert::instanceOf(EventCombatCardAnnounced::class, $world->theah->queuedEvents[1], 'then announce');
                Assert::instanceOf(EventTransition::class, $world->theah->queuedEvents[2], 'then transition');
            },

            'new round choice card: refuses a card that is not in the adversary hand' => function () {
                $world = new TestWorld();
                [, $foe, $technique, $revealed] = $this->duel($world);
                $stray = $world->placeCharacter(new GenericCharacter('Stray'), Game::LOCATION_CITY_FORUM, 2);
                $this->revealThenAdversaryRound($world, $technique, $foe);

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId($world->game, States::DUEL_NEW_ROUND_01090, 'x', $stray->Id);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'not in hand');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::same($world->game->getPlayerFactionDeckName(2), $revealed->Location, 'revealed card stays in deck');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },

            'new round choice card: refuses an opposing player\'s hand card' => function () {
                $world = new TestWorld();
                [, $foe, $technique] = $this->duel($world);
                $mine = $world->placeCharacter(new GenericCharacter('Lorenzo\'s Hand Card'), Game::LOCATION_HAND, 1);
                $this->revealThenAdversaryRound($world, $technique, $foe);

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId($world->game, States::DUEL_NEW_ROUND_01090, 'x', $mine->Id);
                } catch (\BgaUserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'wrong hand');
            },

            'act for an unrelated state does nothing' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);

                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_01093, 'x', 0);

                Assert::count(0, $world->theah->queuedEvents, 'nothing');
                Assert::same([], $world->game->gamestate->transitions, 'no transition');
            },
        ];
    }
}
