<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01147;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Events;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01147 extends Scheme implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Let's Haggle");
        $this->Image = "img/cards/7s5s/147.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 147;

        $this->Initiative = 77;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Bargain", 
            "Market",
        ];

        $this->Actions = [
            new Action_01147(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {

            $event->theah->game->notifyAllPlayers("message", clienttranslate('<strong>${scheme_name}</strong> now resolves.  
            Reknown will be added to The Forum and The Grand Bazaar. 
            Cards will be revealed from the City Deck until an Attachment is revealed, then added to The Grand Bazaar.'), [
                'i18n' => ['scheme_name'],
                "scheme_name" => $this->Name,
            ]);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->playerId = $this->ControllerId;
                $reknown->location = Game::LOCATION_CITY_FORUM;
                $reknown->amount = 1;
                $reknown->source = $this->Name;
            }
            $event->theah->queueEvent($reknown);

            $reknown = $event->theah->createEvent(Events::ReknownAddedToLocation);
            if ($reknown instanceof EventReknownAddedToLocation) {
                $reknown->playerId = $this->ControllerId;
                $reknown->location = Game::LOCATION_CITY_BAZAAR;
                $reknown->amount = 1;
                $reknown->source = $this->Name;
            }
            $event->theah->queueEvent($reknown);

            $game = $event->theah->game;

            $attachment = $game->revealFirstCardTypeFromCityDeck($event->playerId, "Attachment");

            if ($attachment)
            {
                $game->notifyAllPlayers("message", clienttranslate('${attachment} is the first Attachment revealed in the City Deck.'), [
                    'attachment' => $attachment->Name,
                ]);

                $game->globals->set(Game::CHOSEN_CARD, $attachment->Id);
                $addEvent = EventFactory::createCityCardAddedToLocationEvent($attachment->Id, Game::LOCATION_CITY_BAZAAR);
                $event->theah->queueEvent($addEvent);
            }
            else
            {
                $game->globals->delete(Game::CHOSEN_CARD);
                $game->notifyAllPlayers("message", clienttranslate('No Attachment was found in the City Deck.'), []);
            }

            $game->notifyAllPlayers("message", clienttranslate('The rest of the revealed cards have been sunk.'), []);

            $revealEvent = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "01147");
            //This puts the event in same priority as the rest of the resolve events
            $revealEvent->priority = Event::MEDIUM_PRIORITY;
            $event->queueEvent($revealEvent);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array 
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_01147)
        {
            $args['letsHaggleId'] = $this->Id;

            $revealed = json_decode($game->globals->get(Game::REVEALED_CARDS), true);
            $cards = [];
            foreach ($revealed as $cardId)
            {
                $card = $game->getCardObjectFromDb($cardId);
                $cards[] = $card->getPropertyArray($game);
                unset($card);
            }
            $args['cards'] = $cards;
        }

        return $args;
    }

    public function stateFromCard(Game $game, int $state, string $stateName, string $actionId): void
    {
        parent::stateFromCard($game, $state, $stateName, $actionId);

        //Sink all the cards except the revealed mercenary
        $attachmentId = $game->globals->get(Game::CHOSEN_CARD);
        $revealed = json_decode($game->globals->get(Game::REVEALED_CARDS), true);
        $cards = [];
        foreach ($revealed as $cardId)
        {
            if ($cardId == $attachmentId)
            {
                continue;
            }

            $card = $game->getCardObjectFromDb($cardId);
            $cards[] = $card->getPropertyArray($game);
            $event = EventFactory::createCardAddedToCityDiscardPileEvent($card->ControllerId, $card->Id, Game::LOCATION_CITY_DECK);
            $game->theah->queueEvent($event);
        }
    }
    
    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment): int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment);

        $actionId = $theah->game->globals->get(Game::CHOSEN_ACTION);
        $action = $theah->getInPlayActionById($actionId);
        if ($action != null)
        {
            $owner = $action->getOwningCard($theah);

            if ($owner->Id == $this->Id && 
            $performer->ControllerId == $this->ControllerId &&
            $performer->Location == Game::LOCATION_CITY_BAZAAR)
            {
                $discount += 1;
            }
        }

        return $discount;
    }
}