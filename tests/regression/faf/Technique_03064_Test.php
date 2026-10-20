<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03064;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueCanceled;

class Technique_03064_Test extends TestCase
{
    public function name(): string
    {
        return 'Technique_03064';
    }

    public function tests(): array
    {
        return [
            // WHY: Gambling, the harpoon unengaged, and the equipped character is the actor.
            // Resolve engages the harpoon, drops Finesse by 1, and stamps the condition.
            // Cancel and duel end put the Finesse back. A second end does not.
            'harpoons the adversary until the duel ends' => function () {
                $world = new TestWorld();
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $harpoon = $world->placeCard(new _03064(), Game::LOCATION_CITY_DOCKS, 1);
                $harpoon->AttachedToId = $actor->Id;
                $actor->Attachments[] = $harpoon->Id;
                $technique = $harpoon->getTechniques()[0];
                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);

                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not gambled');
                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'available');
                $harpoon->Engaged = true;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'engaged');
                $harpoon->Engaged = false;
                $world->theah->duelActor = $foe;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not actor');
                $world->theah->duelActor = $actor;

                $resolve = new EventResolveTechnique();
                $resolve->theah = $world->theah;
                $resolve->techniqueId = $technique->Id;
                $resolve->playerId = 1;
                $technique->handleEvent($resolve);
                Assert::same($harpoon->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage');
                $finesse = $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0];
                Assert::same($foe->ModifiedFinesse - 1, $finesse->NewFinesse, 'minus one');
                Assert::true($foe->hasCondition(Game::HARPOON_CONDITION), 'condition');

                $cancel = new EventTechniqueCanceled();
                $cancel->theah = $world->theah;
                $cancel->techniqueId = $technique->Id;
                $technique->handleEvent($cancel);
                Assert::false($foe->hasCondition(Game::HARPOON_CONDITION), 'cancel cleared');
                Assert::same($foe->ModifiedFinesse + 1, $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[1]->NewFinesse, 'restored');

                $technique->handleEvent($resolve);
                $end = new EventDuelEnd();
                $end->theah = $world->theah;
                $technique->handleEvent($end);
                Assert::false($foe->hasCondition(Game::HARPOON_CONDITION), 'duel end');

                $technique->handleEvent($resolve);
                $world->game->discardOrLockerByCardId[$foe->Id] = true;
                $technique->handleEvent($end);
                Assert::true($foe->hasCondition(Game::HARPOON_CONDITION), 'locker keeps the stamp');
                $world->game->discardOrLockerByCardId[$foe->Id] = false;
                $before = count($world->theah->queuedOfType(EventCharacterFinesseModifed::class));
                $technique->handleEvent($end);
                Assert::same($before, count($world->theah->queuedOfType(EventCharacterFinesseModifed::class)), 'forgotten id does not restore');
                Assert::true($foe->hasCondition(Game::HARPOON_CONDITION), 'still stamped');
            },
        ];
    }
}
