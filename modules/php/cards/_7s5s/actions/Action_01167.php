<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\actions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\RiskAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;

class Action_01167 extends RiskAction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Take Control of Opponent's Attachment from their Discard Pile");
        $this->RequiresPerformerSelected = true;
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if ( ! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        $characters = $theah->getCharactersInCityByPlayerId($playerId);
        return count($characters) > 0;
    }

    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = $theah->getCharactersInCityByPlayerId($playerId);
        return $performers;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $owner = $this->getOwningCard($event->theah);
            $transition = EventFactory::createTransitionEvent($event->playerId, $owner->Id, "01167", $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getArgsFromAction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromAction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01167)
        {
            $owner = $this->getOwningCard($game->theah);
            $opponents = [];
            $players = $game->loadPlayersBasicInfos();
            foreach ( $players as $playerId => $player ) 
            {
                if ($playerId != $owner->ControllerId)
                    $opponents[] = ['id' => $playerId, 'name' => $player['player_name']];
            }        

            $args['opponents'] = $opponents;
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01167_2)
        {
            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $args["performerId"] = $performerId;

            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $opponentName = $game->getPlayerNameById($opponentId);
            $args["opponentName"] = $opponentName;

            $discardName = $game->getPlayerDiscardDeckName($opponentId);
            $attachments = $game->theah->getCardObjectsAtLocation($discardName);
            $attachments = array_values(array_filter($attachments, fn($attachment) => $attachment instanceof Attachment && ! $attachment->hasTrait('Unique')));

            $args["cards"] = array_map(fn($attachment) => $attachment->getPropertyArray($game), $attachments);
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01167_3)
        {
            $chosenAttachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $chosenAttachment = $game->getCardObjectFromDb($chosenAttachmentId);
            $args['chosenAttachment'] = $chosenAttachment->getPropertyArray($game);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->getCardObjectFromDb($performerId);
            $args['performerId'] = $performerId;

            $discount = $game->globals->get(Game::DISCOUNT);
            $args['discount'] = $discount;
        }

        return $args;
    }

    public function actFromActionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromActionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01167)
        {
            $players = $game->loadPlayersBasicInfos();
            if ( ! isset($players[$id]))
            {
                throw new \BgaUserException($game->translate("Invalid opponent"));
            }

            $game->notify->all("message", clienttranslate('${player_name} has chosen to look at <strong>${opponentName}</strong>\'s Discard Pile.'), [
                'player_name' => $game->getActivePlayerName(),
                'opponentName' => $players[$id]['player_name'],
            ]);

            $game->globals->set(Game::CHOSEN_OPPONENT, $id);
            $game->gamestate->nextState("opponentChosen");
        }

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01167_2)
        {
            $attachment = $game->theah->getCardById($id);
            if ($attachment == null)
            {
                throw new \BgaUserException($game->translate("Card not found"));
            }

            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $opponentName = $game->getPlayerNameById($opponentId);
            $discardName = $game->getPlayerDiscardDeckName($opponentId);
            if ($attachment->Location != $discardName)
            {
                throw new \BgaUserException(sprintf($game->translate("Attachment is not in %s's Discard Pile"), $opponentName));
            }

            if ($attachment->hasTrait("Unique"))
            {
                throw new \BgaUserException($game->translate("You cannot recover a unique attachment"));
            }

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->getCardObjectFromDb($performerId);
            [$discount, $explanations] = $game->theah->getEquipDiscount($performer, $attachment);
            if ($discount > 0)
                $game->notify->player($performer->ControllerId, "message", clienttranslate('Private: Explanations for discount:<br>${explanations}'), [
                    "explanations" => $explanations,
                ]);
            $game->globals->set(Game::DISCOUNT, $discount);
            $game->globals->set(Game::DISCOUNT_EXPLAINATIONS, $explanations);

            $game->globals->set(Game::CHOSEN_CARD, $id);
            $game->gamestate->nextState("attachmentChosen");
        }
    }

    public function actFromActionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromActionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01167_3)
        {
            $attachmentId = $game->globals->get(Game::CHOSEN_CARD);
            $attachment = $game->getCardObjectFromDb($attachmentId);

            $performerId = $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $game->theah->getCharacterById($performerId);

            $owner = $this->getOwningCard($game->theah);
            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $opponentName = $game->getPlayerNameById($opponentId);
    
            $discardName = $game->getPlayerDiscardDeckName($opponentId);
            if ($attachment->Location != $discardName)
            {
                throw new \BgaUserException(sprintf($game->translate("Attachment is not in %s's Discard Pile"), $opponentName));
            }

            if ($attachment instanceof Attachment)
                $cost = $attachment->WealthCost;
            
            $discount = $game->globals->get(Game::DISCOUNT);
            $explanations = $game->globals->get(Game::DISCOUNT_EXPLAINATIONS, '');
            $cost -= $discount;
    
            //Total up the wealth of the cards to see if player paid correctly
            $totalWealth = 0;
            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                if ($card == null)
                    throw new \BgaUserException(sprintf($game->translate("Card %d not found."), $cardId));
    
                //If $card has wealth in its traits, add it to the total wealth
                $totalWealth += $card->hasTrait("Wealth") ? 2 : 1;
            }
            if ($totalWealth != $cost) {
                throw new \BgaUserException(sprintf($game->translate("Cost of Attachment is %d. You selected %d Wealth of cards."), $cost, $totalWealth));
            }

            $game->notify->all('message', clienttranslate('${player_name} has chosen to Equip ${card_inject_code} from <strong>${opponentName}</strong>\'s Discard Pile.'), [
                'player_name' => $game->getActivePlayerName(),
                'card_inject_code' => $attachment->getInjectCode(),
                'opponentName' => $opponentName,
            ]);

            $removeFromDiscardEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($opponentId, $attachmentId);
            $game->theah->queueEvent($removeFromDiscardEvent);

            //Equip the attachment
            $equipAttachmentEvent = EventFactory::createAttachmentEquippedEvent($owner->ControllerId, $performerId, $attachmentId, $discount, $cost, $asAction = true, $explanations);
            $game->theah->eventCheck($equipAttachmentEvent);
            $game->theah->queueEvent($equipAttachmentEvent);
    
            //Move the cards used to pay to the player's discard pile
            foreach ($ids as $cardId) {
                $card = $game->getCardObjectFromDb($cardId);
                $event = EventFactory::createCardDiscardedFromHandEvent($owner->ControllerId, $card->Id, $owner->Id, $asPayment = true);
                $game->theah->queueEvent($event);
            }
    
            $deck = $game->getGameDeckObject();
            $deck->moveCard($attachment->Id, $performer->Location, $attachment->ControllerId);
    
            $this->resetPlayerPassCount($game);
            $game->gamestate->nextState("attachmentEquipped");
        }
    }

    public function actFromActionPass(Game $game, int $state): void
    {
        parent::actFromActionPass($game, $state);

        if ($state == States::HIGH_DRAMA_PLAYER_TURN_01167_2)
        {
            $opponentId = $game->globals->get(Game::CHOSEN_OPPONENT);
            $opponentName = $game->getPlayerNameById($opponentId);
            $discardName = $game->getPlayerDiscardDeckName($opponentId);

            $cards = $game->theah->getCardObjectsAtLocation($discardName);
            if (count($cards) > 0)
            {
                throw new \BgaUserException(sprintf($game->translate("There are attachments in %s's Discard Pile"), $opponentName));
            }

            $game->notify->all("message", clienttranslate('There are no non-unique Attachments in <strong>${opponentName}</strong>\'s Discard Pile.'), [
                'opponentName' => $opponentName,
            ]);

            $this->resetPlayerPassCount($game);
            $game->gamestate->nextState("pass");
        }
    }
}
