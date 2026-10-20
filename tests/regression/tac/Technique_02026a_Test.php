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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02026a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_02026a_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02026a';
    }

    /**
     * Croc on Duelist host; foe wears an unengaged attachment.
     *
     * @return array{0:_02026,1:Technique_02026a,2:GenericCharacter,3:GenericCharacter,4:_02017}
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
        $foeGear->Engaged = false;
        $foe->Attachments[] = $foeGear->Id;
        $world->theah->duelActor = $host;
        $world->theah->duelOpponent = $foe;
        /** @var Technique_02026a $technique */
        $technique = $croc->getTechniques()[0];
        return [$croc, $technique, $host, $foe, $foeGear];
    }

    public function tests(): array
    {
        return [
            // WHY: "Duelist Technique" — equipped character must have Duelist (equip itself unrestricted).
            'available in duel when host is Duelist and foe has unengaged attachment' => function () {
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

            'unavailable when foe attachments are all engaged' => function () {
                $world = new TestWorld();
                [, $technique, , , $foeGear] = $this->scene($world);
                $foeGear->Engaged = true;
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'no unengaged');
            },

            // WHY: Challenge has no duel round — getAdversary uses CHOSEN_TARGET when not IN_DUEL.
            'available on challenge via CHOSEN_TARGET without calling duel opponent' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->theah->duelOpponent = null;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'challenge');
            },

            // WHY: Challenge Resolve runs before Accept/Refuse — in-duel Resolve queues chooser;
            // challenge defers to EventGenerateChallengeThreat when CHALLENGE_ACCEPTED.
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
                Assert::same('02026a', $transitions[0]->transition, 'name');
                Assert::same($croc->Id, $transitions[0]->sourceId, 'source');
                Assert::same($technique->Id, $transitions[0]->internalId, 'id');
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
                [$croc, $technique, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->preview = false;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'chooser');
                Assert::same('02026a', $transitions[0]->transition, 'name');
                Assert::same($croc->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY: Refuse must not prompt; Intervene sets ACCEPTED without EventChallengeAccepted.
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

            'GENERATE_THREAT preview does not queue chooser even when accepted' => function () {
                $world = new TestWorld();
                [, $technique, , $foe] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $world->game->globals->set(Game::CHALLENGE_ACCEPTED, true);

                $event = new EventGenerateChallengeThreat();
                $event->techniqueId = $technique->Id;
                $event->preview = true;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'preview');
            },

            'args list only unengaged foe attachments' => function () {
                $world = new TestWorld();
                [, $technique, , $foe, $foeGear] = $this->scene($world);
                $engaged = $world->placeCard(new _02017(), Game::LOCATION_CITY_DOCKS, 2);
                $engaged->AttachedToId = $foe->Id;
                $engaged->Engaged = true;
                $foe->Attachments[] = $engaged->Id;
                $world->game->globals->set(Game::IN_DUEL, true);

                $args = $technique->getArgsFromTechnique(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02026a,
                    'duelChooseTechnique_02026a'
                );

                Assert::same([['id' => $foeGear->Id, 'name' => $foeGear->Name]], $args['attachments'], 'unengaged only');
            },

            // WHY: 02026a unengaged attachment → engage via createCardEngagedEvent.
            'act engages the chosen unengaged attachment' => function () {
                $world = new TestWorld();
                [$croc, $technique, , , $foeGear] = $this->scene($world);
                $world->game->globals->set(Game::IN_DUEL, true);

                $technique->actFromTechniqueWithId(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02026a,
                    'duelChooseTechnique_02026a',
                    $foeGear->Id
                );

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($foeGear->Id, $engages[0]->cardId, 'attachment');
                Assert::same($croc->Id, $engages[0]->sourceId, 'source');
                Assert::same([null], $world->game->gamestate->transitions, 'bare next');
            },

            'act rejects already-engaged attachment' => function () {
                $world = new TestWorld();
                [, $technique, , , $foeGear] = $this->scene($world);
                $foeGear->Engaged = true;
                $world->game->globals->set(Game::IN_DUEL, true);

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId(
                        $world->game,
                        States::DUEL_CHOOSE_TECHNIQUE_02026a,
                        'duelChooseTechnique_02026a',
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
