<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01114;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01146;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01146b;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_01146b_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_01146b';
    }

    /** @return array{0:_01146,1:Reaction_01146b} */
    private function scene(TestWorld $world): array
    {
        $scheme = $world->placeCard(new _01146(), Game::LOCATION_PLAYER_HOME, 1);
        /** @var Reaction_01146b $reaction */
        $reaction = $scheme->getReactions()[1];
        return [$scheme, $reaction];
    }

    public function tests(): array
    {
        return [
            'buttons say Cancel Technique when TechniqueId set' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->TechniqueId = 'x';
                $reaction->ManeuverId = '';
                $ids = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['cancel', 'pass'], $ids, 'buttons');
                Assert::contains('Cancel Technique', $reaction->getReactionButtonProperties($world->theah)[0]['text'], 'label');
            },

            'buttons say Cancel Maneuver when ManeuverId set' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->ManeuverId = 'x';
                $reaction->TechniqueId = '';
                Assert::contains('Cancel Maneuver', $reaction->getReactionButtonProperties($world->theah)[0]['text'], 'label');
            },

            'offers HIGH_PRIORITY when adversary Technique activates in duel' => function () {
                $world = new TestWorld();
                [$scheme, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = new EventTechniqueActivated();
                $event->techniqueId = 'Technique_Foe';
                $event->ownerId = $foe->Id;
                $event->playerId = 2;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'offered');
                Assert::same(Event::HIGH_PRIORITY, $transitions[0]->priority, 'high');
                Assert::same($scheme->Id, $transitions[0]->sourceId, 'source');
                Assert::same('Technique_Foe', $reaction->TechniqueId, 'stored');
                Assert::same('', $reaction->ManeuverId, 'cleared maneuver');
            },

            'offers HIGH_PRIORITY when adversary Maneuver activates in duel' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = new EventManeuverActivated();
                $event->maneuverId = 'Maneuver_Foe';
                $event->ownerId = $foe->Id;
                $event->playerId = 2;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');
                Assert::same('Maneuver_Foe', $reaction->ManeuverId, 'stored');
                Assert::same('', $reaction->TechniqueId, 'cleared technique');
            },

            // WHY (production comment + Discord): LTSSD cancel is duel-only per rules team.
            'does not offer outside a duel' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::IN_DUEL, false);

                $event = new EventTechniqueActivated();
                $event->techniqueId = 'Technique_Foe';
                $event->ownerId = $foe->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'not in duel');
            },

            'does not offer for own Technique' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::IN_DUEL, true);

                $event = new EventTechniqueActivated();
                $event->techniqueId = 'Technique_Ally';
                $event->ownerId = $ally->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'own');
            },

            'does not offer once used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->game->globals->set(Game::IN_DUEL, true);
                $reaction->Used = true;

                $event = new EventTechniqueActivated();
                $event->techniqueId = 'Technique_Foe';
                $event->ownerId = $foe->Id;
                $event->theah = $world->theah;
                $reaction->handleEvent($event);

                Assert::count(0, $world->theah->queuedEvents, 'used');
            },

            // WHY (journal 2026-10-07-03): canceling effects still consumes the main Technique
            // slot — countsAsMainTechnique mirrors CHOSEN_TECHNIQUE_IS_MAIN read before delete.
            // Supersedes 2026-09-13-11 "technique_is_main stays 0".
            'cancel Technique clears resolve, queues TechniqueCanceled with countsAsMain' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $host = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $flint = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 2);
                $flint->AttachedToId = $host->Id;
                $host->Attachments[] = $flint->Id;
                $techniqueId = $flint->getTechniques()[0]->Id;

                $reaction->TechniqueId = $techniqueId;
                $reaction->ManeuverId = '';
                $world->game->globals->set(Game::CHOSEN_TECHNIQUE, $techniqueId);
                $world->game->globals->set(Game::CHOSEN_TECHNIQUE_IS_MAIN, true);

                $pending = new EventResolveTechnique();
                $pending->techniqueId = $techniqueId;
                $world->theah->queueEvent($pending);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'cancel');

                Assert::count(0, $world->theah->queuedOfType(EventResolveTechnique::class), 'cleared');
                $canceled = $world->theah->queuedOfType(EventTechniqueCanceled::class);
                Assert::count(1, $canceled, 'canceled');
                Assert::true($canceled[0]->countsAsMainTechnique, 'consumes main slot');
                Assert::same('', $reaction->TechniqueId, 'cleared id');
                Assert::true($reaction->Used, 'used');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_TECHNIQUE), 'global cleared');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_TECHNIQUE_IS_MAIN), 'is_main cleared');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'cancel Technique with non-main flag passes countsAsMain=false' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $host = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $flint = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 2);
                $flint->AttachedToId = $host->Id;
                $host->Attachments[] = $flint->Id;
                $techniqueId = $flint->getTechniques()[0]->Id;

                $reaction->TechniqueId = $techniqueId;
                $world->game->globals->set(Game::CHOSEN_TECHNIQUE_IS_MAIN, false);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'cancel');

                $canceled = $world->theah->queuedOfType(EventTechniqueCanceled::class);
                Assert::count(1, $canceled, 'canceled');
                Assert::false($canceled[0]->countsAsMainTechnique, 'non-main');
            },

            'cancel Maneuver clears resolve and queues ManeuverCanceled' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $risk = $world->placeCard(new _01114(), Game::LOCATION_HAND, 2);
                $maneuverId = $risk->getManeuvers()[0]->Id;

                $reaction->ManeuverId = $maneuverId;
                $reaction->TechniqueId = '';
                $world->game->globals->set(Game::CHOSEN_MANEUVER, $maneuverId);

                $pending = new EventResolveManeuver();
                $pending->maneuverId = $maneuverId;
                $world->theah->queueEvent($pending);

                $reaction->performReaction($world->game, 0, $reaction->Id, 'cancel');

                Assert::count(0, $world->theah->queuedOfType(EventResolveManeuver::class), 'cleared');
                Assert::count(1, $world->theah->queuedOfType(EventManeuverCanceled::class), 'canceled');
                Assert::same('', $reaction->ManeuverId, 'cleared id');
                Assert::true($reaction->Used, 'used');
                Assert::same(null, $world->game->globals->get(Game::CHOSEN_MANEUVER), 'global cleared');
            },

            'pass clears stored ids without marking Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->TechniqueId = 't';
                $reaction->ManeuverId = 'm';

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');

                Assert::same('', $reaction->TechniqueId, 'technique cleared');
                Assert::same('', $reaction->ManeuverId, 'maneuver cleared');
                Assert::false($reaction->Used, 'not used');
                Assert::count(0, $world->theah->queuedOfType(EventTechniqueCanceled::class), 'no cancel');
                Assert::same(['done'], $world->game->gamestate->transitions, 'done');
            },

            'Dusk resets Used' => function () {
                $world = new TestWorld();
                [, $reaction] = $this->scene($world);
                $reaction->setUsed($world->theah, true);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $reaction->handleEvent($dusk);

                Assert::false($reaction->Used, 'reset');
            },
        ];
    }
}
