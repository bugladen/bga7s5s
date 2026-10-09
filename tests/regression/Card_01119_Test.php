<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01119;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;

class Card_01119_Test extends TestCase
{
    public function name(): string
    {
        return '_01119 Nazem ibn Umur';
    }

    /** @return array{0:_01119,1:GenericCharacter} */
    private function scene(TestWorld $world): array
    {
        $nazem = $world->placeCharacter(new _01119(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        return [$nazem, $foe];
    }

    private function moved(TestWorld $world, int $cardId, string $from, string $to, bool $engage = true): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->initiatingPlayerId = 1;
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->engage = $engage;
        $event->theah = $world->theah;
        return $event;
    }

    /** @return list<EventCharacterInfluenceModified> */
    private function influence(TestWorld $world): array
    {
        return $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
    }

    public function tests(): array
    {
        return [
            'constructs Ussura Duelist Anatol Ayh with printed stats' => function () {
                $nazem = new _01119();
                Assert::instanceOf(Character::class, $nazem, 'Character');
                Assert::same(5, $nazem->Resolve, 'Resolve');
                Assert::same(2, $nazem->Combat, 'Combat');
                Assert::same(3, $nazem->Finesse, 'Finesse');
                Assert::same(0, $nazem->Influence, 'Influence');
                Assert::true($nazem->hasFaction('Ussura'), 'Ussura');
                Assert::true($nazem->hasTrait('Duelist'), 'Duelist');
                Assert::true($nazem->hasTrait('Anatol Ayh'), 'Anatol Ayh');
            },

            // WHY: EventCardEngaged fires before the character's Engaged flag flips, so the
            // handler does count()+1. Pre-setting Engaged=true would double-count.
            'EventCardEngaged on an enemy at Nazem\'s location grants +1 Influence' => function () {
                $world = new TestWorld();
                [$nazem, $foe] = $this->scene($world);
                Assert::false($foe->Engaged, 'not yet engaged when the event fires');

                $event = new EventCardEngaged();
                $event->cardId = $foe->Id;
                $event->playerId = 2;
                $world->fireOn($nazem, $event);

                $mods = $this->influence($world);
                Assert::count(1, $mods, 'influence');
                Assert::same($nazem->Id, $mods[0]->CharacterId, 'Nazem');
                Assert::same(0, $mods[0]->OldInfluence, 'old');
                Assert::same(1, $mods[0]->NewInfluence, 'new');
            },

            'unengaged enemy does not grant Influence on engage event for a different card' => function () {
                $world = new TestWorld();
                [$nazem, $foe] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCardEngaged();
                $event->cardId = $ally->Id;
                $event->playerId = 1;
                $world->fireOn($nazem, $event);

                Assert::count(0, $this->influence($world), 'own engage ignored');
            },

            // WHY: EventCardMoved also fires for attachments; getCharacterById is null and used to
            // fatal on ->ControllerId / isNotControlledByPlayer. Guard is `$movedCharacter &&`.
            'EventCardMoved for a non-character card does not fatal (null-safe)' => function () {
                $world = new TestWorld();
                [$nazem] = $this->scene($world);
                $hat = $world->placeCard(new _01073(), Game::LOCATION_CITY_FORUM, 2);

                $world->fireOn($nazem, $this->moved(
                    $world,
                    $hat->Id,
                    Game::LOCATION_PLAYER_HOME,
                    Game::LOCATION_CITY_DOCKS
                ));

                Assert::count(0, $this->influence($world), 'attachment ignored');
            },

            'engaged enemy moving into Nazem\'s location grants Influence' => function () {
                $world = new TestWorld();
                [$nazem, $foe] = $this->scene($world);
                $foe->Location = Game::LOCATION_CITY_FORUM;
                $foe->Engaged = true;

                $world->fireOn($nazem, $this->moved(
                    $world,
                    $foe->Id,
                    Game::LOCATION_CITY_FORUM,
                    Game::LOCATION_CITY_DOCKS
                ));

                $mods = $this->influence($world);
                Assert::count(1, $mods, 'gained');
                Assert::same(1, $mods[0]->NewInfluence, '+1');
            },

            'challenge rejected by the target engages them' => function () {
                $world = new TestWorld();
                [$nazem, $foe] = $this->scene($world);
                $foe->Engaged = false;

                $event = new EventChallengeRejected();
                $event->challengerId = $nazem->Id;
                $event->targetId = $foe->Id;
                $world->fireOn($nazem, $event);

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage queued');
                Assert::same($foe->Id, $engages[0]->cardId, 'target engaged');
                Assert::same($nazem->ControllerId, $engages[0]->playerId, 'Nazem controller');
            },

            'challenge rejected does nothing when the target is already engaged' => function () {
                $world = new TestWorld();
                [$nazem, $foe] = $this->scene($world);
                $foe->Engaged = true;

                $event = new EventChallengeRejected();
                $event->challengerId = $nazem->Id;
                $event->targetId = $foe->Id;
                $world->fireOn($nazem, $event);

                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'already engaged');
            },

            // WHY: Fate's Silence skips handleEvent — EngagedEnemyBonus Influence would stick.
            'blanked clears engaged-enemy Influence bonus' => function () {
                $world = new TestWorld();
                [$nazem, $foe] = $this->scene($world);
                $engage = new EventCardEngaged();
                $engage->cardId = $foe->Id;
                $world->fireOn($nazem, $engage);
                $world->theah->takeQueuedEvents();
                // Hub would have applied the +1; mirror that so blanked subtracts cleanly.
                $nazem->ModifiedInfluence = 1;

                $nazem->onAbilitiesBlanked($world->theah);

                $mods = $this->influence($world);
                Assert::count(1, $mods, 'cleared');
                Assert::same(0, $mods[0]->NewInfluence, 'back to printed');
            },
        ];
    }
}
