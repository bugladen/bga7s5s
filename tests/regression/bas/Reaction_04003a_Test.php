<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04003;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04003a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04003a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04003a';
    }

    /**
     * @return array{0:TestWorld,1:_04003,2:Reaction_04003a,3:GenericCharacter,4:GenericCharacter}
     */
    private function ready(): array
    {
        $world = new TestWorld();
        $desideria = $world->placeCharacter(new _04003(), Game::LOCATION_CITY_DOCKS, 1);
        $thug = $world->placeCharacter(new GenericCharacter('Thug', ['Thug']), Game::LOCATION_CITY_DOCKS, 1);
        $source = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_04003a $reaction */
        $reaction = $desideria->getReactions()[0];
        return [$world, $desideria, $reaction, $thug, $source];
    }

    public function tests(): array
    {
        return [
            // WHY: Destroyed has no source. A lethal wound from an opponent is marked
            // first, then OR'd with IN_DUEL. En Garde and same location are required.
            // The Thug stays in the locker until the duel ends.
            'offers after a duel death or an opponent lethal wound, and defers the hand return' => function () {
                [$world, $desideria, $reaction, $thug, $source] = $this->ready();
                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $thug->Id;
                $destroyed->theah = $world->theah;
                $reaction->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'outside a duel with no mark');

                $wound = new EventCharacterWounded();
                $wound->characterId = $thug->Id;
                $wound->sourceId = $source->Id;
                $wound->wounds = 3;
                $wound->theah = $world->theah;
                $reaction->handleEvent($wound);
                $reaction->handleEvent($destroyed);
                $offer = $world->theah->queuedOfType(EventTransition::class)[0];
                Assert::same('reaction', $offer->transition, 'offered');
                Assert::same($reaction->Id, $offer->internalId, 'this reaction');

                $thug->Location = $world->game->getPlayerLockerName(1);
                $world->game->globals->set(Game::IN_DUEL, true);
                $reaction->performReaction($world->game, 0, '', 'recover');
                Assert::true($reaction->Used, 'used');
                Assert::same($thug->Id, (int) $world->game->globals->get('desideria_04003_pending_thug_1'), 'deferred');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToHand::class), 'not yet in hand');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingWounded::class), 'she is wounded');

                Reaction_04003a::flushPendingRecovers($world->game);
                Assert::same(0, (int) $world->game->globals->get('desideria_04003_pending_thug_1'), 'flushed');
                Assert::same($thug->Id, $world->theah->queuedOfType(EventCardRemovedFromLocker::class)[0]->cardId, 'left locker');
                Assert::same($thug->Id, $world->theah->queuedOfType(EventCardAddedToHand::class)[0]->cardId, 'to hand');

                $desideria->Engaged = true;
                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($destroyed);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'engaged');
            },

            'pass does not spend the reaction' => function () {
                [$world, , $reaction, $thug, $source] = $this->ready();
                $wound = new EventCharacterWounded();
                $wound->characterId = $thug->Id;
                $wound->sourceId = $source->Id;
                $wound->wounds = 3;
                $wound->theah = $world->theah;
                $reaction->handleEvent($wound);
                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $thug->Id;
                $destroyed->theah = $world->theah;
                $reaction->handleEvent($destroyed);
                $reaction->performReaction($world->game, 0, '', 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::same('done', $world->game->gamestate->transitions[0], 'done');
            },
        ];
    }
}
