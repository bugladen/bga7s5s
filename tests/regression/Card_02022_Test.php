<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02022;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique_GainLethal;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardSentToLocker;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;

class Card_02022_Test extends TestCase
{
    public function name(): string
    {
        return '_02022 Lord Stranahan III';
    }

    private function hasLethal(Character $character): bool
    {
        return $character->getTechniqueByClassId('Technique_02022') !== null;
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

    private function issued(int $challengerId, int $defenderId): EventChallengeIssued
    {
        $event = new EventChallengeIssued();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        return $event;
    }

    public function tests(): array
    {
        return [
            'constructs Montaigne Musketeer with Technique_GainLethal id Technique_02022' => function () {
                $stranahan = new _02022();
                Assert::instanceOf(Character::class, $stranahan, 'Character');
                Assert::same(5, $stranahan->Resolve, 'Resolve');
                Assert::same(1, $stranahan->Combat, 'Combat');
                Assert::same(3, $stranahan->Finesse, 'Finesse');
                Assert::same(2, $stranahan->Influence, 'Influence');
                Assert::true($stranahan->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($stranahan->hasTrait('Musketeer'), 'Musketeer');
                Assert::true($stranahan->hasTrait('Duelist'), 'Duelist');
                Assert::true($stranahan->hasTrait('Montaigne'), 'Montaigne trait');
                Assert::true($stranahan->hasFaction('Montaigne'), 'Montaigne');
                Assert::instanceOf(Technique_GainLethal::class, $stranahan->getTechniques()[0], 'GainLethal');
                Assert::same('Technique_02022', $stranahan->getTechniques()[0]->ClassId, 'ClassId');
            },

            // WHY: wound challenger when defender is YOUR Diplomat at Stranahan's location;
            // abilityId = (string) card Id; sourceId = card Id.
            'ChallengeIssued wounds challenger when your Diplomat defends at Stranahan location' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $diplomat = $world->placeCharacter(
                    new GenericCharacter('Diplomat', ['Diplomat']),
                    Game::LOCATION_CITY_DOCKS,
                    1
                );
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($stranahan, $this->issued($challenger->Id, $diplomat->Id));

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($challenger->Id, $wounds[0]->characterId, 'challenger');
                Assert::same($stranahan->Id, $wounds[0]->sourceId, 'sourceId');
                Assert::same((string) $stranahan->Id, $wounds[0]->abilityId, 'abilityId');
                Assert::same(1, $wounds[0]->wounds, 'one');
            },

            'ChallengeIssued does not wound when defender is not your Diplomat or wrong location' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $farDiplomat = $world->placeCharacter(
                    new GenericCharacter('Far Diplomat', ['Diplomat']),
                    Game::LOCATION_CITY_FORUM,
                    1
                );
                $foeDiplomat = $world->placeCharacter(
                    new GenericCharacter('Foe Diplomat', ['Diplomat']),
                    Game::LOCATION_CITY_DOCKS,
                    2
                );
                $challenger = $world->placeCharacter(new GenericCharacter('Challenger'), Game::LOCATION_CITY_DOCKS, 2);

                foreach ([$plain, $farDiplomat, $foeDiplomat] as $defender) {
                    $world->theah->queuedEvents = [];
                    $world->fireOn($stranahan, $this->issued($challenger->Id, $defender->Id));
                    Assert::count(
                        0,
                        $world->theah->queuedOfType(EventCharacterBeingWounded::class),
                        $defender->Name
                    );
                }
            },

            'recruiting a Musketeer at Stranahan location grants Technique_02022 Gain Lethal' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);

                $event = new EventCharacterRecruited();
                $event->characterId = $ally->Id;
                $world->fireOn($stranahan, $event);

                Assert::true($this->hasLethal($ally), 'granted');
                $technique = $ally->getTechniqueByClassId('Technique_02022');
                Assert::instanceOf(Technique_GainLethal::class, $technique, 'class');
                Assert::same($ally->Id, $technique->OwnerId, 'owner');
            },

