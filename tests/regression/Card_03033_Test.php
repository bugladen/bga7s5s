<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03033;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;

class Card_03033_Test extends TestCase
{
    public function name(): string
    {
        return '_03033 Glorious';
    }

    public function tests(): array
    {
        return [
            'constructs a Montaigne flourish risk' => function () {
                $risk = new _03033();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasManeuvers::class, $risk, 'maneuvers');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::true($risk->DashedRiposte, 'dashed riposte');
                Assert::same(2, $risk->Parry, 'parry');
                Assert::same(3, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Montaigne'), 'Montaigne');
                Assert::true($risk->hasTrait('Virtue'), 'Virtue');
                Assert::true($risk->hasTrait('Flourish'), 'Flourish');
                Assert::instanceOf(Maneuver_03033::class, $risk->getManeuvers()[0], 'maneuver');
            },

            // WHY: Only the owner's participant heals, and only when the adversary is the
            // one destroyed, the card is in the dueling line, and the participant is wounded.
            'a destroyed adversary heals the wounded participant' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03033(), Game::LOCATION_DUELING_LINE, 1);
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 1);
                $defender = $world->placeCharacter(new GenericCharacter('Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $challenger->Wounds = 1;
                $world->theah->duelActor = $challenger;
                $world->theah->duelOpponent = $defender;
                $world->game->globals->set(Game::IN_DUEL, true);

                $destroyed = new EventCharacterDestroyed();
                $destroyed->theah = $world->theah;
                $destroyed->characterId = $defender->Id;
                $risk->handleEvent($destroyed);
                $heal = $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0];
                Assert::same($challenger->Id, $heal->characterId, 'challenger healed');
                Assert::same(1, $heal->wounds, 'one wound');

                $world->theah->takeQueuedEvents();
                $challenger->Wounds = 0;
                $risk->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'unwounded');

                $challenger->Wounds = 1;
                $destroyed->characterId = $challenger->Id;
                $risk->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'own participant destroyed');

                $destroyed->characterId = $defender->Id;
                $risk->Location = Game::LOCATION_HAND;
                $risk->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'not in the dueling line');

                $risk->Location = Game::LOCATION_DUELING_LINE;
                $world->game->discardOrLockerByCardId[$challenger->Id] = true;
                $risk->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'participant already gone');
            },
        ];
    }
}
