<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04033;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventThreatModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_04033_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04033';
    }

    public function tests(): array
    {
        return [
            // WHY: The Thrust/Parry choice happens at resolve, before calculate. Challenge
            // turns Thrust into adversary threat and ignores Parry. The deferred threat
            // prompt waits for a later round whose actor is not Iago, then clears so it
            // prompts once. Duel end and cancel drop the pending choice.
            'chooses thrust or parry, then may add a threat on the adversary round' => function () {
                $world = new TestWorld();
                $iago = $world->placeCharacter(new _04033(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Technique_04033 $technique */
                $technique = $iago->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'challenge has no actor gate');
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $foe;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not his round');
                $world->theah->duelActor = $iago;
                $world->theah->duelOpponent = $foe;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'his round');

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                Assert::true($technique->Used, 'spent');
                Assert::true($technique->PendingThreatChoice, 'deferred');
                Assert::false($technique->UseThrust, 'defaults to parry until chosen');
                Assert::same('04033', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');

                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_04033, '', 1);
                Assert::true($technique->UseThrust, 'thrust');
                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->thrust, 'duel thrust');

                $challenge = new EventGenerateChallengeThreat();
                $challenge->techniqueId = $technique->Id;
                $challenge->actorId = $iago->Id;
                $challenge->theah = $world->theah;
                $technique->handleEvent($challenge);
                Assert::same(1, $challenge->adversaryThreat, 'challenge threat');

                $technique->UseThrust = false;
                $parry = new EventDuelCalculateTechniqueValues();
                $parry->techniqueId = $technique->Id;
                $parry->theah = $world->theah;
                $technique->handleEvent($parry);
                Assert::same(1, $parry->parry, 'parry');
                $challenge->adversaryThreat = 0;
                $technique->handleEvent($challenge);
                Assert::same(0, $challenge->adversaryThreat, 'parry adds no challenge threat');

                $ownRound = new EventDuelNewRound();
                $ownRound->actorId = $iago->Id;
                $ownRound->theah = $world->theah;
                $technique->handleEvent($ownRound);
                Assert::true($technique->PendingThreatChoice, 'still waiting for their round');

                $world->theah->takeQueuedEvents();
                $theirRound = new EventDuelNewRound();
                $theirRound->actorId = $foe->Id;
                $theirRound->theah = $world->theah;
                $technique->handleEvent($theirRound);
                Assert::false($technique->PendingThreatChoice, 'prompted once');
                Assert::same('04033', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'threat prompt');

                $technique->actFromTechniqueWithId($world->game, States::DUEL_NEW_ROUND_04033, '', 1);
                Assert::same(1, $world->theah->queuedOfType(EventThreatModified::class)[0]->challengerThreat, 'threat on Iago');

                $technique->PendingThreatChoice = true;
                $cancel = new EventTechniqueCanceled();
                $cancel->techniqueId = $technique->Id;
                $cancel->theah = $world->theah;
                $technique->handleEvent($cancel);
                Assert::false($technique->PendingThreatChoice, 'cancel clears');

                $technique->PendingThreatChoice = true;
                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $technique->handleEvent($end);
                Assert::false($technique->PendingThreatChoice, 'duel end clears');
            },
        ];
    }
}
