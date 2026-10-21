<?php

declare(strict_types=1);

namespace Bga\Games\SeventhSeaCityOfFiveSails\Tests\Regression\Framework;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\AbilityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\Assert;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\FrameworkActionsGame;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericCharacterAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\GenericFactionAttachment;
use Bga\Games\SeventhSeaCityOfFiveSails\Tests\Harness\TestCase;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionResolved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionUsed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipping;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentUnequipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPressureOccuring;

/** FrameworkActionsTrait: Equip action (all selection + payment paths) and the basic Claim action. */
class FrameworkActions_EquipClaim_Test extends TestCase
{
    public function name(): string
    {
        return 'FrameworkActionsTrait: equip & claim';
    }

    /** @return list<int> ids of fresh hand cards for player 1 (plain, or Wealth-trait when $wealth) */
    private function hand(FrameworkActionsGame $game, int $count, bool $wealth = false): array
    {
        $ids = [];
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $game->place(new GenericCharacter("Pay $i", $wealth ? ['Wealth'] : []), Game::LOCATION_HAND, 1)->Id;
        }
        return $ids;
    }

    /**
     * Equip scene: player 1's performer at the Docks, an attachment of the requested kind in
     * hand (default) or lying at the Docks, and the globals the equip flow has set by then.
     *
     * @return array{0:FrameworkActionsGame,1:GenericCharacter,2:GenericFactionAttachment}
     */
    private function equipScene(int $cost = 2, string $attachmentLocation = Game::LOCATION_HAND): array
    {
        $game = new FrameworkActionsGame();
        $performer = $game->place(new GenericCharacter('Equipper'), Game::LOCATION_CITY_DOCKS, 1);
        $attachment = $game->place(new GenericFactionAttachment('Rapier', $cost), $attachmentLocation, $attachmentLocation === Game::LOCATION_HAND ? 1 : 0);
        $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
        $game->globals->set(Game::CHOSEN_CARD, $attachment->Id);
        $game->globals->set(Game::DISCOUNT, 0);
        $game->globals->set(Game::EQUIP_TYPE, Game::NORMAL_EQUIP_TYPE);
        return [$game, $performer, $attachment];
    }

    public function tests(): array
    {
        return [
            // ---- Equip: start ----------------------------------------------------------------------

            'actHighDramaEquipActionStart proceeds with an attachment in hand and resets the equip type' => function () {
                $game = new FrameworkActionsGame();
                $game->place(new GenericFactionAttachment('Rapier'), Game::LOCATION_HAND, 1);
                $game->globals->set(Game::EQUIP_TYPE, Game::SMUGGLED_ITEM_EQUIP_TYPE); // stale

                $game->actHighDramaEquipActionStart();

                Assert::same(Game::NORMAL_EQUIP_TYPE, $game->globals->get(Game::EQUIP_TYPE), 'normal equip');
                Assert::same(['equipActionStart'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaEquipActionStart proceeds when a character stands with an unattached attachment' => function () {
                $game = new FrameworkActionsGame();
                $game->place(new GenericCharacter('Equipper'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericAttachment('Lying around'), Game::LOCATION_CITY_DOCKS, 0);

                $game->actHighDramaEquipActionStart();

                Assert::same(['equipActionStart'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaEquipActionStart is refused with no attachment in hand or in reach' => function () {
                $game = new FrameworkActionsGame();
                $game->place(new GenericCharacter('Equipper'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericAttachment('Far away'), Game::LOCATION_CITY_FORUM, 0);

                Assert::throws(fn() => $game->actHighDramaEquipActionStart(), 'Equipping is not allowed', 'refused');
                Assert::same([], $game->gamestate->transitions, 'no transition');
                Assert::false($game->globals->has(Game::EQUIP_TYPE), 'equip type untouched');
            },

            // ---- Equip: performer ---------------------------------------------------------------------

            'actHighDramaEquipActionPerformerChosen accepts a character with an attachment at their location' => function () {
                $game = new FrameworkActionsGame();
                $performer = $game->place(new GenericCharacter('Equipper'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericAttachment('Lying around'), Game::LOCATION_CITY_DOCKS, 0);

                $game->actHighDramaEquipActionPerformerChosen($performer->Id);

                Assert::same($performer->Id, $game->globals->get(Game::CHOSEN_PERFORMER), 'recorded');
                Assert::same(['performerChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaEquipActionPerformerChosen: a hand attachment makes any of your characters eligible, even Home' => function () {
                $game = new FrameworkActionsGame();
                $city = $game->place(new GenericCharacter('In the city'), Game::LOCATION_CITY_FORUM, 1);
                $home = $game->place(new GenericCharacter('At home'), Game::LOCATION_PLAYER_HOME, 1);
                $game->place(new GenericFactionAttachment('Rapier'), Game::LOCATION_HAND, 1);

                $game->actHighDramaEquipActionPerformerChosen($city->Id);
                $game->actHighDramaEquipActionPerformerChosen($home->Id);

                Assert::same(['performerChosen', 'performerChosen'], $game->gamestate->transitions, 'both accepted');
            },

            'actHighDramaEquipActionPerformerChosen refuses a character with nothing to equip' => function () {
                $game = new FrameworkActionsGame();
                $stranded = $game->place(new GenericCharacter('Stranded'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericAttachment('Far away'), Game::LOCATION_CITY_FORUM, 0);
                $home = $game->place(new GenericCharacter('At home'), Game::LOCATION_PLAYER_HOME, 1);

                Assert::throws(fn() => $game->actHighDramaEquipActionPerformerChosen($stranded->Id), 'cannot equip attachments', 'city');
                Assert::throws(fn() => $game->actHighDramaEquipActionPerformerChosen($home->Id), 'cannot equip attachments', 'home needs a hand attachment');
                Assert::false($game->globals->has(Game::CHOSEN_PERFORMER), 'nothing recorded');
            },

            'actHighDramaEquipActionPerformerChosen refuses a host that cannot receive attachments' => function () {
                $game = new FrameworkActionsGame();
                $host = $game->place(new class('No Hands') extends GenericCharacter {
                    public function canReceiveAttachments(): bool
                    {
                        return false;
                    }
                }, Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericFactionAttachment('Rapier'), Game::LOCATION_HAND, 1);

                Assert::throws(fn() => $game->actHighDramaEquipActionPerformerChosen($host->Id), 'cannot equip attachments', 'blocked host');
            },

            'actHighDramaEquipActionPerformerChosen: opponents are only eligible with a hand attachment that can equip to them' => function () {
                $game = new FrameworkActionsGame();
                $theirs = $game->place(new GenericCharacter('Theirs'), Game::LOCATION_CITY_DOCKS, 2);
                $game->place(new GenericFactionAttachment('Ordinary', 0, false), Game::LOCATION_HAND, 1);

                Assert::throws(fn() => $game->actHighDramaEquipActionPerformerChosen($theirs->Id), 'cannot equip attachments', 'ordinary attachment');

                $game->place(new GenericFactionAttachment('Poisoned Gift', 0, true), Game::LOCATION_HAND, 1);
                $game->actHighDramaEquipActionPerformerChosen($theirs->Id);
                Assert::same(['performerChosen'], $game->gamestate->transitions, 'opponent accepted');
            },

            'actHighDramaEquipActionPerformerChosen honours the Extra Action performer lock' => function () {
                $game = new FrameworkActionsGame();
                $performer = $game->place(new GenericCharacter('Equipper'), Game::LOCATION_CITY_DOCKS, 1);
                $game->place(new GenericFactionAttachment('Rapier'), Game::LOCATION_HAND, 1);
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 4242);

                Assert::throws(fn() => $game->actHighDramaEquipActionPerformerChosen($performer->Id), 'same character', 'locked elsewhere');
            },

            // ---- Equip: attachment from hand ----------------------------------------------------------------

            'actHighDramaEquipActionAttachmentFromHandSelected queues the equip payment prompt' => function () {
                [$game, , $attachment] = $this->equipScene();
                $game->globals->set(Game::CHOSEN_CARD, 0);

                $game->actHighDramaEquipActionAttachmentFromHandSelected($attachment->Id);

                Assert::same($attachment->Id, $game->globals->get(Game::CHOSEN_CARD), 'recorded');
                $pay = $game->queued(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay prompt');
                Assert::same(1, $pay[0]->playerId, 'active player pays');
                Assert::same($attachment->Id, $pay[0]->cardId, 'for the attachment');
                Assert::same(Game::PAY_STATE_EQUIP_ATTACHMENT, $pay[0]->payStateType, 'equip payment');
                Assert::same(['attachmentSelected'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaEquipActionAttachmentFromHandSelected refuses an attachment that is not in your hand' => function () {
                [$game, , $attachment] = $this->equipScene();
                $onTable = $game->place(new GenericFactionAttachment('On the table'), Game::LOCATION_CITY_DOCKS, 0);
                $theirHand = $game->place(new GenericFactionAttachment('Their card'), Game::LOCATION_HAND, 2);

                Assert::throws(fn() => $game->actHighDramaEquipActionAttachmentFromHandSelected($onTable->Id), "not in Player's Hand", 'on the table');
                Assert::throws(fn() => $game->actHighDramaEquipActionAttachmentFromHandSelected($theirHand->Id), "not in Player's Hand", "opponent's hand");
                Assert::throws(fn() => $game->actHighDramaEquipActionAttachmentFromHandSelected(99999), "not in Player's Hand", 'unknown');
                Assert::count(0, $game->queued(EventEnteringPayState::class), 'no pay prompt');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaEquipActionAttachmentFromHandSelected: equipping an opponent needs an attachment that allows it' => function () {
                [$game, , $ordinary] = $this->equipScene();
                $theirs = $game->place(new GenericCharacter('Theirs'), Game::LOCATION_CITY_DOCKS, 2);
                $gift = $game->place(new GenericFactionAttachment('Poisoned Gift', 0, true), Game::LOCATION_HAND, 1);
                $game->globals->set(Game::CHOSEN_PERFORMER, $theirs->Id);

                Assert::throws(
                    fn() => $game->actHighDramaEquipActionAttachmentFromHandSelected($ordinary->Id),
                    'cannot be equipped to an opponent',
                    'ordinary attachment'
                );

                $game->actHighDramaEquipActionAttachmentFromHandSelected($gift->Id);
                Assert::same(['attachmentSelected'], $game->gamestate->transitions, 'gift accepted');
            },

            // ---- Equip: attachment from play ----------------------------------------------------------------

            'actHighDramaEquipActionAttachmentFromPlaySelected accepts an attachment at the performer location' => function () {
                [$game, $performer, $attachment] = $this->equipScene(2, Game::LOCATION_CITY_DOCKS);

                $game->actHighDramaEquipActionAttachmentFromPlaySelected($attachment->Id);

                Assert::same($attachment->Id, $game->globals->get(Game::CHOSEN_CARD), 'recorded');
                $pay = $game->queued(EventEnteringPayState::class);
                Assert::count(1, $pay, 'pay prompt');
                Assert::same($performer->ControllerId, $pay[0]->playerId, "the performer's controller pays");
                Assert::same(Game::PAY_STATE_EQUIP_ATTACHMENT, $pay[0]->payStateType, 'equip payment');
                Assert::same(['attachmentSelected'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaEquipActionAttachmentFromPlaySelected refuses an attachment elsewhere or unknown' => function () {
                [$game, , ] = $this->equipScene(2, Game::LOCATION_CITY_DOCKS);
                $far = $game->place(new GenericFactionAttachment('Far'), Game::LOCATION_CITY_FORUM, 0);

                Assert::throws(fn() => $game->actHighDramaEquipActionAttachmentFromPlaySelected($far->Id), "not at Performer's Location", 'elsewhere');
                Assert::throws(fn() => $game->actHighDramaEquipActionAttachmentFromPlaySelected(99999), 'Attachment not found', 'unknown');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaEquipActionAttachmentFromPlaySelected: Smuggled Item reaches any unattached attachment in the city' => function () {
                [$game, , ] = $this->equipScene(2, Game::LOCATION_CITY_DOCKS);
                $far = $game->place(new GenericFactionAttachment('Far'), Game::LOCATION_CITY_FORUM, 0);
                $inHand = $game->place(new GenericFactionAttachment('In hand'), Game::LOCATION_HAND, 1);
                $game->globals->set(Game::EQUIP_TYPE, Game::SMUGGLED_ITEM_EQUIP_TYPE);

                $game->actHighDramaEquipActionAttachmentFromPlaySelected($far->Id);
                Assert::same(['attachmentSelected'], $game->gamestate->transitions, 'across the city is fine');

                Assert::throws(
                    fn() => $game->actHighDramaEquipActionAttachmentFromPlaySelected($inHand->Id),
                    'not available in the City',
                    'a hand card is not in the city'
                );
            },

            'actHighDramaEquipActionAttachmentFromPlaySelected: equipping an opponent needs an attachment that allows it' => function () {
                [$game, , $ordinary] = $this->equipScene(2, Game::LOCATION_CITY_DOCKS);
                $theirs = $game->place(new GenericCharacter('Theirs'), Game::LOCATION_CITY_DOCKS, 2);
                $game->globals->set(Game::CHOSEN_PERFORMER, $theirs->Id);

                Assert::throws(
                    fn() => $game->actHighDramaEquipActionAttachmentFromPlaySelected($ordinary->Id),
                    'cannot be equipped to an opponent',
                    'refused'
                );
            },

            // ---- Equip: payment -----------------------------------------------------------------------------

            'actHighDramaEquipAttachment pays, equips and resolves in that order' => function () {
                [$game, $performer, $attachment] = $this->equipScene(2);
                $game->globals->set(Game::PASS_COUNT, 2);
                $pay = $this->hand($game, 2);

                $game->actHighDramaEquipAttachment(json_encode($pay));

                $types = array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents);
                Assert::same([
                    EventCardDiscardedFromHand::class,
                    EventCardDiscardedFromHand::class,
                    EventAttachmentEquipping::class,
                    EventActionResolved::class,
                ], $types, 'payment first, then the equip, then the resolution');

                $equip = $game->queued(EventAttachmentEquipping::class)[0];
                Assert::same(1, $equip->playerId, 'player');
                Assert::same($performer->Id, $equip->characterId, 'host');
                Assert::same($attachment->Id, $equip->attachmentId, 'attachment');
                Assert::same(2, $equip->cost, 'cost');
                Assert::same(0, $equip->discount, 'discount');
                Assert::true($equip->asAction, 'as an Action');
                Assert::same('', $equip->explanations, 'no explanations');
                Assert::same($pay, array_map(fn($e) => $e->cardId, $game->queued(EventCardDiscardedFromHand::class)), 'payment cards');
                Assert::count(1, array_filter($game->theah->checkedEvents, fn($e) => $e instanceof EventAttachmentEquipping), 'equip offered to the cards');
                Assert::same(0, $game->globals->get(Game::PASS_COUNT), 'pass count reset');
                Assert::same(['attachmentEquipped'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaEquipAttachment applies the discount (floored at zero) and records the explanations' => function () {
                [$game, , $attachment] = $this->equipScene(3);
                $game->globals->set(Game::DISCOUNT, 1);
                $game->globals->set(Game::DISCOUNT_EXPLAINATIONS, 'Haggled.');

                $game->actHighDramaEquipAttachment(json_encode($this->hand($game, 2)));

                $equip = $game->queued(EventAttachmentEquipping::class)[0];
                Assert::same(2, $equip->cost, 'cost 3 - 1');
                Assert::same(1, $equip->discount, 'discount');
                Assert::same('Haggled.', $equip->explanations, 'explanations');

                [$game] = $this->equipScene(1);
                $game->globals->set(Game::DISCOUNT, 4);
                $game->actHighDramaEquipAttachment('[]');
                Assert::same(0, $game->queued(EventAttachmentEquipping::class)[0]->cost, 'floored at zero');
            },

            'actHighDramaEquipAttachment rejects a payment that does not match the cost' => function () {
                [$game] = $this->equipScene(2);

                Assert::throws(
                    fn() => $game->actHighDramaEquipAttachment(json_encode($this->hand($game, 1))),
                    'Cost of Attachment is 2. You selected 1 Wealth',
                    'underpaid'
                );
                Assert::throws(
                    fn() => $game->actHighDramaEquipAttachment(json_encode($this->hand($game, 3))),
                    'Cost of Attachment is 2. You selected 3 Wealth',
                    'overpaid with plain cards'
                );
                Assert::throws(fn() => $game->actHighDramaEquipAttachment(json_encode([99999])), 'Card #99999 not found', 'ghost card');
                Assert::count(0, $game->queued(EventAttachmentEquipping::class), 'never equipped');
                Assert::count(0, $game->queued(EventCardDiscardedFromHand::class), 'nothing discarded');
            },

            'actHighDramaEquipAttachment lets a Wealth card overpay by one' => function () {
                [$game] = $this->equipScene(1);
                $game->actHighDramaEquipAttachment(json_encode($this->hand($game, 1, true)));
                Assert::count(1, $game->queued(EventAttachmentEquipping::class), 'accepted');
            },

            'actHighDramaEquipAttachment equips from the performer location, not from elsewhere' => function () {
                [$game, , $attachment] = $this->equipScene(0, Game::LOCATION_CITY_DOCKS);
                $game->actHighDramaEquipAttachment('[]');
                Assert::count(1, $game->queued(EventAttachmentEquipping::class), 'at the performer location: accepted');

                [$game, , $attachment] = $this->equipScene(0, Game::LOCATION_CITY_FORUM);
                Assert::throws(fn() => $game->actHighDramaEquipAttachment('[]'), "not at Performer's Location", 'elsewhere refused');
            },

            'actHighDramaEquipAttachment refuses a hand attachment that is not in the active player hand' => function () {
                [$game, , $attachment] = $this->equipScene(0);
                $attachment->ControllerId = 2; // now in the opponent's hand

                Assert::throws(fn() => $game->actHighDramaEquipAttachment('[]'), "not in Player's Hand", 'refused');
                Assert::count(0, $game->queued(EventAttachmentEquipping::class), 'never equipped');
            },

            'actHighDramaEquipAttachment: Let\'s Haggle only equips from the Bazaar, and marks its Action used' => function () {
                $game = new FrameworkActionsGame();
                $performer = $game->place(new GenericCharacter('Haggler'), Game::LOCATION_CITY_DOCKS, 1);
                $scheme = new AbilityCharacter('Scheme stand-in');
                $scheme->addAction(new GenericCharacterAction(), $game, false);
                $game->place($scheme, Game::LOCATION_PLAYER_HOME, 1);
                $action = $scheme->getActions()[0];
                $attachment = $game->place(new GenericFactionAttachment('Bazaar Rapier', 0), Game::LOCATION_CITY_BAZAAR, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::CHOSEN_CARD, $attachment->Id);
                $game->globals->set(Game::DISCOUNT, 0);
                $game->globals->set(Game::EQUIP_TYPE, Game::LETS_HAGGLE_EQUIP_TYPE);
                $game->globals->set(Game::CHOSEN_ACTION, $action->Id);

                $game->actHighDramaEquipAttachment('[]');

                Assert::true($action->Used, 'the Let\'s Haggle Action is spent');
                Assert::count(1, $game->queued(EventActionUsed::class), 'ActionUsed queued');
                Assert::contains('performed the Action from', $game->notify->messages[0]['message'], 'announced');
                Assert::count(1, $game->queued(EventAttachmentEquipping::class), 'equipped');

                $elsewhere = $game->place(new GenericFactionAttachment('Docks Rapier', 0), Game::LOCATION_CITY_DOCKS, 0);
                $game->globals->set(Game::CHOSEN_CARD, $elsewhere->Id);
                Assert::throws(fn() => $game->actHighDramaEquipAttachment('[]'), 'not at Bazaar', 'not from the Bazaar');
            },

            'actHighDramaEquipAttachment: Smuggled Item unequips and discards itself, and may equip across the city' => function () {
                $game = new FrameworkActionsGame();
                $performer = $game->place(new GenericCharacter('Smuggler'), Game::LOCATION_CITY_DOCKS, 1);
                $smuggled = $game->place(new GenericFactionAttachment('Smuggled Item', 0), Game::LOCATION_CITY_DOCKS, 1);
                $far = $game->place(new GenericFactionAttachment('Far Rapier', 2), Game::LOCATION_CITY_FORUM, 0);
                $game->globals->set(Game::CHOSEN_PERFORMER, $performer->Id);
                $game->globals->set(Game::CHOSEN_CARD, $far->Id);
                $game->globals->set(Game::EQUIP_TYPE, Game::SMUGGLED_ITEM_EQUIP_TYPE);
                $game->globals->set(Game::SMUGGLED_ITEM_ATTACHMENT_ID, $smuggled->Id);
                $game->globals->set(Game::DISCOUNT, 2); // Smuggled Item makes the equip free

                $game->actHighDramaEquipAttachment('[]');

                $unequipped = $game->queued(EventAttachmentUnequipped::class);
                Assert::count(1, $unequipped, 'Smuggled Item unequipped');
                Assert::same($smuggled->Id, $unequipped[0]->attachmentId, 'the Smuggled Item');
                Assert::same($performer->Id, $unequipped[0]->characterId, 'from the performer');
                $discarded = $game->queued(EventCardDiscardedFromPlay::class);
                Assert::count(1, $discarded, 'Smuggled Item discarded');
                $equip = $game->queued(EventAttachmentEquipping::class);
                Assert::count(1, $equip, 'the chosen attachment is equipped');
                Assert::same($far->Id, $equip[0]->attachmentId, 'chosen attachment');
                Assert::same(0, $equip[0]->cost, 'free');
            },

            'actHighDramaEquipAttachment: Smuggled Item refuses an attachment that is not available in the city' => function () {
                [$game, , $attachment] = $this->equipScene(0); // in hand
                $game->globals->set(Game::EQUIP_TYPE, Game::SMUGGLED_ITEM_EQUIP_TYPE);
                $attachment->Location = Game::LOCATION_PLAYER_HOME; // neither hand nor city

                Assert::throws(fn() => $game->actHighDramaEquipAttachment('[]'), 'not available in the City', 'refused');
            },

            'actHighDramaEquipAttachment equips onto the redirect target some attachments demand' => function () {
                [$game, $performer, $attachment] = $this->equipScene(0);
                $other = $game->place(new GenericCharacter('Required host'), Game::LOCATION_CITY_FORUM, 2);
                $attachment->attachTargetOverride = $other->Id;

                $game->actHighDramaEquipAttachment('[]');

                Assert::same($other->Id, $game->queued(EventAttachmentEquipping::class)[0]->characterId, 'redirected host');
            },

            'actHighDramaEquipAttachment surfaces an equip restriction and does not equip' => function () {
                [$game] = $this->equipScene(0);
                $game->equipRestriction = 'Only one Offhand attachment per character.';

                Assert::throws(fn() => $game->actHighDramaEquipAttachment('[]'), 'Only one Offhand', 'reason shown');
                Assert::count(0, $game->queued(EventAttachmentEquipping::class), 'never equipped');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaEquipAttachment: a card veto on the equip stops payment and resolution too' => function () {
                [$game] = $this->equipScene(2);
                $pay = $this->hand($game, 2);
                $game->theah->eventCheckHook = function (Event $event) {
                    if ($event instanceof EventAttachmentEquipping) {
                        throw new \Exception('This character cannot be equipped');
                    }
                };

                Assert::throws(fn() => $game->actHighDramaEquipAttachment(json_encode($pay)), 'cannot be equipped', 'veto propagates');

                Assert::same([], $game->theah->queuedEvents, 'nothing queued at all');
            },

            // ---- Claim ----------------------------------------------------------------------------------------

            'actHighDramaClaimActionStart proceeds with a ready city character' => function () {
                $game = new FrameworkActionsGame();
                $game->place(new GenericCharacter('Claimer'), Game::LOCATION_CITY_DOCKS, 1);

                $game->actHighDramaClaimActionStart();

                Assert::same(['claimActionStart'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaClaimActionStart is refused when nobody in the city can claim' => function () {
                $game = new FrameworkActionsGame();
                $engaged = $game->place(new GenericCharacter('Engaged'), Game::LOCATION_CITY_DOCKS, 1);
                $engaged->Engaged = true;
                $dashed = $game->place(new GenericCharacter('Dashed'), Game::LOCATION_CITY_FORUM, 1);
                $dashed->DashedInfluence = true;
                $game->place(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);

                Assert::throws(fn() => $game->actHighDramaClaimActionStart(), 'Claim Action is not allowed', 'refused');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaClaimActionPerformerChosen engages the performer, queues the pressure and flags a basic claim' => function () {
                $game = new FrameworkActionsGame();
                $game->activePlayerId = 1;
                $performer = $game->place(new GenericCharacter('Claimer'), Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::PRESSURE_TYPE, Game::CASTILLIAN_CAPER_PRESSURE_TYPE); // stale

                $game->actHighDramaClaimActionPerformerChosen($performer->Id);

                Assert::same(1, $game->globals->get(Game::PRESSURING_PLAYER), 'pressuring player');
                Assert::same($performer->Id, $game->globals->get(Game::CHOSEN_PERFORMER), 'performer');
                // WHY: engage can destroy the performer before the pressure state runs; it must still pressure its original site.
                Assert::same(Game::LOCATION_CITY_FORUM, $game->globals->get(Game::CHOSEN_LOCATION), 'site captured now');
                Assert::same(Game::NORMAL_PRESSURE_TYPE, $game->globals->get(Game::PRESSURE_TYPE), 'normal pressure');
                Assert::true($game->globals->get(Game::IS_BASIC_CLAIM_ACTION), 'basic claim flag');

                Assert::same(
                    [EventCardEngaged::class, EventPressureOccuring::class],
                    array_map(fn(Event $e) => $e::class, $game->theah->queuedEvents),
                    'engage before pressure'
                );
                $engage = $game->queued(EventCardEngaged::class)[0];
                Assert::same($performer->Id, $engage->cardId, 'performer engaged');
                Assert::same(1, $engage->playerId, 'by the active player');
                $pressure = $game->queued(EventPressureOccuring::class)[0];
                Assert::same($performer->Id, $pressure->performerId, 'pressure performer');
                Assert::same(Game::LOCATION_CITY_FORUM, $pressure->location, 'pressure location');
                Assert::same([Game::STAT_INFLUENCE], $pressure->pressureTypes, 'Influence pressure');
                Assert::count(2, $game->theah->checkedEvents, 'both events offered to the cards');
                Assert::same(['performerChosen'], $game->gamestate->transitions, 'transition');
            },

            'actHighDramaClaimActionPerformerChosen refuses an engaged performer, a Dashed Influence one, or one outside the city' => function () {
                $game = new FrameworkActionsGame();
                $engaged = $game->place(new GenericCharacter('Engaged'), Game::LOCATION_CITY_DOCKS, 1);
                $engaged->Engaged = true;
                $dashed = $game->place(new GenericCharacter('Dashed'), Game::LOCATION_CITY_FORUM, 1);
                $dashed->DashedInfluence = true;
                $home = $game->place(new GenericCharacter('Home'), Game::LOCATION_PLAYER_HOME, 1);
                $theirs = $game->place(new GenericCharacter('Theirs'), Game::LOCATION_CITY_BAZAAR, 2);

                Assert::throws(fn() => $game->actHighDramaClaimActionPerformerChosen($engaged->Id), 'because it is engaged', 'engaged');
                Assert::throws(fn() => $game->actHighDramaClaimActionPerformerChosen($dashed->Id), 'Dashed Influence', 'dashed');
                Assert::throws(fn() => $game->actHighDramaClaimActionPerformerChosen($home->Id), 'not in the City', 'home');
                Assert::throws(fn() => $game->actHighDramaClaimActionPerformerChosen($theirs->Id), 'not in the City', "opponent's character");

                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
                Assert::false($game->globals->has(Game::IS_BASIC_CLAIM_ACTION), 'no claim flag');
                Assert::same([], $game->gamestate->transitions, 'no transition');
            },

            'actHighDramaClaimActionPerformerChosen honours the Extra Action performer lock' => function () {
                $game = new FrameworkActionsGame();
                $performer = $game->place(new GenericCharacter('Claimer'), Game::LOCATION_CITY_FORUM, 1);
                $game->globals->set(Game::EXTRA_ACTION_PERFORMER, 4242);

                Assert::throws(fn() => $game->actHighDramaClaimActionPerformerChosen($performer->Id), 'same character', 'locked elsewhere');
                Assert::same([], $game->theah->queuedEvents, 'nothing queued');
            },
        ];
    }
}
