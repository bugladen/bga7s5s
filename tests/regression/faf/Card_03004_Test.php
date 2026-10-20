<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03004;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03004;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEndOfRound;

class Card_03004_Test extends TestCase
{
    public function name(): string
    {
        return '_03004 Elena Agnelli';
    }

    /**
     * @return array{0:_03004,1:GenericCharacter}
     */
    private function duel(TestWorld $world, bool $elenaIsActor = true): array
    {
        $elena = $world->placeCharacter(new _03004(), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $world->theah->duelActor = $elenaIsActor ? $elena : $foe;
        $world->theah->duelOpponent = $elenaIsActor ? $foe : $elena;
        return [$elena, $foe];
    }

    public function tests(): array
    {
        return [
            'constructs a Vodacce Strega with printed Finesse 0 and a Technique' => function () {
                $elena = new _03004();
                Assert::instanceOf(Character::class, $elena, 'character');
                Assert::same(5, $elena->Resolve, 'Resolve');
                Assert::same(2, $elena->Combat, 'Combat');
                Assert::same(0, $elena->Finesse, 'Finesse');
                Assert::same(2, $elena->Influence, 'Influence');
                Assert::same(0, $elena->FinesseBonus, 'bonus starts at 0');
                Assert::true($elena->hasFaction('Vodacce'), 'Vodacce');
                Assert::true($elena->hasTrait('Sorcerer'), 'Sorcerer');
                Assert::true($elena->hasTrait('Strega'), 'Strega');
                Assert::true($elena->hasTrait('Pirate'), 'Pirate');
                Assert::true($elena->hasTrait('Spy'), 'Spy');
                Assert::instanceOf(Technique_03004::class, $elena->getTechniques()[0], 'technique');
            },

            'end of round adds +1 Finesse per Sorcery in her controller dueling line' => function () {
                $world = new TestWorld();
                [$elena] = $this->duel($world);
                $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03009(), Game::LOCATION_DUELING_LINE, 1);
                $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03010(), Game::LOCATION_DUELING_LINE, 1);
                $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\_02061(), Game::LOCATION_DUELING_LINE, 1);
                $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03009(), Game::LOCATION_DUELING_LINE, 2);

                $world->fireOn($elena, new EventDuelEndOfRound());
                Assert::same(2, $elena->FinesseBonus, 'two of her Sorcery cards');
                $mods = $world->theah->queuedOfType(EventCharacterFinesseModifed::class);
                Assert::count(1, $mods, 'one delta');
                Assert::same(0, $mods[0]->OldFinesse, 'printed');
                Assert::same(2, $mods[0]->NewFinesse, 'queued, not applied to ModifiedFinesse');
                Assert::same(0, $elena->ModifiedFinesse, 'the event has not resolved');
                Assert::true($elena->IsUpdated, 'persisted');

                $world->theah->takeQueuedEvents();
                $world->fireOn($elena, new EventDuelEndOfRound());
                Assert::count(0, $world->theah->queuedEvents, 'same count queues nothing');
            },

            'as the defender she still counts, and a bystander clears the bonus' => function () {
                $world = new TestWorld();
                [$elena] = $this->duel($world, false);
                $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03009(), Game::LOCATION_DUELING_LINE, 1);
                $world->fireOn($elena, new EventDuelEndOfRound());
                Assert::same(1, $elena->FinesseBonus, 'defender');

                $world->theah->duelActor = $world->placeCharacter(new GenericCharacter('Other'), Game::LOCATION_CITY_FORUM, 1);
                $world->theah->duelOpponent = $world->placeCharacter(new GenericCharacter('OtherFoe'), Game::LOCATION_CITY_FORUM, 2);
                $world->theah->takeQueuedEvents();
                $world->fireOn($elena, new EventDuelEndOfRound());
                Assert::same(0, $elena->FinesseBonus, 'not a participant');
                // WHY: The event's old value is ModifiedFinesse, which the queued event never writes.
                // Clearing a bonus of 1 therefore publishes 0 → -1, not 1 → 0.
                $mods = $world->theah->queuedOfType(EventCharacterFinesseModifed::class);
                Assert::same(0, $mods[0]->OldFinesse, 'ModifiedFinesse, not the bonus');
                Assert::same(-1, $mods[0]->NewFinesse, 'delta subtracted from ModifiedFinesse');
            },

            'duel end clears the bonus even while Sorcery cards remain in the line' => function () {
                $world = new TestWorld();
                [$elena] = $this->duel($world);
                $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03009(), Game::LOCATION_DUELING_LINE, 1);
                $world->fireOn($elena, new EventDuelEndOfRound());
                $world->theah->takeQueuedEvents();
                $world->fireOn($elena, new EventDuelEnd());
                Assert::same(0, $elena->FinesseBonus, 'reset');
                Assert::count(1, $world->theah->queuedOfType(EventCharacterFinesseModifed::class), 'undo queued');
            },

            // WHY: The controller-0 and blanked returns sit above the duel-end reset.
            // Those paths do not clear FinesseBonus. onAbilitiesBlanked is the blank path that does.
            'an uncontrolled Elena ignores duel end, and blanking clears the bonus' => function () {
                $world = new TestWorld();
                [$elena] = $this->duel($world);
                $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03009(), Game::LOCATION_DUELING_LINE, 1);
                $world->fireOn($elena, new EventDuelEndOfRound());
                $elena->ControllerId = 0;
                $world->theah->takeQueuedEvents();
                $world->fireOn($elena, new EventDuelEnd());
                Assert::same(1, $elena->FinesseBonus, 'controller 0 skips the reset');

                $elena->ControllerId = 1;
                $elena->addCondition(Game::FATES_SILENCE_CONDITION);
                $world->fireOn($elena, new EventDuelEnd());
                Assert::same(1, $elena->FinesseBonus, 'blanked handleEvent also skips');

                $elena->onAbilitiesBlanked($world->theah);
                Assert::same(0, $elena->FinesseBonus, 'blank hook clears');
            },

            'unblanking recomputes, and a dying or locker Elena does not' => function () {
                $world = new TestWorld();
                [$elena] = $this->duel($world);
                $world->placeCard(new \Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\_03009(), Game::LOCATION_DUELING_LINE, 1);
                $elena->addCondition(Game::FATES_SILENCE_CONDITION);
                $elena->onAbilitiesUnblanked($world->theah);
                Assert::same(1, $elena->FinesseBonus, 'recomputed');

                $world->theah->takeQueuedEvents();
                $elena->FinesseBonus = 0;
                $elena->IsDying = true;
                $elena->onAbilitiesUnblanked($world->theah);
                Assert::same(0, $elena->FinesseBonus, 'dying');
                Assert::count(0, $world->theah->queuedEvents, 'no event');

                $elena->IsDying = false;
                $world->game->discardOrLockerByCardId[$elena->Id] = true;
                $elena->onAbilitiesUnblanked($world->theah);
                Assert::same(0, $elena->FinesseBonus, 'locker');
            },
        ];
    }
}
