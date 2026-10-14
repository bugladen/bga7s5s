<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IAbilityThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02010;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02016;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Reaction_02016_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02016';
    }

    /**
     * Cross equipped to owner; ally + foe at Docks; opponent Risk with IAbilityThatTargetsCharacters.
     *
     * @return array{0:_02016,1:Reaction_02016,2:GenericCharacter,3:GenericCharacter,4:_02010}
     */
    private function scene(TestWorld $world): array
    {
        $owner = $world->placeCharacter(new GenericCharacter('Owner'), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $cross = $world->placeCard(new _02016(), Game::LOCATION_CITY_DOCKS, 1);
        $cross->AttachedToId = $owner->Id;
        $owner->Attachments[] = $cross->Id;

        $source = $world->placeCard(new _02010(), Game::LOCATION_HAND, 2);
        /** @var Reaction_02016 $reaction */
        $reaction = $cross->getReactions()[0];
        return [$cross, $reaction, $owner, $ally, $source];
    }

    private function targetProp(Reaction_02016 $reaction): ReflectionProperty
    {
        $prop = new ReflectionProperty(Reaction_02016::class, 'targetCharacterId');
        $prop->setAccessible(true);
        return $prop;
    }

    private function wounded(
        TestWorld $world,
        int $sourceId,
        string $abilityId,
        int $characterId
    ): EventCharacterBeingWounded {
        $event = new EventCharacterBeingWounded();
        $event->sourceId = $sourceId;
        $event->abilityId = $abilityId;
        $event->characterId = $characterId;
        $event->wounds = 1;
        $event->theah = $world->theah;
        return $event;
    }

    private function targeted(
        TestWorld $world,
        int $sourceId,
        string $abilityId,
        int $targetId
    ): EventCharacterTargeted {
        $event = new EventCharacterTargeted();
        $event->playerId = 2;
        $event->sourceId = $sourceId;
        $event->abilityId = $abilityId;
        $event->targetId = $targetId;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'is an AttachmentReaction' => function () {
                Assert::instanceOf(AttachmentReaction::class, new Reaction_02016(), 'AttachmentReaction');
            },

            // WHY (journal / 2026-07-01 fix): redirect only to equipped performer, not any ally.
            'buttons only offer redirect to equipped owner plus Decline' => function () {
                $world = new TestWorld();
                [, $reaction, $owner, $ally] = $this->scene($world);
                $this->targetProp($reaction)->setValue($reaction, $ally->Id);

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array("redirect-{$owner->Id}", $ids, true), 'owner redirect');
                Assert::false(in_array("redirect-{$ally->Id}", $ids, true), 'no ally redirect');
                Assert::true(in_array('decline', $ids, true), 'decline');
                foreach ($ids as $id) {
                    if (str_starts_with((string) $id, 'redirect-') && $id !== "redirect-{$owner->Id}") {
                        Assert::true(false, 'extra character redirect: ' . $id);
                    }
                }
            },

            // WHY: opponent ability targeting your character at location (not already the performer).
            'offers on EventCharacterBeingWounded from opponent IAbilityThatTargetsCharacters' => function () {
                $world = new TestWorld();
                [$cross, $reaction, , $ally, $source] = $this->scene($world);
                $ability = $source->getActions()[0];
                Assert::instanceOf(IAbilityThatTargetsCharacters::class, $ability, 'gate');

                $event = $this->wounded($world, $source->Id, $ability->Id, $ally->Id);
                $reaction->handleEvent($event);

                Assert::true($event->canceled, 'held canceled');
                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($cross->Id, $transitions[0]->sourceId, 'source');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same($ally->Id, $this->targetProp($reaction)->getValue($reaction), 'armed');
            },

            'offers on EventCharacterTargeted for opponent targeting ability' => function () {
                $world = new TestWorld();
                [, $reaction, , $ally, $source] = $this->scene($world);
                $ability = $source->getActions()[0];

                $reaction->handleEvent($this->targeted($world, $source->Id, $ability->Id, $ally->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offer');
            },

            'does not offer when ability is not IAbilityThatTargetsCharacters' => function () {
                $world = new TestWorld();
                [, $reaction, , $ally] = $this->scene($world);
                $plain = $world->placeCard(new _02018(), Game::LOCATION_HAND, 2);
                $maneuver = $plain->getManeuvers()[0];

                $reaction->handleEvent($this->wounded($world, $plain->Id, $maneuver->Id, $ally->Id));
                Assert::count(0, $world->theah->queuedEvents, 'no offer');
            },

            'does not offer for own controller ability' => function () {
                $world = new TestWorld();
                [, $reaction, , $ally] = $this->scene($world);
                $own = $world->placeCard(new _02010(), Game::LOCATION_HAND, 1);
                $ability = $own->getActions()[0];

                $reaction->handleEvent($this->wounded($world, $own->Id, $ability->Id, $ally->Id));
                Assert::count(0, $world->theah->queuedEvents, 'own');
            },

            'does not offer when target is already the equipped performer' => function () {
                $world = new TestWorld();
                [, $reaction, $owner, , $source] = $this->scene($world);
                $ability = $source->getActions()[0];

                $reaction->handleEvent($this->wounded($world, $source->Id, $ability->Id, $owner->Id));
                Assert::count(0, $world->theah->queuedEvents, 'already performer');
            },

            // WHY: cost wound on performer + redirect held wound onto them.
            'performReaction redirect wounds performer and re-queues redirected wound' => function () {
                $world = new TestWorld();
                [$cross, $reaction, $owner, $ally, $source] = $this->scene($world);
                $ability = $source->getActions()[0];

                $held = $this->wounded($world, $source->Id, $ability->Id, $ally->Id);
                $reaction->handleEvent($held);
                $world->theah->queuedEvents = [];

                $reaction->performReaction($world->game, 0, $reaction->Id, "redirect-{$owner->Id}");

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(2, $wounds, 'cost + redirected');
                Assert::same($owner->Id, $wounds[0]->characterId, 'cost wound');
                Assert::same(1, $wounds[0]->wounds, 'one');
                Assert::same($cross->Id, $wounds[0]->sourceId, 'cross');
                Assert::same($owner->Id, $wounds[1]->characterId, 'redirected');
                Assert::same($source->Id, $wounds[1]->sourceId, 'original source');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'performReaction rejects redirect to non-owner' => function () {
                $world = new TestWorld();
                [, $reaction, , $ally, $source] = $this->scene($world);
                $ability = $source->getActions()[0];
                $reaction->handleEvent($this->wounded($world, $source->Id, $ability->Id, $ally->Id));
                $world->theah->queuedEvents = [];

                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, $reaction->Id, "redirect-{$ally->Id}");
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },

            // WHY: Decline re-releases onto original target so the ability does not fizzle.
            'Decline re-queues wound onto original target without cost wound' => function () {
                $world = new TestWorld();
                [, $reaction, , $ally, $source] = $this->scene($world);
                $ability = $source->getActions()[0];
                $reaction->handleEvent($this->wounded($world, $source->Id, $ability->Id, $ally->Id));
                $world->theah->queuedEvents = [];

                $reaction->performReaction($world->game, 0, $reaction->Id, 'decline');

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'original only');
                Assert::same($ally->Id, $wounds[0]->characterId, 'ally');
                Assert::false($reaction->Used, 'not used on decline');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