            // WHY: Does NOT grant at Player Home.
            'does not grant Gain Lethal at Player Home' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_PLAYER_HOME, 1);
                $ally = $this->musketeer($world, Game::LOCATION_PLAYER_HOME);

                $event = new EventCharacterRecruited();
                $event->characterId = $ally->Id;
                $world->fireOn($stranahan, $event);

                Assert::false($this->hasLethal($ally), 'Home');
            },

            'non-Musketeer, opposing, or other-location recruits get nothing' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $this->musketeer($world, Game::LOCATION_CITY_DOCKS, 2, 'Foe');
                $far = $this->musketeer($world, Game::LOCATION_CITY_FORUM, 1, 'Far');

                foreach ([$plain, $foe, $far] as $character) {
                    $event = new EventCharacterRecruited();
                    $event->characterId = $character->Id;
                    $world->fireOn($stranahan, $event);
                    Assert::false($this->hasLethal($character), $character->Name);
                }
            },

            // WHY: idempotent grant — no duplicate Technique_02022.
            'grant is idempotent' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);

                $event = new EventCharacterRecruited();
                $event->characterId = $ally->Id;
                $world->fireOn($stranahan, $event);
                $world->fireOn($stranahan, $event);

                $matches = array_filter(
                    $ally->getTechniques(),
                    fn($t) => $t->ClassId === 'Technique_02022'
                );
                Assert::count(1, $matches, 'one copy');
            },

            'Musketeer moving to Stranahan location gains Lethal; leaving loses it' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_FORUM);

                $ally->Location = Game::LOCATION_CITY_DOCKS;
                $world->fireOn($stranahan, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                Assert::true($this->hasLethal($ally), 'gained');

                $ally->Location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($stranahan, $this->moved($ally->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));
                Assert::false($this->hasLethal($ally), 'lost');
            },

            'Stranahan moving transfers aura between locations' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $oldAlly = $this->musketeer($world, Game::LOCATION_CITY_DOCKS, 1, 'Old');
                $newAlly = $this->musketeer($world, Game::LOCATION_CITY_FORUM, 1, 'New');
                $world->fireOn($stranahan, $this->moved($oldAlly->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                Assert::true($this->hasLethal($oldAlly), 'precondition');

                $stranahan->Location = Game::LOCATION_CITY_FORUM;
                $world->fireOn($stranahan, $this->moved($stranahan->Id, Game::LOCATION_CITY_DOCKS, Game::LOCATION_CITY_FORUM));

                Assert::false($this->hasLethal($oldAlly), 'old revoked');
                Assert::true($this->hasLethal($newAlly), 'new granted');
                Assert::count(1, $stranahan->getTechniques(), 'native kept');
            },

            'destroy strips allies even when blanked' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->fireOn($stranahan, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));
                $stranahan->addCondition(Game::FATES_SILENCE_CONDITION);

                $event = new EventCharacterDestroyed();
                $event->characterId = $stranahan->Id;
                $world->fireOn($stranahan, $event);

                Assert::false($this->hasLethal($ally), 'stripped');
            },

            'sent to locker strips allies' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $world->fireOn($stranahan, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));

                $event = new EventCardSentToLocker();
                $event->cardId = $stranahan->Id;
                $world->fireOn($stranahan, $event);

                Assert::false($this->hasLethal($ally), 'stripped');
            },

            // WHY: Fate's Silence skips handleEvent — onAbilitiesBlanked clears; unblank re-grants.
            'blanking strips allies and unblanking re-grants Musketeers' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $plain = $world->placeCharacter(new GenericCharacter('Plain'), Game::LOCATION_CITY_DOCKS, 1);
                $world->fireOn($stranahan, $this->moved($ally->Id, Game::LOCATION_CITY_FORUM, Game::LOCATION_CITY_DOCKS));

                $stranahan->onAbilitiesBlanked($world->theah);
                Assert::false($this->hasLethal($ally), 'cleared');

                $stranahan->onAbilitiesUnblanked($world->theah);
                Assert::true($this->hasLethal($ally), 're-granted');
                Assert::false($this->hasLethal($plain), 'non-Musketeer');
            },

            'unblanking at Home does not re-grant' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_PLAYER_HOME, 1);
                $ally = $this->musketeer($world, Game::LOCATION_PLAYER_HOME);

                $stranahan->onAbilitiesUnblanked($world->theah);
                Assert::false($this->hasLethal($ally), 'Home');
            },

            'blanked Stranahan does not grant on recruit' => function () {
                $world = new TestWorld();
                $stranahan = $world->placeCharacter(new _02022(), Game::LOCATION_CITY_DOCKS, 1);
                $ally = $this->musketeer($world, Game::LOCATION_CITY_DOCKS);
                $stranahan->addCondition(Game::FATES_SILENCE_CONDITION);

                $event = new EventCharacterRecruited();
                $event->characterId = $ally->Id;
                $world->fireOn($stranahan, $event);

                Assert::false($this->hasLethal($ally), 'blanked');
            },
        ];
    }
}
