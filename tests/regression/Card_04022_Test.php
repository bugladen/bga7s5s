<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04022;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;

class Card_04022_Test extends TestCase
{
    public function name(): string
    {
        return '_04022 Axelle Hubert';
    }

    public function tests(): array
    {
        return [
            'is a diplomat scoundrel' => function () {
                $card = new _04022();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(4, $card->Resolve, 'resolve');
                Assert::same(2, $card->Finesse, 'finesse');
                Assert::true($card->hasTrait('Diplomat'), 'Diplomat');
                Assert::true($card->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::false($card->DuelistAuraApplied, 'aura starts off');
                Assert::count(1, $card->getReactions(), 'reaction');
            },

            // WHY: Flag ±1, not an absolute rewrite, because attachments also touch the
            // modified stats. A Duelist moving in is not in the location list yet, so the
            // event card is checked separately. Controller 0 returns before any stamp.
            // Home is shared, so an opponent arriving there does not count.
            'aura turns on for a duelist moving in and off when they leave' => function () {
                $world = new TestWorld();
                $axelle = $world->placeCharacter(new _04022(), Game::LOCATION_CITY_DOCKS, 1);
                $duelist = $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_PLAYER_HOME, 1);

                $enter = new EventCardMoved();
                $enter->cardId = $duelist->Id;
                $enter->fromLocation = Game::LOCATION_PLAYER_HOME;
                $enter->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($axelle, $enter);
                Assert::true($axelle->DuelistAuraApplied, 'on');
                $finesse = $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0];
                $influence = $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0];
                Assert::same($axelle->ModifiedFinesse, $finesse->OldFinesse, 'finesse old');
                Assert::same($axelle->ModifiedFinesse + 1, $finesse->NewFinesse, 'finesse +1');
                Assert::same($axelle->ModifiedInfluence + 1, $influence->NewInfluence, 'influence +1');

                $world->theah->takeQueuedEvents();
                $leave = new EventCardMoved();
                $leave->cardId = $duelist->Id;
                $leave->fromLocation = Game::LOCATION_CITY_DOCKS;
                $leave->toLocation = Game::LOCATION_CITY_FORUM;
                $world->fireOn($axelle, $leave);
                Assert::false($axelle->DuelistAuraApplied, 'off');
                Assert::same($axelle->ModifiedFinesse - 1, $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0]->NewFinesse, 'finesse -1');

                $axelle->ControllerId = 0;
                $world->theah->takeQueuedEvents();
                $world->fireOn($axelle, $enter);
                Assert::false($axelle->DuelistAuraApplied, 'uncontrolled');
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'no stamp');
            },

            // WHY: Approach may not have copied ControllerId onto the card yet, so the
            // event player is the controller. Blanking must drop a stuck +1. Locker
            // skips the update entirely.
            'approach uses the event player, and blanking clears a stuck aura' => function () {
                $world = new TestWorld();
                $axelle = $world->placeCharacter(new _04022(), Game::LOCATION_APPROACH, 0);
                $world->placeCharacter(new GenericCharacter('Duelist', ['Duelist']), Game::LOCATION_PLAYER_HOME, 1);
                $approach = new EventApproachCharacterPlayed();
                $approach->characterId = $axelle->Id;
                $approach->playerId = 1;
                $world->fireOn($axelle, $approach);
                Assert::true($axelle->DuelistAuraApplied, 'approach player');
                Assert::same(0, $axelle->ControllerId, 'card controller still unset');

                $axelle->ControllerId = 1;
                $axelle->onAbilitiesBlanked($world->theah);
                Assert::false($axelle->DuelistAuraApplied, 'blanked');
                $axelle->onAbilitiesUnblanked($world->theah);
                Assert::false($axelle->DuelistAuraApplied, 'still on approach, not home');

                $axelle->Location = Game::LOCATION_PLAYER_HOME;
                $axelle->onAbilitiesUnblanked($world->theah);
                Assert::true($axelle->DuelistAuraApplied, 'home duelist after unblank');

                $axelle->DuelistAuraApplied = true;
                $axelle->ControllerId = 0;
                $world->theah->takeQueuedEvents();
                $axelle->onAbilitiesBlanked($world->theah);
                Assert::true($axelle->DuelistAuraApplied, 'blank with controller 0 does not clear');

                $axelle->ControllerId = 1;
                $world->game->forceInDiscardOrLocker = true;
                $world->theah->takeQueuedEvents();
                $moved = new EventCardMoved();
                $moved->cardId = $axelle->Id;
                $moved->fromLocation = Game::LOCATION_PLAYER_HOME;
                $moved->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($axelle, $moved);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'locker skips');
            },
        ];
    }
}
