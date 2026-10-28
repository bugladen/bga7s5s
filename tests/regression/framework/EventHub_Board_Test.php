<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationPressureResult;

/**
 * EventHub: board mutations (move, engage, claim, renown, stats, pressure claim gate).
 *
 * WHY: TestTheah stubs queueEvent/runEvents so the hub never drains in card suites.
 * Calling handleEvent directly pins the central state writes those suites miss.
 */
class EventHub_Board_Test extends TestCase
{
    public function name(): string
    {
        return 'EventHub: board (move, engage, claim, renown, stats, pressure)';
    }

    private function world(): TestWorld
    {
        return new TestWorld();
    }

    public function tests(): array
    {
        return [
            // ---- canceled --------------------------------------------------------------------

            'canceled events are no-ops' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Idle'), Game::LOCATION_CITY_DOCKS, 1);
                $event = EventFactory::createCardEngagedEvent(1, $c->Id);
                $event->canceled = true;
                $world->applyHub($event);
                Assert::false($c->Engaged, 'still en garde');
                Assert::count(0, $world->game->notify->messages, 'no notify');
            },

            // ---- engage / engarde ------------------------------------------------------------

            'CardEngaged sets Engaged and notifies' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Sword'), Game::LOCATION_CITY_FORUM, 1);
                $world->applyHub(EventFactory::createCardEngagedEvent(1, $c->Id));
                Assert::true($c->Engaged, 'engaged');
                Assert::true($c->IsUpdated, 'updated');
                Assert::same('cardEngaged', $world->game->notify->messages[0]['type'], 'type');
            },

