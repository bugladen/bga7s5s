<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03049;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_03049_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03049';
    }

    /**
     * @return array{0:_03049,1:Technique_03049}
     */
    private function scene(TestWorld $world): array
    {
        $ekaterina = $world->placeCharacter(new _03049(), Game::LOCATION_CITY_DOCKS, 1);
        $world->theah->duelActor = $ekaterina;
        $world->theah->duelOpponent = $world->placeCharacter(new \Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->game->globals->set(Game::IN_DUEL, true);
        /** @var Technique_03049 $technique */
        $technique = $ekaterina->getTechniques()[0];
        return [$ekaterina, $technique];
    }

    public function tests(): array
    {
        return [
            // WHY: +1 Parry needs no Artifact, so she can always use it while she is the
            // actor. No Artifact locks the bonus in on resolve. Cancel clears it before calculate.
            'with no Artifact she adds 1 Parry' => function () {
                $world = new TestWorld();
                [$ekaterina, $technique] = $this->scene($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
                $world->theah->duelActor = $world->theah->duelOpponent;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not the actor');
                $world->theah->duelActor = $ekaterina;
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in a duel');
                $world->game->globals->set(Game::IN_DUEL, true);

                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $technique->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no choice');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->theah = $world->theah;
                $calc->techniqueId = $technique->Id;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->parry, 'one parry');
                Assert::true($technique->Used, 'spent on calculate');
            },

            // WHY: The choice transition is highest priority so it runs before calculate.
            // Engaging an unengaged Artifact is +2. A fake or engaged Artifact is not a choice.
            'engaging an Artifact is +2 Parry' => function () {
                $world = new TestWorld();
                [$ekaterina, $technique] = $this->scene($world);
                $artifact = $world->placeCard(new _03043(), Game::LOCATION_CITY_DOCKS, 1);
                $artifact->addTrait($world->game, 'Artifact', true);
                $ekaterina->Attachments[] = $artifact->Id;

                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $technique->handleEvent($resolve);
                $transition = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('03049', $transition->transition, 'choice');
                Assert::same(Event::HIGHEST_PRIORITY, $transition->priority, 'before calculate');

                $args = $technique->getArgsFromTechnique($world->game, States::DUEL_CHOOSE_TECHNIQUE_03049, 'x');
                Assert::same($artifact->Id, $args['attachments'][0]['id'], 'the artifact');
                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_03049, 'x', $artifact->Id);
                Assert::same($artifact->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engaged');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->theah = $world->theah;
                $calc->techniqueId = $technique->Id;
                $technique->handleEvent($calc);
                Assert::same(2, $calc->parry, 'two parry');

                $artifact->Engaged = true;
                $world->theah->takeQueuedEvents();
                $technique->handleEvent($resolve);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'engaged artifact is not a choice');

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_03049, 'x', $artifact->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'engaged artifact rejected');
            },

            'choosing the base bonus does not engage, and cancel drops the bonus' => function () {
                $world = new TestWorld();
                [$ekaterina, $technique] = $this->scene($world);
                $artifact = $world->placeCard(new _03043(), Game::LOCATION_CITY_DOCKS, 1);
                $artifact->addTrait($world->game, 'Artifact', true);
                $ekaterina->Attachments[] = $artifact->Id;
                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_03049, 'x', 0);
                Assert::count(0, $world->theah->queuedOfType(EventCardEngaged::class), 'not engaged');

                $cancel = new EventTechniqueCanceled();
                $cancel->theah = $world->theah;
                $cancel->techniqueId = $technique->Id;
                $technique->handleEvent($cancel);
                $calc = new EventDuelCalculateTechniqueValues();
                $calc->theah = $world->theah;
                $calc->techniqueId = $technique->Id;
                $technique->handleEvent($calc);
                Assert::same(0, $calc->parry, 'bonus cleared');
                Assert::false($technique->Used, 'calculate did not spend it');
            },
        ];
    }
}
