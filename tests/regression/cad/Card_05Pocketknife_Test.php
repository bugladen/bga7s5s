<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\FactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Pocketknife;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\maneuvers\Maneuver_05Pocketknife;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\techniques\Technique_05Pocketknife;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToFactionDeck;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Card_05Pocketknife_Test extends TestCase
{
    public function name(): string
    {
        return 'Card_05Pocketknife';
    }

    /** @return array{0:GenericCharacter,1:_05Pocketknife,2:Technique_05Pocketknife,3:Maneuver_05Pocketknife} */
    private function attach(TestWorld $world, string $location = Game::LOCATION_CITY_DOCKS): array
    {
        $host = $world->placeCharacter(new GenericCharacter('Host'), $location, 1);
        $knife = $world->placeCard(new _05Pocketknife(), $location, 1);
        $knife->AttachedToId = $host->Id;
        /** @var Technique_05Pocketknife $technique */
        $technique = $knife->getTechniques()[0];
        /** @var Maneuver_05Pocketknife $maneuver */
        $maneuver = $knife->getManeuvers()[0];
        return [$host, $knife, $technique, $maneuver];
    }

    public function tests(): array
    {
        return [
            'constructs Castille FactionAttachment with Technique and Maneuver' => function () {
                $knife = new _05Pocketknife();
                Assert::instanceOf(FactionAttachment::class, $knife, 'FactionAttachment');
                Assert::instanceOf(IHasTechniques::class, $knife, 'techniques');
                Assert::instanceOf(IHasManeuvers::class, $knife, 'maneuvers');
                Assert::same(0, $knife->WealthCost, 'cost');
                Assert::same(0, $knife->Riposte, 'riposte');
                Assert::same(3, $knife->Parry, 'parry');
                Assert::same(2, $knife->Thrust, 'thrust');
                Assert::true($knife->hasTrait('Weapon'), 'Weapon');
                Assert::true($knife->hasTrait('Sword'), 'Sword');
                Assert::true($knife->hasTrait('Flourish'), 'Flourish');
                Assert::true($knife->hasTrait('Unique'), 'Unique');
                Assert::instanceOf(Technique_05Pocketknife::class, $knife->getTechniques()[0], 'technique');
                Assert::instanceOf(Maneuver_05Pocketknife::class, $knife->getManeuvers()[0], 'maneuver');
            },

            // WHY: Covert Technique — duel + actor + equipped + uncontrolled City (Corncob).
            'Covert Technique is only offered in a duel at an uncontrolled city when host is actor' => function () {
                $world = new TestWorld();
                [$host, , $technique] = $this->attach($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $host;
                $world->theah->duelOpponent = $foe;

                Assert::true($technique->isAvailableToPlayer(1, $world->theah), 'baseline');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'controlled');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $host->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'Home');
                $host->Location = Game::LOCATION_CITY_DOCKS;

                $world->theah->duelActor = $foe;
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not actor');
                $world->theah->duelActor = $host;

                $world->game->globals->set(Game::IN_DUEL, false);
                Assert::false($technique->isAvailableToPlayer(1, $world->theah), 'not in duel');
            },

            'Covert Technique queues unequip-to-top-of-deck and adversary discard transition' => function () {
                $world = new TestWorld();
                [$host, $knife, $technique] = $this->attach($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $handCard = $world->placeCard(new GenericCharacter('Hand'), Game::LOCATION_HAND, 2);
                $handCard->ControllerId = 2;

                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $host;
                $world->theah->duelOpponent = $foe;

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);

                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'unequip');
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromPlay::class), 'remove');
                $deckAdds = $world->theah->queuedOfType(EventCardAddedToFactionDeck::class);
                Assert::count(1, $deckAdds, 'deck');
                Assert::true($deckAdds[0]->onTop, 'on top');
                Assert::same($knife->Id, $deckAdds[0]->cardId, 'this card');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'discard picker');
                Assert::same('05Pocketknife', $transitions[0]->transition, 'key');
                Assert::same(2, $transitions[0]->playerId, 'adversary chooses');
                Assert::same($knife->Id, $transitions[0]->sourceId, 'attachment sourceId');
            },

            'Covert Technique skips discard transition when adversary hand is empty' => function () {
                $world = new TestWorld();
                [$host, , $technique] = $this->attach($world);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);

                $world->game->globals->set(Game::IN_DUEL, true);
                $world->theah->duelActor = $host;
                $world->theah->duelOpponent = $foe;

                $resolve = new EventResolveTechnique();
                $resolve->techniqueId = $technique->Id;
                $resolve->theah = $world->theah;
                $technique->handleEvent($resolve);

                Assert::count(1, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'still unequips');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'no picker');
            },

            // WHY: Gambling Maneuver — IN_DUEL + DUEL_GAMBLED only (no Covert / attach gate).
            'Gambling Maneuver requires IN_DUEL and DUEL_GAMBLED' => function () {
                $world = new TestWorld();
                $knife = $world->placeCard(new _05Pocketknife(), Game::LOCATION_DUELING_LINE, 1);
                /** @var Maneuver_05Pocketknife $maneuver */
                $maneuver = $knife->getManeuvers()[0];

                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'baseline off');

                $world->game->globals->set(Game::IN_DUEL, true);
                Assert::false($maneuver->isAvailableToPlayer(1, $world->theah), 'duel without gamble');

                $world->game->globals->set(Game::DUEL_GAMBLED, true);
                Assert::true($maneuver->isAvailableToPlayer(1, $world->theah), 'gambled');
            },

            'Gambling Maneuver wounds adversary and offers dueling-line return picker' => function () {
                $world = new TestWorld();
                $knife = $world->placeCard(new _05Pocketknife(), Game::LOCATION_DUELING_LINE, 1);
                $actor = $world->placeCharacter(new GenericCharacter('Actor'), Game::LOCATION_CITY_DOCKS, 1);
                $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
                $other = $world->placeCard(new GenericCharacter('OtherCard'), Game::LOCATION_DUELING_LINE, 1);
                $other->ControllerId = 1;

                $world->theah->duelActor = $actor;
                $world->theah->duelOpponent = $foe;
                $world->game->globals->set(Game::IN_DUEL, true);
                $world->game->globals->set(Game::DUEL_GAMBLED, true);

                /** @var Maneuver_05Pocketknife $maneuver */
                $maneuver = $knife->getManeuvers()[0];

                $resolve = new EventResolveManeuver();
                $resolve->maneuverId = $maneuver->Id;
                $resolve->theah = $world->theah;
                $maneuver->handleEvent($resolve);

                $wounds = $world->theah->queuedOfType(EventCharacterBeingWounded::class);
                Assert::count(1, $wounds, 'wound');
                Assert::same($foe->Id, $wounds[0]->characterId, 'adversary');

                $transitions = $world->theah->queuedOfType(EventTransition::class);
                Assert::count(1, $transitions, 'picker');
                Assert::same('05Pocketknife', $transitions[0]->transition, 'key');
                Assert::same(1, $transitions[0]->playerId, 'controller chooses');
            },

            'Gambling Maneuver act returns chosen dueling-line card to hand' => function () {
                $world = new TestWorld();
                $knife = $world->placeCard(new _05Pocketknife(), Game::LOCATION_DUELING_LINE, 1);
                $other = $world->placeCard(new GenericCharacter('OtherCard'), Game::LOCATION_DUELING_LINE, 1);
                $other->ControllerId = 1;

                /** @var Maneuver_05Pocketknife $maneuver */
                $maneuver = $knife->getManeuvers()[0];

                $maneuver->actFromManeuverWithId(
                    $world->game,
                    \Bga\Games\SeventhSeaCityOfFiveSails\States::DUEL_RESOLVE_MANEUVER_05POCKETKNIFE,
                    'duelResolveManeuver_05Pocketknife',
                    $other->Id
                );

                $adds = $world->theah->queuedOfType(EventCardAddedToHand::class);
                Assert::count(1, $adds, 'to hand');
                Assert::same($other->Id, $adds[0]->cardId, 'chosen card');
            },
        ];
    }
}
