<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04032;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;

class Card_04032_Test extends TestCase
{
    public function name(): string
    {
        return '_04032 Giacinto';
    }

    public function tests(): array
    {
        return [
            'is an academic zealot villain' => function () {
                $card = new _04032();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::same(4, $card->Resolve, 'resolve');
                Assert::same(3, $card->Finesse, 'finesse');
                Assert::true($card->hasTrait('Academic'), 'Academic');
                Assert::true($card->hasTrait('Villain'), 'Villain');
                Assert::true($card->hasTrait('Zealot'), 'Zealot');
                Assert::count(1, $card->getActions(), 'action');
            },

            // WHY: Opposing Sorcerers at his city location lose 1 Influence via a condition,
            // so a second application does not stack. Home never counts. A Sorcerer moving
            // in is not listed yet. Blanking and destruction clear every stamp. Controller
            // 0 applies nothing.
            'reduces an opposing sorcerer and clears the stamp when he leaves' => function () {
                $world = new TestWorld();
                $giacinto = $world->placeCharacter(new _04032(), Game::LOCATION_CITY_DOCKS, 1);
                $sorcerer = $world->placeCharacter(new GenericCharacter('Witch', ['Sorcerer']), Game::LOCATION_CITY_FORUM, 2);

                $enter = new EventCardMoved();
                $enter->cardId = $sorcerer->Id;
                $enter->fromLocation = Game::LOCATION_CITY_FORUM;
                $enter->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($giacinto, $enter);
                Assert::true($sorcerer->hasCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION), 'stamped');
                $reduced = $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0];
                Assert::same($sorcerer->ModifiedInfluence - 1, $reduced->NewInfluence, '-1');

                $world->theah->takeQueuedEvents();
                $world->fireOn($giacinto, $enter);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterInfluenceModified::class), 'does not stack');

                $leave = new EventCardMoved();
                $leave->cardId = $giacinto->Id;
                $leave->fromLocation = Game::LOCATION_CITY_DOCKS;
                $leave->toLocation = Game::LOCATION_CITY_FORUM;
                $sorcerer->Location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($giacinto, $leave);
                Assert::false($sorcerer->hasCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION), 'cleared on leave');
                Assert::same($sorcerer->ModifiedInfluence + 1, $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0]->NewInfluence, '+1 back');

                $sorcerer->addCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION);
                $world->theah->takeQueuedEvents();
                $giacinto->onAbilitiesBlanked($world->theah);
                Assert::false($sorcerer->hasCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION), 'blank clears');

                $sorcerer->addCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION);
                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $giacinto->Id;
                $world->fireOn($giacinto, $destroyed);
                Assert::false($sorcerer->hasCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION), 'death clears');

                $giacinto->ControllerId = 0;
                $world->theah->takeQueuedEvents();
                $world->fireOn($giacinto, $enter);
                Assert::false($sorcerer->hasCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION), 'uncontrolled');

                $giacinto->ControllerId = 1;
                $giacinto->Location = Game::LOCATION_PLAYER_HOME;
                $home = new EventCardMoved();
                $home->cardId = $sorcerer->Id;
                $home->fromLocation = Game::LOCATION_CITY_DOCKS;
                $home->toLocation = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($giacinto, $home);
                Assert::false($sorcerer->hasCondition(Game::GIACINTO_INFLUENCE_REDUCTION_CONDITION), 'home never counts');
            },
        ];
    }
}
