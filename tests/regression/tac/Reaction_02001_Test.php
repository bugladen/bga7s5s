<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ISorcererAbility;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02001;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02001;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSorcererAbilityStart;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_02001_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02001';
    }

    /**
     * Andriana and opposing non-Sorcerer at the same city location.
     *
     * @return array{0:_02001,1:Reaction_02001,2:GenericCharacter}
     */
    private function scene(TestWorld $world, string $foeLocation = Game::LOCATION_CITY_DOCKS): array
    {
        $andriana = $world->placeCharacter(new _02001(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), $foeLocation, 2);
        /** @var Reaction_02001 $reaction */
        $reaction = $andriana->getReactions()[0];
        return [$andriana, $reaction, $foe];
    }

    private function intervened(TestWorld $world, int $playerId, int $newTargetId): EventCharacterIntervened
    {
        $event = new EventCharacterIntervened();
        $event->playerId = $playerId;
        $event->oldTargetId = 0;
        $event->newTargetId = $newTargetId;
        $event->theah = $world->theah;
        return $event;
    }

    private function rejected(TestWorld $world, int $targetId): EventChallengeRejected
    {
        $event = new EventChallengeRejected();
        $event->challengerId = 0;
        $event->targetId = $targetId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is a Sorcerer CardReaction that targets characters' => function () {
                $reaction = new Reaction_02001();
                Assert::instanceOf(CardReaction::class, $reaction, 'CardReaction');
                Assert::instanceOf(ISorcererAbility::class, $reaction, 'sorcerer');
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $reaction, 'targets');
            },

            // WHY: "opposing" = opponent controller AND same city location (2026-03-16-01).
            'offers when opposing non-Sorcerer intervenes at Andriana\'s location' => function () {
                $world = new TestWorld();
                [$andriana, $reaction, $foe] = $this->scene($world);

                $reaction->handleEvent($this->intervened($world, 2, $foe->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($andriana->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same($foe->Id, $reaction->CharacterId, 'stored');
            },

            'does not offer when intervenor is at a different location' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->scene($world, Game::LOCATION_CITY_FORUM);

                $reaction->handleEvent($this->intervened($world, 2, $foe->Id));
                Assert::count(0, $world->theah->queuedEvents, 'elsewhere');
            },

            'does not offer when Andriana is at Home' => function () {
                $world = new TestWorld();
                [$andriana, $reaction, $foe] = $this->scene($world);
                $andriana->Location = Game::LOCATION_PLAYER_HOME;
                $foe->Location = Game::LOCATION_PLAYER_HOME;

                $reaction->handleEvent($this->intervened($world, 2, $foe->Id));
                Assert::count(0, $world->theah->queuedEvents, 'Home');
            },

            'does not offer for intervening Sorcerer' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $sorcerer = $world->placeCharacter(
                    new GenericCharacter('Enemy Sorcerer', ['Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );

                $reaction->handleEvent($this->intervened($world, 2, $sorcerer->Id));
                Assert::count(0, $world->theah->queuedEvents, 'Sorcerer');
            },

            'does not offer when own character intervenes' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $reaction->handleEvent($this->intervened($world, 1, $ally->Id));
                Assert::count(0, $world->theah->queuedEvents, 'own');
            },

            'offers when opposing non-Sorcerer refuses a challenge at her location' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->scene($world);

                $reaction->handleEvent($this->rejected($world, $foe->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'reject offer');
                Assert::same($foe->Id, $reaction->CharacterId, 'stored');
            },

            'does not offer refuse when foe is elsewhere' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->scene($world, Game::LOCATION_CITY_BAZAAR);

                $reaction->handleEvent($this->rejected($world, $foe->Id));
                Assert::count(0, $world->theah->queuedEvents, 'elsewhere');
            },

            // WHY: Home shares LOCATION_PLAYER_HOME across players — co-location alone
            // is not enough; Andriana must be in the city (isValidTargetForAbility).
            'isValidTargetForAbility requires city co-location and non-Sorcerer opponent' => function () {
                $world = new TestWorld();
                [$andriana, $reaction, $foe] = $this->scene($world);

                [$ok,] = $reaction->isValidTargetForAbility($world->game, $foe);
                Assert::true($ok, 'valid');

                $sorcerer = $world->placeCharacter(
                    new GenericCharacter('Sorc', ['Sorcerer']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );
                [$okSorc,] = $reaction->isValidTargetForAbility($world->game, $sorcerer);
                Assert::false($okSorc, 'Sorcerer');

                $andriana->Location = Game::LOCATION_PLAYER_HOME;
                $foe->Location = Game::LOCATION_PLAYER_HOME;
                [$okHome,] = $reaction->isValidTargetForAbility($world->game, $foe);
                Assert::false($okHome, 'Home');
            },

            // WHY: pick queues ONLY CharacterTargeted so Cloud Bottle / UL / Maryam can cancel
            // before the Sorcerer bracketing and wound are emitted.
            'woundNonSorcerer queues only CharacterTargeted and marks Used' => function () {
                $world = new TestWorld();
                [$andriana, $reaction, $foe] = $this->scene($world);
                $reaction->CharacterId = $foe->Id;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'woundNonSorcerer');

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class);
                Assert::count(1, $targeted, 'one targeting');
                Assert::same($foe->Id, $targeted[0]->targetId, 'target');
                Assert::same($andriana->Id, $targeted[0]->sourceId, 'source');
                Assert::same($reaction->Id, $targeted[0]->abilityId, 'ability');
                Assert::true($targeted[0]->batchId > 0, 'batched');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'no start yet');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'no wound yet');
                Assert::count(0, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'no played yet');
                Assert::true($reaction->Used, 'used');
                Assert::same(0, $reaction->CharacterId, 'cleared');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'surviving CharacterTargeted wounds inside Sorcerer bracketing with shared batch' => function () {
                $world = new TestWorld();
                [$andriana, $reaction, $foe] = $this->scene($world);
                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $reaction->Id;
                $targeted->targetId = $foe->Id;
                $targeted->sourceId = $andriana->Id;
                $targeted->batchId = 77;
                $targeted->theah = $world->theah;
                $reaction->handleEvent($targeted);

                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityStart::class), 'start');
                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'foe');
                Assert::same(1, $wounds[0]->wounds, '1');
                Assert::same(77, $wounds[0]->batchId, 'shares batch');
                Assert::count(1, $world->theah->queuedOfType(EventSorcererAbilityPlayed::class), 'played');
            },

            'canceled or foreign CharacterTargeted queues nothing' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->scene($world);
                $targeted = new EventCharacterTargeted();
                $targeted->abilityId = $reaction->Id;
                $targeted->targetId = $foe->Id;
                $targeted->canceled = true;
                $targeted->theah = $world->theah;
                $reaction->handleEvent($targeted);
                Assert::count(0, $world->theah->queuedEvents, 'canceled');

                $other = new EventCharacterTargeted();
                $other->abilityId = 'other';
                $other->targetId = $foe->Id;
                $other->theah = $world->theah;
                $reaction->handleEvent($other);
                Assert::count(0, $world->theah->queuedEvents, 'other ability');
            },

            'decline does not wound' => function () {
                $world = new TestWorld();
                [, $reaction, $foe] = $this->scene($world);
                $reaction->CharacterId = $foe->Id;

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                Assert::count(0, $world->theah->queuedEvents, 'no events');
                Assert::false($reaction->Used, 'not used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
