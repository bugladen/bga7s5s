<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Seadog;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterInfluenceModified;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;

class Card_05Seadog_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Seadog';
    }

    public function tests(): array
    {
        return [
            'is an academic spy seaborn' => function () {
                $card = new _05Seadog();
                Assert::instanceOf(Character::class, $card, 'Character');
                Assert::instanceOf(IHasReactions::class, $card, 'reactions');
                Assert::same(5, $card->Resolve, 'resolve');
                Assert::same(2, $card->Combat, 'combat');
                Assert::same(2, $card->Finesse, 'finesse');
                Assert::same(1, $card->Influence, 'influence');
                Assert::true($card->hasTrait('Academic'), 'Academic');
                Assert::true($card->hasTrait('Spy'), 'Spy');
                Assert::true($card->hasTrait('Seaborn'), 'Seaborn');
                Assert::count(1, $card->getReactions(), 'reaction');
            },

            // WHY: Opposing characters at his city location lose 1 Influence only when that
            // opponent controls equal or more characters than Mikael's controller. Home never
            // counts. Blanking and destruction clear every stamp. A second application does
            // not stack. An outnumbered opponent's character stays clean.
            'reduces opposing characters when the opponent outnumbers or ties' => function () {
                $world = new TestWorld();
                $mikael = $world->placeCharacter(new _05Seadog(), Game::LOCATION_CITY_DOCKS, 1);
                // Opponent has 1 character, Mikael has 1 → equal → eligible.
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_FORUM, 2);

                $enter = new EventCardMoved();
                $enter->cardId = $foe->Id;
                $enter->fromLocation = Game::LOCATION_CITY_FORUM;
                $enter->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($mikael, $enter);
                Assert::true($foe->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION), 'stamped');
                $reduced = $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0];
                Assert::same($foe->ModifiedInfluence - 1, $reduced->NewInfluence, '-1');

                $world->theah->takeQueuedEvents();
                $world->fireOn($mikael, $enter);
                Assert::count(0, $world->theah->queuedOfType(EventCharacterInfluenceModified::class), 'does not stack');

                $leave = new EventCardMoved();
                $leave->cardId = $mikael->Id;
                $leave->fromLocation = Game::LOCATION_CITY_DOCKS;
                $leave->toLocation = Game::LOCATION_CITY_FORUM;
                $foe->Location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($mikael, $leave);
                Assert::false($foe->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION), 'cleared on leave');
                Assert::same($foe->ModifiedInfluence + 1, $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0]->NewInfluence, '+1 back');

                $foe->addCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION);
                $world->theah->takeQueuedEvents();
                $mikael->onAbilitiesBlanked($world->theah);
                Assert::false($foe->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION), 'blank clears');

                $foe->addCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION);
                $destroyed = new EventCharacterDestroyed();
                $destroyed->characterId = $mikael->Id;
                $world->fireOn($mikael, $destroyed);
                Assert::false($foe->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION), 'death clears');

                $mikael->ControllerId = 0;
                $world->theah->takeQueuedEvents();
                $world->fireOn($mikael, $enter);
                Assert::false($foe->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION), 'uncontrolled');

                $mikael->ControllerId = 1;
                $mikael->Location = Game::LOCATION_PLAYER_HOME;
                $home = new EventCardMoved();
                $home->cardId = $foe->Id;
                $home->fromLocation = Game::LOCATION_CITY_DOCKS;
                $home->toLocation = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($mikael, $home);
                Assert::false($foe->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION), 'home never counts');
            },

            // WHY: "their characters" is per-opponent. Outnumbered foes stay clean; a
            // muster elsewhere that ties the count must resync and stamp without a local move.
            'skips an outnumbered opponent until a muster ties the count' => function () {
                $world = new TestWorld();
                $mikael = $world->placeCharacter(new _05Seadog(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_BAZAAR, 1);
                // Mikael side = 2. Lone foe = 1 → fewer → no stamp.
                $lonely = $world->placeCharacter(new GenericCharacter('Lonely'), Game::LOCATION_CITY_DOCKS, 2);

                $arrive = new EventCardMoved();
                $arrive->cardId = $lonely->Id;
                $arrive->fromLocation = Game::LOCATION_CITY_FORUM;
                $arrive->toLocation = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($mikael, $arrive);
                Assert::false($lonely->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION), 'outnumbered clean');

                // Bring a second foe into play elsewhere → equal (2 vs 2) → resync stamps lonely.
                $backup = $world->placeCharacter(new GenericCharacter('Backup'), Game::LOCATION_CITY_FORUM, 2);
                $muster = new EventCharacterMustered();
                $muster->characterId = $backup->Id;
                $muster->location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($mikael, $muster);
                Assert::true($lonely->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION), 'tied after muster');
            },

            'clears the stamp when an opponent drops below equal' => function () {
                $world = new TestWorld();
                $mikael = $world->placeCharacter(new _05Seadog(), Game::LOCATION_CITY_DOCKS, 1);
                $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_BAZAAR, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foeBuddy = $world->placeCharacter(new GenericCharacter('Buddy'), Game::LOCATION_CITY_FORUM, 2);
                // 2 vs 2 — stamp already applied as if aura was live
                $foe->addCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION);

                // Destroy buddy — exclude from count → foe side 1, Mikael side 2 → clear stamp
                $destroy = new EventCharacterDestroyed();
                $destroy->characterId = $foeBuddy->Id;
                $world->fireOn($mikael, $destroy);
                Assert::false($foe->hasCondition(Game::SEADOG_INFLUENCE_REDUCTION_CONDITION), 'cleared when outnumbered');
                Assert::same($foe->ModifiedInfluence + 1, $world->theah->queuedOfType(EventCharacterInfluenceModified::class)[0]->NewInfluence, '+1 back');
            },
        ];
    }
}
