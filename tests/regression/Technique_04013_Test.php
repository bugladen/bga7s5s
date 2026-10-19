<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04008_Silence;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04013;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\techniques\Technique_04013;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_04013_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_04013';
    }

    public function tests(): array
    {
        return [
            // WHY: Duel only, and only while he is the actor with a real attachment.
            // A fake shell does not qualify. Calculate is +2 Thrust. Challenge threat is
            // +2 only when he is the actor. Destroy unequips then discards as an effect.
            'needs a real attachment, then adds thrust and destroys it' => function () {
                $world = new TestWorld();
                $world->game->globals->set(Game::IN_DUEL, true);
                $tomas = $world->placeCharacter(new _04013(), Game::LOCATION_CITY_DOCKS, 1);
                $garb = $world->placeCard(new _04006(), Game::LOCATION_CITY_DOCKS, 1);
                $garb->AttachedToId = $tomas->Id;
                $tomas->Attachments = [$garb->Id];
                $fake = $world->placeCard(new _04008_Silence(), Game::LOCATION_CITY_DOCKS, 1);
                $world->theah->duelActor = $tomas;
                /** @var Technique_04013 $technique */
                $technique = $tomas->getTechniques()[0];
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'real attachment');

                $tomas->Attachments = [$fake->Id];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'fake only');
                $tomas->Attachments = [$garb->Id];
                $world->theah->duelActor = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not the actor');
                $world->theah->duelActor = $tomas;

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                Assert::same('04013', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::true($technique->Used, 'used');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->thrust = 1;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                Assert::same(3, $calc->thrust, 'plus 2');

                $threat = new EventGenerateChallengeThreat();
                $threat->techniqueId = $technique->Id;
                $threat->actorId = $tomas->Id;
                $threat->adversaryThreat = 0;
                $threat->theah = $world->theah;
                $technique->handleEvent($threat);
                Assert::same(2, $threat->adversaryThreat, 'challenge threat');
                $threat->actorId = 999;
                $threat->adversaryThreat = 0;
                $technique->handleEvent($threat);
                Assert::same(0, $threat->adversaryThreat, 'someone else is the actor');

                $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_04013, '', $garb->Id);
                Assert::same($garb->Id, $world->theah->queuedOfType(EventAttachmentUnequipped::class)[0]->attachmentId, 'unequipped');
                $discard = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class)[0];
                Assert::true($discard->asEffect, 'effect');
                Assert::same(null, $world->game->gamestate->transitions[0], 'bare next state');

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId($world->game, States::DUEL_CHOOSE_TECHNIQUE_04013, '', $fake->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not equipped to him');
            },
        ];
    }
}
