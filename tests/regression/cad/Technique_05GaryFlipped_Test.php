<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05GaryFlipped;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05GaryFlipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_05GaryFlipped_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_05GaryFlipped';
    }

    public function tests(): array
    {
        return [
            // WHY: Rage (and other challenges) flip her before TechniqueAvailable with
            // IN_DUEL still false. Old gate hid the technique entirely on challenge.
            // Challenge uses performer host; duel still needs actor==owner + a real attach.
            'is offered on challenge and in duel when she has a real attachment' => function () {
                $world = new TestWorld();
                $rena = $world->placeCharacter(new _05GaryFlipped(), Game::LOCATION_CITY_DOCKS, 1);
                $gear = $world->placeCard(new GenericAttachment('Gear'), Game::LOCATION_CITY_DOCKS, 1);
                $gear->AttachedToId = $rena->Id;
                $rena->Attachments = [$gear->Id];
                /** @var Technique_05GaryFlipped $technique */
                $technique = $rena->getTechniques()[0];

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'challenge');

                $rena->Attachments = [];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no attach on challenge');
                $rena->Attachments = [$gear->Id];

                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $rena;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'duel actor');

                $world->theah->duelActor = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not actor');
            },

            'resolve queues destroy picker; calculate and challenge threat add thrust' => function () {
                $world = new TestWorld();
                $rena = $world->placeCharacter(new _05GaryFlipped(), Game::LOCATION_CITY_DOCKS, 1);
                $rena->Wounds = 2;
                $gear = $world->placeCard(new GenericAttachment('Gear'), Game::LOCATION_CITY_DOCKS, 1);
                $gear->AttachedToId = $rena->Id;
                $rena->Attachments = [$gear->Id];
                /** @var Technique_05GaryFlipped $technique */
                $technique = $rena->getTechniques()[0];

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                Assert::same('05GaryFlipped', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'picker');
                Assert::true($technique->Used, 'used');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->thrust = 0;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->thrust, '+1 Thrust');

                $threat = new EventGenerateChallengeThreat();
                $threat->techniqueId = $technique->Id;
                $threat->actorId = $rena->Id;
                $threat->adversaryThreat = 0;
                $threat->theah = $world->theah;
                $technique->handleEvent($threat);
                Assert::same(1, $threat->adversaryThreat, 'challenge threat');

                $technique->actFromTechniqueWithId(
                    $world->game,
                    States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_05GARYFLIPPED,
                    '',
                    $gear->Id
                );
                Assert::same($gear->Id, $world->theah->queuedOfType(EventAttachmentUnequipped::class)[0]->attachmentId, 'unequipped');
                Assert::true($world->theah->queuedOfType(EventCardDiscardedFromPlay::class)[0]->asEffect, 'discard effect');
                Assert::same(1, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->wounds, 'heal');
                Assert::same(null, $world->game->gamestate->transitions[0], 'bare next state');

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId(
                        $world->game,
                        States::HIGH_DRAMA_CHALLENGE_ACTION_RESOLVE_TECHNIQUE_05GARYFLIPPED,
                        '',
                        999
                    );
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not equipped');
            },
        ];
    }
}
