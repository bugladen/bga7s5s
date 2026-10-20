<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01047;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01047_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01047';
    }

    private function equipPanzer(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        $panzer = $world->placeCard(new _01047(), Game::LOCATION_CITY_DOCKS, 1);
        $panzer->AttachedToId = $host->Id;
        $host->Attachments[] = $panzer->Id;
        return [$host, $panzer];
    }

    public function tests(): array
    {
        return [
            // WHY: Reaction_03044 documents that 01047 compares ControllerId to character Id
            // (id-space mix). Under correct duel geometry this currently does NOT offer.
            'does not offer under correct adversary geometry due to id-space mix' => function () {
                $world = new TestWorld();
                [$host, $panzer] = $this->equipPanzer($world);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $host;

                /** @var Reaction_01047 $reaction */
                $reaction = $panzer->getReactions()[0];
                $event = new EventTechniqueActivated();
                $event->techniqueId = 'Technique_TestFoe';
                $event->playerId = $actor->ControllerId;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'bug: no offer');
            },

            'cancel clears technique events and queues TechniqueCanceled' => function () {
                $world = new TestWorld();
                [, $panzer] = $this->equipPanzer($world);
                /** @var Reaction_01047 $reaction */
                $reaction = $panzer->getReactions()[0];
                $reaction->TechniqueId = $panzer->getTechniques()[0]->Id;

                $pending = new EventResolveTechnique();
                $pending->techniqueId = $reaction->TechniqueId;
                $world->theah->queueEvent($pending);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'cancel');

                Assert::count(0, $world->theah->queuedOfType(EventResolveTechnique::class), 'cleared resolve');
                Assert::count(1, $world->theah->queuedOfType(EventTechniqueCanceled::class), 'canceled');
                Assert::same('', $reaction->TechniqueId, 'cleared id');
                Assert::true($reaction->Used, 'used');
            },
        ];
    }
}
