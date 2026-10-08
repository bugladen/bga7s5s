<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01063;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01063;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\techniques\Technique_01063Swap;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterMustered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class Card_01063_Test extends TestCase
{
    public function name(): string
    {
        return '_01063 Bastien Girard';
    }

    private function hasSwap(Character $character): bool
    {
        return $character->getTechniqueByClassId('Technique_01063Swap') !== null;
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
            'constructs Musketeer Duelist with Swap and wound Techniques' => function () {
                $bastien = new _01063();
                Assert::same(3, $bastien->Resolve, 'Resolve');
                Assert::same(2, $bastien->Combat, 'Combat');
                Assert::same(4, $bastien->Finesse, 'Finesse');
                Assert::same(1, $bastien->Influence, 'Influence');
                Assert::true($bastien->hasTrait('Musketeer'), 'Musketeer');
                Assert::true($bastien->hasTrait('Duelist'), 'Duelist');
                Assert::true($bastien->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Technique_01063Swap::class, $bastien->getTechniques()[0], 'Swap native');
                Assert::instanceOf(Technique_01063::class, $bastien->getTechniques()[1], 'wound technique');
            },

            'recruit at Bastien location grants Swap to ally' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCharacterRecruited();
                $event->characterId = $ally->Id;
                $world->fireOn($bastien, $event);

                Assert::true($this->hasSwap($ally), 'Swap granted');
            },

            'granted Swap is owned by the ally with class id Technique_01063Swap' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCharacterRecruited();
                $event->characterId = $ally->Id;
                $world->fireOn($bastien, $event);

                $swap = $ally->getTechniqueByClassId('Technique_01063Swap');
                Assert::same($ally->Id, $swap->OwnerId, 'owner');
                Assert::same($ally->Id . '_Technique_01063Swap', $swap->Id, 'unique id');
            },

            'recruit elsewhere, at Home, or for opponent grants nothing' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $elsewhere = $world->placeCharacter(new GenericCharacter('Elsewhere'), Game::LOCATION_CITY_FORUM, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                foreach ([$elsewhere, $foe] as $character) {
                    $event = new EventCharacterRecruited();
                    $event->characterId = $character->Id;
                    $world->fireOn($bastien, $event);
                    Assert::false($this->hasSwap($character), $character->Name);
                }

                $homeBastien = $world->placeCharacter(new _01063(), Game::LOCATION_PLAYER_HOME, 1);
                $homeAlly = $world->placeCharacter(new GenericCharacter('Home Ally'), Game::LOCATION_PLAYER_HOME, 1);
                $event = new EventCharacterRecruited();
                $event->characterId = $homeAlly->Id;
                $world->fireOn($homeBastien, $event);
                Assert::false($this->hasSwap($homeAlly), 'Home never grants');
            },

            'recruit does not grant Swap to Bastien himself' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCharacterRecruited();
                $event->characterId = $bastien->Id;
                $world->fireOn($bastien, $event);

                Assert::count(2, $bastien->getTechniques(), 'still only native Swap + wound');
            },

            'blanked Bastien does not grant on recruit' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $bastien->addCondition(Game::FATES_SILENCE_CONDITION);

                $event = new EventCharacterRecruited();
                $event->characterId = $ally->Id;
                $world->fireOn($bastien, $event);

                Assert::false($this->hasSwap($ally), 'blanked');
            },

            // WHY (journal 2026-10-01): muster emits EventCharacterMustered, not EventCardMoved.
            // Mustering Bastien onto an occupied location must still grant the Swap aura.
            'mustering Bastien onto occupied location grants Swap to allies there' => function () {
                $world = new TestWorld();
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                // Hub has already updated Location before cards handle Mustered.
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCharacterMustered();
                $event->characterId = $bastien->Id;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($bastien, $event);

                Assert::true($this->hasSwap($ally), 'ally granted');
                Assert::false($this->hasSwap($foe), 'foe not granted');
            },

            'mustering ally onto Bastien location grants Swap' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);

                $event = new EventCharacterMustered();
                $event->characterId = $ally->Id;
                $event->location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($bastien, $event);

                Assert::true($this->hasSwap($ally), 'granted');
            },

            'mustering to Home or onto another location grants nothing' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);

                $event = new EventCharacterMustered();
                $event->characterId = $ally->Id;
                $event->location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($bastien, $event);
                Assert::false($this->hasSwap($ally), 'other location');

                $homeBastien = $world->placeCharacter(new _01063(), Game::LOCATION_PLAYER_HOME, 1);
                $homeAlly = $world->placeCharacter(new GenericCharacter('Home Ally'), Game::LOCATION_PLAYER_HOME, 1);
                $event = new EventCharacterMustered();
                $event->characterId = $homeBastien->Id;
                $event->location = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($homeBastien, $event);
                Assert::false($this->hasSwap($homeAlly), 'Bastien mustered Home');
            },

            'ally moving to Bastien location gains Swap; leaving loses it' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_FORUM, 1);

                $ally->Location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($bastien, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                Assert::true($this->hasSwap($ally), 'gained on arrival');

                $ally->Location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($bastien, $this->moved($ally->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                Assert::false($this->hasSwap($ally), 'lost on departure');
            },

            'opposing character moving to Bastien location gains nothing' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($bastien, $this->moved($foe->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                Assert::false($this->hasSwap($foe), 'foe');
            },

            'Bastien moving swaps the aura from old location allies to new location allies' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $oldAlly = $world->placeCharacter(new GenericCharacter('Old'), Game::LOCATION_CITY_DOCKS, 1);
                $newAlly = $world->placeCharacter(new GenericCharacter('New'), Game::LOCATION_CITY_FORUM, 1);

                $world->fireOn($bastien, $this->moved($oldAlly->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                Assert::true($this->hasSwap($oldAlly), 'precondition');

                $bastien->Location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($bastien, $this->moved($bastien->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::false($this->hasSwap($oldAlly), 'old location revoked');
                Assert::true($this->hasSwap($newAlly), 'new location granted');
                Assert::count(2, $bastien->getTechniques(), 'Bastien native Techniques untouched');
            },

            'Bastien moving Home revokes aura and grants none' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->fireOn($bastien, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));

                $bastien->Location = Game::LOCATION_PLAYER_HOME;
                $world->fireOn($bastien, $this->moved($bastien->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_PLAYER_HOME));

                Assert::false($this->hasSwap($ally), 'revoked');
            },

            'destroy clears granted Swap from all controlled characters' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->fireOn($bastien, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                Assert::true($this->hasSwap($ally), 'precondition');

                $event = new EventCharacterDestroyed();
                $event->characterId = $bastien->Id;
                $world->fireOn($bastien, $event);

                Assert::false($this->hasSwap($ally), 'cleared on destroy');
                Assert::count(2, $bastien->getTechniques(), 'Bastien native Techniques kept');
            },

            // WHY: leave-play clear sits above the blanked early-return, so destroy while Silence is on still strips allies.
            'destroy clears granted Swap even when Bastien is blanked' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->fireOn($bastien, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                $bastien->addCondition(Game::FATES_SILENCE_CONDITION);

                $event = new EventCharacterDestroyed();
                $event->characterId = $bastien->Id;
                $world->fireOn($bastien, $event);

                Assert::false($this->hasSwap($ally), 'cleared while blanked');
            },

            'sent to locker clears granted Swap' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->fireOn($bastien, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));

                $event = new EventCardSentToLocker();
                $event->cardId = $bastien->Id;
                $world->fireOn($bastien, $event);

                Assert::false($this->hasSwap($ally), 'cleared on locker');
            },

            'another character destroyed does not clear the aura' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->fireOn($bastien, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));

                $event = new EventCharacterDestroyed();
                $event->characterId = $ally->Id;
                $world->fireOn($bastien, $event);

                Assert::true($this->hasSwap($ally), 'unaffected');
            },

            // WHY: Fate's Silence skips handleEvent, so blank/unblank hooks must clear and re-grant the aura.
            'blanking clears Swap from allies and unblanking re-grants it' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $world->fireOn($bastien, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));

                $bastien->onAbilitiesBlanked($world->theah);
                Assert::false($this->hasSwap($ally), 'cleared on blank');

                $bastien->onAbilitiesUnblanked($world->theah);
                Assert::true($this->hasSwap($ally), 're-granted on unblank');
                Assert::false($this->hasSwap($foe), 'foe never granted');
            },

            'unblank does not duplicate an existing Swap' => function () {
                $world = new TestWorld();
                $bastien = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
                $world->fireOn($bastien, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));

                $bastien->onAbilitiesUnblanked($world->theah);

                Assert::count(1, $ally->getTechniques(), 'single Swap');
            },

            'unblank grants nothing at Home, when dying, or when in discard/locker' => function () {
                $world = new TestWorld();
                $homeBastien = $world->placeCharacter(new _01063(), Game::LOCATION_PLAYER_HOME, 1);
                $homeAlly = $world->placeCharacter(new GenericCharacter('Home Ally'), Game::LOCATION_PLAYER_HOME, 1);
                $homeBastien->onAbilitiesUnblanked($world->theah);
                Assert::false($this->hasSwap($homeAlly), 'home');

                $dying = $world->placeCharacter(new _01063(), Game::LOCATION_CITY_FORUM, 1);
                $dyingAlly = $world->placeCharacter(new GenericCharacter('Forum Ally'), Game::LOCATION_CITY_FORUM, 1);
                $dying->IsDying = true;
                $dying->onAbilitiesUnblanked($world->theah);
                Assert::false($this->hasSwap($dyingAlly), 'dying');

                $dying->IsDying = false;
                $world->game->forceInDiscardOrLocker = true;
                $dying->onAbilitiesUnblanked($world->theah);
                Assert::false($this->hasSwap($dyingAlly), 'discard/locker');
            },
        ];
    }
}
