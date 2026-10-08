<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01100;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01100;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeAccepted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReactionActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01100_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01100';
    }

    /**
     * Player 1's host wears the Cat's Glass at Docks; player 2's foe stands at Docks too.
     *
     * @return array{0:GenericCharacter,1:GenericCharacter,2:_01100,3:Reaction_01100}
     */
    private function scene(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $glass = $world->placeCard(new _01100(), Game::LOCATION_CITY_DOCKS, 1);
        $glass->AttachedToId = $host->Id;
        $host->Attachments[] = $glass->Id;
        /** @var Reaction_01100 $reaction */
        $reaction = $glass->getReactions()[0];
        return [$host, $foe, $glass, $reaction];
    }

    private function accepted(TestWorld $world, int $challengerId, int $targetId): EventChallengeAccepted
    {
        $event = new EventChallengeAccepted();
        $event->challengerId = $challengerId;
        $event->targetId = $targetId;
        $event->theah = $world->theah;
        return $event;
    }

    private function intervened(TestWorld $world, int $newTargetId): EventCharacterIntervened
    {
        $event = new EventCharacterIntervened();
        $event->playerId = 0;
        $event->oldTargetId = 0;
        $event->newTargetId = $newTargetId;
        $event->theah = $world->theah;
        return $event;
    }

    private function offered(TestWorld $world): int
    {
        return count($world->theah->queuedOfType(EventTransition::class));
    }

    /** @return list<string> */
    private function gambleExplanations(TestWorld $world, Reaction_01100 $reaction, GenericCharacter $actor, int &$count): array
    {
        $explanations = [];
        $count = $reaction->getNumberOfGambleCardsToReveal($world->theah, $actor, $explanations);
        return $explanations;
    }

    public function tests(): array
    {
        return [
            // ---- accept ----
            'offers when the glass wearer\'s side issued the challenge and the foe accepted' => function () {
                $world = new TestWorld();
                [$host, $foe, $glass, $reaction] = $this->scene($world);

                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same($glass->Id, $transitions[0]->sourceId, 'source glass');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'reaction id');
                Assert::same('reaction', $transitions[0]->transition, 'reaction transition');
                Assert::same($glass->ControllerId, $transitions[0]->playerId, 'glass controller decides');
                Assert::same($foe->Id, $reaction->AdversaryId, 'adversary is the target');
            },

            'offers when the glass wearer\'s side is challenged and accepts' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);

                $reaction->handleEvent($this->accepted($world, $foe->Id, $host->Id));

                Assert::same(1, $this->offered($world), 'offered');
                Assert::same($foe->Id, $reaction->AdversaryId, 'adversary is the challenger');
            },

            'does not offer when neither participant is on the glass controller\'s side' => function () {
                $world = new TestWorld();
                [, , , $reaction] = $this->scene($world);
                $a = $world->placeCharacter(new GenericCharacter('Third A'), Game::LOCATION_CITY_DOCKS, 2);
                $b = $world->placeCharacter(new GenericCharacter('Third B'), Game::LOCATION_CITY_DOCKS, 2);

                $reaction->handleEvent($this->accepted($world, $a->Id, $b->Id));

                Assert::same(0, $this->offered($world), 'not our fight');
                Assert::same(0, $reaction->AdversaryId, 'no adversary recorded');
            },

            // WHY: the challenge must be "at this location" - a friendly participant elsewhere does not qualify.
            'does not offer when our participant is at a different location than the glass' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $host->Location = Game::LOCATION_CITY_FORUM;
                $foe->Location = Game::LOCATION_CITY_FORUM;

                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));

                Assert::same(0, $this->offered($world), 'other location');
            },

            'does not offer when the glass is not attached to a character' => function () {
                $world = new TestWorld();
                [$host, $foe, $glass, $reaction] = $this->scene($world);
                $glass->AttachedToId = 0;

                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));

                Assert::same(0, $this->offered($world), 'unattached');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));

                Assert::same(0, $this->offered($world), 'used');
            },

            // ---- intervene ----
            // WHY (journal 2026-04-10 pattern): an intervener replaces the target mid-challenge, so Accepted never names
            // the new defender. The challenger comes from CHOSEN_PERFORMER and the new target from the event.
            'offers when the glass wearer\'s side challenged and the foe intervened' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                $reaction->handleEvent($this->intervened($world, $foe->Id));

                Assert::same(1, $this->offered($world), 'offered');
                Assert::same($foe->Id, $reaction->AdversaryId, 'adversary is the new target');
            },

            'offers when the foe challenged and the glass wearer intervened' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $foe->Id);

                $reaction->handleEvent($this->intervened($world, $host->Id));

                Assert::same(1, $this->offered($world), 'offered');
                Assert::same($foe->Id, $reaction->AdversaryId, 'adversary is the challenger (CHOSEN_PERFORMER)');
            },

            'intervene without CHOSEN_PERFORMER being ours does not use the event as challenger' => function () {
                $world = new TestWorld();
                [, $foe, , $reaction] = $this->scene($world);
                $outsider = $world->placeCharacter(new GenericCharacter('Outsider'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $outsider->Id);

                $reaction->handleEvent($this->intervened($world, $foe->Id));

                Assert::same(0, $this->offered($world), 'neither side is ours');
            },

            'intervene at another location does not offer' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $host->Location = Game::LOCATION_CITY_FORUM;
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                $reaction->handleEvent($this->intervened($world, $foe->Id));

                Assert::same(0, $this->offered($world), 'other location');
            },

            'intervene does not offer when unattached or used' => function () {
                $world = new TestWorld();
                [$host, $foe, $glass, $reaction] = $this->scene($world);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $host->Id);

                $glass->AttachedToId = 0;
                $reaction->handleEvent($this->intervened($world, $foe->Id));
                Assert::same(0, $this->offered($world), 'unattached');

                $glass->AttachedToId = $host->Id;
                $reaction->Used = true;
                $reaction->handleEvent($this->intervened($world, $foe->Id));
                Assert::same(0, $this->offered($world), 'used');
            },

            // ---- perform ----
            'buttons offer Activate and Pass' => function () {
                $world = new TestWorld();
                [, , , $reaction] = $this->scene($world);

                $ids = array_map(fn($b) => $b['reaction'], $reaction->getReactionButtonProperties($world->theah));

                Assert::same(['activate', 'pass'], $ids, 'buttons');
            },

            'activate flags the effect, engages the glass itself, marks Used and finishes' => function () {
                $world = new TestWorld();
                [$host, $foe, $glass, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));

                $reaction->performReaction($world->game, 0, $reaction->Id, 'activate');

                Assert::true($reaction->IsActivated, 'effect on');
                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'one engage');
                Assert::same($glass->Id, $engages[0]->cardId, 'the glass is engaged (cost)');
                Assert::same($glass->Id, $engages[0]->sourceId, 'source glass');
                Assert::same($reaction->Id, $engages[0]->abilityId, 'ability id');
                Assert::same($glass->ControllerId, $engages[0]->playerId, 'glass controller');
                Assert::true($reaction->Used, 'used');
                Assert::count(1, $world->theah->queuedOfType(EventReactionActivated::class), 'activation announced');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass leaves the effect off and the reaction available' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));
                $world->theah->takeQueuedEvents();

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::false($reaction->IsActivated, 'effect off');
                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'glass not engaged');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            // ---- gamble reveal hook ----
            'activated: the adversary at the glass location reveals one fewer card with an explanation' => function () {
                $world = new TestWorld();
                [$host, $foe, $glass, $reaction] = $this->scene($world);
                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'activate');

                $count = 0;
                $explanations = $this->gambleExplanations($world, $reaction, $foe, $count);

                Assert::same(-1, $count, 'one less');
                Assert::count(1, $explanations, 'explanation recorded');
                Assert::contains($glass->getInjectCode(), $explanations[0], 'names the glass');
            },

            'not activated: no change to the reveal count' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));

                $count = 99;
                $explanations = $this->gambleExplanations($world, $reaction, $foe, $count);

                Assert::same(0, $count, 'not activated yet');
                Assert::same([], $explanations, 'no explanation');
            },

            'activated: only the recorded adversary is affected, not other characters' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'activate');

                $count = 99;
                $this->gambleExplanations($world, $reaction, $host, $count);
                Assert::same(0, $count, 'wearer unaffected');

                $other = $world->placeCharacter(new GenericCharacter('Bystander'), Game::LOCATION_CITY_DOCKS, 2);
                $this->gambleExplanations($world, $reaction, $other, $count);
                Assert::same(0, $count, 'bystander unaffected');
            },

            // WHY: "while the adversary is at this location" - leaving the wearer's location lifts the penalty.
            'activated: the penalty lifts when the adversary is no longer at the wearer\'s location' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'activate');
                $foe->Location = Game::LOCATION_CITY_FORUM;

                $count = 99;
                $this->gambleExplanations($world, $reaction, $foe, $count);

                Assert::same(0, $count, 'adversary moved away');
            },

            'activated: the penalty follows the wearer to a new location' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'activate');
                $host->Location = Game::LOCATION_CITY_FORUM;
                $foe->Location = Game::LOCATION_CITY_FORUM;

                $count = 0;
                $this->gambleExplanations($world, $reaction, $foe, $count);

                Assert::same(-1, $count, 'compares against the wearer, not a stored location');
            },

            // ---- clear at duel end ----
            'duel end clears the adversary and the activated flag' => function () {
                $world = new TestWorld();
                [$host, $foe, , $reaction] = $this->scene($world);
                $reaction->handleEvent($this->accepted($world, $host->Id, $foe->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'activate');

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::false($reaction->IsActivated, 'flag cleared');
                Assert::same(0, $reaction->AdversaryId, 'adversary cleared');
                $count = 99;
                $this->gambleExplanations($world, $reaction, $foe, $count);
                Assert::same(0, $count, 'no lingering penalty in later duels');
            },
        ];
    }
}
