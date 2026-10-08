<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01089;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions\Reaction_01089;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengerSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterFinesseModifed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDefenderSwapped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;

class Card_01089_Test extends TestCase
{
    public function name(): string
    {
        return '_01089 Soline el Gato';
    }

    /**
     * Soline + friendly Ally at Docks (player 1) vs Foe at Docks (player 2).
     * Foe starts with `$foeFinesse` Finesse.
     *
     * @return array{0:_01089,1:Character,2:Character}
     */
    private function scene(TestWorld $world, int $foeFinesse = 2): array
    {
        $soline = $world->placeCharacter(new _01089(), Game::LOCATION_CITY_DOCKS, 1);
        $ally = $world->placeCharacter(new GenericCharacter('Ally'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $foe->Finesse = $foeFinesse;
        $foe->ModifiedFinesse = $foeFinesse;
        $world->theah->duelActor = $ally;
        $world->theah->duelOpponent = $foe;
        return [$soline, $ally, $foe];
    }

    private function started(int $challengerId, int $defenderId): EventDuelStarted
    {
        $event = new EventDuelStarted();
        $event->challengerId = $challengerId;
        $event->defenderId = $defenderId;
        return $event;
    }

    private function duelEnd(): EventDuelEnd
    {
        return new EventDuelEnd();
    }

    /** @return list<EventCharacterFinesseModifed> */
    private function finesseEvents(TestWorld $world): array
    {
        return $world->theah->queuedOfType(EventCharacterFinesseModifed::class);
    }

    /** @return list<string> notification types */
    private function notifyTypes(TestWorld $world): array
    {
        return array_column($world->game->notify->messages, 'type');
    }

    public function tests(): array
    {
        return [
            'constructs Castille Pirate Leader with Reaction_01089' => function () {
                $soline = new _01089();
                Assert::instanceOf(Leader::class, $soline, 'Leader');
                Assert::instanceOf(IHasReactions::class, $soline, 'reactions');
                Assert::same(7, $soline->Resolve, 'Resolve');
                Assert::same(3, $soline->Combat, 'Combat');
                Assert::same(2, $soline->Finesse, 'Finesse');
                Assert::same(2, $soline->Influence, 'Influence');
                Assert::same(6, $soline->CrewCap, 'CrewCap');
                Assert::same(6, $soline->Panache, 'Panache');
                Assert::true($soline->hasTrait('Leader'), 'Leader');
                Assert::true($soline->hasTrait('Pirate'), 'Pirate');
                Assert::true($soline->hasTrait('Scoundrel'), 'Scoundrel');
                Assert::true($soline->hasTrait('Castille'), 'Castille');
                Assert::true($soline->hasFaction('Castille'), 'Castille faction');
                Assert::same(0, $soline->AffectedCharacterId, 'no victim yet');
                Assert::false($soline->FinessePenaltyAbsorbed, 'not absorbed yet');
                Assert::instanceOf(Reaction_01089::class, $soline->getReactions()[0], 'Reaction_01089');
            },

            // --- Duel start: -1 Finesse on the adversary ---

            'duel start with Soline\'s ally as challenger lowers the defender\'s Finesse' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world);

                $world->fireOn($soline, $this->started($ally->Id, $foe->Id));

                $events = $this->finesseEvents($world);
                Assert::count(1, $events, 'one finesse change');
                Assert::same($foe->Id, $events[0]->CharacterId, 'adversary');
                Assert::same(2, $events[0]->OldFinesse, 'old');
                Assert::same(1, $events[0]->NewFinesse, 'new (-1)');
                Assert::same(1, $events[0]->PlayerId, 'Soline controller');
                Assert::same($foe->Id, $soline->AffectedCharacterId, 'remembers victim');
                Assert::true($soline->FinessePenaltyAbsorbed, 'stored reduction');
                Assert::true($foe->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'condition stamped');
                Assert::true(in_array('solineElGatoConditionStarted', $this->notifyTypes($world), true), 'started notification');
                Assert::true($soline->IsUpdated, 'persisted');
            },

            'duel start with Soline\'s ally as defender lowers the challenger\'s Finesse' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world);

                $world->fireOn($soline, $this->started($foe->Id, $ally->Id));

                $events = $this->finesseEvents($world);
                Assert::count(1, $events, 'one finesse change');
                Assert::same($foe->Id, $events[0]->CharacterId, 'challenger is the adversary');
                Assert::same(1, $events[0]->NewFinesse, '-1');
                Assert::same($foe->Id, $soline->AffectedCharacterId, 'victim');
            },

