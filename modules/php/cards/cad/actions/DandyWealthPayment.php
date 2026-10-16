<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

trait DandyWealthPayment
{
    private function playerCanPayOneWealth(int $playerId, Theah $theah): bool
    {
        return $this->playerCanPayWealth($playerId, $theah, 1);
    }

    private function playerCanPayWealth(int $playerId, Theah $theah, int $cost): bool
    {
        return $theah->game->handWealthCount($playerId) >= $cost;
    }

    /**
     * @param array<int, mixed> $ids
     */
    private function queueOneWealthPayment(Game $game, Character $owner, array $ids): void
    {
        $this->queueWealthPayment($game, $owner, $ids, 1);
    }

    /**
     * @param array<int, mixed> $ids
     */
    private function queueWealthPayment(Game $game, Character $owner, array $ids, int $cost): void
    {
        $cards = [];
        $seen = [];
        $total = 0;
        $hasWealth = false;

        foreach ($ids as $id)
        {
            $cardId = (int) $id;
            if (isset($seen[$cardId]))
            {
                throw new UserException(sprintf("Pay exactly %d Wealth.", $cost));
            }
            $seen[$cardId] = true;

            $card = $game->getCardObjectFromDb($cardId);
            if ($card === null || $card->Location != Game::LOCATION_HAND || $card->ControllerId != $owner->ControllerId)
            {
                throw new UserException("Payment card is not in your hand.");
            }

            $isWealth = $card->hasTrait("Wealth");
            if ($isWealth)
            {
                $hasWealth = true;
            }
            $total += $isWealth ? 2 : 1;
            $cards[] = $card;
        }

        // WHY: A Wealth-trait card may overpay by 1. Two plain cards, one Wealth
        // card, or one Wealth plus one plain card are the legal ways to pay 2.
        if (! $game->isValidWealthPayment($total, $cost, $hasWealth))
        {
            throw new UserException(sprintf("Pay exactly %d Wealth.", $cost));
        }

        foreach ($cards as $card)
        {
            $discard = EventFactory::createCardDiscardedFromHandEvent($card->OwnerId, $card->Id, $owner->Id, true);
            $game->theah->queueEvent($discard);
        }
    }
}
