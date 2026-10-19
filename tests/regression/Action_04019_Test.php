<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04019;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04019;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_04019_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_04019';
    }

    public function tests(): array
    {
        return [
            // WHY: The attachment must be a Melee Weapon or an Eisenfaust, and unengaged.
            // A ranged weapon does not qualify. The challenge type is not NORMAL, so the
            // performer is not auto-engaged: only the attachment is.
            'engages the melee weapon and issues a combat challenge' => function () {
                $world = new TestWorld();
                $risk = $world->placeCard(new _04019(), Game::LOCATION_HAND, 1);
                $performer = $world->placeCharacter(new GenericCharacter('Mine'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $ranged = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 1);
                $ranged->AttachedToId = $performer->Id;
                $performer->Attachments = [$ranged->Id];
                /** @var Action_04019 $action */
                $action = $risk->getActions()[0];
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'ranged is not melee');

                $ranged->addTrait($world->game, 'Melee', true);
                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'melee weapon');
                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'en garde');
                $performer->Engaged = false;

                $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_04019, '', $ranged->Id);
                Assert::same($ranged->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'attachment engaged');
                Assert::same(Game::NO_MORE_WORDS_CHALLENGE_TYPE, $world->game->globals->get(Game::CHALLENGE_TYPE), 'not a normal challenge');
                Assert::same(Game::STAT_COMBAT, $world->game->globals->get(Game::CHALLENGE_STAT), 'combat');
                Assert::same('04019_2', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pick the target next');
                Assert::same('attachmentChosen', $world->game->gamestate->transitions[0], 'next');
                Assert::same($foe->ControllerId, 2, 'foe stayed');
            },
        ];
    }
}
