<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Card;

/** @disregard P1012 */
class DB extends \APP_DbObject
{
    public function getObjectList(string $sql, bool $bUniqueValue = false): array
    {
        /** @disregard P1012 */
        return $this->getCollectionFromDb($sql);
    }

    public function getCardObjectsAtLocation(string $location, $playerId = null): array
    {
        $sql = "
            SELECT card_location_arg as playerId, card_serialized as json
            FROM card 
            WHERE card_location = '$location'
            ";
        if ($playerId) {
            $sql .= " AND playerId = $playerId";
        }
        /** @disregard P1012 */
        $data = $this->getObjectListFromDB();

        $cards = [];
        foreach ($data as $result) {
            $cards[] = unserialize($result['json']);
        }

        return $cards;
    }

    private function getCardObjectFromDb($cardId) : Card {
        /** @disregard P1012 */
        $data = $this->getObjectFromDB("SELECT card_serialized FROM card WHERE card_id = $cardId");
        $card = unserialize($data['card_serialized']);
        return $card;
    }
}