<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03036;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03044;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03044;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03044_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03044';
    }

    /**
     * @return array{0:_03044,1:Reaction_03044,2:GenericCharacter,3:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe', ['Duelist']), Game::LOCATION_CITY_DOCKS, 2);
        $cloak = $world->placeCard(new _03044(), Game::LOCATION_CITY_DOCKS, 1);
        $cloak->AttachedToId = $host->Id;
        $world->theah->duelActor = $foe;
        $world->theah->duelOpponent = $host;
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->game->globals->set(Game::CHOSEN_TECHNIQUE_IS_MAIN, true);
        /** @var Reaction_03044 $reaction */
        $reaction = $cloak->getReactions()[0];
        return [$cloak, $reaction, $host, $foe];
    }

    private function activated(TestWorld $world, string $techniqueId): EventTechniqueActivated
    {
        $event = new EventTechniqueActivated();
        $event->theah = $world->theah;
        $event->playerId = 2;
        $event->techniqueId = $techniqueId;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: The equipped character must be the actor's adversary, and the activator
            // must be the actor's controller. Engage strips the pending resolve immediately.
            // An empty hand confirms the cancel without a discard prompt. Pass spends nothing.
            'an empty hand cancels the technique as soon as the cloak engages' => function () {
                $world = new TestWorld();
                [$cloak, $reaction] = $this->scene($world);
                $pending = new EventResolveTechnique();
                $pending->techniqueId = 'tech-1';
                $world->theah->queueEvent($pending);

                $reaction->handleEvent($this->activated($world, 'tech-1'));
                Assert::same('offer', $reaction->stage, 'offer');
                $offer = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same(Event::HIGH_PRIORITY, $offer->priority, 'ahead of resolve');
                Assert::same($cloak->ControllerId, $offer->playerId, 'cloak controller');
                Assert::same(['engage', 'pass'], array_column($reaction->getReactionButtonProperties($world->theah), 'reaction'), 'engage or pass');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::same('', $reaction->stage, 'passed');
                Assert::false($reaction->Used, 'pass does not spend');
                Assert::count(1, $world->theah->queuedOfType(EventResolveTechnique::class), 'resolve still queued');

                $reaction->handleEvent($this->activated($world, 'tech-1'));
                $world->theah->takeQueuedEvents();
                $world->theah->queueEvent($pending);
                $reaction->performReaction($world->game, 0, $reaction->Id, 'engage');
                Assert::same($cloak->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engaged');
                Assert::count(0, $world->theah->queuedOfType(EventResolveTechnique::class), 'stripped');
                Assert::same('tech-1', $world->theah->queuedOfType(EventTechniqueCanceled::class)[0]->techniqueId, 'canceled');
                Assert::true($world->theah->queuedOfType(EventTechniqueCanceled::class)[0]->countsAsMainTechnique, 'it was the main technique');
                Assert::true($reaction->Used, 'spent');
                Assert::same('', $reaction->stage, 'reset');
                Assert::false($reaction->pendingCancel, 'cancel confirmed');
            },

            // WHY: Discard puts the resolve and the calculate back. The wrong player
            // clicking a discard button does not discard. Accept Cancel confirms it.
            'a discard keeps the technique; accepting the cancel does not' => function () {
                $world = new TestWorld();
                [$cloak, $reaction, , $foe] = $this->scene($world);
                $hand = $world->placeCard(new _03036(), Game::LOCATION_HAND, 2);
                $reaction->handleEvent($this->activated($world, 'tech-2'));
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'engage');
                Assert::same('threat', $reaction->stage, 'adversary decides');
                Assert::false($reaction->Used, 'not spent until they choose');
                $threat = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same($foe->ControllerId, $threat->playerId, 'adversary');
                Assert::same(Event::HIGH_PRIORITY, $threat->priority, 'high');
                Assert::true($reaction->pendingCancel, 'waiting');

                $world->game->activePlayerId = 1;
                $reaction->performReaction($world->game, 0, $reaction->Id, 'discardHand-' . $hand->Id);
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromHand::class), 'not the adversary');
                Assert::same('threat', $reaction->stage, 'still waiting');

                $world->game->activePlayerId = 2;
                $reaction->performReaction($world->game, 0, $reaction->Id, 'discardHand-' . $hand->Id);
                Assert::same($hand->Id, $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0]->cardId, 'discarded');
                Assert::same('tech-2', $world->game->globals->get(Game::CHOSEN_TECHNIQUE), 'technique restored');
                Assert::true((bool) $world->game->globals->get(Game::CHOSEN_TECHNIQUE_IS_MAIN), 'main restored');
                Assert::count(1, $world->theah->queuedOfType(EventResolveTechnique::class), 'resolve restored');
                Assert::count(1, $world->theah->queuedOfType(EventDuelCalculateTechniqueValues::class), 'calculate restored');
                Assert::true($reaction->Used, 'spent');
                Assert::false($reaction->pendingCancel, 'kept');

                $reaction->Used = false;
                $reaction->handleEvent($this->activated($world, 'tech-3'));
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'engage');
                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'acceptCancel');
                Assert::same('tech-3', $world->theah->queuedOfType(EventTechniqueCanceled::class)[0]->techniqueId, 'accepted');
                Assert::count(0, $world->theah->queuedOfType(EventResolveTechnique::class), 'not restored');
                unset($cloak);
            },

            'a maneuver activation offers the same engage, and the cloak must be attached and unengaged' => function () {
                $world = new TestWorld();
                [$cloak, $reaction, $host] = $this->scene($world);
                $activated = new EventManeuverActivated();
                $activated->theah = $world->theah;
                $activated->playerId = 2;
                $activated->maneuverId = 'man-1';
                $reaction->handleEvent($activated);
                Assert::same('man-1', $reaction->ManeuverId, 'maneuver');
                Assert::same('', $reaction->TechniqueId, 'not a technique');

                $world->theah->takeQueuedEvents();
                $reaction->performReaction($world->game, 0, $reaction->Id, 'engage');
                $reaction->performReaction($world->game, 0, $reaction->Id, 'acceptCancel');
                Assert::same('man-1', $world->theah->queuedOfType(EventManeuverCanceled::class)[0]->maneuverId, 'maneuver canceled');

                $reaction->Used = false;
                $cloak->Engaged = true;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($activated);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'engaged cloak');

                $cloak->Engaged = false;
                $cloak->AttachedToId = 0;
                $reaction->handleEvent($activated);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not attached');

                $cloak->AttachedToId = $host->Id;
                $world->game->globals->set(Game::IN_DUEL, false);
                $reaction->handleEvent($this->activated($world, 'tech-x'));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not in a duel');
            },
        ];
    }
}
