<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03051;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03051;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class Card_03051_Test extends TestCase
{
    public function name(): string
    {
        return '_03051 Yepikhodov';
    }

    public function tests(): array
    {
        return [
            'constructs an Ussuran scoundrel' => function () {
                $card = new _03051();
                Assert::instanceOf(Character::class, $card, 'character');
                Assert::instanceOf(IHasActions::class, $card, 'actions');
                Assert::same(4, $card->Resolve, 'resolve');
                Assert::same(2, $card->Combat, 'combat');
                Assert::same(2, $card->Finesse, 'finesse');
                Assert::same(2, $card->Influence, 'influence');
                Assert::true($card->hasFaction('Ussura'), 'Ussura');
                Assert::true($card->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($card->hasTrait('Cossack'), 'Cossack');
                Assert::true($card->hasTrait('Squire'), 'Squire');
                Assert::instanceOf(Action_03051::class, $card->getActions()[0], 'action');
            },

            // WHY: "Your other characters" has no trait filter. Home never receives the aura.
            // Opponent characters at the location do not. Destroy, locker, and blank clear it
            // even when controller is later zeroed only after the clear has already run.
            'grants the copy technique to other characters at his city location' => function () {
                $world = new TestWorld();
                $yep = $world->placeCharacter(new _03051(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $homeAlly = $world->placeCharacter(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);

                $recruited = new EventCharacterRecruited();
                $recruited->characterId = $ally->Id;
                $world->fireOn($yep, $recruited);
                Assert::true($ally->getTechniqueByClassId('Technique_03051') !== null, 'ally granted');

                $recruited->characterId = $foe->Id;
                $world->fireOn($yep, $recruited);
                Assert::true($foe->getTechniqueByClassId('Technique_03051') === null, 'foe');

                $yep->Location = Game::LOCATION_PLAYER_HOME;
                $recruited->characterId = $homeAlly->Id;
                $world->fireOn($yep, $recruited);
                Assert::true($homeAlly->getTechniqueByClassId('Technique_03051') === null, 'home');

                $yep->Location = Game::LOCATION_CITY_DOCKS;
                $forumAlly = $world->placeCharacter(new GenericCharacter('Forum'), Game::LOCATION_CITY_FORUM, 1);
                $moved = new EventCardMoved();
                $moved->cardId = $yep->Id;
                $moved->fromLocation = Game::LOCATION_CITY_DOCKS;
                $moved->toLocation = Game::LOCATION_CITY_FORUM;
                $world->fireOn($yep, $moved);
                Assert::true($ally->getTechniqueByClassId('Technique_03051') === null, 'left behind');
                Assert::true($forumAlly->getTechniqueByClassId('Technique_03051') !== null, 'arrived');

                $yep->Location = Game::LOCATION_CITY_FORUM;
                $joining = $world->placeCharacter(new GenericCharacter('Joining'), Game::LOCATION_CITY_DOCKS, 1);
                $join = new EventCardMoved();
                $join->cardId = $joining->Id;
                $join->fromLocation = Game::LOCATION_CITY_DOCKS;
                $join->toLocation = Game::LOCATION_CITY_FORUM;
                $joining->Location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($yep, $join);
                Assert::true($joining->getTechniqueByClassId('Technique_03051') !== null, 'joined');

                $leave = new EventCardMoved();
                $leave->cardId = $joining->Id;
                $leave->fromLocation = Game::LOCATION_CITY_FORUM;
                $leave->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($yep, $leave);
                Assert::true($joining->getTechniqueByClassId('Technique_03051') === null, 'left');

                $yep->onAbilitiesBlanked($world->theah);
                Assert::true($forumAlly->getTechniqueByClassId('Technique_03051') === null, 'blanked');
                $yep->onAbilitiesUnblanked($world->theah);
                Assert::true($forumAlly->getTechniqueByClassId('Technique_03051') !== null, 'unblanked');

                $yep->Location = Game::LOCATION_PLAYER_HOME;
                $yep->onAbilitiesBlanked($world->theah);
                $yep->onAbilitiesUnblanked($world->theah);
                Assert::true($forumAlly->getTechniqueByClassId('Technique_03051') === null, 'unblank at home');
            },

            'destroy and locker clear the aura; controller 0 does not' => function () {
                $world = new TestWorld();
                $yep = $world->placeCharacter(new _03051(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $recruited = new EventCharacterRecruited();
                $recruited->characterId = $ally->Id;
                $world->fireOn($yep, $recruited);

                $yep->ControllerId = 0;
                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $yep->Id;
                $world->fireOn($yep, $destroyed);
                Assert::true($ally->getTechniqueByClassId('Technique_03051') !== null, 'controller 0');

                $yep->ControllerId = 1;
                $locker = new EventCardSentToLocker();
                $locker->cardId = $yep->Id;
                $world->fireOn($yep, $locker);
                Assert::true($ally->getTechniqueByClassId('Technique_03051') === null, 'locker');
            },
        ];
    }
}
