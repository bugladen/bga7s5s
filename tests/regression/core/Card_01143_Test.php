<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01143;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01143;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

/** City-deck stand-in so resolve step 2 can discard ICityDeckCard at the chosen location. */
final class TestCityCard_01143 extends CityAttachment
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = 'Test City Card';
        $this->Image = 'test.jpg';
        $this->ExpansionName = '_test';
        $this->ExpansionNumber = 0;
        $this->CardNumber = 0;
        $this->resetCard();
    }
}

class Card_01143_Test extends TestCase
{
    public function name(): string
    {
        return '_01143 Contempt and Hatred';
    }

    /** @return array{0:_01143,1:GenericCharacter} */
    private function revealed(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01143(), Game::LOCATION_PLAYER_HOME, 1);
        $merc = $world->placeCharacter(
            new GenericCharacter('Merc', ['Mercenary']),
            Game::LOCATION_CITY_DOCKS,
            2
        );
        $merc->ModifiedInfluence = 2;
        $world->game->activePlayerId = 1;
        return [$scheme, $merc];
    }

    public function tests(): array
    {
        return [
            'constructs Demoralize Duress Scheme with Action_01143' => function () {
                $scheme = new _01143();
                Assert::instanceOf(Scheme::class, $scheme, 'Scheme');
                Assert::instanceOf(IHasActions::class, $scheme, 'actions');
                Assert::same(43, $scheme->Initiative, 'Initiative');
                Assert::same(0, $scheme->PanacheModifier, 'Panache');
                Assert::true($scheme->hasTrait('Demoralize'), 'Demoralize');
                Assert::true($scheme->hasTrait('Duress'), 'Duress');
                Assert::instanceOf(Action_01143::class, $scheme->getActions()[0], 'Action_01143');
            },

            'resolving queues Forum Renown, Mercenary aura, and 01143 transition' => function () {
                $world = new TestWorld();
                [$scheme, $merc] = $this->revealed($world);

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'Forum Renown');
                Assert::same(Game::LOCATION_CITY_FORUM, $renown[0]->location, 'Forum');

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'aura');
                Assert::same($merc->Id, $mods[0]->CharacterId, 'merc');
                Assert::same(2, $mods[0]->OldInfluence, 'old');
                Assert::same(1, $mods[0]->NewInfluence, 'new');
                Assert::true($merc->hasCondition(Game::CONTEMPT_AND_HATRED_CONDITION), 'stamped');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('01143', $transitions[0]->transition, 'name');
                Assert::same(Event::MEDIUM_PRIORITY, $transitions[0]->priority, 'medium');
            },

            'resolving does not stamp a non-Mercenary' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _01143(), Game::LOCATION_PLAYER_HOME, 1);
                $civilian = $world->placeCharacter(new GenericCharacter('Civ'), Game::LOCATION_CITY_DOCKS, 2);
                $civilian->ModifiedInfluence = 2;

                $event = new EventResolveScheme();
                $event->scheme = $scheme;
                $event->playerId = 1;
                $event->playerName = 'Player One';
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterInfluenceModified::class), 'no aura');
                Assert::false($civilian->hasCondition(Game::CONTEMPT_AND_HATRED_CONDITION), 'no stamp');
            },

            // WHY (journal 2026-09-13-07): aura only while scheme is at Player Home.
            'recruiting a Mercenary while the scheme is in play applies the aura' => function () {
                $world = new TestWorld();
                [$scheme] = $this->revealed($world);
                $world->theah->takeQueuedEvents();
                $recruit = $world->placeCharacter(
                    new GenericCharacter('New Merc', ['Mercenary']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $recruit->ModifiedInfluence = 3;

                $event = new EventCharacterRecruited();
                $event->characterId = $recruit->Id;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);

                Assert::count(1, $world->theah->queuedOfType(EventCharacterInfluenceModified::class), 'debuff');
                Assert::true($recruit->hasCondition(Game::CONTEMPT_AND_HATRED_CONDITION), 'stamped');
            },

            'recruiting a Mercenary after the scheme leaves Home does nothing' => function () {
                $world = new TestWorld();
                [$scheme] = $this->revealed($world);
                $scheme->Location = $world->game->getPlayerLockerName(1);
                $recruit = $world->placeCharacter(
                    new GenericCharacter('Late Merc', ['Mercenary']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );

                $event = new EventCharacterRecruited();
                $event->characterId = $recruit->Id;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);

                Assert::count(0, $world->theah->queuedOfType(EventCharacterInfluenceModified::class), 'no aura');
                Assert::false($recruit->hasCondition(Game::CONTEMPT_AND_HATRED_CONDITION), 'no stamp');
            },

            'sending the scheme to The Locker clears the aura from in-play Mercenaries' => function () {
                $world = new TestWorld();
                [$scheme, $merc] = $this->revealed($world);
                $merc->addCondition(Game::CONTEMPT_AND_HATRED_CONDITION);
                $merc->ModifiedInfluence = 1;

                $event = new EventCardSentToLocker();
                $event->cardId = $scheme->Id;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);

                $mods = $world->theah->queuedOfType(EventCharacterInfluenceModified::class);
                Assert::count(1, $mods, 'restore');
                Assert::same(1, $mods[0]->OldInfluence, 'old');
                Assert::same(2, $mods[0]->NewInfluence, 'new');
                Assert::false($merc->hasCondition(Game::CONTEMPT_AND_HATRED_CONDITION), 'cleared');
            },

            // WHY: Spend-to-Locker corpses keep serialized Conditions; strip + write Influence
            // directly because locker rows are not flushed via InfluenceModified IsUpdated.
            'sending the scheme to The Locker strips aura from Spend-to-Locker Mercenaries' => function () {
                $world = new TestWorld();
                [$scheme] = $this->revealed($world);
                $lockerMerc = $world->placeCharacter(
                    new GenericCharacter('Dead Merc', ['Mercenary']),
                    $world->game->getPlayerLockerName(2),
                    2
                );
                $lockerMerc->addCondition(Game::CONTEMPT_AND_HATRED_CONDITION);
                $lockerMerc->ModifiedInfluence = 1;
                $world->game->discardOrLockerByCardId[$lockerMerc->Id] = true;

                $event = new EventCardSentToLocker();
                $event->cardId = $scheme->Id;
                $event->playerId = 1;
                $world->fireOn($scheme, $event);

                Assert::same(2, $lockerMerc->ModifiedInfluence, 'written +1');
                Assert::false($lockerMerc->hasCondition(Game::CONTEMPT_AND_HATRED_CONDITION), 'stamp gone');
            },

            'choosing a second location adds Renown and discards City Cards there' => function () {
                $world = new TestWorld();
                [$scheme] = $this->revealed($world);
                $city = $world->placeCard(new TestCityCard_01143(), Game::LOCATION_CITY_DOCKS, 0);
                $faction = $world->placeCard(new _01143(), Game::LOCATION_CITY_DOCKS, 2);

                $scheme->actFromCardWithIds(
                    $world->game,
                    States::PLANNING_PHASE_RESOLVE_SCHEMES_01143,
                    'planningPhaseResolveSchemes_01143',
                    '',
                    [Game::LOCATION_CITY_DOCKS]
                );

                $renown = $world->theah->queuedOfType(EventRenownAddedToLocation::class);
                Assert::count(1, $renown, 'Renown');
                Assert::same(Game::LOCATION_CITY_DOCKS, $renown[0]->location, 'docks');

                $discards = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discards, 'city card only');
                Assert::same($city->Id, $discards[0]->cardId, 'city');
                Assert::false(
                    in_array($faction->Id, array_map(fn($e) => $e->cardId, $discards), true),
                    'faction scheme not discarded'
                );
                Assert::same([''], $world->game->gamestate->transitions, 'empty transition');
            },
        ];
    }
}
