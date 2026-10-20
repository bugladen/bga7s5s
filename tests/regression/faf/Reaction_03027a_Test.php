<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03027a;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownMovingBetweenLocations;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_03027a_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_03027a';
    }

    /**
     * @return array{0:_03027,1:Reaction_03027a,2:GenericCharacter}
     */
    private function scene(TestWorld $world): array
    {
        $odette = $world->placeCharacter(new _03027(), Game::LOCATION_CITY_DOCKS, 1);
        $fallen = $world->placeCharacter(new GenericCharacter('Fallen'), Game::LOCATION_CITY_DOCKS, 2);
        /** @var Reaction_03027a $reaction */
        $reaction = $odette->getReactions()[0];
        return [$odette, $reaction, $fallen];
    }

    private function destroyed(TestWorld $world, int $characterId): EventCharacterDestroyed
    {
        $event = new EventCharacterDestroyed();
        $event->theah = $world->theah;
        $event->characterId = $characterId;
        return $event;
    }

    public function tests(): array
    {
        return [
            // WHY: City, another character here. Offer when she is wounded or an adjacent
            // city has Renown. Pass returns before setUsed. Any other choice spends, and
            // heals first when she is wounded, even if the Renown location is not legal.
            'heals, and may move adjacent Renown onto her location' => function () {
                $world = new TestWorld();
                [$odette, $reaction, $fallen] = $this->scene($world);
                $odette->Wounds = 1;
                $world->theah->setLocationRenown(Game::LOCATION_CITY_FORUM, 2);
                $reaction->handleEvent($this->destroyed($world, $fallen->Id));
                Assert::count(1, $world->theah->queuedOfType(EventTransition::class), 'offered');

                $buttons = array_column($reaction->getReactionButtonProperties($world->theah), 'reaction');
                Assert::same(['moveFrom-' . Game::LOCATION_CITY_FORUM, 'healOnly', 'pass'], $buttons, 'forum, heal, pass');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveFrom-' . Game::LOCATION_CITY_FORUM);
                Assert::same($odette->Id, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->characterId, 'healed');
                $moving = $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class)[0];
                $removed = $world->theah->queuedOfType(EventRenownRemovedFromLocation::class)[0];
                $added = $world->theah->queuedOfType(EventRenownAddedToLocation::class)[0];
                Assert::same($moving->batchId, $removed->batchId, 'one batch');
                Assert::same($moving->batchId, $added->batchId, 'add shares it');
                Assert::true($added->isMove, 'this add is a move');
                Assert::same(Game::LOCATION_CITY_DOCKS, $added->location, 'onto her location');
                Assert::true($reaction->Used, 'spent');
            },

            'pass does not spend, and an illegal move still heals and spends' => function () {
                $world = new TestWorld();
                [$odette, $reaction, $fallen] = $this->scene($world);
                $odette->Wounds = 1;
                $reaction->handleEvent($this->destroyed($world, $fallen->Id));
                $reaction->performReaction($world->game, 0, $reaction->Id, 'pass');
                Assert::false($reaction->Used, 'pass');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'no heal');

                $reaction->performReaction($world->game, 0, $reaction->Id, 'moveFrom-' . Game::LOCATION_CITY_BAZAAR);
                Assert::count(1, $world->theah->queuedOfType(EventCharacterBeingHealed::class), 'heal still happens');
                Assert::count(0, $world->theah->queuedOfType(EventRenownMovingBetweenLocations::class), 'bazaar is not adjacent');
                Assert::true($reaction->Used, 'spent anyway');
            },

            'herself, home, or nothing to do does not offer' => function () {
                $world = new TestWorld();
                [$odette, $reaction, $fallen] = $this->scene($world);
                $reaction->handleEvent($this->destroyed($world, $odette->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not herself');

                $odette->Location = Game::LOCATION_PLAYER_HOME;
                $reaction->handleEvent($this->destroyed($world, $fallen->Id));
                Assert::count(0, $world->theah->queuedEvents, 'not in the city');

                $odette->Location = Game::LOCATION_CITY_DOCKS;
                $reaction->handleEvent($this->destroyed($world, $fallen->Id));
                Assert::count(0, $world->theah->queuedEvents, 'unwounded and no adjacent renown');
            },
        ];
    }
}
