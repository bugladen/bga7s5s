<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03026;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;

class Card_03026_Test extends TestCase
{
    public function name(): string
    {
        return '_03026 Angeline Dèmone, Uneasy Ally';
    }

    public function tests(): array
    {
        return [
            'constructs the Montaigne character' => function () {
                $angeline = new _03026();
                Assert::instanceOf(Character::class, $angeline, 'character');
                Assert::instanceOf(IHasActions::class, $angeline, 'actions');
                Assert::same(4, $angeline->Resolve, 'resolve');
                Assert::same(2, $angeline->Combat, 'combat');
                Assert::same(3, $angeline->Finesse, 'finesse');
                Assert::same(1, $angeline->Influence, 'influence');
                Assert::true($angeline->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($angeline->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($angeline->hasTrait('Duelist'), 'Duelist');
                Assert::true($angeline->hasTrait('Pirate'), 'Pirate');
                Assert::true($angeline->hasTrait('Villain'), 'Villain');
            },

            // WHY: The bonus is absolute (printed Influence + 0 or 1). EventCardMoved fires
            // before Location is written, so a sorcerer moving in is counted from the event
            // and a sorcerer moving out is excluded even though they are still listed.
            // Queued influence events are not applied, so the test writes ModifiedInfluence
            // before asserting the drop.
            'another controlled Sorcerer at her location is +1 Influence' => function () {
                $world = new TestWorld();
                $angeline = $world->placeCharacter(new _03026(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Sorcerer', 'Hero']), Game::LOCATION_CITY_FORUM, 1);

                $angeline->handleEvent($this->moved($world, $ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                $grant = $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0];
                Assert::same(1, $grant->OldInfluence, 'printed');
                Assert::same(2, $grant->NewInfluence, 'plus one');
                $angeline->ModifiedInfluence = 2;

                $ally->Location = Game::LOCATION_CITY_DOCKS;
                $world->theah->takeQueuedEvents();
                $angeline->handleEvent($this->moved($world, $ally->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                $drop = $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0];
                Assert::same(1, $drop->NewInfluence, 'sorcerer left');

                $angeline->ModifiedInfluence = 1;
                $world->theah->takeQueuedEvents();
                $angeline->handleEvent($this->moved($world, $ally->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                Assert::count(0, $world->theah->queuedEvents, 'already at printed influence');
            },

            'a home arrival controlled by someone else is ignored, and approach uses the event player' => function () {
                $world = new TestWorld();
                $angeline = $world->placeCharacter(new _03026(), Game::LOCATION_PLAYER_HOME, 1);
                $stranger = $world->placeCharacter(new GenericCharacter('Stranger', ['Sorcerer', 'Hero']), Game::LOCATION_CITY_DOCKS, 2);
                $angeline->handleEvent($this->moved($world, $stranger->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_PLAYER_HOME));
                Assert::count(0, $world->theah->queuedEvents, 'other controller at home');

                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Sorcerer', 'Hero']), Game::LOCATION_PLAYER_HOME, 1);
                $angeline->ControllerId = 0;
                $approach = new EventApproachCharacterPlayed();
                $approach->theah = $world->theah;
                $approach->characterId = $angeline->Id;
                $approach->playerId = 1;
                $angeline->handleEvent($approach);
                Assert::same(2, $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0]->NewInfluence, 'playerId override');
                unset($ally);
            },

            'blanking resets to printed Influence, and a blanked move does not recompute' => function () {
                $world = new TestWorld();
                $angeline = $world->placeCharacter(new _03026(), Game::LOCATION_CITY_DOCKS, 1);
                $angeline->ModifiedInfluence = 2;
                $angeline->onAbilitiesBlanked($world->theah);
                Assert::same(1, $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0]->NewInfluence, 'printed');

                $angeline->addCondition(Game::FATES_SILENCE_CONDITION);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Sorcerer', 'Hero']), Game::LOCATION_CITY_FORUM, 1);
                $world->theah->takeQueuedEvents();
                $angeline->handleEvent($this->moved($world, $ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                Assert::count(0, $world->theah->queuedEvents, 'blanked');

                $angeline->removeCondition(Game::FATES_SILENCE_CONDITION);
                $angeline->ModifiedInfluence = 1;
                $ally->Location = Game::LOCATION_CITY_DOCKS;
                $angeline->IsDying = true;
                $angeline->onAbilitiesUnblanked($world->theah);
                Assert::count(0, $world->theah->queuedEvents, 'dying');
            },
        ];
    }

    private function moved(TestWorld $world, int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->theah = $world->theah;
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        return $event;
    }
}
