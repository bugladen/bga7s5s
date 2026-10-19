<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04014;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04014;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskEndOfDay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04014_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04014';
    }

    public function tests(): array
    {
        return [
            // WHY: Continuous. Engaging the weapon does not setUsed. The finesse condition
            // names the source. A resolved action clears it only outside a duel.
            'engaging a weapon grants finesse for the action and can fire again' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _04014(), Game::LOCATION_PLAYER_HOME, 1);
                $hero = $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                $weapon = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 1);
                $hero->Attachments = [$weapon->Id];
                $weapon->AttachedToId = $hero->Id;
                /** @var Reaction_04014 $reaction */
                $reaction = $scheme->getReactions()[0];

                $issued = new EventChallengeIssued();
                $issued->challengerId = $hero->Id;
                $issued->theah = $world->theah;
                $reaction->handleEvent($issued);
                Assert::same('reaction', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'offered');

                $reaction->performReaction($world->game, 0, '', 'engage-' . $weapon->Id);
                Assert::false($reaction->Used, 'continuous');
                Assert::same($weapon->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'weapon engaged');
                Assert::same(2, $world->theah->queuedOfType(EventCharacterFinesseModifed::class)[0]->NewFinesse, 'plus one');
                Assert::true($hero->hasCondition(Game::FORGED_FOR_BATTLE_CONDITION), 'named');

                $world->game->globals->set(Game::IN_DUEL, true);
                $resolved = new EventActionResolved();
                $resolved->theah = $world->theah;
                $reaction->handleEvent($resolved);
                Assert::true($hero->hasCondition(Game::FORGED_FOR_BATTLE_CONDITION), 'kept during the duel');

                $world->game->globals->set(Game::IN_DUEL, false);
                $reaction->handleEvent($resolved);
                Assert::false($hero->hasCondition(Game::FORGED_FOR_BATTLE_CONDITION), 'cleared');

                $weapon->Engaged = true;
                $world->theah->takeQueuedEvents();
                $reaction->handleEvent($issued);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'engaged weapon is not eligible');

                $weapon->Engaged = false;
                $hero->addCondition(Game::FORGED_FOR_BATTLE_CONDITION);
                $reaction->performReaction($world->game, 0, '', 'pass');
                $dusk = new EventDuskEndOfDay();
                $dusk->theah = $world->theah;
                $reaction->handleEvent($issued);
                $reaction->performReaction($world->game, 0, '', 'engage-' . $weapon->Id);
                $reaction->handleEvent($dusk);
                Assert::false($hero->hasCondition(Game::FORGED_FOR_BATTLE_CONDITION), 'dusk clears');
            },
        ];
    }
}
