<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\AttachmentAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Shakedown;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Shakedown_Att;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Shakedownb;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardHidden;

class Action_05Shakedownb_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Shakedownb';
    }

    /**
     * Blackmail shell on foe (controller 2); original Risk hidden.
     *
     * @return array{0:_05Shakedown_Att,1:Action_05Shakedownb,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $att = $world->placeCard(new _05Shakedown_Att(), Game::LOCATION_CITY_DOCKS, 2);
        $att->AttachedToId = $foe->Id;
        $foe->Attachments[] = $att->Id;
        $original = $world->placeCard(new _05Shakedown(), Game::LOCATION_PERMANENTLY_HIDDEN, 1);
        $att->OriginalCardId = $original->Id;
        $att->OwnerId = 1;

        /** @var Action_05Shakedownb $action */
        $action = $att->getActions()[0];
        return [$att, $action, $foe];
    }

    public function tests(): array
    {
        return [
            'is an AttachmentAction on the Blackmail shell' => function () {
                $att = new _05Shakedown_Att();
                Assert::same(-1, $att->InfluenceModifier, 'influence');
                Assert::true($att->FakeAttachment, 'fake');
                Assert::instanceOf(AttachmentAction::class, $att->getActions()[0], 'AttachmentAction');
            },

            'available to the equipped character controller when en garde' => function () {
                $world = new TestWorld();
                [, $action] = $this->scene($world);
                Assert::true($action->isAvailableToPlayer(2, $world->theah), 'controller');
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'not owner');
            },

            'unavailable when the equipped character is engaged' => function () {
                $world = new TestWorld();
                [, $action, $foe] = $this->scene($world);
                $foe->Engaged = true;
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'engaged');
            },

            'unavailable when not attached' => function () {
                $world = new TestWorld();
                $att = $world->placeCard(new _05Shakedown_Att(), Game::LOCATION_CITY_DOCKS, 2);
                /** @var Action_05Shakedownb $action */
                $action = $att->getActions()[0];
                Assert::false($action->isAvailableToPlayer(2, $world->theah), 'unattached');
            },

            // WHY: Engage cost then removeRiskAttachment (01102 destroy seat, no discard chooser).
            'engages the host and destroys the Blackmail shell' => function () {
                $world = new TestWorld();
                [$att, $action, $foe] = $this->scene($world);

                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 2;
                $triggered->theah = $world->theah;
                $action->handleEvent($triggered);

                Assert::same($foe->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage host');
                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'unequip');
                Assert::count(1, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'discard shell');
                Assert::count(1, $world->theah->queuedOfType(EventCardHidden::class), 'hide shell');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::same($att->Id, $world->theah->queuedOfType(EventAttachmentUnequipped::class)[0]->attachmentId, 'this shell');
            },
        ];
    }
}
