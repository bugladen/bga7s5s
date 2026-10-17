<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02043b;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_02043b_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02043b';
    }

    /**
     * @return array{0:_02043,1:Technique_02043b}
     */
    private function scene(TestWorld $world): array
    {
        $twins = $world->placeCharacter(new _02043(), Game::LOCATION_CITY_DOCKS, 1);
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Technique_02043b $technique */
        $technique = $twins->getTechniques()[1];
        return [$twins, $technique];
    }

    public function tests(): array
    {
        return [
            'is a Technique' => function () {
                Assert::instanceOf(Technique::class, new Technique_02043b(), 'Technique');
            },

            'state constant registered' => function () {
                Assert::same(521020432, States::DUEL_CHOOSE_TECHNIQUE_02043b, 'chooser');
            },

            'available in a duel when a Flourish is in your discard' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'empty discard');

                $flourish = $world->placeCard(new _02041(), $world->game->getPlayerDiscardDeckName(1), 1);
                Assert::true($flourish->hasTrait('Flourish'), 'trait');
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'has flourish');

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'outside duel');
            },

            'a non-Flourish in discard does not make the technique available' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $world->placeCard(new _02043(), $world->game->getPlayerDiscardDeckName(1), 1);

                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no flourish');
            },

            'resolve opens the discard chooser' => function () {
                $world = new TestWorld();
                [$twins, $technique] = $this->scene($world);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02043b', $transitions[0]->transition, 'name');
                Assert::same($twins->Id, $transitions[0]->sourceId, 'source');
                Assert::same($technique->Id, $transitions[0]->internalId, 'id');
            },

            'args list Flourishes in your discard' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $discard = $world->game->getPlayerDiscardDeckName(1);
                $flourish = $world->placeCard(new _02041(), $discard, 1);
                $world->placeCard(new _02043(), $discard, 1);
                $world->placeCard(new _02041(), $world->game->getPlayerDiscardDeckName(2), 2);

                $args = $technique->getArgsFromTechnique(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02043b,
                    'duelChooseTechnique_02043b'
                );

                Assert::count(1, $args['cards'], 'one');
                Assert::same($flourish->Id, $args['cards'][0]['id'], 'flourish');
            },

            'choosing a Flourish removes it from discard and puts it on top of the deck' => function () {
                $world = new TestWorld();
                [$twins, $technique] = $this->scene($world);
                $flourish = $world->placeCard(new _02041(), $world->game->getPlayerDiscardDeckName(1), 1);

                $technique->actFromTechniqueWithId(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02043b,
                    'duelChooseTechnique_02043b',
                    $flourish->Id
                );

                $removed = $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class);
                $added = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class);
                Assert::count(1, $removed, 'removed');
                Assert::same($flourish->Id, $removed[0]->cardId, 'card');
                Assert::same($twins->ControllerId, $removed[0]->playerId, 'player');
                Assert::count(1, $added, 'deck');
                Assert::same($flourish->Id, $added[0]->cardId, 'same card');
                Assert::true($added[0]->onTop, 'top');
                Assert::same([null], $world->game->gamestate->transitions, 'next');
            },

            'refuses a card that is not a Flourish in your discard' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $other = $world->placeCard(new _02043(), $world->game->getPlayerDiscardDeckName(1), 1);
                $threw = false;
                try {
                    $technique->actFromTechniqueWithId(
                        $world->game,
                        States::DUEL_CHOOSE_TECHNIQUE_02043b,
                        'duelChooseTechnique_02043b',
                        $other->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
