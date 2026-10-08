<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01071;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01071;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01071_Test extends TestCase
{
    public function name(): string
    {
        return '_01071 Épée Sanglante';
    }

    private function musketeer(TestWorld $world, string $location, int $controller = 1): GenericCharacter
    {
        /** @var GenericCharacter $m */
        $m = $world->placeCharacter(new GenericCharacter('Musketeer', ['Musketeer']), $location, $controller);
        return $m;
    }

    private function moved(TestWorld $world, int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->initiatingPlayerId = 1;
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        $event->theah = $world->theah;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Scheme with Action_01071' => function () {
                $scheme = new _01071();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(26, $scheme->Initiative, 'Initiative');
                Assert::same(-1, $scheme->PanacheModifier, 'PanacheModifier');
                Assert::true($scheme->hasTrait('Challenge'), 'Challenge');
                Assert::true($scheme->hasTrait('Duty'), 'Duty');
                Assert::true($scheme->hasTrait('Glory'), 'Glory');
                Assert::true($scheme->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Action_01071::class, $scheme->getActions()[0], 'Action_01071');
            },

            // --- When Revealed ---

            'resolve transitions 01071 at medium priority (player picks any location)' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('01071', $transitions[0]->transition, 'transition');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'priority');
            },

            'resolve of a different scheme is ignored' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $other = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 2);

                $event = new EventResolveScheme();
                $event->scheme = $other;
                $event->playerId = 2;
                $event->playerName = 'Player Two';
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedEvents, 'not our scheme');
            },

            // --- Influence aura: EventCardMoved ---

            // WHY regression (journal 2026-10-06-08): EventCardMoved fires for attachments too;
            // getCharacterById is null for them and used to fatal on ->ControllerId.
            'EventCardMoved for a non-character card does not fatal (null-safe character)' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $hat = $world->placeCard(new _01073(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($scheme, $this->moved($world, $hat->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_DOCKS));

                Assert::count(0, $world->theah->queuedEvents, 'no influence change');
            },

            'Musketeer moving Home to a 2+ Renown location gains +1 Influence' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $m = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($scheme, $this->moved($world, $m->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_DOCKS));

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'influence event');
                Assert::same($m->Id, $mods[0]->CharacterId, 'musketeer');
                Assert::same(1, $mods[0]->OldInfluence, 'old');
                Assert::same(2, $mods[0]->NewInfluence, 'new');
                Assert::true($m->hasCondition(Game::EPEE_SANGLANTE_CONDITION), 'condition stamped');
            },

            'Musketeer moving Home to a <2 Renown location gains nothing' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $m = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                $world->fireOn($scheme, $this->moved($world, $m->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_DOCKS));

                Assert::count(0, $world->theah->queuedEvents, 'no change');
                Assert::false($m->hasCondition(Game::EPEE_SANGLANTE_CONDITION), 'no condition');
            },

            'Musketeer leaving a 2+ Renown location for Home loses +1 Influence' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $m = $this->musketeer($world, Game::LOCATION_PLAYER_HOME);
                $m->addCondition(Game::EPEE_SANGLANTE_CONDITION);
                $m->ModifiedInfluence = 2;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($scheme, $this->moved($world, $m->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_PLAYER_HOME));

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'influence event');
                Assert::same(2, $mods[0]->OldInfluence, 'old');
                Assert::same(1, $mods[0]->NewInfluence, 'new');
                Assert::false($m->hasCondition(Game::EPEE_SANGLANTE_CONDITION), 'condition cleared');
            },

            'Musketeer moving from <2 Renown city location to 2+ Renown city location gains Influence' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $m = $this->musketeer($world, Game::LOCATION_CITY_FORUM);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 0);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);

                $world->fireOn($scheme, $this->moved($world, $m->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::count(1, $world->theah->queuedOfType(EventCharacterInfluenceModified::class), 'gained');
            },

            'Musketeer moving from 2+ Renown city location to <2 Renown city location loses Influence' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $m = $this->musketeer($world, Game::LOCATION_CITY_FORUM);
                $m->addCondition(Game::EPEE_SANGLANTE_CONDITION);
                $m->ModifiedInfluence = 2;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 3);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 1);

                $world->fireOn($scheme, $this->moved($world, $m->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'lost');
                Assert::same(1, $mods[0]->NewInfluence, 'back to base');
            },

            'moving between two 2+ Renown city locations is net zero' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $m = $this->musketeer($world, Game::LOCATION_CITY_FORUM);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);

                $world->fireOn($scheme, $this->moved($world, $m->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::count(0, $world->theah->queuedEvents, 'no change');
            },

            'non-Musketeer and opposing Musketeer get no aura on move' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $theirs = $this->musketeer($world, Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($scheme, $this->moved($world, $plain->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_DOCKS));
                $world->fireOn($scheme, $this->moved($world, $theirs->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_DOCKS));

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },

            'aura is inactive when scheme is not at Player Home' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_CITY_DISCARD, 1);
                $m = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($scheme, $this->moved($world, $m->Id, Game::LOCATION_PLAYER_HOME, Game::LOCATION_CITY_DOCKS));

                Assert::count(0, $world->theah->queuedEvents, 'inactive');
            },

            // --- Influence aura: Renown crossing the 2 threshold ---

            'Renown crossing 1 -> 2 gives local Musketeers +1 Influence' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $m1 = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $m2 = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventRenownAddedToLocation();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->amount = 1;
                $world->fireOn($scheme, $event);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(2, $mods, 'both Musketeers');
                Assert::true($m1->hasCondition(Game::EPEE_SANGLANTE_CONDITION), 'm1');
                Assert::true($m2->hasCondition(Game::EPEE_SANGLANTE_CONDITION), 'm2');
                Assert::false($plain->hasCondition(Game::EPEE_SANGLANTE_CONDITION), 'plain unaffected');
            },

            'Renown already at 2+ before add does not stack Influence' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 3);

                $event = new EventRenownAddedToLocation();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->amount = 1;
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedEvents, 'no stack');
            },

            'Renown dropping 2 -> 1 removes Influence from local Musketeers' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $m = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $m->addCondition(Game::EPEE_SANGLANTE_CONDITION);
                $m->ModifiedInfluence = 2;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventRenownRemovedFromLocation();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->amount = 1;
                $world->fireOn($scheme, $event);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'lost');
                Assert::false($m->hasCondition(Game::EPEE_SANGLANTE_CONDITION), 'condition cleared');
            },

            'Renown removal that stays at 2+ keeps Influence' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01071(), Game::LOCATION_PLAYER_HOME, 1);
                $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventRenownRemovedFromLocation();
                $event->playerId = 1;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $event->amount = 1;
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedEvents, 'unchanged');
            },

            'state constant registered' => function () {
                Assert::same(2601071, \Bga\Games\SeventhSeaCityOfFiveSails\States::PLANNING_PHASE_RESOLVE_SCHEMES_01071, 'scheme state');
            },
        ];
    }
}
