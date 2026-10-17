<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03021;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03021;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeRejected;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;

class Card_03021_Test extends TestCase
{
    public function name(): string
    {
        return '_03021 Cornered';
    }

    public function tests(): array
    {
        return [
            'constructs an Eisen challenge risk' => function () {
                $risk = new _03021();
                Assert::instanceOf(Risk::class, $risk, 'risk');
                Assert::instanceOf(IHasActions::class, $risk, 'actions');
                Assert::instanceOf(IRiskThatTargetsCharacters::class, $risk, 'targets');
                Assert::same(0, $risk->WealthCost, 'wealth');
                Assert::same(0, $risk->Riposte, 'riposte');
                Assert::same(2, $risk->Parry, 'parry');
                Assert::same(3, $risk->Thrust, 'thrust');
                Assert::true($risk->hasFaction('Eisen'), 'Eisen');
                Assert::true($risk->hasTrait('Hunt'), 'Hunt');
                Assert::true($risk->hasTrait('Zeal'), 'Zeal');
                Assert::instanceOf(Action_03021::class, $risk->getActions()[0], 'action');
            },

            // WHY: Refuse and intervene are on the card, gated by CORNERED_CHALLENGE_TYPE.
            // A target who is already engaged is not engaged again. The wound's ability
            // id is the card id so other cards can attribute it.
            'refusing engages the target, and intervening wounds them' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _03021(), Game::LOCATION_HAND, 1);
                $target = $world->placeCharacter(new GenericCharacter('Target', ['Sorcerer']), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::CORNERED_CHALLENGE_TYPE);

                $refused = new EventChallengeRejected();
                $refused->theah = $world->theah;
                $refused->targetId = $target->Id;
                $risk->handleEvent($refused);
                Assert::same($target->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engaged');

                $world->theah->takeQueuedEvents();
                $target->Engaged = true;
                $risk->handleEvent($refused);
                Assert::count(0, $world->theah->queuedEvents, 'already engaged');

                $intervener = $world->placeCharacter(new GenericCharacter('Intervener'), Game::LOCATION_CITY_DOCKS, 2);
                $intervened = new EventCharacterIntervened();
                $intervened->theah = $world->theah;
                $intervened->newTargetId = $intervener->Id;
                $risk->handleEvent($intervened);
                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class)[0];
                Assert::same($intervener->Id, $wound->characterId, 'intervener');
                Assert::same($risk->Id, $wound->sourceId, 'the risk');
                Assert::same((string) $risk->Id, $wound->abilityId, 'card id, not the action');

                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::NORMAL_CHALLENGE_TYPE);
                $world->theah->takeQueuedEvents();
                $risk->handleEvent($refused);
                $risk->handleEvent($intervened);
                Assert::count(0, $world->theah->queuedEvents, 'other challenge types');
            },
        ];
    }
}
