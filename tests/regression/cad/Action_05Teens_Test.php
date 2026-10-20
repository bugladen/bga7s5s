<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestWorld;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04017;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\_04027;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\_05Teens;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions\Action_05Teens;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterTargeted;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTransition;

class Action_05Teens_Test extends TestCase
{
    public function name(): string
    {
        return 'Action_05Teens';
    }

    /**
     * Risk in hand (player 1), performer + equipped foe (player 2) at the Docks.
     * Docks is uncontrolled by default.
     *
     * @return array{world: TestWorld, risk: Risk, action: Action_05Teens, performer: GenericCharacter, foe: GenericCharacter, bow: _04017}
     */
    private function scene(): array
    {
        $world = new TestWorld();
        $risk = $world->placeCard(new _05Teens(), Game::LOCATION_HAND, 1);
        $performer = $world->placeCharacter(new GenericCharacter('Thief'), Game::LOCATION_CITY_DOCKS, 1);
        $foe = $world->placeCharacter(new GenericCharacter('Foe'), Game::LOCATION_CITY_DOCKS, 2);
        $bow = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);
        $bow->WealthCost = 1;
        $bow->AttachedToId = $foe->Id;
        $foe->Attachments = [$bow->Id];
        /** @var Action_05Teens $action */
        $action = $risk->getActions()[0];
        $world->game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);

        return compact('world', 'risk', 'action', 'performer', 'foe', 'bow');
    }

    public function tests(): array
    {
        return [
            'is a Vodacce risk with a single Covert action' => function () {
                $card = new _05Teens();
                Assert::instanceOf(Risk::class, $card, 'Risk');
                Assert::same(0, $card->WealthCost, 'cost');
                Assert::same(1, $card->Riposte, 'riposte');
                Assert::same(1, $card->Parry, 'parry');
                Assert::same(2, $card->Thrust, 'thrust');
                Assert::count(1, $card->getActions(), 'one action');
            },

            // WHY: Covert = only at an uncontrolled City location. "Engage your performer" is a
            // cost, so an engaged performer cannot be used. "Equipped" = a real attachment.
            'is only offered to an en garde performer at an uncontrolled city location with an equipped foe' => function () {
                ['world' => $world, 'action' => $action, 'performer' => $performer, 'foe' => $foe, 'bow' => $bow] = $this->scene();

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'baseline');

                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 2);
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'controlled location is not Covert');
                $world->theah->setLocationController(Game::LOCATION_CITY_DOCKS, 0);

                $performer->Engaged = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'engaged performer');
                $performer->Engaged = false;

                $performer->Location = Game::LOCATION_PLAYER_HOME;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'home performer');
                $performer->Location = Game::LOCATION_CITY_DOCKS;

                $bow->FakeAttachment = true;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'fake attachment does not count as equipped');
                $bow->FakeAttachment = false;

                $foe->Location = Game::LOCATION_CITY_FORUM;
                Assert::false($action->isAvailableToPlayer(1, $world->theah), 'foe must share the performer location');
                $foe->Location = Game::LOCATION_CITY_DOCKS;

                Assert::true($action->isAvailableToPlayer(1, $world->theah), 'restored');
            },

            // WHY: Engage is printed before the bullet = cost, paid at announce so a later cancel still paid it.
            'engages the performer at announce and starts the target chooser on trigger' => function () {
                ['world' => $world, 'risk' => $risk, 'action' => $action, 'performer' => $performer] = $this->scene();

                $action->announceAction($world->game);
                Assert::same($performer->Id, $world->theah->queuedOfType(EventCardEngaged::class)[0]->cardId, 'engage cost');

                $world->theah->takeQueuedEvents();
                $triggered = new EventActionTriggered();
                $triggered->actionId = $action->Id;
                $triggered->playerId = 1;
                $world->fireOn($risk, $triggered);
                Assert::same('05Teens', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'target chooser');
            },

            'target must be an opposing character at the performer location with an attachment' => function () {
                ['world' => $world, 'action' => $action, 'performer' => $performer, 'foe' => $foe] = $this->scene();

                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS, '')['ids'];
                Assert::same([$foe->Id], array_values($ids), 'only the equipped foe');

                [$ok] = $action->isValidTargetForAbility($world->game, $performer);
                Assert::false($ok, 'own character');

                $bare = $world->placeCharacter(new GenericCharacter('Bare'), Game::LOCATION_CITY_DOCKS, 2);
                [$ok] = $action->isValidTargetForAbility($world->game, $bare);
                Assert::false($ok, 'no attachment');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS, '', $foe->Id);
                Assert::same($foe->Id, (int) $world->game->globals->get(Game::CHOSEN_TARGET), 'target stored');
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'to the attachment chooser');
            },

            // WHY: The destroy is emitted from the targeting hook so a cancel (Unyielding Loyalty)
            // stops it. The "take control" prompt is queued after the destroy.
            'destroys the chosen attachment only if targeting survives, then asks about taking control' => function () {
                ['world' => $world, 'action' => $action, 'foe' => $foe, 'bow' => $bow] = $this->scene();
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);

                $listed = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_2, '')['attachments'];
                Assert::same([$bow->Id], array_column($listed, 'id'), 'attachment buttons');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_2, '', $bow->Id);
                Assert::count(1, $world->theah->queuedOfType(EventCharacterTargeted::class), 'targeting hook');
                Assert::count(0, $world->theah->queuedOfType(EventCardDiscardedFromPlay::class), 'not destroyed yet');

                $targeted = $world->theah->queuedOfType(EventCharacterTargeted::class)[0];
                $targeted->canceled = true;
                $world->theah->takeQueuedEvents();
                $action->handleEvent($targeted);
                Assert::count(0, $world->theah->queuedOfType(EventAttachmentUnequipped::class), 'cancel stops the unequip');
                Assert::count(0, $world->theah->queuedOfType(EventTransition::class), 'and the take-control prompt');

                $targeted->canceled = false;
                $action->handleEvent($targeted);
                $queued = $world->theah->takeQueuedEvents();
                Assert::same(EventAttachmentUnequipped::class, $queued[0]::class, 'unequip first');
                Assert::same($bow->Id, $queued[0]->attachmentId, 'unequip the chosen one');
                Assert::same(EventCardDiscardedFromPlay::class, $queued[1]::class, 'then destroy');
                Assert::same($bow->Id, $queued[1]->cardId, 'destroy the chosen one');
                Assert::same(EventTransition::class, $queued[2]::class, 'then the prompt');
                Assert::same('05Teens_3', $queued[2]->transition, 'take-control chooser');
            },

            'wrong or unequipped attachment is rejected' => function () {
                ['world' => $world, 'action' => $action, 'foe' => $foe] = $this->scene();
                $world->game->globals->set(Game::CHOSEN_TARGET, $foe->Id);
                $other = $world->placeCard(new _04017(), Game::LOCATION_CITY_DOCKS, 2);

                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_2, '', $other->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not equipped to the target');
            },

            // WHY: "You may take control ... equip it to your character paying all costs." Pay uses the
            // 04029_2 shape, and the equip discount / target read CHOSEN_PERFORMER (re-pointed).
            'may take control of the destroyed attachment and equip it to your character' => function () {
                ['world' => $world, 'action' => $action, 'performer' => $performer, 'bow' => $bow] = $this->scene();
                $buddy = $world->placeCharacter(new GenericCharacter('Buddy'), Game::LOCATION_CITY_FORUM, 1);
                $pay = $world->placeCard(new _04027(), Game::LOCATION_HAND, 1);

                // State after the destroy resolved: bow sits in its owner's discard pile.
                $bow->AttachedToId = 0;
                $bow->Location = $world->game->getPlayerDiscardDeckName(2);
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $bow->Id);

                $ids = $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_3, '')['ids'];
                Assert::same([$performer->Id, $buddy->Id], array_values($ids), 'any of your characters, home or city, not just the performer');

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_3, '', $buddy->Id);
                Assert::same($buddy->Id, (int) $world->game->globals->get(Game::CHOSEN_PERFORMER), 'equip target for discount + equip');
                Assert::same(1, $bow->ControllerId, 'control taken');
                Assert::same($bow->Id, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class)[0]->cardId, 'out of the discard');
                Assert::same(2, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class)[0]->playerId, 'owner pile');
                Assert::same($bow->Id, $world->theah->queuedOfType(EventCardAddedToHand::class)[0]->cardId, 'into hand');
                Assert::same(Game::PAY_STATE_EQUIP_ATTACHMENT, $world->theah->queuedOfType(EventEnteringPayState::class)[0]->payStateType, 'equip pay');
                Assert::same('05Teens_4', $world->theah->queuedOfType(EventTransition::class)[0]->transition, 'pay step');
                Assert::same('characterChosen', end($world->game->gamestate->transitions), 'left the chooser');

                // Pay step.
                $bow->Location = Game::LOCATION_HAND;
                $world->game->globals->set(Game::DISCOUNT, 0);
                $world->game->globals->set(Game::DISCOUNT_EXPLAINATIONS, '');
                $world->theah->takeQueuedEvents();

                $threw = false;
                try {
                    $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_4, '', []);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'cost 1 needs payment');

                $action->actFromActionWithIds($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_4, '', [$pay->Id]);
                Assert::same($pay->Id, $world->theah->queuedOfType(EventCardDiscardedFromHand::class)[0]->cardId, 'paid');
                Assert::same($buddy->Id, $world->theah->queuedOfType(EventAttachmentEquipping::class)[0]->characterId, 'equipped to the chosen character');
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
            },

            'a City attachment is taken from the City Discard instead' => function () {
                ['world' => $world, 'action' => $action, 'performer' => $performer, 'bow' => $bow] = $this->scene();
                $bow->AttachedToId = 0;
                $bow->Location = Game::LOCATION_CITY_DISCARD;
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $bow->Id);

                $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_3, '', $performer->Id);
                Assert::count(1, $world->theah->queuedOfType(EventCardRemovedFromCityDiscardPile::class), 'city discard removal');
                Assert::count(0, $world->theah->queuedOfType(EventCardRemovedFromPlayerDiscardPile::class), 'not a player pile');
            },

            'cannot take control of something that is not in a discard pile or give it to an opposing character' => function () {
                ['world' => $world, 'action' => $action, 'performer' => $performer, 'foe' => $foe, 'bow' => $bow] = $this->scene();
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $bow->Id);

                // Still equipped (destroy prevented): nothing to take.
                Assert::same([], $action->getArgsFromAction($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_3, '')['ids'], 'no candidates');
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_3, '', $performer->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'not in a discard pile');

                $bow->AttachedToId = 0;
                $bow->Location = $world->game->getPlayerDiscardDeckName(2);
                $threw = false;
                try {
                    $action->actFromActionWithId($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_3, '', $foe->Id);
                } catch (UserException) {
                    $threw = true;
                }
                Assert::true($threw, 'must be your own character');
            },

            'passing leaves the attachment destroyed and resolves the action' => function () {
                ['world' => $world, 'action' => $action, 'bow' => $bow] = $this->scene();
                $bow->Location = $world->game->getPlayerDiscardDeckName(2);
                $world->game->globals->set(Game::CHOSEN_ATTACHMENT, $bow->Id);

                $action->actFromActionPass($world->game, States::HIGH_DRAMA_PLAYER_TURN_05TEENS_3);
                Assert::count(1, $world->theah->queuedOfType(EventActionResolved::class), 'resolved');
                Assert::count(0, $world->theah->queuedOfType(EventCardAddedToHand::class), 'nothing taken');
                Assert::same(2, $bow->ControllerId, 'control unchanged');
                Assert::same('pass', end($world->game->gamestate->transitions), 'pass transition');
            },
        ];
    }
}
