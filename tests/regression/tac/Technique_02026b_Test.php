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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02026;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02026b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_02026b_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02026b';
    }

    /**
     * Croc on Duelist host; foe wears an engaged attachment.
     *
     * @return array{0:_02026,1:Technique_02026b,2:GenericCharacter,3:GenericCharacter,4:_02017}
     */
    private function scene(TestWorld $world, array $hostTraits = ['Duelist']): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host', $hostTraits), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $croc = $world->placeCard(new _02026(), Game::LOCATION_CITY_DOCKS, 1);
        $croc->AttachedToId = $host->Id;
        $host->Attachments[] = $croc->Id;
        $foeGear = $world->placeCard(new _02017(), Game::LOCATION_CITY_DOCKS, 2);
        $foeGear->AttachedToId = $foe->Id;
        $foeGear->Engaged = true;
        $foe->Attachments[] = $foeGear->Id;
        $world->theah->duelActor = $host;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_02026b $technique */
        $technique = $croc->getTechniques()[1];
        return [$croc, $technique, $host, $foe, $foeGear];
    }

    public function tests(): array
    {
        return [
            // WHY: "Duelist Technique" — equipped character must have Duelist.
            'available in duel when host is Duelist and foe has engaged attachment' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            'unavailable when host is not a Duelist' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world, []);
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no Duelist');
            },

            'unavailable when foe has only unengaged attachments' => function () {
                $world = new TestWorld();
                [, $technique, , , $foeGear] = $this->scene($world);
                $foeGear->Engaged = false;
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no engaged');
            },

            // WHY: Challenge has no duel round — getAdversary uses CHOSEN_TARGET when not IN_DUEL.
            'available on challenge via CHOSEN_TARGET' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->theah->duelOpponent = null;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'challenge');
            },

            // WHY: Challenge Resolve defers; in-duel Resolve is the real effect timing.
            'in-duel Resolve queues attachment chooser' => function () {
                $world = new TestWorld();
                [$croc, $technique] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->inDuel = true;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02026b', $transitions[0]->transition, 'name');
                Assert::same($croc->Id, $transitions[0]->sourceId, 'source');
            },

            'challenge Resolve does not queue chooser' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->inDuel = false;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'deferred');
            },

            'GENERATE_THREAT with CHALLENGE_ACCEPTED queues chooser' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->preview = false;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'chooser');
            },

            // WHY: Refuse must not prompt.
            'GENERATE_THREAT without CHALLENGE_ACCEPTED does not queue chooser' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, false);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->preview = false;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'refuse');
            },

            'args list only engaged foe attachments' => function () {
                $world = new TestWorld();
                [, $technique, , $foe, $foeGear] = $this->scene($world);
                $ready = $world->placeCard(new _02017(), Game::LOCATION_CITY_DOCKS, 2);
                $ready->AttachedToId = $foe->Id;
                $ready->Engaged = false;
                $foe->Attachments[] = $ready->Id;
                $world->game->globals->set(Game::IN_DUEL, true);

                $args = $technique->getArgsFromTechnique(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02026b,
                    'duelChooseTechnique_02026b'
                );

                Assert::same([['id' => $foeGear->Id, 'name' => $foeGear->Name]], $args['attachments'], 'engaged only');
            },

            // WHY: 02026b engaged attachment → unequip + discard from play as effect.
            'act unequips and discards engaged attachment as effect' => function () {
                $world = new TestWorld();
                [$croc, $technique, , $foe, $foeGear] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, true);

                $technique->actFromTechniqueWithId(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02026b,
                    'duelChooseTechnique_02026b',
                    $foeGear->Id
                );

                $unequips = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequips, 'unequip');
                Assert::same($foeGear->Id, $unequips[0]->attachmentId, 'attachment');
                Assert::same($foe->Id, $unequips[0]->characterId, 'host');
                $discards = $world->theah->queuedOfType(EventCardDiscardedFromPlay::class);
                Assert::count(1, $discards, 'discard');
                Assert::same($foeGear->Id, $discards[0]->cardId, 'card');
                Assert::same($croc->Id, $discards[0]->sourceId, 'source');
                Assert::true($discards[0]->asEffect, 'as effect');
                Assert::same([null], $world->game->gamestate->transitions, 'bare next');
            },

            'act rejects unengaged attachment' => function () {
                $world = new TestWorld();
                [, $technique, , , $foeGear] = $this->scene($world);
                $foeGear->Engaged = false;
                $world->game->globals->set(Game::IN_DUEL, true);

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId(
                        $world->game,
                        States::DUEL_CHOOSE_TECHNIQUE_02026b,
                        'duelChooseTechnique_02026b',
                        $foeGear->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },
        ];
    }
}
