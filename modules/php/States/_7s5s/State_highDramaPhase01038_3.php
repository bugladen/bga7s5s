<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_highDramaPhase01038_3 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::HIGH_DRAMA_PLAYER_TURN_01038_3,
            type: StateType::ACTIVE_PLAYER,
            name: "highDramaPhase01038_3",

            description: clienttranslate('${actplayer} may choose an Attachment to put into their Faction Hand.'),
            descriptionMyTurn: clienttranslate('${you} may choose an Attachment to put into your Faction Hand:'),
            transitions: [
                "cardChosen" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
                "pass" => States::HIGH_DRAMA_PLAYER_TURN_EVENTS,
            ],
            updateGameProgression: false,
            initialPrivate: null,
        );
    }

    public function getArgs(): array
    {
        return $this->game->argsForState();
    }

    #[PossibleAction]
    public function actFromCardWithId(string $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    #[PossibleAction]
    public function actFromCardPass(): void
    {
        $this->game->actFromCardPass();
    }

    public function zombie(int $playerId): void
    {
        // WHY: Named cardChosen/pass. actFromCardPass throws if Attachments remain among
        // the top cards — pick first Attachment from args when present, else pass.
        $args = $this->game->argsForState();
        $cards = $args['args']['cards'] ?? [];
        foreach ($cards as $card) {
            $type = $card['type'] ?? $card['Type'] ?? '';
            $id = $card['id'] ?? $card['Id'] ?? null;
            if ($id === null) {
                continue;
            }
            // Attachment property arrays expose Type / type; prefer Type from getPropertyArray.
            if ($type === 'Attachment' || !empty($card['IsAttachment'])) {
                $this->game->actFromCardWithId((string)$id);
                return;
            }
        }
        $this->game->actFromCardPass();
    }
}
