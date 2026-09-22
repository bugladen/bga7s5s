<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\actions\Action_04038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04038;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IRiskThatTargetsCharacters;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventEnteringPayState;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerTurnEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _04038 extends Risk implements IHasActions, IHasManeuvers, IRiskThatTargetsCharacters
{
    use ActionTrait;
    use ManeuverTrait;

    // WHY: Ids of hand cards that received a temporary Wealth trait while paying for
    // Panacea. Sticky on the Risk so locker + revoke still work after this card leaves
    // hand (purgatory / discard / dueling line). Mirror Action_04018 sticky lists.
    /** @var list<int> */
    public array $GrantedWealthCardIds = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Panacea");
        $this->Image = "04038.jpg";
        $this->ExpansionName = "bas";
        $this->ExpansionNumber = 4;
        $this->CardNumber = 38;

        $this->initializeFaction("Castille");

        $this->WealthCost = 2;

        $this->Riposte = 1;
        $this->Parry = 1;
        $this->Thrust = 1;

        $this->Traits = [
            clienttranslate("Alquimia"),
            clienttranslate("Spagyrics"),
            clienttranslate("Invigorant")
        ];

        $this->Text = clienttranslate("<p>When paying for this card, <b>Alquimia</b> and <b>Discovery</b> cards gain Wealth. <i>(This card counts as two when discarded to pay costs. Send it to The Locker after paying costs.)</i></p>
<p><b>Academic City Action:</b> En garde a target character at your performer's location. They heal a wound.</p>
<p><b>Academic Maneuver:</b> En Garde your participant. They heal a wound.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_04038(),
        ];

        $this->Maneuvers = [
            new Maneuver_04038(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventEnteringPayState)
        {
            $payingForThisCard = $event->cardId == $this->Id
                && ($event->payStateType == Game::PAY_STATE_IN_HAND_ACTION
                    || $event->payStateType == Game::PAY_STATE_USE_MANEUVER_FROM_COMBAT_CARD);

            if ($payingForThisCard)
            {
                $this->grantAlquimiaDiscoveryWealth($event->theah, $event->playerId);
            }
            elseif (count($this->GrantedWealthCardIds) > 0
                && $event->payStateType != Game::PAY_STATE_IN_HAND_REACTION)
            {
                // WHY: Player backed out of Panacea pay then started paying for something
                // else. Temporary Wealth must not leak into the next payment. Skip
                // IN_HAND_REACTION — those nest inside Panacea's own pre-pay events
                // (02021 fires here) and must not strip Wealth before the player pays.
                $this->revokeGrantedWealth($event->theah);
            }
        }

        if ($event instanceof EventCardDiscardedFromHand && $event->AsPayment
            && in_array($event->cardId, $this->GrantedWealthCardIds, true))
        {
            $lockerEvent = EventFactory::createCardSentToLockerEvent($event->ownerId, $event->cardId);
            $event->theah->queueEvent($lockerEvent);

            $this->GrantedWealthCardIds = array_values(array_filter(
                $this->GrantedWealthCardIds,
                fn(int $id) => $id != $event->cardId
            ));
            $this->IsUpdated = true;
        }

        if ($event instanceof EventActionTriggered && $event->sourceId == $this->Id)
        {
            $this->revokeGrantedWealth($event->theah);
        }

        if ($event instanceof EventManeuverActivated && $event->ownerId == $this->Id)
        {
            $this->revokeGrantedWealth($event->theah);
        }

        if ($event instanceof EventPlayerTurnEnd || $event instanceof EventDuelEnd)
        {
            $this->revokeGrantedWealth($event->theah);
        }
    }

    private function grantAlquimiaDiscoveryWealth(Theah $theah, int $playerId): void
    {
        // Already granted for this pay session (Back then re-enter Panacea pay).
        if (count($this->GrantedWealthCardIds) > 0)
        {
            return;
        }

        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $playerId);
        foreach ($hand as $handCard)
        {
            // WHY getCardById: getCardObjectsAtLocation unserializes a fresh DB copy
            // that is not in $theah->cards. runEvents only persists the cache, so
            // addTrait on the copy notifies the client then evaporates. Pay validation
            // reloads from DB (actPayForInHandAction) and the card still has no Wealth.
            // Anghos (Reaction_02021) avoids this via getAttachmentById (cache).
            $card = $theah->getCardById($handCard->Id);
            if ($card === null)
            {
                continue;
            }

            // WHY exclude this card: it is the one being paid for, not a payment card.
            // Printed Wealth on other cards (Opulence) already counts as two + lockers
            // itself — do not double-grant / double-locker.
            if ($card->Id == $this->Id)
            {
                continue;
            }
            if ($card->hasTrait("Wealth"))
            {
                continue;
            }
            if (! $card->hasTrait("Alquimia") && ! $card->hasTrait("Discovery"))
            {
                continue;
            }

            $card->addTrait($theah->game, "Wealth");
            $this->GrantedWealthCardIds[] = $card->Id;
        }

        $this->IsUpdated = true;
    }

    private function revokeGrantedWealth(Theah $theah): void
    {
        if (count($this->GrantedWealthCardIds) == 0)
        {
            return;
        }

        foreach ($this->GrantedWealthCardIds as $cardId)
        {
            $card = $theah->getCardById($cardId);
            if ($card !== null && $card->Location == Game::LOCATION_HAND && $card->hasTrait("Wealth"))
            {
                $card->removeTrait($theah->game, "Wealth");
            }
        }

        $this->GrantedWealthCardIds = [];
        $this->IsUpdated = true;
    }
}
