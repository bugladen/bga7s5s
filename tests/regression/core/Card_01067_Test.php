<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01067;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01067;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_PlusOneRiposte;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;

class Card_01067_Test extends TestCase
{
    public function name(): string
    {
        return '_01067 Jean Urbain';
    }

    private function hasRiposte(Character $character): bool
    {
        return $character->getTechniqueByClassId('Technique_01067') !== null;
    }

    private function musketeer(TestWorld $world, string $location, int $controller = 1, string $name = 'Musketeer'): GenericCharacter
    {
        return $world->placeCharacter(new GenericCharacter($name, ['Musketeer']), $location, $controller);
    }

    private function moved(int $cardId, string $from, string $to): EventCardMoved
    {
        $event = new EventCardMoved();
        $event->cardId = $cardId;
        $event->fromLocation = $from;
        $event->toLocation = $to;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Musketeer Duelist with Technique_01067' => function () {
                $jean = new _01067();
                Assert::same(4, $jean->Resolve, 'Resolve');
                Assert::same(3, $jean->Combat, 'Combat');
                Assert::same(2, $jean->Finesse, 'Finesse');
                Assert::same(1, $jean->Influence, 'Influence');
                Assert::true($jean->hasTrait('Musketeer'), 'Musketeer');
                Assert::true($jean->hasTrait('Duelist'), 'Duelist');
                Assert::true($jean->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Technique_01067::class, $jean->getTechniques()[0], 'Technique_01067');
            },

