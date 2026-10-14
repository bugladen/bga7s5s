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
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02006;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02006;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Technique_02006_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02006';
    }

    /**
     * Red Scepter equipped to duel actor; ally at same location for wound target.
     *
     * @return array{0:GenericCharacter,1:_02006,2:Technique_02006,3:GenericCharacter}
     */
    private function duel(TestWorld $world, bool $engaged = false): array
    {
        $actor = $world->placeCharacter(
            new GenericCharacter('Actor', ['Red Hand']),
            Game::LOCATION_CITY_DOCKS,
            1
        );
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $scepter = $world->placeCard(new _02006(), Game::LOCATION_CITY_DOCKS, 1);
        $scepter->AttachedToId = $actor->Id;
        $scepter->Engaged = $engaged;
        $actor->Attachments[] = $scepter->Id;

        $world->theah->duelActor = $actor;
        $world->theah->duelOpponent = $foe;
        $world->game->globals->set(Game::IN_DUEL, true);

        /** @var Technique_02006 $technique */
        $technique = $scepter->getTechniques()[0];
        return [$actor, $scepter, $technique, $ally];
    }

    public function tests(): array
    {
        return [
            'available in duel with another friendly character at location' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
            },

            // WHY (journal 2026-03-16-06): technique unavailable when attachment already Engaged.
            'unavailable when attachment is Engaged' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world, true);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'engaged');
            },

            'unavailable outside a duel' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'unavailable when actor is alone at location' => function () {
                $world = new TestWorld();
                $actor = $world->placeCharacter(
                    new GenericCharacter('Actor', ['Red Hand']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $scepter = $world->placeCard(new _02006(), Game::LOCATION_CITY_DOCKS, 1);
                $scepter->AttachedToId = $actor->Id;
                $actor->Attachments[] = $scepter->Id;
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);
                /** @var Technique_02006 $technique */
                $technique = $scepter->getTechniques()[0];
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'alone');
            },

            'resolve queues technique transition 02006' => function () {
                $world = new TestWorld();
                [, $scepter, $technique] = $this->duel($world);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->playerId = 1;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'transition');
                Assert::same('02006', $transitions[0]->transition, 'name');
                Assert::same($scepter->Id, $transitions[0]->sourceId, 'source');
            },

            // WHY: printed +1 Parry — applied on duel calc, engage cost is separate in act.
            'duel calc adds +1 Parry' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);

                $event = new EventDuelCalculateTechniqueValues();
                $event->techniqueId = $technique->Id;
                $event->parry = 0;
                $event->explanations = [];
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::same(1, $event->parry, 'parry');
                Assert::count(1, $event->explanations, 'explained');
            },

            'args list other friendly characters at actor location' => function () {
                $world = new TestWorld();
                [$actor, , $technique, $ally] = $this->duel($world);
                $world->placeCharacter(new GenericCharacter('Far'), Game::LOCATION_CITY_FORUM, 1);

                $args = $technique->getArgsFromTechnique(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02006,
                    'duelChooseTechnique_02006'
                );

                Assert::same($actor->Id, $args['performerId'], 'actor');
                Assert::same([$ally->Id], $args['ids'], 'ally only');
            },

            // WHY (journal 2026-03-16-06): act rejects wounding the actor — must be OTHER character.
            'act rejects wounding the actor' => function () {
                $world = new TestWorld();
                [$actor, , $technique] = $this->duel($world);

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId(
                        $world->game,
                        States::DUEL_CHOOSE_TECHNIQUE_02006,
                        'duelChooseTechnique_02006',
                        $actor->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
                Assert::count(0, $world->theah->queuedEvents, 'no wound/engage');
            },

            'act rejects opposing character' => function () {
                $world = new TestWorld();
                [, , $technique] = $this->duel($world);
                $foe = $world->theah->duelOpponent;

                $threw = false;
                try {
                    $technique->actFromTechniqueWithId(
                        $world->game,
                        States::DUEL_CHOOSE_TECHNIQUE_02006,
                        'duelChooseTechnique_02006',
                        $foe->Id
                    );
                } catch (UserException $e) {
                    $threw = true;
                }
                Assert::true($threw, 'rejected');
            },

            // WHY: attachment engages itself for +1 Parry cost — wound ally, engage scepter.
            'act wounds ally and engages the attachment' => function () {
                $world = new TestWorld();
                [, $scepter, $technique, $ally] = $this->duel($world);

                $technique->actFromTechniqueWithId(
                    $world->game,
                    States::DUEL_CHOOSE_TECHNIQUE_02006,
                    'duelChooseTechnique_02006',
                    $ally->Id
                );

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($ally->Id, $wounds[0]->characterId, 'ally');
                Assert::same(1, $wounds[0]->wounds, 'one');
                Assert::same($scepter->Id, $wounds[0]->sourceId, 'source');

                $engages = $world->theah->queuedOfType(EventCardEngaged::class);
                Assert::count(1, $engages, 'engage');
                Assert::same($scepter->Id, $engages[0]->cardId, 'scepter');
                Assert::same([null], $world->game->gamestate->transitions, 'next state');
            },
        ];
    }
}
