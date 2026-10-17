<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03028;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03028;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03028_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03028';
    }

    /**
     * @return array{0:_03028,1:Reaction_03028,2:GenericCharacter,3:_03019}
     */
    private function scene(TestWorld $world): array
    {
        $terence = $world->placeCharacter(new _03028(), Game::LOCATION_CITY_BAZAAR, 1);
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_BAZAAR, 2);
        $attachment = $world->placeCard(new _03019(), Game::LOCATION_CITY_BAZAAR, 2);
        /** @var Reaction_03028 $reaction */
        $reaction = $terence->getReactions()[0];
        return [$terence, $reaction, $host, $attachment];
    }

    private function equipped(TestWorld $world, int $characterId, int $attachmentId): EventAttachmentEquipped
    {
        $event = new EventAttachmentEquipped();
        $event->theah = $world->theah;
        $event->characterId = $characterId;
        $event->attachmentId = $attachmentId;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: He must be in the city at the Grand Bazaar. The attachment is real, and
            // the character who equipped it is still at the Bazaar. Draw spends. Pass does not.
            'draws after an equip at the Grand Bazaar' => function () {
                $world = new TestWorld();
                [$terence, $reaction, $host, $attachment] = $this->scene($world);
                $reaction->handleEvent($this->equipped($world, $host->Id, $attachment->Id));
                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['drawCard', 'pass'], $buttons, 'draw or pass');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'drawCard');
                Assert::same($terence->ControllerId, $world->theah->queuedOfType(EventCardDrawn::class)[0]->playerId, 'his controller');
                Assert::true($reaction->Used, 'spent');
            },

            'pass, a fake attachment, and anywhere else do not offer a draw' => function () {
                $world = new TestWorld();
                [$terence, $reaction, $host, $attachment] = $this->scene($world);
                $reaction->handleEvent($this->equipped($world, $host->Id, $attachment->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCardDrawn::class), 'no draw');

                $attachment->FakeAttachment = true;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($this->equipped($world, $host->Id, $attachment->Id));
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'fake');

                $attachment->FakeAttachment = false;
                $terence->Location = Game::LOCATION_CITY_DOCKS;
                $reaction->handleEvent($this->equipped($world, $host->Id, $attachment->Id));
                Assert::count(0, $world->theah->queuedEvents, 'he is not at the bazaar');
            },
        ];
    }
}
