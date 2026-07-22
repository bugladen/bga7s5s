<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityCharacter;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd01;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterBeingWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlay;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelStarted;

class _03cd01 extends CityCharacter implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Penya');
        $this->Image = '03cd01.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;

        $this->Title = clienttranslate('Hard Knocks Hustler');
        $this->Resolve = 1;

        $this->Combat = 0;
        $this->DashedCombat = true;
        $this->Finesse = 2;
        $this->Influence = 0;
        $this->DashedInfluence = true;

        $this->WealthCost = 1;
        $this->CityCardNumber = 1;

        $this->Traits = [
            clienttranslate('Mercenary'),
            clienttranslate('Hero'),
            clienttranslate('Swindler'),
            clienttranslate('Orphan'),
        ];

        $this->Text = clienttranslate("<p>Negotiable (You may parley when paying for this card.)</p><p>Penya cannot intervene.</p><p><b>City Forced:</b> When Penya participates in a duel or would be wounded • Put the top card of the City Deck at his location. Then, shuffle him into the City Deck.</p><p><b>City Action:</b> Engage Penya • Move Penya and another of your characters at this location to the same adjacent <b>City</b> location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03cd01(),
        ];
    }

    public function canIntervene(): bool
    {
        return false;
    }

    public function eventCheck(Event $event)
    {
        parent::eventCheck($event);

        if ($event instanceof EventCharacterIntervened && $event->newTargetId == $this->Id)
        {
            throw new UserException($event->theah->game->translate("Penya cannot intervene."));
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // City Forced: When Penya participates in a duel
        if ($event instanceof EventDuelStarted
            && ($event->challengerId == $this->Id || $event->defenderId == $this->Id)
            && $event->theah->cardInCity($this))
        {
            $this->triggerForcedAbility($event);
        }

        // City Forced: When Penya would be wounded
        if ($event instanceof EventCharacterBeingWounded
            && $event->characterId == $this->Id
            && $event->theah->cardInCity($this))
        {
            $event->canceled = true;
            $this->triggerForcedAbility($event);
        }

        // After Penya is removed from play to the city deck, shuffle it
        if ($event instanceof EventCardRemovedFromPlay
            && $event->cardId == $this->Id
            && $event->toLocation == Game::LOCATION_CITY_DECK)
        {
            $game = $event->theah->game;
            $game->getGameDeckObject()->shuffle(Game::LOCATION_CITY_DECK);
            $game->notify->all("message", clienttranslate('The City Deck has been shuffled.'), []);
        }
    }

    private function triggerForcedAbility(Event $event): void
    {
        $game = $event->theah->game;
        $location = $this->Location;

        $game->notify->all("message", clienttranslate('${card_inject_code}: Forced ability triggered.'), [
            "card_inject_code" => $this->getInjectCode(),
        ]);

        // Put the top card of the City Deck at his location
        $topCards = $game->getCardsOnTopOfCityDeck(1);
        if (count($topCards) > 0)
        {
            $topCard = array_values($topCards)[0];
            $cityCardEvent = EventFactory::createCityCardAddedToLocationEvent((int)$topCard['id'], $location);
            $event->theah->queueEvent($cityCardEvent);
        }

        // Remove Penya from play and move to city deck (shuffle handled by EventCardRemovedFromPlay listener above)
        $removeEvent = EventFactory::createCardRemovedFromPlayEvent($this->ControllerId, $this->Id, Game::LOCATION_CITY_DECK);
        $event->theah->queueEvent($removeEvent);
    }
}
