<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04047;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01049;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01160;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01186;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01186;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoving;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_01186_Test extends TestCase
{
    public function name(): string
    {
        return '_01186 Maryam Benu Pleroma';
    }

    /** @return array{0:_01186,1:_01160} */
    private function scene(TestWorld $world): array
    {
        $maryam = $world->placeCharacter(new _01186(), Game::LOCATION_CITY_DOCKS, 1);
        $risk = $world->placeCard(new _01160(), Game::LOCATION_HAND, 2);
        return [$maryam, $risk];
    }

    public function tests(): array
    {
        return [
            'constructs Negotiable CityCharacter Mercenary with Technique_01186' => function () {
                $maryam = new _01186();
                Assert::instanceOf(CityCharacter::class, $maryam, 'CityCharacter');
                Assert::instanceOf(IHasTechniques::class, $maryam, 'techniques');
                Assert::true($maryam->Negotiable, 'Negotiable');
                Assert::same(5, $maryam->Resolve, 'Resolve');
                Assert::same(4, $maryam->Combat, 'Combat');
                Assert::same(3, $maryam->Finesse, 'Finesse');
                Assert::same(0, $maryam->Influence, 'Influence');
                Assert::true($maryam->DashedInfluence, 'dashed Influence');
                Assert::same(6, $maryam->WealthCost, 'Wealth');
                Assert::same(10, $maryam->CityCardNumber, 'city #');
                Assert::true($maryam->hasTrait('Mercenary'), 'Mercenary');
                Assert::true($maryam->hasTrait('Duelist'), 'Duelist');
                Assert::true($maryam->hasTrait('Weapons Master'), 'Weapons Master');
                Assert::true($maryam->hasTrait('Ashur'), 'Ashur');
                Assert::instanceOf(Technique_01186::class, $maryam->getTechniques()[0], 'Technique');
            },

            'owns Technique_01186 when placed' => function () {
                $world = new TestWorld();
                $maryam = $world->placeCharacter(new _01186(), Game::LOCATION_CITY_DOCKS, 1);
                Assert::count(1, $maryam->getTechniques(), 'one');
                Assert::same($maryam->Id, $maryam->getTechniques()[0]->OwnerId, 'owner');
            },

            // WHY (journal 2026-04-08-10): first Risk-targeting each Day cancels; costs still paid.
            'cancels EventCharacterBeingWounded from a targeting Risk and stamps condition' => function () {
                $world = new TestWorld();
                [$maryam, $risk] = $this->scene($world);

                $event = new EventCharacterBeingWounded();
                $event->characterId = $maryam->Id;
                $event->sourceId = $risk->Id;
                $event->wounds = 1;
                $event->theah = $world->theah;
                $maryam->handleEvent($event);

                Assert::true($event->canceled, 'canceled');
                Assert::true($maryam->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED), 'used');
            },

            'cancels EventCharacterTargeted from a targeting Risk' => function () {
                $world = new TestWorld();
                [$maryam, $risk] = $this->scene($world);

                $event = new EventCharacterTargeted();
                $event->targetId = $maryam->Id;
                $event->sourceId = $risk->Id;
                $event->theah = $world->theah;
                $maryam->handleEvent($event);

                Assert::true($event->canceled, 'canceled');
                Assert::true($maryam->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED), 'used');
            },

            // WHY: Defending Honor queues the choose-target transition with no batchId.
            // Spending Imperviousness must remove that transition or the challenge still starts.
            'unbatched EventCharacterTargeted strips the follow-up transition and resolves the action' => function () {
                $world = new TestWorld();
                [$maryam, $risk] = $this->scene($world);
                $world->theah->queueEvent(EventFactory::createTransitionEvent($maryam->ControllerId, $risk->Id, '01078', 'ability'));

                $event = new EventCharacterTargeted();
                $event->targetId = $maryam->Id;
                $event->sourceId = $risk->Id;
                $event->playerId = $risk->ControllerId;
                $event->theah = $world->theah;
                $maryam->handleEvent($event);

                Assert::true($event->canceled, 'canceled');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'transition removed');
                $resolved = $world->theah->queuedOfType(EventActionResolved::class);
                Assert::count(1, $resolved, 'action resolved');
                Assert::same($risk->ControllerId, $resolved[0]->playerId, 'risk controller');
            },

            'cancels EventCardMoving from a targeting Risk' => function () {
                $world = new TestWorld();
                [$maryam, $risk] = $this->scene($world);

                $event = new EventCardMoving();
                $event->cardId = $maryam->Id;
                $event->sourceId = $risk->Id;
                $event->fromLocation = Game::LOCATION_CITY_DOCKS;
                $event->toLocation = Game::LOCATION_CITY_FORUM;
                $event->theah = $world->theah;
                $maryam->handleEvent($event);

                Assert::true($event->canceled, 'canceled');
            },

            'cancels EventCardEngaged from a targeting Risk' => function () {
                $world = new TestWorld();
                [$maryam, $risk] = $this->scene($world);

                $event = new EventCardEngaged();
                $event->cardId = $maryam->Id;
                $event->sourceId = $risk->Id;
                $event->theah = $world->theah;
                $maryam->handleEvent($event);

                Assert::true($event->canceled, 'canceled');
            },

            // WHY (journal 2026-09-29-01): canceling ChallengeIssued alone does not stop
            // Accept/Refuse — CHALLENGE_CANCELLED global is required.
            'cancels EventChallengeIssued and sets CHALLENGE_CANCELLED' => function () {
                $world = new TestWorld();
                [$maryam, $risk] = $this->scene($world);
                $challenger = $world->placeCharacter(new _01186(), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventChallengeIssued();
                $event->challengerId = $challenger->Id;
                $event->defenderId = $maryam->Id;
                $event->sourceId = $risk->Id;
                $event->theah = $world->theah;
                $maryam->handleEvent($event);

                Assert::true($event->canceled, 'canceled');
                Assert::true((bool) $world->game->globals->get(Game::CHALLENGE_CANCELLED, false), 'global');
            },

            // WHY (journal 2026-04-14-03): Impervious also covers Risk-sourced equipping.
            'cancels EventAttachmentEquipping from a targeting Risk and queues remove' => function () {
                $world = new TestWorld();
                [$maryam, $risk] = $this->scene($world);
                $att = $world->placeCard(new _01049(), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventAttachmentEquipping();
                $event->playerId = 2;
                $event->characterId = $maryam->Id;
                $event->attachmentId = $att->Id;
                $event->sourceId = $risk->Id;
                $event->theah = $world->theah;
                $maryam->handleEvent($event);

                Assert::true($event->canceled, 'canceled');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlay::class), 'remove');
            },

            'second Risk target the same Day is not canceled' => function () {
                $world = new TestWorld();
                [$maryam, $risk] = $this->scene($world);
                $maryam->addCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED);

                $event = new EventCharacterBeingWounded();
                $event->characterId = $maryam->Id;
                $event->sourceId = $risk->Id;
                $event->wounds = 1;
                $event->theah = $world->theah;
                $maryam->handleEvent($event);

                Assert::false($event->canceled, 'not canceled');
            },

            'ignores wounds with sourceId 0' => function () {
                $world = new TestWorld();
                [$maryam] = $this->scene($world);

                $event = new EventCharacterBeingWounded();
                $event->characterId = $maryam->Id;
                $event->sourceId = 0;
                $event->wounds = 1;
                $event->theah = $world->theah;
                $maryam->handleEvent($event);

                Assert::false($event->canceled, 'no cancel');
                Assert::false($maryam->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED), 'unused');
            },

            // WHY: Same Celerity immunity as Silver Spine — Forced cancel must honor
            // Risk::effectsCannotBeCancelled() or Imperviousness announces a no-op cancel.
            'Celerity targeting does not cancel or spend Imperviousness' => function () {
                $world = new TestWorld();
                [$maryam] = $this->scene($world);
                $celerity = $world->placeCard(new _04047(), Game::LOCATION_HAND, 2);

                $event = new EventCharacterTargeted();
                $event->targetId = $maryam->Id;
                $event->sourceId = $celerity->Id;
                $event->theah = $world->theah;
                $world->game->globals->set(Game::CHALLENGE_TYPE, Game::CELERITY_CHALLENGE_TYPE);
                $maryam->handleEvent($event);

                Assert::false($event->canceled, 'not canceled');
                Assert::false($maryam->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED), 'unused');
            },

            'Dusk clears the Impervious condition' => function () {
                $world = new TestWorld();
                [$maryam] = $this->scene($world);
                $maryam->addCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED);

                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $maryam->handleEvent($dusk);

                Assert::false($maryam->hasCondition(Game::MARYAM_BENU_PLEROMA_ABILITY_USED), 'cleared');
            },
        ];
    }
}
