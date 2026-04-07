<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions\Action_01147;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\CardAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _01147 extends Scheme implements IHasActions
{
    use ActionTrait;
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Let's Haggle");
        $this->Image = "01147.jpg";
        $this->ExpansionName = "_7s5s";
        $this->ExpansionNumber = 1;
        $this->CardNumber = 147;

        $this->Initiative = 77;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate("Bargain"), 
            clienttranslate("Market"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The Grand Bazaar] and [The Forums].</p><p>Reveal cards from the City Deck until you find an attachment. Add it to [The Grand Bazaar]. Sink the rest.</p><hr><p><b>City Action:</b> Your performer equips target attachment from [The Grand Bazaar]. If they are at [The Grand Bazaar], it has -1 cost.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_01147(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;

            $game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves.  
            Renown will be added to The Forum and The Grand Bazaar. 
            Cards will be revealed from the City Deck until an Attachment is revealed, then added to The Grand Bazaar.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $reknown = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);

            $reknown = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($reknown);

            $attachment = $game->revealFirstCardTypeFromCityDeck($event->playerId, "Attachment", $this->Id);

            if ($attachment)
            {
                $game->notify->all("message", clienttranslate('${attachment} is the first Attachment revealed in the City Deck.'), [
                    'attachment' => $attachment->Name,
                ]);

                $game->globals->set(Game::CHOSEN_CARD, $attachment->Id);
                $addEvent = EventFactory::createCityCardAddedToLocationEvent($attachment->Id, Game::LOCATION_CITY_BAZAAR);
                $event->theah->queueEvent($addEvent);
            }
            else
            {
                $game->globals->delete(Game::CHOSEN_CARD);
                $game->notify->all("message", clienttranslate('No Attachment was found in the City Deck.'), []);
            }

            $game->notify->all("message", clienttranslate('The rest of the revealed cards have been sunk.'), []);

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
        $deck = $game->getGameDeckObject();
        $attachmentId = $game->globals->get(Game::CHOSEN_CARD);
        $revealed = json_decode($game->globals->get(Game::REVEALED_CARDS), true);

        foreach ($revealed as $cardId)
        {
            if ($cardId == $attachmentId)
            {
                continue;
            }

            $deck->insertCardOnExtremePosition($cardId, Game::LOCATION_CITY_DECK, false);
        }
    }
    
    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment, Array &$explanations): int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment, $explanations);

        $actionId = $theah->game->globals->get(Game::CHOSEN_ACTION);
        $action = $theah->getInPlayActionById($actionId);
        if ($action != null)
        {
            $id = Game::THEAH_ID;
            if ($action instanceof CardAction)
                $id = $action->OwnerId;

            if ($id == $this->Id && 
                $performer->ControllerId == $this->ControllerId &&
                $performer->Location == Game::LOCATION_CITY_BAZAAR)
            {
                $discount += 1;
                $explanations[] = sprintf($theah->game->translate("%s: -1 because performer is at the Bazaar."), $this->getInjectCode());
            }
        }

        return $discount;
    }
}