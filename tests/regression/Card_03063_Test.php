<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03055;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03063;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03063;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventRenownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class Card_03063_Test extends TestCase
{
    public function name(): string
    {
        return '_03063 Smuggling Run';
    }

    public function tests(): array
    {
        return [
            'constructs a neutral crime scheme' => function () {
                $card = new _03063();
                Assert::instanceOf(Scheme::class, $card, 'scheme');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(52, $card->Initiative, 'initiative');
                Assert::same(0, $card->PanacheModifier, 'panache');
                Assert::true($card->hasFaction('Neutral'), 'Neutral');
                Assert::true($card->hasTrait('Cunning'), 'Cunning');
                Assert::true($card->hasTrait('Crime'), 'Crime');
                Assert::instanceOf(Action_03063::class, $card->getActions()[0], 'action');
            },

            // WHY: The tax is on the equipping player. City attachments stay at controller 0
            // until equipped, so the target's controller is the equipper. Your own equip,
            // Home, and a location with no Scoundrel of yours are not taxed. One Scoundrel is -1.
            'an opponent equipping opposite your scoundrel costs one more' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03063(), Game::LOCATION_PLAYER_HOME, 1);
                $scoundrel = $world->placeCharacter(new GenericCharacter('Scoundrel', ['Scoundrel']), Game::LOCATION_CITY_DOCKS, 1);
                $target = $world->placeCharacter(new GenericCharacter('Target'), Game::LOCATION_CITY_DOCKS, 2);
                $attachment = $world->placeCard(new _03055(), Game::LOCATION_HAND, 2);
                $explanations = [];
                Assert::same(-1, $scheme->getEquipDiscount($world->theah, $target, $attachment, $explanations), 'tax');

                $attachment->ControllerId = 1;
                $explanations = [];
                Assert::same(0, $scheme->getEquipDiscount($world->theah, $target, $attachment, $explanations), 'your equip');
                $attachment->ControllerId = 2;

                $scheme->Location = Game::LOCATION_HAND;
                $explanations = [];
                Assert::same(0, $scheme->getEquipDiscount($world->theah, $target, $attachment, $explanations), 'not chosen');
                $scheme->Location = Game::LOCATION_PLAYER_HOME;

                $target->Location = Game::LOCATION_PLAYER_HOME;
                $explanations = [];
                Assert::same(0, $scheme->getEquipDiscount($world->theah, $target, $attachment, $explanations), 'home');
                $target->Location = Game::LOCATION_CITY_DOCKS;

                $scoundrel->Location = Game::LOCATION_CITY_FORUM;
                $explanations = [];
                Assert::same(0, $scheme->getEquipDiscount($world->theah, $target, $attachment, $explanations), 'scoundrel elsewhere');

                $scoundrel->Location = Game::LOCATION_CITY_DOCKS;
                $attachment->ControllerId = 0;
                $explanations = [];
                Assert::same(-1, $scheme->getEquipDiscount($world->theah, $target, $attachment, $explanations), 'city attachment uses performer');
            },

            'resolve adds renown to the bazaar and the docks' => function () {
                $world = new TestWorld();
                $scheme = $world->placeCard(new _03063(), Game::LOCATION_PLAYER_HOME, 1);
                $resolve = new EventResolveScheme();
                $resolve->scheme = $scheme;
                $resolve->playerId = 1;
                $world->fireOn($scheme, $resolve);
                $locations = array_map(fn ($e) => $e->location, $world->theah->queuedOfType(EventRenownAddedToLocation::class));
                Assert::same([Game::LOCATION_CITY_BAZAAR, Game::LOCATION_CITY_DOCKS], $locations, 'bazaar and docks');
            },
        ];
    }
}