            'Soline herself dueling lowers her adversary\'s Finesse' => function () {
                $world = new TestWorld();
                [$soline, , $foe] = $this->scene($world);

                $world->fireOn($soline, $this->started($soline->Id, $foe->Id));

                Assert::count(1, $this->finesseEvents($world), 'finesse change');
                Assert::same($foe->Id, $soline->AffectedCharacterId, 'victim');
            },

            // WHY: "adversaries at Soline's location" — a duel elsewhere must not be touched.
            'duel between characters at another location does nothing' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world);
                $ally->Location = Game::LOCATION_CITY_FORUM;
                $foe->Location = Game::LOCATION_CITY_FORUM;

                $world->fireOn($soline, $this->started($ally->Id, $foe->Id));

                Assert::count(0, $this->finesseEvents($world), 'no change');
                Assert::same(0, $soline->AffectedCharacterId, 'no victim');
                Assert::false($foe->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'no condition');
            },

            'a duel between two opposing characters at Soline\'s location does nothing' => function () {
                $world = new TestWorld();
                [$soline, , $foe] = $this->scene($world);
                $other = $world->placeCharacter(new GenericCharacter('Other Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $world->fireOn($soline, $this->started($foe->Id, $other->Id));

                Assert::count(0, $this->finesseEvents($world), 'no change');
                Assert::same(0, $soline->AffectedCharacterId, 'no victim');
            },

            // WHY (2026-10-05-09 floor fix): the hub clamps Finesse at 0, so queuing -1 on a 0-Finesse
            // adversary would "store" a reduction that never happened and later +1s would over-credit.
            // Stamp the condition (tooltip + re-absorb hook) but queue nothing and leave Absorbed=false.
            'floor: 0-Finesse adversary is stamped but no -1 is queued and nothing is absorbed' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world, 0);

                $world->fireOn($soline, $this->started($ally->Id, $foe->Id));

                Assert::count(0, $this->finesseEvents($world), 'no fake -1');
                Assert::false($soline->FinessePenaltyAbsorbed, 'not absorbed');
                Assert::true($foe->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'condition still stamped');
                Assert::same($foe->Id, $soline->AffectedCharacterId, 'victim tracked for re-absorb');
            },

            // --- Duel end: restore ---

            'duel end restores the Finesse and clears the condition and victim' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world);
                $world->fireOn($soline, $this->started($ally->Id, $foe->Id));
                $world->theah->takeQueuedEvents();
                $foe->ModifiedFinesse = 1; // the hub applied the -1

                $world->fireOn($soline, $this->duelEnd());

                $events = $this->finesseEvents($world);
                Assert::count(1, $events, 'one restore');
                Assert::same($foe->Id, $events[0]->CharacterId, 'victim');
                Assert::same(1, $events[0]->OldFinesse, 'old');
                Assert::same(2, $events[0]->NewFinesse, 'new (+1)');
                Assert::false($foe->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'condition removed');
                Assert::same(0, $soline->AffectedCharacterId, 'victim cleared');
                Assert::false($soline->FinessePenaltyAbsorbed, 'absorbed cleared');
                Assert::true(in_array('solineElGatoConditionEnded', $this->notifyTypes($world), true), 'ended notification');
            },

            // WHY (floor fix): a never-reduced 0-Finesse victim must NOT get +1 at duel end (overshoot).
            'floor: duel end removes the condition without raising a 0-Finesse victim' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world, 0);
                $world->fireOn($soline, $this->started($ally->Id, $foe->Id));
                $world->theah->takeQueuedEvents();

                $world->fireOn($soline, $this->duelEnd());

                Assert::count(0, $this->finesseEvents($world), 'no overshoot');
                Assert::false($foe->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'condition removed');
                Assert::same(0, $soline->AffectedCharacterId, 'victim cleared');
            },

            // WHY (2026-10-06-01): Absorbed lives on Soline's instance and can desync (mid-duel deploy of a new
            // typed property, instance fields wiped). If the victim still carries the condition AND has
            // Finesse > 0, the reduction is live — restore it even though Absorbed is false.
            'desync: Absorbed=false but victim keeps condition and Finesse > 0 still restores +1' => function () {
                $world = new TestWorld();
                [$soline, , $foe] = $this->scene($world);
                $foe->ModifiedFinesse = 1;
                $foe->addCondition(Game::SOLINE_EL_GATO_CONDITION);
                $soline->AffectedCharacterId = $foe->Id;
                $soline->FinessePenaltyAbsorbed = false;

                $world->fireOn($soline, $this->duelEnd());

                $events = $this->finesseEvents($world);
                Assert::count(1, $events, 'restored');
                Assert::same(2, $events[0]->NewFinesse, '+1');
                Assert::false($foe->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'condition removed');
            },

            'duel end with no victim does nothing' => function () {
                $world = new TestWorld();
                [$soline] = $this->scene($world);

                $world->fireOn($soline, $this->duelEnd());
                Assert::count(0, $this->finesseEvents($world), 'nothing');
            },

            // WHY: a victim destroyed mid-duel (discard/locker) must not receive a +1, but Soline's
            // tracking still resets. The stale condition on the dead card is a known, accepted caveat.
            'duel end skips the raise for a dead victim but still clears tracking' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world);
                $world->fireOn($soline, $this->started($ally->Id, $foe->Id));
                $world->theah->takeQueuedEvents();
                $world->game->forceInDiscardOrLocker = true;

                $world->fireOn($soline, $this->duelEnd());

                Assert::count(0, $this->finesseEvents($world), 'no raise for the dead');
                Assert::same(0, $soline->AffectedCharacterId, 'tracking cleared');
                Assert::false($soline->FinessePenaltyAbsorbed, 'absorbed cleared');
            },

            // --- Swaps ---

            // WHY raise-then-lower: Absorbed is ONE flag on Soline. Raising the old victim first consumes it for
            // them; lowering the new victim then sets it for the new one. Reversed order would +1 the old victim
            // using the new victim's flag.
            'defender swap raises the old defender then lowers the new one' => function () {
                $world = new TestWorld();
                [$soline, $ally, $oldDefender] = $this->scene($world);
                $newDefender = $world->placeCharacter(new GenericCharacter('New Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $newDefender->Finesse = 2;
                $newDefender->ModifiedFinesse = 2;

                $world->fireOn($soline, $this->started($ally->Id, $oldDefender->Id));
                $world->theah->takeQueuedEvents();
                $oldDefender->ModifiedFinesse = 1;

                // After the swap the duel table's opponent for the new defender is the challenger (ally).
                $world->theah->duelOpponent = $ally;
                $event = new EventDefenderSwapped();
                $event->oldDefenderId = $oldDefender->Id;
                $event->newDefenderId = $newDefender->Id;
                $world->fireOn($soline, $event);

                $events = $this->finesseEvents($world);
                Assert::count(2, $events, 'raise + lower');
                Assert::same($oldDefender->Id, $events[0]->CharacterId, 'old first');
                Assert::same(2, $events[0]->NewFinesse, 'old +1');
                Assert::same($newDefender->Id, $events[1]->CharacterId, 'new second');
                Assert::same(1, $events[1]->NewFinesse, 'new -1');
                Assert::false($oldDefender->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'old cleared');
                Assert::true($newDefender->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'new stamped');
                Assert::same($newDefender->Id, $soline->AffectedCharacterId, 'victim moved');
                Assert::true($soline->FinessePenaltyAbsorbed, 'flag belongs to the new victim');
            },

            'defender swap away from a floored old victim does not +1 them but absorbs for the new one' => function () {
                $world = new TestWorld();
                [$soline, $ally, $oldDefender] = $this->scene($world, 0);
                $newDefender = $world->placeCharacter(new GenericCharacter('New Defender'), Game::LOCATION_CITY_DOCKS, 2);
                $newDefender->Finesse = 2;
                $newDefender->ModifiedFinesse = 2;

                $world->fireOn($soline, $this->started($ally->Id, $oldDefender->Id));
                $world->theah->takeQueuedEvents();

                $world->theah->duelOpponent = $ally;
                $event = new EventDefenderSwapped();
                $event->oldDefenderId = $oldDefender->Id;
                $event->newDefenderId = $newDefender->Id;
                $world->fireOn($soline, $event);

                $events = $this->finesseEvents($world);
                Assert::count(1, $events, 'only the new victim is lowered');
                Assert::same($newDefender->Id, $events[0]->CharacterId, 'new victim');
                Assert::true($soline->FinessePenaltyAbsorbed, 'flag set for the new victim');
                Assert::false($oldDefender->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'old cleared');
            },

            // WHY: if the CHALLENGER is the adversary and the defender swaps, the debuffed challenger is still the opponent.
            'defender swap where the challenger is not Soline\'s side leaves the debuff alone' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world);
                $newDefender = $world->placeCharacter(new GenericCharacter('New Defender'), Game::LOCATION_CITY_DOCKS, 1);

                $world->fireOn($soline, $this->started($foe->Id, $ally->Id));
                $world->theah->takeQueuedEvents();

                $world->theah->duelOpponent = $foe;
                $event = new EventDefenderSwapped();
                $event->oldDefenderId = $ally->Id;
                $event->newDefenderId = $newDefender->Id;
                $world->fireOn($soline, $event);

                Assert::count(0, $this->finesseEvents($world), 'unchanged');
                Assert::same($foe->Id, $soline->AffectedCharacterId, 'challenger stays the victim');
            },

            'challenger swap raises the old challenger then lowers the new one while in a duel' => function () {
                $world = new TestWorld();
                [$soline, $ally, $oldChallenger] = $this->scene($world);
                $newChallenger = $world->placeCharacter(new GenericCharacter('New Challenger'), Game::LOCATION_CITY_DOCKS, 2);
                $newChallenger->Finesse = 2;
                $newChallenger->ModifiedFinesse = 2;

                $world->fireOn($soline, $this->started($oldChallenger->Id, $ally->Id));
                $world->theah->takeQueuedEvents();
                $oldChallenger->ModifiedFinesse = 1;
                $world->game->globals->set(Game::IN_DUEL, true);

                $world->theah->duelOpponent = $ally;
                $event = new EventChallengerSwapped();
                $event->oldChallengerId = $oldChallenger->Id;
                $event->newChallengerId = $newChallenger->Id;
                $world->fireOn($soline, $event);

                $events = $this->finesseEvents($world);
                Assert::count(2, $events, 'raise + lower');
                Assert::same($oldChallenger->Id, $events[0]->CharacterId, 'old first');
                Assert::same($newChallenger->Id, $events[1]->CharacterId, 'new second');
                Assert::same($newChallenger->Id, $soline->AffectedCharacterId, 'victim moved');
            },

            'challenger swap outside a duel is ignored' => function () {
                $world = new TestWorld();
                [$soline, $ally, $oldChallenger] = $this->scene($world);
                $newChallenger = $world->placeCharacter(new GenericCharacter('New Challenger'), Game::LOCATION_CITY_DOCKS, 2);
                $world->theah->duelOpponent = $ally;

                $event = new EventChallengerSwapped();
                $event->oldChallengerId = $oldChallenger->Id;
                $event->newChallengerId = $newChallenger->Id;
                $world->fireOn($soline, $event);

                Assert::count(0, $this->finesseEvents($world), 'not in duel');
                Assert::same(0, $soline->AffectedCharacterId, 'no victim');
            },

            // --- Floor re-absorb ---

            // WHY (2026-10-05-09): after a floored apply, the first time the victim's Finesse rises above 0
            // (Elena Sorcery +1) Soline must absorb her -1 then, otherwise the +1 shows unpenalized.
            'floor: first Finesse rise on the victim re-absorbs the -1 exactly once' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world, 0);
                $world->fireOn($soline, $this->started($ally->Id, $foe->Id));
                $world->theah->takeQueuedEvents();

                // Hub already applied +1 (Hub runs before cards): Finesse is now 1.
                $foe->ModifiedFinesse = 1;
                $rise = new EventCharacterFinesseModifed();
                $rise->CharacterId = $foe->Id;
                $rise->OldFinesse = 0;
                $rise->NewFinesse = 1;
                $world->fireOn($soline, $rise);

                $events = $this->finesseEvents($world);
                Assert::count(1, $events, 'absorbed');
                Assert::same(1, $events[0]->OldFinesse, 'old');
                Assert::same(0, $events[0]->NewFinesse, '-1 applied');
                Assert::true($soline->FinessePenaltyAbsorbed, 'now absorbed');

                // Her own re-apply event comes back through the same hook; no infinite loop.
                $world->theah->takeQueuedEvents();
                $again = new EventCharacterFinesseModifed();
                $again->CharacterId = $foe->Id;
                $again->OldFinesse = 1;
                $again->NewFinesse = 0;
                $world->fireOn($soline, $again);
                Assert::count(0, $this->finesseEvents($world), 'no loop');
            },

            'floor: Finesse event that leaves the victim at 0 does not absorb' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world, 0);
                $world->fireOn($soline, $this->started($ally->Id, $foe->Id));
                $world->theah->takeQueuedEvents();

                $noop = new EventCharacterFinesseModifed();
                $noop->CharacterId = $foe->Id;
                $noop->OldFinesse = 0;
                $noop->NewFinesse = 0;
                $world->fireOn($soline, $noop);

                Assert::count(0, $this->finesseEvents($world), 'still at floor');
                Assert::false($soline->FinessePenaltyAbsorbed, 'not absorbed');
            },

            'Finesse change on someone other than the victim is ignored' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world, 0);
                $world->fireOn($soline, $this->started($ally->Id, $foe->Id));
                $world->theah->takeQueuedEvents();

                $ally->ModifiedFinesse = 3;
                $other = new EventCharacterFinesseModifed();
                $other->CharacterId = $ally->Id;
                $other->OldFinesse = 2;
                $other->NewFinesse = 3;
                $world->fireOn($soline, $other);

                Assert::count(0, $this->finesseEvents($world), 'ignored');
            },

            // --- clearLeftoverDebuffs safety net (stDuelEnd) ---

            'clearLeftoverDebuffs restores a leftover victim even when Soline lost her tracking' => function () {
                $world = new TestWorld();
                [$soline, , $foe] = $this->scene($world);
                $foe->ModifiedFinesse = 1;
                $foe->addCondition(Game::SOLINE_EL_GATO_CONDITION);
                // Soline's fields were wiped (AffectedCharacterId = 0, Absorbed = false).

                _01089::clearLeftoverDebuffs($world->game);

                $events = $this->finesseEvents($world);
                Assert::count(1, $events, 'restored');
                Assert::same($foe->Id, $events[0]->CharacterId, 'victim');
                Assert::same(2, $events[0]->NewFinesse, '+1');
                Assert::false($foe->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'condition removed');
                Assert::same(0, $soline->AffectedCharacterId, 'tracking clean');
            },

            'clearLeftoverDebuffs persists Soline\'s cleared tracking' => function () {
                $world = new TestWorld();
                [$soline, $ally, $foe] = $this->scene($world);
                $world->fireOn($soline, $this->started($ally->Id, $foe->Id));
                $world->theah->takeQueuedEvents();
                $foe->ModifiedFinesse = 1;
                $world->game->dbCards[$soline->Id] = clone $soline; // stale DB copy still has the victim

                _01089::clearLeftoverDebuffs($world->game);

                Assert::same(0, $soline->AffectedCharacterId, 'in-world cleared');
                Assert::same(0, $world->game->dbCards[$soline->Id]->AffectedCharacterId, 'DB copy updated');
                Assert::false($world->game->dbCards[$soline->Id]->FinessePenaltyAbsorbed, 'DB absorbed cleared');
            },

            'clearLeftoverDebuffs skips a dead character with a stale condition' => function () {
                $world = new TestWorld();
                [, , $foe] = $this->scene($world);
                $foe->ModifiedFinesse = 1;
                $foe->addCondition(Game::SOLINE_EL_GATO_CONDITION);
                $world->game->forceInDiscardOrLocker = true;

                _01089::clearLeftoverDebuffs($world->game);

                Assert::count(0, $this->finesseEvents($world), 'no raise');
                Assert::true($foe->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'left alone');
            },

            'clearLeftoverDebuffs is a no-op when Soline is not in the world' => function () {
                $world = new TestWorld();
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $foe->addCondition(Game::SOLINE_EL_GATO_CONDITION);

                _01089::clearLeftoverDebuffs($world->game);

                Assert::count(0, $world->theah->queuedEvents, 'nothing queued');
                Assert::true($foe->hasCondition(Game::SOLINE_EL_GATO_CONDITION), 'untouched');
            },
        ];
    }
}
