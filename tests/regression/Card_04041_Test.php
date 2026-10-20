<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04058;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;

class Card_04041_Test extends TestCase
{
    public function name(): string
    {
        return '_04041 Jak-Sen';
    }

    public function tests(): array
    {
        return [
            'is a shenzhou sorcerer' => function () {
                $card = new _04041();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(4, $card->Resolve, 'resolve');
                Assert::same(0, $card->Combat, 'combat');
                Assert::true($card->DashedCombat, 'dashed combat');
                Assert::same(3, $card->Finesse, 'finesse');
                Assert::same(2, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Hero'), 'Hero');
                Assert::true($card->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($card->hasTrait('Shenzhou'), 'Shenzhou');
                Assert::count(1, $card->getReactions(), 'reaction');
            },

            // WHY: Lethal is read off the combat card's Sorcery trait during calculate,
            // and only while Jak-Sen is the actor. The factory marks the other seat.
            'sorcery combat card grants lethal only while he is the actor' => function () {
                $world = new TestWorld();
                $jak = $world->placeCharacter(new _04041(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new _04041(), Game::LOCATION_CITY_DOCKS, 2);
                $sorcery = $world->placeCard(new _04058(), Game::LOCATION_DUELING_LINE, 1);
                $plain = $world->placeCard(new _04041(), Game::LOCATION_DUELING_LINE, 1);
                $world->theah->duelActor = $jak;
                $world->theah->duelOpponent = $foe;

                $calc = new EventDuelCalculateCombatCardStats();
                $calc->actorId = $jak->Id;
                $calc->combatCardId = $sorcery->Id;
                $world->fireOn($jak, $calc);
                $lethal = $world->theah->queuedOfType(EventThreatModified::class)[0];
                Assert::true($lethal->defenderThreatIsLethal, 'other seat');
                Assert::same(null, $lethal->challengerThreatIsLethal, 'actor seat unchanged');

                $world->theah->takeQueuedEvents();
                $calc->combatCardId = $plain->Id;
                $world->fireOn($jak, $calc);
                Assert::count(0, $world->theah->queuedOfType(EventThreatModified::class), 'not sorcery');

                $calc->actorId = $foe->Id;
                $calc->combatCardId = $sorcery->Id;
                $world->fireOn($jak, $calc);
                Assert::count(0, $world->theah->queuedOfType(EventThreatModified::class), 'not the actor');
            },
        ];
    }
}
