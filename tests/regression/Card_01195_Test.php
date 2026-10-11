<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\_01195;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;

class Card_01195_Test extends TestCase
{
    public function name(): string
    {
        return '_01195 Eager Blade';
    }

    /**
     * @return array{0:_01195,1:GenericCharacter}
     */
    private function equipped(TestWorld $world): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
        /** @var _01195 $blade */
        $blade = $world->placeCard(new _01195(), Game::LOCATION_CITY_DOCKS, 1);
        $blade->AttachedToId = $host->Id;
        $host->Attachments[] = $blade->Id;
        return [$blade, $host];
    }

    public function tests(): array
    {
        return [
            // WHY: card-only CityAttachment — no Action/Technique/Maneuver/Reaction composites.
            'constructs Unique CityAttachment Weapon with Combat +1 and no composites' => function () {
                $blade = new _01195();
                Assert::instanceOf(CityAttachment::class, $blade, 'CityAttachment');
                Assert::false($blade instanceof IHasActions, 'no Actions');
                Assert::false($blade instanceof IHasTechniques, 'no Techniques');
                Assert::false($blade instanceof IHasManeuvers, 'no Maneuvers');
                Assert::false($blade instanceof IHasReactions, 'no Reactions');
                Assert::same(1, $blade->WealthCost, 'wealth');
                Assert::same(19, $blade->CityCardNumber, 'city #');
                Assert::same(1, $blade->CombatModifier, 'Combat +1');
                Assert::true($blade->hasTrait('Corruption'), 'Corruption');
                Assert::true($blade->hasTrait('Weapon'), 'Weapon');
                Assert::true($blade->hasTrait('Melee'), 'Melee');
                Assert::true($blade->hasTrait('Sword'), 'Sword');
                Assert::true($blade->hasTrait('Unique'), 'Unique');
            },

            // WHY (journal 2026-04-08-02): Forced — city discard → Grand Bazaar.
            'discard into city pile queues move to The Grand Bazaar' => function () {
                $world = new TestWorld();
                [$blade] = $this->equipped($world);

                $event = new EventCardAddedToCityDiscardPile();
                $event->cardId = $blade->Id;
                $world->fireOn($blade, $event);

                $moves = $world->theah->queuedOfType(EventCityCardAddedToLocation::class);
                Assert::count(1, $moves, 'one move');
                Assert::same($blade->Id, $moves[0]->cardId, 'blade');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $moves[0]->location, 'bazaar');
            },

            'discard of a different card does not move Eager Blade' => function () {
                $world = new TestWorld();
                [$blade] = $this->equipped($world);

                $event = new EventCardAddedToCityDiscardPile();
                $event->cardId = 99999;
                $world->fireOn($blade, $event);

                Assert::count(0, $world->theah->queuedEvents, 'ignored');
            },

            // WHY (journal 2026-04-08-02 / 2026-10-09 column note): equipped host combat card
            // → +1 Riposte + destroy (asEffect=true). Recycles via Forced above.
            'equipped host combat card grants +1 Riposte and destroys as effect' => function () {
                $world = new TestWorld();
                [$blade, $host] = $this->equipped($world);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $host->Id;
                $event->theah = $world->theah;
                $blade->handleEvent($event);

                Assert::same(1, $event->riposte, '+1 Riposte');
                Assert::count(1, $event->explanations, 'explained');

                $unequip = $world->theah->queuedOfType(EventAttachmentUnequipped::class);
                Assert::count(1, $unequip, 'unequip');
                Assert::same($blade->Id, $unequip[0]->attachmentId, 'blade');
                Assert::same($host->Id, $unequip[0]->characterId, 'host');

                $discards = $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class);
                Assert::count(1, $discards, 'city discard');
                Assert::same($blade->Id, $discards[0]->cardId, 'blade');
                Assert::true($discards[0]->asEffect, 'destroy is effect');
            },

            // WHY: destruction is mandatory even when Riposte is dashed.
            'dashed Riposte still destroys Eager Blade' => function () {
                $world = new TestWorld();
                [$blade, $host] = $this->equipped($world);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $host->Id;
                $event->dashedRiposte = true;
                $event->theah = $world->theah;
                $blade->handleEvent($event);

                Assert::same(0, $event->riposte, 'dashed unchanged');
                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'destroy');
                Assert::count(1, $world->theah->queuedOfType(EventCardAddedToCityDiscardPile::class), 'discard');
            },

            'adversary combat card does not trigger' => function () {
                $world = new TestWorld();
                [$blade] = $this->equipped($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $foe->Id;
                $event->theah = $world->theah;
                $blade->handleEvent($event);

                Assert::same(0, $event->riposte, 'no Riposte');
                Assert::count(0, $world->theah->queuedEvents, 'no destroy');
            },

            'unattached blade does not trigger on combat card' => function () {
                $world = new TestWorld();
                [$blade, $host] = $this->equipped($world);
                $blade->AttachedToId = 0;

                $event = new EventDuelCalculateCombatCardStats();
                $event->actorId = $host->Id;
                $event->theah = $world->theah;
                $blade->handleEvent($event);

                Assert::same(0, $event->riposte, 'no Riposte');
                Assert::count(0, $world->theah->queuedEvents, 'no destroy');
            },
        ];
    }
}
