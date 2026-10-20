<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02039;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\actions\Action_02033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02033;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class Card_02033_Test extends TestCase
{
    public function name(): string
    {
        return '_02033 “Prima” Rosa';
    }

    public function tests(): array
    {
        return [
            'constructs Castille Scoundrel Bard Zealot with Action and Reaction' => function () {
                $rosa = new _02033();
                Assert::instanceOf(Character::class, $rosa, 'Character');
                Assert::instanceOf(IHasActions::class, $rosa, 'actions');
                Assert::instanceOf(IHasReactions::class, $rosa, 'reactions');
                Assert::same(5, $rosa->Resolve, 'Resolve');
                Assert::same(0, $rosa->Combat, 'Combat');
                Assert::same(2, $rosa->Finesse, 'Finesse');
                Assert::same(2, $rosa->Influence, 'Influence');
                Assert::true($rosa->hasFaction('Castille'), 'Castille');
                Assert::true($rosa->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($rosa->hasTrait('Bard'), 'Bard');
                Assert::true($rosa->hasTrait('Zealot'), 'Zealot');
                Assert::instanceOf(Action_02033::class, $rosa->getActions()[0], 'action');
                Assert::instanceOf(Reaction_02033::class, $rosa->getReactions()[0], 'reaction');
            },

            'owns action and reaction when placed' => function () {
                $world = new TestWorld();
                $rosa = $world->placeCharacter(new _02033(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::same($rosa->Id, $rosa->getActions()[0]->OwnerId, 'action');
                Assert::same($rosa->Id, $rosa->getReactions()[0]->OwnerId, 'reaction');
            },

            // WHY: Revelry combat card while Rosa is the actor grants Lethal (defender side when she challenges).
            'Revelry combat card queues Lethal' => function () {
                $world = new TestWorld();
                $rosa = $world->placeCharacter(new _02033(), Game::LOCATION_CITY_DOCKS, 1);
                $combat = $world->placeCard(new _02039(), Game::LOCATION_HAND, 1);
                $combat->addTrait($world->game, 'Revelry', true);
                $world->theah->duelActor = $rosa;

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $rosa->Id;
                $event->combatCardId = $combat->Id;
                $event->explanations = [];
                $world->fireOn($rosa, $event);

                $threats = $world->theah->queuedOfType(EventThreatModified::class);
                Assert::count(1, $threats, 'lethal');
                Assert::same(0, $threats[0]->challengerThreat, 'no extra challenger threat');
                Assert::same(0, $threats[0]->defenderThreat, 'no extra defender threat');
                Assert::true($threats[0]->defenderThreatIsLethal, 'defender lethal');
                Assert::count(1, $event->explanations, 'explained');
            },

            'non-Revelry combat card does not grant Lethal' => function () {
                $world = new TestWorld();
                $rosa = $world->placeCharacter(new _02033(), Game::LOCATION_CITY_DOCKS, 1);
                $combat = $world->placeCard(new _02039(), Game::LOCATION_HAND, 1);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $rosa->Id;
                $event->combatCardId = $combat->Id;
                $event->explanations = [];
                $world->fireOn($rosa, $event);

                Assert::count(0, $world->theah->queuedOfType(EventThreatModified::class), 'none');
                Assert::count(0, $event->explanations, 'silent');
            },

            'ignores another actor combat card even if it is Revelry' => function () {
                $world = new TestWorld();
                $rosa = $world->placeCharacter(new _02033(), Game::LOCATION_CITY_DOCKS, 1);
                $combat = $world->placeCard(new _02039(), Game::LOCATION_HAND, 2);
                $combat->addTrait($world->game, 'Revelry', true);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = 999;
                $event->combatCardId = $combat->Id;
                $event->explanations = [];
                $world->fireOn($rosa, $event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
            },

            'missing combat card does not grant Lethal' => function () {
                $world = new TestWorld();
                $rosa = $world->placeCharacter(new _02033(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $rosa->Id;
                $event->combatCardId = 999;
                $event->explanations = [];
                $world->fireOn($rosa, $event);

                Assert::count(0, $world->theah->queuedEvents, 'none');
                Assert::instanceOf(Risk::class, new _02039(), 'risk still a risk');
            },
        ];
    }
}
