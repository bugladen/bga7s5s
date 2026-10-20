<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\AttachmentReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02037;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02037;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;
use ReflectionProperty;

class Reaction_02037_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_02037';
    }

    /**
     * @return array{0:_02037,1:Reaction_02037,2:GenericCharacter,3:GenericCharacter,4:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
        $mysta = $world->placeCard(new _02037(), Game::LOCATION_CITY_DOCKS, 1);
        $mysta->AttachedToId = $host->Id;
        /** @var Reaction_02037 $reaction */
        $reaction = $mysta->getReactions()[0];
        return [$mysta, $reaction, $host, $ally, $challenger];
    }

    private function issued(TestWorld $world, int $challengerId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = $challengerId;
        $event->theah = $world->theah;
        return $event;
    }

    private function pending(Reaction_02037 $reaction): ReflectionProperty
    {
        $prop = new ReflectionProperty(Reaction_02037::class, 'pendingChallengerId');
        $prop->setAccessible(true);
        return $prop;
    }

    public function tests(): array
    {
        return [
            'is an AttachmentReaction' => function () {
                Assert::instanceOf(AttachmentReaction::class, new Reaction_02037(), 'AttachmentReaction');
            },

            // Opposing challenge, Mysta already equipped, another friendly character at that location.
            'opposing challenge offers equip onto another character at the location' => function () {
                $world = new TestWorld();
                [$mysta, $reaction, $host, $ally, $challenger] = $this->scene($world);

                $reaction->handleEvent($this->issued($world, $challenger->Id));

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offer');
                Assert::same($mysta->ControllerId, $transitions[0]->playerId, 'owner');
                Assert::same($mysta->Id, $transitions[0]->sourceId, 'mysta');
                Assert::same($reaction->Id, $transitions[0]->internalId, 'id');
                Assert::same($challenger->Id, $this->pending($reaction)->getValue($reaction), 'pending');

                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::true(in_array('equip_' . $ally->Id, $ids, true), 'ally');
                Assert::false(in_array('equip_' . $host->Id, $ids, true), 'not current host');
                Assert::true(in_array('pass', $ids, true), 'pass');
            },

            'does not offer for your own challenge' => function () {
                $world = new TestWorld();
                [, $reaction, $host] = $this->scene($world);

                $reaction->handleEvent($this->issued($world, $host->Id));
                Assert::count(0, $world->theah->queuedEvents, 'own');
            },

            'does not offer when Mysta is not attached' => function () {
                $world = new TestWorld();
                [$mysta, $reaction, , , $challenger] = $this->scene($world);
                $mysta->AttachedToId = 0;

                $reaction->handleEvent($this->issued($world, $challenger->Id));
                Assert::count(0, $world->theah->queuedEvents, 'unattached');
            },

            'does not offer when the current host is the only friendly character there' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);
                $mysta = $world->placeCard(new _02037(), Game::LOCATION_CITY_DOCKS, 1);
                $mysta->AttachedToId = $host->Id;
                /** @var Reaction_02037 $reaction */
                $reaction = $mysta->getReactions()[0];

                $reaction->handleEvent($this->issued($world, $challenger->Id));
                Assert::count(0, $world->theah->queuedEvents, 'nobody else');
            },

            'does not offer when already Used' => function () {
                $world = new TestWorld();
                [, $reaction, , , $challenger] = $this->scene($world);
                $reaction->Used = true;

                $reaction->handleEvent($this->issued($world, $challenger->Id));
                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // Factory method is named Equipped but queues EventAttachmentEquipping.
            'equip queues AttachmentEquipping and marks Used' => function () {
                $world = new TestWorld();
                [$mysta, $reaction, , $ally, $challenger] = $this->scene($world);
                $this->pending($reaction)->setValue($reaction, $challenger->Id);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'equip_' . $ally->Id);

                $equips = $world->theah->queuedOfType(EventAttachmentEquipping::class);
                Assert::count(1, $equips, 'equip');
                Assert::same($ally->Id, $equips[0]->characterId, 'target');
                Assert::same($mysta->Id, $equips[0]->attachmentId, 'mysta');
                Assert::false($equips[0]->asAction, 'not an action');
                Assert::same($reaction->Id, $equips[0]->abilityId, 'ability');
                Assert::true($reaction->Used, 'used');
                Assert::same(null, $this->pending($reaction)->getValue($reaction), 'cleared');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'pass clears the pending challenger without equipping' => function () {
                $world = new TestWorld();
                [, $reaction, , , $challenger] = $this->scene($world);
                $this->pending($reaction)->setValue($reaction, $challenger->Id);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::count(0, $world->theah->queuedOfType(EventAttachmentEquipping::class), 'no equip');
                Assert::false($reaction->Used, 'unused');
                Assert::same(null, $this->pending($reaction)->getValue($reaction), 'cleared');
            },

            'rejects a character who is not at the challenger location' => function () {
                $world = new TestWorld();
                [, $reaction, , , $challenger] = $this->scene($world);
                $away = $world->placeCharacter(new GenericCharacter('Away'), Game::LOCATION_CITY_FORUM, 1);
                $this->pending($reaction)->setValue($reaction, $challenger->Id);

                $threw = false;
                try {
                    $reaction->performReaction($world->game, 0, $reaction->Id, 'equip_' . $away->Id);
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::same(null, $this->pending($reaction)->getValue($reaction), 'cleared anyway');
                Assert::false($reaction->Used, 'unused');
            },
        ];
    }
}
