<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04016;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\reactions\Reaction_04016;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Reaction_04016_Test extends TestCase
{
    public function name(): string
    {
        return 'Reaction_04016';
    }

    public function tests(): array
    {
        return [
            // WHY: Hunter or Berserker is not an equip restriction. It only gates the heal,
            // and only when that character is already wounded.
            'a wounded hunter or berserker may heal after equipping' => function () {
                $world = new TestWorld();
                $card = $world->placeCard(new _04016(), Game::LOCATION_CITY_DOCKS, 1);
                $hunter = $world->placeCharacter(new GenericCharacter('Hunter', ['Hunter']), Game::LOCATION_CITY_DOCKS, 1);
                $hunter->Wounds = 1;
                $card->AttachedToId = $hunter->Id;
                /** @var Reaction_04016 $reaction */
                $reaction = $card->getReactions()[0];

                $equipped = new EventAttachmentEquipped();
                $equipped->attachmentId = $card->Id;
                $equipped->characterId = $hunter->Id;
                $equipped->theah = $world->theah;
                $reaction->handleEvent($equipped);
                Assert::same($reaction->Id, $world->theah->queuedOfType(EventTransition::class)[0]->internalId, 'offered');

                $reaction->performReaction($world->game, 0, '', 'healWound');
                Assert::same($hunter->Id, $world->theah->queuedOfType(EventCharacterBeingHealed::class)[0]->characterId, 'healed');
                Assert::true($reaction->Used, 'used');

                $hero = $world->placeCharacter(new GenericCharacter('Hero'), Game::LOCATION_CITY_DOCKS, 1);
                $hero->Wounds = 1;
                $card->AttachedToId = $hero->Id;
                $reaction->Used = false;
                $world->theah->takeQueuedEvents();
                $equipped->characterId = $hero->Id;
                $reaction->handleEvent($equipped);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'not a hunter');

                $hunter->Wounds = 0;
                $card->AttachedToId = $hunter->Id;
                $equipped->characterId = $hunter->Id;
                $reaction->handleEvent($equipped);
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no wound');
            },
        ];
    }
}
