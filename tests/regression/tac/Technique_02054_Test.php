<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRangedAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02054;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02054;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRangedAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_02054_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02054';
    }

    /**
     * @return array{0:_02054,1:Technique_02054,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $pistol = $world->placeCard(new _02054(), Game::LOCATION_CITY_DOCKS, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Shooter'), Game::LOCATION_CITY_DOCKS, 1);
        $adversary = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_FORUM, 2);
        $pistol->AttachedToId = $actor->Id;
        $actor->addAttachment($world->theah, $pistol);
        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $adversary;
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Technique_02054 $technique */
        $technique = $pistol->getTechniques()[0];
        return [$pistol, $technique, $actor, $adversary];
    }

    public function tests(): array
    {
        return [
            'is a ranged technique' => function () {
                $technique = new Technique_02054();
                Assert::instanceOf(IRangedAbility::class, $technique, 'ranged');
                Assert::false($technique->AdversarySufferedWound, 'starts clear');
                Assert::same(52102054, States::DUEL_CHOOSE_TECHNIQUE_02054, 'state');
            },

            'available only during a duel' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'in duel');
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'outside');
            },

            // WHY: The chooser is the adversary's controller. The wound flag starts false so
            // a cancel before they answer still grants the Parry.
            'resolve asks the adversary whether to suffer the wound' => function () {
                $world = new TestWorld();
                [$pistol, $technique, , $adversary] = $this->scene($world);
                $technique->AdversarySufferedWound = true;
                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->adversaryId = $adversary->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->AdversarySufferedWound, 'reset');
                Assert::true($pistol->IsUpdated, 'persisted');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::same('02054', $transitions[0]->transition, 'name');
                Assert::same($adversary->ControllerId, $transitions[0]->playerId, 'adversary chooses');
                Assert::same($pistol->Id, $transitions[0]->sourceId, 'source');
                Assert::same($technique->Id, $transitions[0]->internalId, 'technique');
            },

            'Parry is added only when the adversary did not take the wound' => function () {
                $world = new TestWorld();
                [$pistol, $technique] = $this->scene($world);
                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->parry = 2;
                $event->theah = $world->theah;
                $technique->handleEvent($event);
                Assert::same(3, $event->parry, 'parry');
                Assert::contains($pistol->getInjectCode(), $event->explanations[0], 'explained');

                $technique->AdversarySufferedWound = true;
                $event->parry = 2;
                $event->explanations = [];
                $technique->handleEvent($event);
                Assert::same(2, $event->parry, 'wound chosen');
                Assert::count(0, $event->explanations, 'silent');
            },

            // WHY: nextState rebuilds from the DB before the wound flag would otherwise flush.
            // Suffering the wound writes the card immediately. Declining does not.
            'suffering the wound writes the flag and still plays the ranged ability' => function () {
                $world = new TestWorld();
                [$pistol, $technique, $actor, $adversary] = $this->scene($world);
                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_02054, 'duelChooseTechnique_02054', 1);

                Assert::true($technique->AdversarySufferedWound, 'flag');
                Assert::true(in_array($pistol->Id, $world->game->updatedCardObjectIds, true), 'written now');
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                $ranged = $world->theah->queuedOfType(EventRangedAbilityPlayed::class);
                Assert::same($adversary->Id, $wound[0]->characterId, 'adversary, not the actor');
                Assert::same($pistol->Id, $wound[0]->sourceId, 'source');
                Assert::same($technique->Id, $wound[0]->abilityId, 'ability');
                Assert::same(1, $wound[0]->wounds, 'one');
                Assert::same($actor->Id, $ranged[0]->performerId, 'host');
                Assert::same($adversary->Id, $ranged[0]->targetId, 'target');
                Assert::same($adversary->Location, $ranged[0]->targetLocation, 'location');
                Assert::same([null], $world->game->gamestate->transitions, 'empty transition');
            },

            'declining leaves the flag clear and does not write the card' => function () {
                $world = new TestWorld();
                [$pistol, $technique] = $this->scene($world);
                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_02054, 'duelChooseTechnique_02054', 2);

                Assert::false($technique->AdversarySufferedWound, 'clear');
                Assert::false(in_array($pistol->Id, $world->game->updatedCardObjectIds, true), 'not written');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound');
                Assert::count(1, $world->theah->queuedOfType(EventRangedAbilityPlayed::class), 'ranged still plays');
            },

            'cancel, a new round, and the end of the duel clear the wound flag' => function () {
                $world = new TestWorld();
                [$pistol, $technique] = $this->scene($world);
                foreach ([new EventTechniqueCanceled(), new EventDuelNewRound(), new EventDuelEnd()] as $event) {
                    $technique->AdversarySufferedWound = true;
                    $pistol->IsUpdated = false;
                    if ($event instanceof EventTechniqueCanceled) {
                        $event->techniqueId = $technique->Id;
                    }
                    $event->theah = $world->theah;
                    $technique->handleEvent($event);
                    Assert::false($technique->AdversarySufferedWound, $event::class);
                    Assert::true($pistol->IsUpdated, $event::class . ' persisted');
                }
            },
        ];
    }
}