            'CardEngarded clears Engaged' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Ready'), Game::LOCATION_CITY_FORUM, 1);
                $c->Engaged = true;
                $world->applyHub(EventFactory::createCardEngardedEvent(1, $c->Id));
                Assert::false($c->Engaged, 'en garde');
                Assert::same('cardEngarded', $world->game->notify->messages[0]['type'], 'type');
            },

            // ---- move ------------------------------------------------------------------------

            'CardMoving queues CardMoved with the same fields' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Walker'), Game::LOCATION_CITY_DOCKS, 1);
                $world->applyHub(EventFactory::createCardMovingEvent(
                    1,
                    $c->Id,
                    Game::LOCATION_CITY_DOCKS,
                    Game::LOCATION_CITY_FORUM,
                    false,
                    99,
                    'ability'
                ));
                $queued = $world->theah->queuedOfType(EventCardMoved::class);
                Assert::count(1, $queued, 'one moved');
                /** @var EventCardMoved $moved */
                $moved = $queued[0];
                Assert::same($c->Id, $moved->cardId, 'card');
                Assert::same(Game::LOCATION_CITY_FORUM, $moved->toLocation, 'to');
                Assert::false($moved->engage, 'engage flag');
                Assert::same(99, $moved->sourceId, 'source');
                Assert::same('ability', $moved->abilityId, 'ability');
            },

            'CardMoved relocates character and equipped attachments' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Host'), Game::LOCATION_CITY_DOCKS, 1);
                $att = $world->placeCard(new GenericAttachment('Gear'), Game::LOCATION_CITY_DOCKS, 1);
                $c->Attachments[] = $att->Id;
                $att->AttachedToId = $c->Id;

                $moved = new EventCardMoved();
                $moved->initiatingPlayerId = 1;
                $moved->cardId = $c->Id;
                $moved->fromLocation = Game::LOCATION_CITY_DOCKS;
                $moved->toLocation = Game::LOCATION_CITY_BAZAAR;
                $moved->engage = true;
                $moved->sourceId = 0;
                $world->applyHub($moved);

                Assert::same(Game::LOCATION_CITY_BAZAAR, $c->Location, 'char loc');
                Assert::true($c->Engaged, 'engaged on move');
                Assert::same(Game::LOCATION_CITY_BAZAAR, $att->Location, 'att follows');
                Assert::same('cardMoved', $world->game->notify->messages[0]['type'], 'notify');
            },

            // ---- claim / uncontrol -----------------------------------------------------------

            'LocationClaimed dual-writes controller + notifies' => function () {
                $world = $this->world();
                $world->applyHub(EventFactory::createLocationClaimedEvent(1, null, Game::LOCATION_CITY_FORUM));
                Assert::same(1, $world->theah->getCityLocation(Game::LOCATION_CITY_FORUM)->Controller, 'city');
                Assert::same(1, $world->game->getControllerForLocation(Game::LOCATION_CITY_FORUM), 'globals');
                Assert::same('locationClaimed', $world->game->notify->messages[0]['type'], 'type');
            },

            'LocationBecomesUncontrolled clears controller' => function () {
                $world = $this->world();
                $world->theah->setLocationController(Game::LOCATION_CITY_FORUM, 2);
                $world->game->setControllerForLocation(Game::LOCATION_CITY_FORUM, 2);
                $world->applyHub(EventFactory::createLocationBecomesUncontrolledEvent(1, Game::LOCATION_CITY_FORUM));
                Assert::same(0, $world->theah->getCityLocation(Game::LOCATION_CITY_FORUM)->Controller, 'city');
                Assert::same(0, $world->game->getControllerForLocation(Game::LOCATION_CITY_FORUM), 'globals');
            },

            // ---- renown ----------------------------------------------------------------------

            'RenownAddedToLocation updates city + game' => function () {
                $world = $this->world();
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 1);
                $world->applyHub(EventFactory::createRenownAddedToLocationEvent(
                    1,
                    Game::LOCATION_CITY_DOCKS,
                    2,
                    'test'
                ));
                Assert::same(3, $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->Renown, 'city');
                Assert::same(3, $world->game->getRenownForLocation(Game::LOCATION_CITY_DOCKS), 'game');
            },

            'RenownRemovedFromLocation clamps at 0' => function () {
                $world = $this->world();
                $world->theah->setLocationRenown(Game::LOCATION_CITY_DOCKS, 2);
                $world->applyHub(EventFactory::createRenownRemovedFromLocationEvent(
                    1,
                    Game::LOCATION_CITY_DOCKS,
                    5,
                    'overflow'
                ));
                Assert::same(0, $world->theah->getCityLocation(Game::LOCATION_CITY_DOCKS)->Renown, 'clamped');
            },

            'RenownRemovedFromLocation removeAll clears live amount' => function () {
                $world = $this->world();
                $world->theah->setLocationRenown(Game::LOCATION_CITY_BAZAAR, 4);
                $event = EventFactory::createRenownRemovedFromLocationEvent(
                    1,
                    Game::LOCATION_CITY_BAZAAR,
                    1,
                    'Shifting Tides'
                );
                $event->removeAll = true;
                $world->applyHub($event);
                Assert::same(0, $world->theah->getCityLocation(Game::LOCATION_CITY_BAZAAR)->Renown, 'cleared');
                Assert::same(4, $world->game->notify->messages[0]['args']['amount'], 'notify amount was live');
            },

            'Reknown on card add/remove clamps at 0' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Token'), Game::LOCATION_CITY_DOCKS, 1);
                $world->applyHub(EventFactory::createReknownAddedToCardEvent(1, $c->Id, 3));
                Assert::same(3, $c->Reknown, 'added');
                $world->applyHub(EventFactory::createReknownRemovedFromCardEvent(1, $c->Id, 10));
                Assert::same(0, $c->Reknown, 'clamped');
            },

            'PlayerGainsReknown and PlayerLosesReknown via getDBObject' => function () {
                $world = $this->world();
                $world->game->setPlayerReknown(1, 5);
                $world->applyHub(EventFactory::createPlayerGainsReknownEvent(1, 3));
                Assert::same(8, $world->game->getPlayerReknown(1), 'gained');
                $world->applyHub(EventFactory::createPlayerLosesReknownEvent(1, 100));
                Assert::same(0, $world->game->getPlayerReknown(1), 'lost clamped');
            },

            'PlayerLosesReknown is a no-op at zero' => function () {
                $world = $this->world();
                $world->game->setPlayerReknown(1, 0);
                $world->applyHub(EventFactory::createPlayerLosesReknownEvent(1, 2));
                Assert::same(0, $world->game->getPlayerReknown(1), 'still zero');
                Assert::count(0, $world->game->notify->messages, 'no notify when already broke');
            },

            // ---- character stats -------------------------------------------------------------

            'CombatModified writes absolute NewCombat (floored at 0)' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Fighter'), Game::LOCATION_CITY_DOCKS, 1);
                $c->ModifiedCombat = 4;
                $world->applyHub(EventFactory::createCharacterCombatModifiedEvent(1, $c->Id, 4, 2, 'debuff'));
                Assert::same(2, $c->ModifiedCombat, 'absolute');
                $world->applyHub(EventFactory::createCharacterCombatModifiedEvent(1, $c->Id, 2, -3, 'floor'));
                Assert::same(0, $c->ModifiedCombat, 'floor');
            },

            'FinesseModifed applies Old→New as a live delta' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Duelist'), Game::LOCATION_CITY_DOCKS, 1);
                $c->ModifiedFinesse = 5;
                // Snapshot said 3→1 (−2). Live is 5, so result must be 3 — not absolute 1.
                $world->applyHub(EventFactory::createCharacterFinesseModifedEvent(1, $c->Id, 3, 1, 'clear bonus'));
                Assert::same(3, $c->ModifiedFinesse, 'delta from live');
            },

            'InfluenceModified writes absolute NewInfluence; dashed skips' => function () {
                $world = $this->world();
                $c = $world->placeCharacter(new GenericCharacter('Diplomat'), Game::LOCATION_CITY_DOCKS, 1);
                $c->ModifiedInfluence = 4;
                $world->applyHub(EventFactory::createCharacterInfluenceModifiedEvent(1, $c->Id, 4, 1, 'aura'));
                Assert::same(1, $c->ModifiedInfluence, 'absolute');

                $dashed = $world->placeCharacter(new GenericCharacter('Dash'), Game::LOCATION_CITY_DOCKS, 1);
                $dashed->DashedInfluence = true;
                $dashed->ModifiedInfluence = 2;
                $world->applyHub(EventFactory::createCharacterInfluenceModifiedEvent(1, $dashed->Id, 2, 0, 'ignored'));
                Assert::same(2, $dashed->ModifiedInfluence, 'dashed unchanged');
            },

            // ---- CharacterTargeted / pressure result -----------------------------------------

            'CharacterTargeted syncs CHOSEN_TARGET when abilityId matches CHOSEN_ACTION' => function () {
                $world = $this->world();
                $world->game->globals->set(Game::CHOSEN_ACTION, 'challengeBasic');
                $world->game->globals->set(Game::CHOSEN_TARGET, 111);
                $match = EventFactory::createCharacterTargetedEvent(1, 222, 10, 'challengeBasic');
                $world->applyHub($match);
                Assert::same(222, $world->game->globals->get(Game::CHOSEN_TARGET), 'synced');

                $mismatch = EventFactory::createCharacterTargetedEvent(1, 333, 10, 'otherAbility');
                $world->applyHub($mismatch);
                Assert::same(222, $world->game->globals->get(Game::CHOSEN_TARGET), 'unrelated ignored');
            },

            'LocationPressureResult basic success queues Claim + ActionResolved' => function () {
                $world = $this->world();
                $event = EventFactory::createLocationPressureResultEvent(
                    1,
                    50,
                    Game::LOCATION_CITY_FORUM,
                    'Influence',
                    true,
                    'totals',
                    true,
                    'claim'
                );
                $world->applyHub($event);
                Assert::count(1, $world->theah->queuedOfType(EventLocationClaimed::class), 'claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'LocationPressureResult basic success respects CanBeClaimed=false' => function () {
                $world = $this->world();
                $world->theah->setLocationCanBeClaimed(Game::LOCATION_CITY_FORUM, false);
                $event = EventFactory::createLocationPressureResultEvent(
                    1,
                    50,
                    Game::LOCATION_CITY_FORUM,
                    'Influence',
                    true,
                    'totals',
                    true,
                    'claim'
                );
                $world->applyHub($event);
                Assert::count(0, $world->theah->queuedOfType(EventLocationClaimed::class), 'no claim');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'still resolved');
                $msgs = array_column($world->game->notify->messages, 'message');
                Assert::true(
                    (bool) array_filter($msgs, fn($m) => str_contains((string) $m, 'cannot be claimed')),
                    'cannot claim message'
                );
            },

            'CardHidden moves to permanently hidden' => function () {
                $world = $this->world();
                $c = $world->placeCard(new GenericAttachment('Secret'), Game::LOCATION_HAND, 1);
                $world->applyHub(EventFactory::createCardHiddenEvent(1, $c->Id));
                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $c->Location, 'hidden');
            },

            'CardRemovedFromPlayerDiscardPile permanentlyHide moves off discard' => function () {
                $world = $this->world();
                $c = $world->placeCard(new GenericAttachment('Junk'), 'Discard-1', 1);
                $world->applyHub(EventFactory::createCardRemovedFromPlayerDiscardPileEvent(1, $c->Id, false, true));
                Assert::same(Game::LOCATION_PERMANENTLY_HIDDEN, $c->Location, 'hid');
            },
        ];
    }
}
