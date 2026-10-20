<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01063;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01063Swap;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_01063Swap_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_01063Swap';
    }

    /**
     * Bastien carries the native Swap; a GenericCharacter Musketeer is the swap partner.
     * @return array{0:_01063,1:GenericCharacter,2:Technique_01063Swap}
     */
    private function armed(TestWorld $world): array
    {
        $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
        $partner = $world->placeCharacter(new GenericCharacter('Partner', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 1);
        /** @var Technique_01063Swap $swap */
        $swap = $bastien->getTechniqueByClassId('Technique_01063Swap');
        return [$bastien, $partner, $swap];
    }

    private function expectThrows(callable $fn, string $label): void
    {
        $threw = false;
        try {
            $fn();
        } catch (\Throwable $e) {
            $threw = true;
        }
        Assert::true($threw, $label);
    }

    private function choose(TestWorld $world, Technique_01063Swap $swap, int $state, int $id): void
    {
        $swap->actFromTechniqueWithId($world->game, $state, 'stateName', $id);
    }

    public function tests(): array
    {
        return [
            'available only with another friendly Musketeer at the location' => function () {
                $world = new TestWorld();
                [$bastien, , $swap] = $this->armed($world);
                Assert::true($swap->isAvailableToPlayer(1, $world->theah), 'friendly Musketeer');

                $world2 = new TestWorld();
                $bastien2 = $world2->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $world2->placeCharacter(new GenericCharacter('Foe Musketeer', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 2);
                $world2->placeCharacter(new GenericCharacter('Not Musketeer'), Game::LOCATION_CITY_DOCKS, 1);
                $world2->placeCharacter(new GenericCharacter('Elsewhere', ['Musketeer']), Game::LOCATION_CITY_FORUM, 1);
                /** @var Technique_01063Swap $swap2 */
                $swap2 = $bastien2->getTechniqueByClassId('Technique_01063Swap');
                Assert::false($swap2->isAvailableToPlayer(1, $world2->theah), 'no eligible partner');
            },

            // WHY: Harpoon check happens at activation so the failure is immediate on the button click.
            'eventCheck refuses Harpooned owner in a duel on activation' => function () {
                $world = new TestWorld();
                [$bastien, , $swap] = $this->armed($world);
                $bastien->addCondition(Game::HARPOON_CONDITION);
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = new EventTechniqueActivated();
                $event->techniqueId = $swap->Id;
                $event->theah = $world->theah;

                $this->expectThrows(fn() => $swap->eventCheck($event), 'harpooned in duel');
            },

            'eventCheck allows Harpooned owner outside a duel' => function () {
                $world = new TestWorld();
                [$bastien, , $swap] = $this->armed($world);
                $bastien->addCondition(Game::HARPOON_CONDITION);

                $event = new EventTechniqueActivated();
                $event->techniqueId = $swap->Id;
                $event->theah = $world->theah;
                $swap->eventCheck($event);

                Assert::true(true, 'no throw');
            },

            'eventCheck allows unharpooned owner in a duel' => function () {
                $world = new TestWorld();
                [, , $swap] = $this->armed($world);
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = new EventTechniqueActivated();
                $event->techniqueId = $swap->Id;
                $event->theah = $world->theah;
                $swap->eventCheck($event);

                Assert::true(true, 'no throw');
            },

            'resolve queues transition 01063' => function () {
                $world = new TestWorld();
                [, , $swap] = $this->armed($world);

                $event = new EventResolveTechnique();
                $event->techniqueId = $swap->Id;
                $event->theah = $world->theah;
                $swap->handleEvent($event);

                Assert::same('01063', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'transition');
            },

            'args list owner as performer and friendly Musketeers only' => function () {
                $world = new TestWorld();
                [$bastien, $partner, $swap] = $this->armed($world);
                $world->placeCharacter(new GenericCharacter('Foe', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);

                $args = $swap->getArgsFromTechnique(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_01063,
                    'duelChooseTechnique_01063'
                );

                Assert::same($bastien->Id, $args['performerId'], 'performer');
                Assert::same([$partner->Id], $args['characterIds'], 'only friendly Musketeer');
            },

            'duel choice stores partner and uses characterChosen transition' => function () {
                $world = new TestWorld();
                [, $partner, $swap] = $this->armed($world);

                $this->choose($world, $swap, States::DUEL_CHOOSE_TECHNIQUE_01063, $partner->Id);

                $swapId = new \ReflectionProperty(Technique_01063Swap::class, 'swapId');
                $swapId->setAccessible(true);
                Assert::same($partner->Id, $swapId->getValue($swap), 'swapId');
                // WHY: duel chooser has "back" + "characterChosen"; bare nextState() would be ambiguous.
                Assert::same(['characterChosen'], $world->game->gamestate->transitions, 'named transition');
            },

            'challenge resolve choice uses bare nextState' => function () {
                $world = new TestWorld();
                [, $partner, $swap] = $this->armed($world);

                $this->choose($world, $swap, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01063, $partner->Id);

                Assert::same([null], $world->game->gamestate->transitions, 'single "" transition');
            },

            'choice rejects non-Musketeer, opposing, and other-location characters' => function () {
                $world = new TestWorld();
                [, , $swap] = $this->armed($world);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe', ['Musketeer']), Game::LOCATION_CITY_DOCKS, 2);
                $far = $world->placeCharacter(new GenericCharacter('Far', ['Musketeer']), Game::LOCATION_CITY_FORUM, 1);

                foreach ([$plain, $foe, $far] as $bad) {
                    $this->expectThrows(
                        fn() => $this->choose($world, $swap, States::DUEL_CHOOSE_TECHNIQUE_01063, $bad->Id),
                        $bad->Name
                    );
                }
                Assert::count(0, $world->game->gamestate->transitions, 'no transition');
            },

            // WHY: failing on confirm lets the player use Back instead of a half-resolved technique.
            'duel choice refuses Harpooned owner' => function () {
                $world = new TestWorld();
                [$bastien, $partner, $swap] = $this->armed($world);
                $bastien->addCondition(Game::HARPOON_CONDITION);

                $this->expectThrows(
                    fn() => $this->choose($world, $swap, States::DUEL_CHOOSE_TECHNIQUE_01063, $partner->Id),
                    'harpooned'
                );
                Assert::count(0, $world->game->gamestate->transitions, 'no transition');
            },

            'GenerateChallengeThreat preview redirects actor but leaves performer globals alone' => function () {
                $world = new TestWorld();
                [$bastien, $partner, $swap] = $this->armed($world);
                $this->choose($world, $swap, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01063, $partner->Id);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $bastien->Id);

                $event = new EventGenerateChallengeThreat();
                $event->actorId = $bastien->Id;
                $event->techniqueId = $swap->Id;
                $event->preview = true;
                $event->theah = $world->theah;
                $swap->handleEvent($event);

                Assert::same($partner->Id, $event->actorId, 'actor redirected');
                Assert::same($bastien->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer untouched');
                Assert::count(0, $world->theah->queuedEvents, 'no events');
            },

            // WHY: GENERATE_THREAT also runs on rejection; re-adding DUEL_CHALLENGER then would stick on a non-duelist.
            'GenerateChallengeThreat on rejected challenge redirects actor without condition changes' => function () {
                $world = new TestWorld();
                [$bastien, $partner, $swap] = $this->armed($world);
                $this->choose($world, $swap, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01063, $partner->Id);
                $bastien->addCondition(Game::DUEL_CHALLENGER);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, false);

                $event = new EventGenerateChallengeThreat();
                $event->actorId = $bastien->Id;
                $event->techniqueId = $swap->Id;
                $event->theah = $world->theah;
                $swap->handleEvent($event);

                Assert::same($partner->Id, $event->actorId, 'actor redirected');
                Assert::same($partner->Id, $world->game->globals->get(Game::CHOSEN_PERFORMER), 'performer swapped');
                Assert::false($partner->hasCondition(Game::DUEL_CHALLENGER), 'partner not marked challenger');
                Assert::true($bastien->hasCondition(Game::DUEL_CHALLENGER), 'original untouched');
                Assert::count(0, $world->theah->queuedOfType(EventChallengerSwapped::class), 'no swapped event');
            },

            'GenerateChallengeThreat on accepted challenge moves Challenger condition and announces swap' => function () {
                $world = new TestWorld();
                [$bastien, $partner, $swap] = $this->armed($world);
                $this->choose($world, $swap, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01063, $partner->Id);
                $bastien->addCondition(Game::DUEL_CHALLENGER);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $event = new EventGenerateChallengeThreat();
                $event->actorId = $bastien->Id;
                $event->techniqueId = $swap->Id;
                $event->theah = $world->theah;
                $swap->handleEvent($event);

                Assert::false($bastien->hasCondition(Game::DUEL_CHALLENGER), 'original cleared');
                Assert::true($partner->hasCondition(Game::DUEL_CHALLENGER), 'partner challenger');
                $swapped = $world->theah->queuedOfType(EventChallengerSwapped::class);
                Assert::count(1, $swapped, 'swapped event');
                Assert::same($bastien->Id, $swapped[0]->oldChallengerId, 'old');
                Assert::same($partner->Id, $swapped[0]->newChallengerId, 'new');
            },

            'GenerateChallengeThreat for another technique is ignored' => function () {
                $world = new TestWorld();
                [$bastien, $partner, $swap] = $this->armed($world);
                $this->choose($world, $swap, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01063, $partner->Id);

                $event = new EventGenerateChallengeThreat();
                $event->actorId = $bastien->Id;
                $event->techniqueId = 'someOtherTechnique';
                $event->theah = $world->theah;
                $swap->handleEvent($event);

                Assert::same($bastien->Id, $event->actorId, 'untouched');
            },

            'CalculateTechniqueValues swaps duel participants in the current round' => function () {
                $world = new TestWorld();
                [$bastien, $partner, $swap] = $this->armed($world);
                $this->choose($world, $swap, States::DUEL_CHOOSE_TECHNIQUE_01063, $partner->Id);
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->game->globals->set(Game::DUEL_ID, 7);
                $world->game->globals->set(Game::DUEL_ROUND, 2);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $swap->Id;
                $event->theah = $world->theah;
                $swap->handleEvent($event);

                Assert::same(
                    [['duelId' => 7, 'round' => 2, 'oldId' => $bastien->Id, 'newId' => $partner->Id]],
                    $world->theah->swappedParticipants,
                    'participants swapped'
                );
            },

            'CalculateTechniqueValues outside a duel does not touch duel rows' => function () {
                $world = new TestWorld();
                [, $partner, $swap] = $this->armed($world);
                $this->choose($world, $swap, States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_01063, $partner->Id);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $swap->Id;
                $event->theah = $world->theah;
                $swap->handleEvent($event);

                Assert::count(0, $world->theah->swappedParticipants, 'no swap');
            },

            'canceled technique forgets the chosen partner' => function () {
                $world = new TestWorld();
                [, $partner, $swap] = $this->armed($world);
                $this->choose($world, $swap, States::DUEL_CHOOSE_TECHNIQUE_01063, $partner->Id);

                $event = new EventTechniqueCanceled();
                $event->techniqueId = $swap->Id;
                $event->theah = $world->theah;
                $swap->handleEvent($event);

                $swapId = new \ReflectionProperty(Technique_01063Swap::class, 'swapId');
                $swapId->setAccessible(true);
                Assert::same(0, $swapId->getValue($swap), 'cleared');
            },
        ];
    }
}
