<?php
namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

class Theah
{
    private Game $game;
    private array $cards;

    public function __construct($game)
    {
        $this->game = $game;
        $this->cards = [];
    }


    public function buildCity()
    {
        // Get the cards at the all the home locations
        /** @disregard P1012 */
        $location = self::LOCATION_PLAYER_HOME;
        $homeCardsResult = $this->game->getObjectListFromDB("
            SELECT card_id as id, card_location_arg as playerId
            FROM card 
            WHERE card_location = '{$location}'
            ");
        $homeCards = [];
        foreach ($homeCardsResult as $homeCard) {
            $card = $this->getCardObjectFromDb($homeCard['id']);
            $homeCard['card'] = $card->getPropertyArray();
            $homeCards[] = $homeCard;
        }

    }

    public function getCardObjectFromDb($cardId) : Card {
        $data = $this->game->getObjectFromDB("SELECT card_serialized FROM card WHERE card_id = $cardId");
        $card = unserialize($data['card_serialized']);
        return $card;
    }

}
