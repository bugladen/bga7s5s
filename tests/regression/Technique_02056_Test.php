<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02056;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02056;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Technique_02056_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02056';
    }

    /**
     * @return array{0:_02056,1:Technique_02056,2:GenericCharacter}
     */
    private function scene(TestWorld $world, bool $attach = true): array
    {
        $card = $world->placeCard(new _02056(), Game::LOCATION_CITY_DOCKS, 1);
        $actor = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
        if ($attach) {
            $card->AttachedToId = $actor->Id;
            $actor->addAttachment($world->theah, $card);
        }
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Technique_02056 $technique */
        $technique = $card->getTechniques()[0];
        return [$card, $technique, $actor];
    }

    private function calculate(TestWorld $world, Technique_02056 $technique): EventDuelCalculateTechniqueValues
    {
        $event = new EventDuelCalculateTechniqueValues();
        $event->techniqueId = $technique->Id;
        $event->parry = 1;
        $event->riposte = 1;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
        return $event;
    }

    public function tests(): array
    {
        return [
            'available only during a duel' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'in duel');
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'outside');
            },

            // WHY: 3 Finesse replaces Parry with Riposte. 2 Finesse stays on Parry.
            // The host is the attached character, via ModifiedFinesse.
            '3 Finesse adds Riposte and less than 3 adds Parry' => function () {
                $world = new TestWorld();
                [$card, $technique, $actor] = $this->scene($world);
                $actor->ModifiedFinesse = 3;
                $event = $this->calculate($world, $technique);
                Assert::same(2, $event->riposte, 'riposte');
                Assert::same(1, $event->parry, 'parry unchanged');
                Assert::contains($card->getInjectCode(), $event->explanations[0], 'explained');

                $actor->ModifiedFinesse = 2;
                $event = $this->calculate($world, $technique);
                Assert::same(1, $event->riposte, 'no riposte');
                Assert::same(2, $event->parry, 'parry');
            },

            'an unattached dagger does not change the totals' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world, false);
                $event = $this->calculate($world, $technique);
                Assert::same(1, $event->riposte, 'riposte');
                Assert::same(1, $event->parry, 'parry');
                Assert::count(0, $event->explanations, 'silent');
            },

            'ignores a different technique id' => function () {
                $world = new TestWorld();
                [, $technique, $actor] = $this->scene($world);
                $actor->ModifiedFinesse = 5;
                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = 'other';
                $event->riposte = 4;
                $event->parry = 4;
                $event->theah = $world->theah;
                $technique->handleEvent($event);
                Assert::same(4, $event->riposte, 'riposte');
                Assert::same(4, $event->parry, 'parry');
            },
        ];
    }
}
