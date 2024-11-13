<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

trait ActionsTrait
{
    public function actPickDeck(string $deck_type, string $deck_id): void
    {
        $playerId = $this->getCurrentPlayerId();

        $sql = "UPDATE player SET deck_source = '$deck_type', deck_id = '$deck_id'  WHERE player_id='$playerId'";
        $this->DbQuery($sql);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'deckPicked'); // deactivate player; if none left, transition to 'deckPicked' state
    }

    public function actDayPlanned(int $scheme, int $character): void
    {
        $playerId = $this->getCurrentPlayerId();
        $sql = "UPDATE player SET selected_scheme_id = '$scheme', selected_character_id = '$character'  WHERE player_id='$playerId'";
        $this->DbQuery($sql);

        //Move the cards to a purgatory state while waiting for the other players to finish their day planning.
        //This is necessary to prevent the card from being shown back in the player's approach deck if they F5.
        $this->cards->moveCard($scheme, Game::LOCATION_PURGATORY);
        $this->cards->moveCard($character, Game::LOCATION_PURGATORY);

        $this->gamestate->setPlayerNonMultiactive($playerId, 'dayPlanned'); // deactivate player; if none left, transition to 'deckPicked' state
    }

    public function actPlayCard(int $card_id): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // check input values
        // $args = $this->argPlayerTurn();
        // $playableCardsIds = $args['playableCardsIds'];
        // if (!in_array($card_id, $playableCardsIds)) {
        //     throw new \BgaUserException('Invalid card choice');
        // }

        // Add your game logic to play a card here.
        // $card_name = $this->card_types[$card_id]['card_name'];

        // Notify all players about the card played.
        // $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} plays ${card_name}'), [
        //     "player_id" => $player_id,
        //     "player_name" => $this->getActivePlayerName(),
        //     "card_name" => $card_name,
        //     "card_id" => $card_id,
        //     "i18n" => ['card_name'],
        // ]);

        // at the end of the action, move to the next state
        // $this->gamestate->nextState("playCard");
    }

    public function actPass(): void
    {
        // Retrieve the active player ID.
        $player_id = (int)$this->getActivePlayerId();

        // Notify all players about the choice to pass.
        $this->notifyAllPlayers("cardPlayed", clienttranslate('${player_name} passes'), [
            "player_id" => $player_id,
            "player_name" => $this->getActivePlayerName(),
        ]);

        // at the end of the action, move to the next state
        $this->gamestate->nextState("pass");
    }
}