            'recruiting a Musketeer at Jean location grants +1 Riposte' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);

                $event = new EventCharacterRecruited();
                $event->characterId = $ally->Id;
                $world->fireOn($jean, $event);

                Assert::true($this->hasRiposte($ally), 'granted');
            },

            // WHY: granted Technique must use Technique_PlusOneRiposte with ClassId Technique_01067 so clears can find it.
            'granted technique is PlusOneRiposte with ClassId Technique_01067 owned by the ally' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);

                $event = new EventCharacterRecruited();
                $event->characterId = $ally->Id;
                $world->fireOn($jean, $event);

                $technique = $ally->getTechniqueByClassId('Technique_01067');
                Assert::instanceOf(Technique_PlusOneRiposte::class, $technique, 'class');
                Assert::same('Technique_01067', $technique->ClassId, 'ClassId');
                Assert::same($ally->Id, $technique->OwnerId, 'owner');
                Assert::same($ally->Id . '_Technique_01067', $technique->Id, 'id');
            },

            'non-Musketeer, opposing, Home, or other-location recruits get nothing' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $this->musketeer($world, Game::LOCATION_CITY_DOCKS, 2, 'Foe');
                $far = $this->musketeer($world, Game::LOCATION_CITY_FORUM, 1, 'Far');

                foreach ([$plain, $foe, $far] as $character) {
                    $event = new EventCharacterRecruited();
                    $event->characterId = $character->Id;
                    $world->fireOn($jean, $event);
                    Assert::false($this->hasRiposte($character), $character->Name);
                }

                $homeJean = $world->placeCharacter(new _01067(), Game::LOCATION_PLAYER_HOME, 1);
                $homeAlly = $this->musketeer($world, Game::LOCATION_PLAYER_HOME, 1, 'Home Ally');
                $event = new EventCharacterRecruited();
                $event->characterId = $homeAlly->Id;
                $world->fireOn($homeJean, $event);
                Assert::false($this->hasRiposte($homeAlly), 'Home');
            },

            'blanked Jean does not grant on recruit' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $jean->addCondition(Game::FATES_SILENCE_CONDITION);

                $event = new EventCharacterRecruited();
                $event->characterId = $ally->Id;
                $world->fireOn($jean, $event);

                Assert::false($this->hasRiposte($ally), 'blanked');
            },

            // WHY (same gap as Bastien): muster emits Mustered, not CardMoved; the aura must be granted on muster.
            'mustering Jean onto occupied location grants Musketeers there' => function () {
                $world = new TestWorld();
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCharacterMustered();
                $event->characterId = $jean->Id;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jean, $event);

                Assert::true($this->hasRiposte($ally), 'Musketeer granted');
                Assert::false($this->hasRiposte($plain), 'non-Musketeer not granted');
            },

            'mustering a Musketeer onto Jean location grants +1 Riposte' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);

                $event = new EventCharacterMustered();
                $event->characterId = $ally->Id;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jean, $event);

                Assert::true($this->hasRiposte($ally), 'granted');
            },

            'mustering a non-Musketeer or to another location grants nothing' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $far = $this->musketeer($world, Game::LOCATION_CITY_FORUM, 1, 'Far');

                $event = new EventCharacterMustered();
                $event->characterId = $plain->Id;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jean, $event);
                Assert::false($this->hasRiposte($plain), 'non-Musketeer');

                $event = new EventCharacterMustered();
                $event->characterId = $far->Id;
                $event->location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($jean, $event);
                Assert::false($this->hasRiposte($far), 'other location');
            },

            'Musketeer moving to Jean location gains +1 Riposte; leaving loses it' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_FORUM);

                $ally->Location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($jean, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                Assert::true($this->hasRiposte($ally), 'gained');

                $ally->Location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($jean, $this->moved($ally->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                Assert::false($this->hasRiposte($ally), 'lost');
            },

            'non-Musketeer and opposing movers do not gain +1 Riposte' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $this->musketeer($world, Game::LOCATION_CITY_DOCKS, 2, 'Foe');

                foreach ([$plain, $foe] as $character) {
                    $world->fireOn($jean, $this->moved($character->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                    Assert::false($this->hasRiposte($character), $character->Name);
                }
            },

            'Jean moving transfers the aura between locations without touching his own Technique' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $oldAlly = $this->musketeer($world, Game::LOCATION_CITY_DOCKS, 1, 'Old');
                $newAlly = $this->musketeer($world, Game::LOCATION_CITY_FORUM, 1, 'New');
                $world->fireOn($jean, $this->moved($oldAlly->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                Assert::true($this->hasRiposte($oldAlly), 'precondition');

                $jean->Location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($jean, $this->moved($jean->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::false($this->hasRiposte($oldAlly), 'old revoked');
                Assert::true($this->hasRiposte($newAlly), 'new granted');
                Assert::count(1, $jean->getTechniques(), 'Jean still has exactly his native Technique');
            },

            // WHY: granted PlusOneRiposte shares ClassId Technique_01067 with Jean's own Technique,
            // so leave-play clears must skip Jean himself or he loses his printed Technique.
            'destroy strips allies but keeps Jean native Technique_01067' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->fireOn($jean, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                Assert::true($this->hasRiposte($ally), 'precondition');

                $event = new EventCharacterDestroyed();
                $event->characterId = $jean->Id;
                $world->fireOn($jean, $event);

                Assert::false($this->hasRiposte($ally), 'ally stripped');
                Assert::count(1, $jean->getTechniques(), 'native kept');
                Assert::instanceOf(Technique_01067::class, $jean->getTechniques()[0], 'still Technique_01067');
            },

            'destroy strips allies even when Jean is blanked' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->fireOn($jean, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                $jean->addCondition(Game::FATES_SILENCE_CONDITION);

                $event = new EventCharacterDestroyed();
                $event->characterId = $jean->Id;
                $world->fireOn($jean, $event);

                Assert::false($this->hasRiposte($ally), 'stripped while blanked');
            },

            'sent to locker strips allies' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->fireOn($jean, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));

                $event = new EventCardSentToLocker();
                $event->cardId = $jean->Id;
                $world->fireOn($jean, $event);

                Assert::false($this->hasRiposte($ally), 'stripped');
                Assert::count(1, $jean->getTechniques(), 'native kept');
            },

            'blanking strips allies and unblanking re-grants only Musketeers' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $this->musketeer($world, Game::LOCATION_CITY_DOCKS, 2, 'Foe');
                $world->fireOn($jean, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));

                $jean->onAbilitiesBlanked($world->theah);
                Assert::false($this->hasRiposte($ally), 'cleared');
                Assert::count(1, $jean->getTechniques(), 'Jean native kept');

                $jean->onAbilitiesUnblanked($world->theah);
                Assert::true($this->hasRiposte($ally), 're-granted');
                Assert::false($this->hasRiposte($plain), 'non-Musketeer');
                Assert::false($this->hasRiposte($foe), 'opponent');
            },

            'unblank does not duplicate an existing grant and skips Home/dying/locker' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->fireOn($jean, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                $jean->onAbilitiesUnblanked($world->theah);
                Assert::count(1, $ally->getTechniques(), 'single grant');

                $homeJean = $world->placeCharacter(new _01067(), Game::LOCATION_PLAYER_HOME, 1);
                $homeAlly = $this->musketeer($world, Game::LOCATION_PLAYER_HOME, 1, 'Home Ally');
                $homeJean->onAbilitiesUnblanked($world->theah);
                Assert::false($this->hasRiposte($homeAlly), 'Home');

                $dyingJean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_BAZAAR, 1);
                $bazaarAlly = $this->musketeer($world, Game::LOCATION_CITY_BAZAAR, 1, 'Bazaar Ally');
                $dyingJean->IsDying = true;
                $dyingJean->onAbilitiesUnblanked($world->theah);
                Assert::false($this->hasRiposte($bazaarAlly), 'dying');

                $dyingJean->IsDying = false;
                $world->game->forceInDiscardOrLocker = true;
                $dyingJean->onAbilitiesUnblanked($world->theah);
                Assert::false($this->hasRiposte($bazaarAlly), 'locker');
            },

            'granted +1 Riposte technique adds 1 Riposte and is duel-only' => function () {
                $world = new TestWorld();
                $jean = $world->placeCharacter(new _01067(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->fireOn($jean, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                $technique = $ally->getTechniqueByClassId('Technique_01067');

                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'outside duel');
                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'in duel');

                $calc = new EventDuelCalculateTechniqueValues();
                $calc->techniqueId = $technique->Id;
                $calc->theah = $world->theah;
                $technique->handleEvent($calc);
                Assert::same(1, $calc->riposte, '+1 Riposte');
                Assert::same(0, $calc->thrust, 'no Thrust');
            },
        ];
    }
}
