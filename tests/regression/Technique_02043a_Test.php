<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02040;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02043;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\techniques\Technique_02043a;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelNewRound;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;

class Technique_02043a_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_02043a';
    }

    /**
     * @return array{0:_02043,1:Technique_02043a}
     */
    private function scene(TestWorld $world): array
    {
        $twins = $world->placeCharacter(new _02043(), Game::LOCATION_CITY_DOCKS, 1);
        $world->game->globals->set(Game::IN_DUEL, true);
        $world->theah->duelActor = $twins;
        /** @var Technique_02043a $technique */
        $technique = $twins->getTechniques()[0];
        return [$twins, $technique];
    }

    private function perform(TestWorld $world, Technique_02043a $technique, string $maneuverId): void
    {
        $event = new EventResolveManeuver();
        $event->maneuverId = $maneuverId;
        $event->theah = $world->theah;
        $technique->handleEvent($event);
    }

    public function tests(): array
    {
        return [
            'is a once-per-day Technique that survives the end of the duel' => function () {
                $technique = new Technique_02043a();
                Assert::instanceOf(Technique::class, $technique, 'Technique');
                Assert::true($technique->ResetOnDayEnd, 'day');
                Assert::false($technique->ResetOnDuelEnd, 'not duel');
            },

            'available only in a duel after the twins performed the maneuver' => function () {
                $world = new TestWorld();
                [$twins, $technique] = $this->scene($world);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'before');

                $this->perform($world, $technique, 'maneuver-1');
                Assert::true($technique->maneuverPerformed, 'flag');
                Assert::same('maneuver-1', $technique->lastManeuverId, 'id');
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'after');

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'outside duel');
                $world->game->globals->set(Game::IN_DUEL, true);

                $world->theah->duelActor = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'other actor');
                $world->theah->duelActor = $twins;
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'twins again');
            },

            'a maneuver resolved by someone else does not arm the technique' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $world->theah->duelActor = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);

                $this->perform($world, $technique, 'theirs');

                Assert::false($technique->maneuverPerformed, 'not theirs');
                Assert::same('', $technique->lastManeuverId, 'blank');
            },

            // WHY: The copy is a new maneuver instance on the twins (own id), not a second
            // resolve of the original. Locker send waits for end of round so delayed maneuvers still fire.
            'resolve clones the maneuver and defers the locker send' => function () {
                $world = new TestWorld();
                [$twins, $technique] = $this->scene($world);
                $risk = $world->placeCard(new _02041(), Game::LOCATION_DUELING_LINE, 1);
                $original = $risk->getManeuvers()[0];
                $world->theah->combatCardsForCurrentRound = [$risk];
                $world->theah->duelOpponent = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $this->perform($world, $technique, $original->Id);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($technique->pendingLockerSend, 'pending');
                Assert::same((string) $risk->Id, $technique->lockerCombatCardId, 'combat card');
                Assert::count(1, $twins->getManeuvers(), 'clone added');
                $clone = $twins->getManeuvers()[0];
                Assert::same($twins->Id . '_Maneuver_02041', $clone->Id, 'clone id');
                Assert::same($clone->Id, $technique->clonedManeuverId, 'remembered');
                Assert::false($clone->Id === $original->Id, 'not the original id');

                $activated = $world->theah->queuedOfType(EventManeuverActivated::class);
                $resolved = $world->theah->queuedOfType(EventResolveManeuver::class);
                $calcs = $world->theah->queuedOfType(EventDuelCalculateManeuverValues::class);
                Assert::count(1, $activated, 'activated');
                Assert::same($clone->Id, $activated[0]->maneuverId, 'activate id');
                Assert::same($twins->Id, $activated[0]->ownerId, 'owner');
                Assert::count(1, $resolved, 'resolve');
                Assert::same($clone->Id, $resolved[0]->maneuverId, 'resolve id');
                Assert::same($world->theah->duelOpponent->Id, $resolved[0]->adversaryId, 'adversary');
                Assert::count(1, $calcs, 'calc');
                Assert::same($clone->Id, $calcs[0]->maneuverId, 'calc id');
                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'not yet');
            },

            'end of round sends the combat card to the locker' => function () {
                $world = new TestWorld();
                [$twins, $technique] = $this->scene($world);
                $technique->pendingLockerSend = true;
                $technique->lockerCombatCardId = '55';
                $technique->maneuverPerformed = true;

                $event = new EventDuelEndOfRound();
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->pendingLockerSend, 'spent');
                Assert::true($technique->maneuverPerformed, 'still armed until new round');
                $sent = $world->theah->queuedOfType(EventCardSentToLocker::class);
                Assert::count(1, $sent, 'locker');
                Assert::same(55, $sent[0]->cardId, 'card');
                Assert::same($twins->ControllerId, $sent[0]->playerId, 'player');
            },

            // WHY: New Round clears the pending locker flag without sending. End of round is the send.
            'new round clears the copy and does not send a still-pending card' => function () {
                $world = new TestWorld();
                [$twins, $technique] = $this->scene($world);
                $risk = $world->placeCard(new _02041(), Game::LOCATION_DUELING_LINE, 1);
                $this->perform($world, $technique, $risk->getManeuvers()[0]->Id);
                $world->theah->combatCardsForCurrentRound = [$risk];
                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                Assert::count(1, $twins->getManeuvers(), 'cloned');
                $world->theah->takeQueuedEvents();

                $event = new EventDuelNewRound();
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->maneuverPerformed, 'cleared');
                Assert::same('', $technique->lastManeuverId, 'id');
                Assert::false($technique->pendingLockerSend, 'pending dropped');
                Assert::same('', $technique->clonedManeuverId, 'clone id');
                Assert::count(0, $twins->getManeuvers(), 'clone removed');
                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'not sent');
            },

            'duel end sends a still-pending card and removes the clone' => function () {
                $world = new TestWorld();
                [$twins, $technique] = $this->scene($world);
                $technique->Used = true;
                $risk = $world->placeCard(new _02041(), Game::LOCATION_DUELING_LINE, 1);
                $this->perform($world, $technique, $risk->getManeuvers()[0]->Id);
                $world->theah->combatCardsForCurrentRound = [$risk];
                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                $world->theah->takeQueuedEvents();

                $event = new EventDuelEnd();
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::count(1, $world->theah->queuedOfType(EventCardSentToLocker::class), 'sent');
                Assert::false($technique->pendingLockerSend, 'cleared');
                Assert::count(0, $twins->getManeuvers(), 'clone removed');
                Assert::true($technique->Used, 'still used until dusk');
            },

            'dusk clears Used because the technique resets at day end' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $technique->Used = true;

                $event = new EventDuskEndOfDay();
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->Used, 'reset');
            },

            'cancel drops the pending locker send and the clone' => function () {
                $world = new TestWorld();
                [$twins, $technique] = $this->scene($world);
                $risk = $world->placeCard(new _02041(), Game::LOCATION_DUELING_LINE, 1);
                $this->perform($world, $technique, $risk->getManeuvers()[0]->Id);
                $world->theah->combatCardsForCurrentRound = [$risk];
                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);
                $world->theah->takeQueuedEvents();

                $event = new EventTechniqueCanceled();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->pendingLockerSend, 'dropped');
                Assert::same('', $technique->clonedManeuverId, 'forgotten');
                Assert::count(0, $twins->getManeuvers(), 'clone removed');
                Assert::count(0, $world->theah->queuedOfType(EventCardSentToLocker::class), 'not sent');
                Assert::true($technique->maneuverPerformed, 'cancel does not clear the arm flag');
            },

            'resolve with no matching maneuver still marks the combat card for the locker' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $risk = $world->placeCard(new _02040(), Game::LOCATION_DUELING_LINE, 1);
                $world->theah->combatCardsForCurrentRound = [$risk];
                $this->perform($world, $technique, 'missing');

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::true($technique->pendingLockerSend, 'pending');
                Assert::same((string) $risk->Id, $technique->lockerCombatCardId, 'card');
                Assert::same('', $technique->clonedManeuverId, 'no clone');
                Assert::count(0, $world->theah->queuedOfType(EventManeuverActivated::class), 'no activate');
            },

            'an opponent combat card is not the one sent to the locker' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $risk = $world->placeCard(new _02041(), Game::LOCATION_DUELING_LINE, 2);
                $world->theah->combatCardsForCurrentRound = [$risk];
                $this->perform($world, $technique, $risk->getManeuvers()[0]->Id);

                $event = new EventResolveTechnique();
                $event->techniqueId = $technique->Id;
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->pendingLockerSend, 'not theirs');
                Assert::count(0, $world->theah->queuedOfType(EventManeuverActivated::class), 'no clone');
            },

            'ignores resolve of a different technique' => function () {
                $world = new TestWorld();
                [, $technique] = $this->scene($world);
                $this->perform($world, $technique, 'maneuver-1');

                $event = new EventResolveTechnique();
                $event->techniqueId = 'other';
                $event->theah = $world->theah;
                $technique->handleEvent($event);

                Assert::false($technique->pendingLockerSend, 'not this technique');
                Assert::count(0, $world->theah->queuedEvents, 'none');
            },
        ];
    }
}
