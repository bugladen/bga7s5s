<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03cd05;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGambleSetup;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_03cd05_Test extends TestCase
{
    public function name(): string
    {
        return '_03cd05 Devil Jonah\'s Bones';
    }

    public function tests(): array
    {
        return [
            'constructs a Unique Artifact Trinket' => function () {
                $bones = new _03cd05();
                Assert::instanceOf(CityAttachment::class, $bones, 'CityAttachment');
                Assert::same(5, $bones->CityCardNumber, 'city number');
                Assert::same(1, $bones->WealthCost, 'wealth');
                Assert::true($bones->hasTrait('Artifact'), 'Artifact');
                Assert::true($bones->hasTrait('Corruption'), 'Corruption');
                Assert::true($bones->hasTrait('Trinket'), 'Trinket');
                Assert::true($bones->hasTrait('Unique'), 'Unique');
            },

            // WHY: abilityId is the card id (Joern / Stranahan). Any equip of this card wounds,
            // including an equip event that has not yet written AttachedToId.
            'equipping these bones wounds that character' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $bones = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 1);

                $equipped = new EventAttachmentEquipped();
                $equipped->attachmentId = $bones->Id;
                $equipped->characterId = $host->Id;
                $equipped->theah = $world->theah;
                $bones->handleEvent($equipped);

                $wound = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wound, 'wound');
                Assert::same($host->Id, $wound[0]->characterId, 'host');
                Assert::same($bones->Id, $wound[0]->sourceId, 'source');
                Assert::same(1, $wound[0]->wounds, 'one');
                Assert::same((string) $bones->Id, $wound[0]->abilityId, 'card id');
            },

            'equipping a different attachment does not wound' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $bones = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 1);

                $equipped = new EventAttachmentEquipped();
                $equipped->attachmentId = $other->Id;
                $equipped->characterId = $host->Id;
                $equipped->theah = $world->theah;
                $bones->handleEvent($equipped);

                Assert::count(0, $world->theah->queuedEvents, 'silent');
            },

            'the equipped character\'s gamble offers the top-or-bottom choice' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 2);
                $bones = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 2);
                $bones->AttachedToId = $host->Id;

                $setup = new EventGambleSetup();
                $setup->actorId = $host->Id;
                $setup->playerId = 1;
                $setup->theah = $world->theah;
                $bones->handleEvent($setup);

                $transition = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transition, 'choice');
                Assert::same('03cd05', $transition[0]->transition, 'state');
                Assert::same(2, $transition[0]->playerId, 'host controller');
                Assert::same($bones->Id, $transition[0]->sourceId, 'bones');
            },

            // WHY: AttachedToId > 0 counts as attached even if that character is gone.
            // The prompt then falls back to the gamble event's player.
            'a dangling attach still prompts, using the gamble player' => function () {
                $world = new TestWorld();
                $bones = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 1);
                $bones->AttachedToId = 4242;

                $setup = new EventGambleSetup();
                $setup->actorId = 4242;
                $setup->playerId = 1;
                $setup->theah = $world->theah;
                $bones->handleEvent($setup);

                $transition = $world->theah->queuedOfType(EventTransition::class);
                Assert::same(1, $transition[0]->playerId, 'event player');
            },

            'an unattached copy or a different actor does not prompt' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 1);
                $bones = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 1);

                $setup = new EventGambleSetup();
                $setup->actorId = $host->Id;
                $setup->theah = $world->theah;
                $bones->handleEvent($setup);
                Assert::count(0, $world->theah->queuedEvents, 'not attached');

                $bones->AttachedToId = $host->Id;
                $setup->actorId = $other->Id;
                $bones->handleEvent($setup);
                Assert::count(0, $world->theah->queuedEvents, 'other actor');
            },

            'the equipped character reveals one extra gamble card' => function () {
                $world = new TestWorld();
                $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $other = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_DOCKS, 2);
                $bones = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 1);
                $bones->AttachedToId = $host->Id;

                $explanations = [];
                Assert::same(1, $bones->getNumberOfGambleCardsToReveal($world->theah, $host, $explanations), '+1');
                Assert::count(1, $explanations, 'explained');
                Assert::contains($bones->getInjectCode(), $explanations[0], 'names the card');

                $explanations = [];
                Assert::same(0, $bones->getNumberOfGambleCardsToReveal($world->theah, $other, $explanations), 'other actor');
                $bones->AttachedToId = 0;
                $explanations = [];
                Assert::same(0, $bones->getNumberOfGambleCardsToReveal($world->theah, $host, $explanations), 'unattached');
            },

            // WHY: id 2 is the bottom. Any other id, including 1, forces the top.
            // The transition is the sole empty one, so nextState() is bare.
            'bottom reveal sets the global; anything else clears it' => function () {
                $world = new TestWorld();
                $bones = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->activePlayerId = 2;

                $bones->actFromCardWithId($world->game, States::DUEL_GAMBLE_SETUP_03CD05, '', '', 2);
                Assert::true($world->game->globals->get(Game::GAMBLE_REVEAL_FROM_BOTTOM), 'bottom');

                $bones->actFromCardWithId($world->game, States::DUEL_GAMBLE_SETUP_03CD05, '', '', 1);
                Assert::false($world->game->globals->get(Game::GAMBLE_REVEAL_FROM_BOTTOM), 'top');
                Assert::same([null, null], $world->game->gamestate->transitions, 'bare nextState');
            },

            'a different state does not touch the gamble global' => function () {
                $world = new TestWorld();
                $bones = $world->placeCard(new _03cd05(), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->globals->set(Game::GAMBLE_REVEAL_FROM_BOTTOM, true);

                $bones->actFromCardWithId($world->game, 0, '', '', 2);

                Assert::true($world->game->globals->get(Game::GAMBLE_REVEAL_FROM_BOTTOM), 'unchanged');
                Assert::count(0, $world->game->gamestate->transitions, 'no transition');
            },
        ];
    }
}
