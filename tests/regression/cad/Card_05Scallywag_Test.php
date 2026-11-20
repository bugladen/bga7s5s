<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericLeader;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Scallywag;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05Scallywag;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_05Scallywag_Test extends TestCase
{
    public function name(): string
    {
        return '_05Scallywag Friends at Sea';
    }

    public function tests(): array
    {
        return [
            'constructs Daring Camaraderie scheme with Reaction' => function () {
                $scheme = new _05Scallywag();
                Assert::same('Friends at Sea', $scheme->Name, 'name');
                Assert::same(21, $scheme->Initiative, 'initiative');
                Assert::same(1, $scheme->PanacheModifier, 'panache');
                Assert::true($scheme->hasTrait('Daring'), 'Daring');
                Assert::true($scheme->hasTrait('Camaraderie'), 'Camaraderie');
                Assert::instanceOf(Reaction_05Scallywag::class, $scheme->getReactions()[0], 'reaction');
            },

            // WHY: Text is Forum or Docks — one pick transition, not fixed dual Renown.
            'resolve queues Forum-or-Docks pick' => function () {
                $world = new TestWorld();
                /** @var _05Scallywag $scheme */
                $scheme = $world->placeCard(new _05Scallywag(), Game::LOCATION_PLAYER_HOME, 1);

                $event = new EventResolveScheme();
                $event->playerId = 1;
                $event->scheme = $scheme;
                $world->fireOn($scheme, $event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'one transition');
                Assert::same('05Scallywag', $transitions[0]->transition, 'pick key');

                $names = $scheme->getRenownLocationNames($world->theah);
                Assert::true(in_array(Game::LOCATION_CITY_FORUM, $names, true), 'Forum');
                Assert::true(in_array(Game::LOCATION_CITY_DOCKS, $names, true), 'Docks');
                Assert::count(2, $names, 'only those two');
            },

            // WHY: Challenge refuse / pre-duel — CHOSEN_PERFORMER owned + wounded is CHOSEN_TARGET.
            'offers when opponent Leader wounded as challenge target' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _05Scallywag(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $leader = $world->placeCharacter(new GenericLeader('Enemy Captain'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Corsair', ['Pirate']), Game::LOCATION_APPROACH, 1);
                /** @var Reaction_05Scallywag $reaction */
                $reaction = $scheme->getReactions()[0];

                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $leader->Id);

                $wound = new EventCharacterWounded();
                $wound->theah = $world->theah;
                $wound->characterId = $leader->Id;
                $wound->wounds = 1;
                $reaction->handleEvent($wound);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
            },

            'offers during duel when you are challenger and Leader is participant' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _05Scallywag(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_FORUM, 1);
                $leader = $world->placeCharacter(new GenericLeader('Enemy Captain'), Game::LOCATION_CITY_FORUM, 2);
                $world->placeCharacter(new GenericCharacter('Corsair', ['Pirate']), Game::LOCATION_APPROACH, 1);
                /** @var Reaction_05Scallywag $reaction */
                $reaction = $scheme->getReactions()[0];

                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelChallengerIdOverride = $performer->Id;
                $world->theah->duelOpponent = $leader;

                $wound = new EventCharacterWounded();
                $wound->theah = $world->theah;
                $wound->characterId = $leader->Id;
                $wound->wounds = 1;
                $reaction->handleEvent($wound);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'duel offered');
            },

            'does not offer without Pirate in Approach' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _05Scallywag(), Game::LOCATION_PLAYER_HOME, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $leader = $world->placeCharacter(new GenericLeader('Enemy Captain'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Reaction_05Scallywag $reaction */
                $reaction = $scheme->getReactions()[0];

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $world->game->globals->set(Game::CHOSEN_TARGET, $leader->Id);

                $wound = new EventCharacterWounded();
                $wound->theah = $world->theah;
                $wound->characterId = $leader->Id;
                $wound->wounds = 1;
                $reaction->handleEvent($wound);

                Assert::count(0, $world->theah->queuedEvents, 'no pirate');
            },

            'does not offer when you are only the defender' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _05Scallywag(), Game::LOCATION_PLAYER_HOME, 1);
                $myChar = $world->placeCharacter(new GenericCharacter('My Defender'), Game::LOCATION_CITY_DOCKS, 1);
                $enemyLeader = $world->placeCharacter(new GenericLeader('Enemy Captain'), Game::LOCATION_CITY_DOCKS, 2);
                $world->placeCharacter(new GenericCharacter('Corsair', ['Pirate']), Game::LOCATION_APPROACH, 1);
                /** @var Reaction_05Scallywag $reaction */
                $reaction = $scheme->getReactions()[0];

                // WHY: Opponent issued the challenge — we are defender. Text requires our performer.
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelChallengerIdOverride = $enemyLeader->Id;
                $world->theah->duelOpponent = $myChar;

                $wound = new EventCharacterWounded();
                $wound->theah = $world->theah;
                $wound->characterId = $enemyLeader->Id;
                $wound->wounds = 1;
                $reaction->handleEvent($wound);

                Assert::count(0, $world->theah->queuedEvents, 'not our challenge');
            },

            'muster queues engaged Pirate at captured location' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _05Scallywag(), Game::LOCATION_PLAYER_HOME, 1);
                $pirate = $world->placeCharacter(new GenericCharacter('Corsair', ['Pirate']), Game::LOCATION_APPROACH, 1);
                /** @var Reaction_05Scallywag $reaction */
                $reaction = $scheme->getReactions()[0];

                $prop = new \ReflectionProperty(Reaction_05Scallywag::class, 'leaderLocation');
                $prop->setAccessible(true);
                $prop->setValue($reaction, Game::LOCATION_CITY_DOCKS);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'muster-' . $pirate->Id);

                $musters = $world->theah->queuedOfType(EventCharacterMustered::class);
                Assert::count(1, $musters, 'muster');
                Assert::same($pirate->Id, $musters[0]->characterId, 'pirate');
                Assert::same(Game::LOCATION_CITY_DOCKS, $musters[0]->location, 'at Leader loc');

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engaged');
                Assert::same($pirate->Id, $engages[0]->cardId, 'engage pirate');
                Assert::true($reaction->Used, 'used');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },
        ];
    }
}
