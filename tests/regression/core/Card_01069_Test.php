<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01069;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01069;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;

class Card_01069_Test extends TestCase
{
    public function name(): string
    {
        return '_01069 Maxime de Lafayette';
    }

    private function wound(TestWorld $world, int $characterId, int $sourceId, string $abilityId = '', int $wounds = 1): EventCharacterWounded
    {
        $event = new EventCharacterWounded();
        $event->characterId = $characterId;
        $event->sourceId = $sourceId;
        $event->abilityId = $abilityId;
        $event->wounds = $wounds;
        $event->reason = 'test';
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Sorcerer Character with Action_01069' => function () {
                $maxime = new _01069();
                Assert::instanceOf(Character::class, $maxime, 'Character');
                Assert::instanceOf(IHasActions::class, $maxime, 'actions');
                Assert::same(4, $maxime->Resolve, 'Resolve');
                Assert::same(1, $maxime->Combat, 'Combat');
                Assert::same(2, $maxime->Finesse, 'Finesse');
                Assert::same(3, $maxime->Influence, 'Influence');
                Assert::true($maxime->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($maxime->hasTrait('Villain'), 'Villain');
                Assert::true($maxime->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Action_01069::class, $maxime->getActions()[0], 'Action_01069');
            },

            // WHY: A Sorcery's effect that wounds its chosen sorcerer wounds the performer; when that
            // performer is Maxime the wound is ignored (wound costs are considered paid).
            'ignores wound sourced from a Sorcery card' => function () {
                $world = new TestWorld();
                $maxime = $world->placeCharacter(new _01069(), Game::LOCATION_CITY_DOCKS, 1);
                $sorcery = $world->placeCharacter(new GenericCharacter('Sorcery', ['Sorcery']), Game::LOCATION_HAND, 1);

                $event = $this->wound($world, $maxime->Id, $sorcery->Id);
                $maxime->handleEvent($event);

                Assert::same(0, $maxime->Wounds, 'no wound applied');
                Assert::false($event->characterHandled, 'parent wound handling skipped');
            },

            'ignores wound from own Sorcerer ability when Maxime is CHOSEN_PERFORMER' => function () {
                $world = new TestWorld();
                $maxime = $world->placeCharacter(new _01069(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $maxime->Id);
                $abilityId = $maxime->getActions()[0]->Id;

                $event = $this->wound($world, $maxime->Id, $maxime->Id, $abilityId);
                $maxime->handleEvent($event);

                Assert::same(0, $maxime->Wounds, 'no wound applied');
                Assert::false($event->characterHandled, 'parent skipped');
                $messages = $world->game->notify->messages;
                Assert::same('message', $messages[count($messages) - 1]['type'], 'ignore message not characterWounded');
            },

            // WHY: "he performs" — another performer using a Sorcerer ability must not shield Maxime.
            'does not ignore Sorcerer ability wound when someone else is performer' => function () {
                $world = new TestWorld();
                $maxime = $world->placeCharacter(new _01069(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other Sorcerer', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $other->Id);
                $abilityId = $maxime->getActions()[0]->Id;

                $event = $this->wound($world, $maxime->Id, $maxime->Id, $abilityId);
                $maxime->handleEvent($event);

                Assert::same(1, $maxime->Wounds, 'wound applies');
                Assert::true($event->characterHandled, 'parent ran');
            },

            'wound from ordinary opposing source still applies' => function () {
                $world = new TestWorld();
                $maxime = $world->placeCharacter(new _01069(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $event = $this->wound($world, $maxime->Id, $foe->Id);
                $maxime->handleEvent($event);

                Assert::same(1, $maxime->Wounds, 'wound applies');
                Assert::true($event->characterHandled, 'handled');
            },

            'unknown ability id on source falls through to parent wound handling' => function () {
                $world = new TestWorld();
                $maxime = $world->placeCharacter(new _01069(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $maxime->Id);

                $event = $this->wound($world, $maxime->Id, $foe->Id, 'no_such_ability');
                $maxime->handleEvent($event);

                Assert::same(1, $maxime->Wounds, 'wound applies');
            },

            'sourceId 0 (no source) falls through to parent wound handling' => function () {
                $world = new TestWorld();
                $maxime = $world->placeCharacter(new _01069(), Game::LOCATION_CITY_DOCKS, 1);

                $event = $this->wound($world, $maxime->Id, 0);
                $maxime->handleEvent($event);

                Assert::same(1, $maxime->Wounds, 'wound applies');
            },

            'Sorcery wound on another character is not intercepted by Maxime' => function () {
                $world = new TestWorld();
                $maxime = $world->placeCharacter(new _01069(), Game::LOCATION_CITY_DOCKS, 1);
                $victim = $world->placeCharacter(new GenericCharacter('Victim'), Game::LOCATION_CITY_DOCKS, 2);
                $sorcery = $world->placeCharacter(new GenericCharacter('Sorcery', ['Sorcery']), Game::LOCATION_HAND, 1);

                $event = $this->wound($world, $victim->Id, $sorcery->Id);
                $maxime->handleEvent($event);

                Assert::same(0, $maxime->Wounds, 'Maxime untouched');
                Assert::false($event->characterHandled, 'not Maxime wound');
            },
        ];
    }
}
