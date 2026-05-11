<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\CityEventCard;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03cd13;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;

class _03cd13 extends CityEventCard implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Crabs in a Bucket');
        $this->Image = '03cd13.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 0;

        $this->CityCardNumber = 13;

        $this->Traits = [
            clienttranslate('Treachery'),
            clienttranslate('Schadenfreude'),
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> When this card is revealed • Each player with fewer Renown than the player with the most Renown draws a card.</p><p><b>City Action:</b> If you have fewer Renown than target player, engage your performer • Claim this location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03cd13(),
        ];
    }

    public function getCrabsInABucketUsedListData(Game $game): array
    {
        return $this->Actions[0]->getUsedListData($game);
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCityCardAddedToLocation && $event->cardId == $this->Id)
        {
            $theah = $event->theah;
            $game = $theah->game;

            $players = $game->loadPlayersBasicInfos();

            $maxReknown = 0;
            foreach ($players as $playerId => $player)
            {
                $reknown = $game->getPlayerReknown($playerId);
                if ($reknown > $maxReknown)
                {
                    $maxReknown = $reknown;
                }
            }

            $game->notify->all("message", clienttranslate(
                '${card_inject_code} effect triggers. Each player with fewer Renown than the highest will draw a card.'), [
                "card_inject_code" => $this->getInjectCode(),
            ]);

            foreach ($players as $playerId => $player)
            {
                if ($game->getPlayerReknown($playerId) < $maxReknown)
                {
                    $drawEvent = EventFactory::createCardDrawnEvent($playerId, $this->getInjectCode());
                    $theah->queueEvent($drawEvent);
                }
            }
        }
    }
}
