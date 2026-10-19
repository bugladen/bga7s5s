<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04cd02;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class Card_04cd02_Test extends TestCase
{
    public function name(): string
    {
        return '_04cd02 Jack Trades';
    }

    public function tests(): array
    {
        return [
            'is a mercenary hero factotum' => function () {
                $card = new _04cd02();
                Assert::instanceOf(CityCharacter::class, $card, 'CityCharacter');
                Assert::same(2, $card->CityCardNumber, 'city number');
                Assert::same(2, $card->Resolve, 'resolve');
                Assert::false($card->Negotiable, 'not negotiable');
                Assert::same([], $card->CopiedTraits, 'nothing copied yet');
                Assert::true($card->hasTrait('Factotum'), 'Factotum');
                Assert::false($card->hasTrait('Diplomat'), 'no diplomat yet');
            },

            // WHY: EventCardMoved runs before Location is written. A card moving in is
            // read off the event; a card moving out is excluded even though it is still listed.
            // Only Diplomat, Duelist, Pirate, and Zealot copy, and only from his controller.
            'gains a copyable trait from an ally arriving and loses it when they leave' => function () {
                $world = new TestWorld();
                $jack = $world->placeCharacter(new _04cd02(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Diplomat', 'Mercenary']), Game::LOCATION_CITY_FORUM, 1);

                $arrive = new EventCardMoved();
                $arrive->cardId = $ally->Id;
                $arrive->fromLocation = Game::LOCATION_CITY_FORUM;
                $arrive->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jack, $arrive);

                Assert::true($jack->hasTrait('Diplomat'), 'gained');
                Assert::same(['Diplomat'], $jack->CopiedTraits, 'only the copyable trait');
                Assert::same(Game::LOCATION_CITY_FORUM, $ally->Location, 'location not written yet');

                $ally->Location = Game::LOCATION_CITY_DOCKS;
                $leave = new EventCardMoved();
                $leave->cardId = $ally->Id;
                $leave->fromLocation = Game::LOCATION_CITY_DOCKS;
                $leave->toLocation = Game::LOCATION_CITY_FORUM;
                $world->fireOn($jack, $leave);

                Assert::false($jack->hasTrait('Diplomat'), 'lost');
                Assert::same([], $jack->CopiedTraits, 'grant cleared');
            },

            'an opponent at his location does not grant, and a trait he did not copy is kept' => function () {
                $world = new TestWorld();
                $jack = $world->placeCharacter(new _04cd02(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe', ['Pirate']), Game::LOCATION_CITY_FORUM, 2);
                $jack->addTrait($world->game, 'Diplomat');

                $arrive = new EventCardMoved();
                $arrive->cardId = $foe->Id;
                $arrive->fromLocation = Game::LOCATION_CITY_FORUM;
                $arrive->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jack, $arrive);

                Assert::false($jack->hasTrait('Pirate'), 'opponent pirate');
                Assert::true($jack->hasTrait('Diplomat'), 'outside grant stays');
                Assert::same([], $jack->CopiedTraits, 'he did not grant it');
            },

            'home uses his own characters, and an opponent arriving home is ignored' => function () {
                $world = new TestWorld();
                $jack = $world->placeCharacter(new _04cd02(), Game::LOCATION_PLAYER_HOME, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Zealot']), Game::LOCATION_PLAYER_HOME, 1);

                $refresh = new EventCardMoved();
                $refresh->cardId = $jack->Id;
                $refresh->fromLocation = Game::LOCATION_CITY_DOCKS;
                $refresh->toLocation = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($jack, $refresh);
                Assert::true($jack->hasTrait('Zealot'), 'ally at home');

                $foe = $world->placeCharacter(new GenericCharacter('Foe', ['Duelist']), Game::LOCATION_CITY_DOCKS, 2);
                $intrude = new EventCardMoved();
                $intrude->cardId = $foe->Id;
                $intrude->fromLocation = Game::LOCATION_CITY_DOCKS;
                $intrude->toLocation = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($jack, $intrude);
                Assert::false($jack->hasTrait('Duelist'), 'opponent home arrival skipped');
                Assert::true($jack->hasTrait('Zealot'), 'ally grant remains');
            },

            'a destroyed ally is excluded even though they are still at the location' => function () {
                $world = new TestWorld();
                $jack = $world->placeCharacter(new _04cd02(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Duelist']), Game::LOCATION_CITY_DOCKS, 1);
                $refresh = new EventCardMoved();
                $refresh->cardId = $jack->Id;
                $refresh->fromLocation = Game::LOCATION_CITY_FORUM;
                $refresh->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jack, $refresh);
                Assert::true($jack->hasTrait('Duelist'), 'before');

                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $ally->Id;
                $world->fireOn($jack, $destroyed);
                Assert::false($jack->hasTrait('Duelist'), 'excluded');
                Assert::same(Game::LOCATION_CITY_DOCKS, $ally->Location, 'not moved yet');
            },

            'a locker copy of an ally does not grant' => function () {
                $world = new TestWorld();
                $jack = $world->placeCharacter(new _04cd02(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Pirate']), Game::LOCATION_CITY_DOCKS, 1);
                $world->game->discardOrLockerByCardId[$ally->Id] = true;

                $refresh = new EventCardMoved();
                $refresh->cardId = $jack->Id;
                $refresh->fromLocation = Game::LOCATION_CITY_FORUM;
                $refresh->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jack, $refresh);
                Assert::false($jack->hasTrait('Pirate'), 'locker');
            },

            // WHY: While he is uncontrolled the passive does not listen, except his own
            // muster/recruit, which pass the event player as the controller override.
            // Override 0 clears grants. Override 1 copies that player's allies.
            'uncontrolled jack ignores movement, and muster uses the event player' => function () {
                $world = new TestWorld();
                $jack = $world->placeCharacter(new _04cd02(), Game::LOCATION_CITY_DOCKS, 0);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Diplomat']), Game::LOCATION_CITY_FORUM, 1);
                $jack->addTrait($world->game, 'Pirate');
                $jack->CopiedTraits = ['Pirate'];

                $arrive = new EventCardMoved();
                $arrive->cardId = $ally->Id;
                $arrive->fromLocation = Game::LOCATION_CITY_FORUM;
                $arrive->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jack, $arrive);
                Assert::same(['Pirate'], $jack->CopiedTraits, 'move ignored');

                $clear = new EventCharacterMustered();
                $clear->characterId = $jack->Id;
                $clear->playerId = 0;
                $clear->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jack, $clear);
                Assert::same([], $jack->CopiedTraits, 'override 0 clears');
                Assert::false($jack->hasTrait('Pirate'), 'grant removed');

                $ally->Location = Game::LOCATION_CITY_DOCKS;
                $muster = new EventCharacterMustered();
                $muster->characterId = $jack->Id;
                $muster->playerId = 1;
                $muster->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jack, $muster);
                Assert::true($jack->hasTrait('Diplomat'), 'override controller');
                Assert::same(0, $jack->ControllerId, 'his own controller unchanged');
            },

            'recruiting him or an ally at his location refreshes the copy' => function () {
                $world = new TestWorld();
                $jack = $world->placeCharacter(new _04cd02(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally', ['Pirate']), Game::LOCATION_CITY_DOCKS, 1);

                $recruited = new EventCharacterRecruited();
                $recruited->characterId = $ally->Id;
                $recruited->playerId = 1;
                $world->fireOn($jack, $recruited);
                Assert::true($jack->hasTrait('Pirate'), 'ally recruit');

                $jack->Location = Game::LOCATION_CITY_FORUM;
                $self = new EventCharacterRecruited();
                $self->characterId = $jack->Id;
                $self->playerId = 1;
                $world->fireOn($jack, $self);
                Assert::false($jack->hasTrait('Pirate'), 'he left, ally is still at the docks');
            },
        ];
    }
}
