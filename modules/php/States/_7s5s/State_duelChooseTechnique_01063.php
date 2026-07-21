<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\States\_7s5s;

use Bga\GameFramework\StateType;
use Bga\GameFramework\States\GameState;
use Bga\GameFramework\States\PossibleAction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;

class State_duelChooseTechnique_01063 extends GameState
{
    function __construct(
        protected Game $game,
    )
    {
        parent::__construct($game,
            id: States::DUEL_CHOOSE_TECHNIQUE_01063,
            type: StateType::ACTIVE_PLAYER,
            name: "duelChooseTechnique_01063",

            description: clienttranslate('${actplayer} is choosing their Duel Action options.'),
            descriptionMyTurn: clienttranslate('Bastien Girard') . clienttranslate(': Swap with a Musketeer: ${you} must choose a Musketeer:'),
            transitions: [
                "" => States::DUEL_CHOOSE_TECHNIQUE_EVENTS,
                "back" => States::DUEL_CHOOSE_TECHNIQUE,
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
    public function actFromCardWithId(int $id): void
    {
        $this->game->actFromCardWithId($id);
    }

    // WHY: Harpoon (and similar) can leave the player with no legal swap after they
    // already committed to Technique_01063Swap. Cancel must strip pending
    // CalculateTechniqueValues / reopen Used (mirror 01146b), then return to the
    // technique picker — not DUEL_CHOOSE_ACTION via EVENTS endOfEvents.
    #[PossibleAction]
    public function actBack(): void
    {
        $game = $this->game;
        $game->theah->buildCity();

        $techniqueId = $game->globals->get(Game::CHOSEN_TECHNIQUE, '');
        if ($techniqueId !== '')
        {
            $technique = $game->theah->getTechniqueById($techniqueId);
            $owner = $technique?->getOwningCard($game->theah);

            $game->notify->all('message', clienttranslate('${player_name} canceled Technique: [${technique}]'), [
                'i18n' => ['technique'],
                'player_name' => $game->getActivePlayerName(),
                'technique' => $technique ? $technique->Name : $techniqueId,
            ]);

            $game->globals->delete(Game::CHOSEN_TECHNIQUE);
            $game->globals->delete(Game::CHOSEN_TECHNIQUE_IS_MAIN);
            $game->theah->deleteTechniqueEvents($techniqueId);

            // WHY: EventTechniqueActivated already set Used. Player abort must reopen
            // the technique this round (unlike opponent cancel via 01146b, which leaves Used).
            if ($technique !== null)
            {
                $technique->setUsed($game->theah, false);
            }

            $playerId = $owner?->ControllerId ?? (int) $game->getActivePlayerId();
            $canceledEvent = EventFactory::createTechniqueCanceledEvent($playerId, $techniqueId);
            $game->theah->queueEvent($canceledEvent);

            // WHY: Flush TechniqueCanceled / TechniqueUsed without taking an EventTransition
            // or endOfEvents (which would leave DUEL_CHOOSE_TECHNIQUE).
            $game->theah->runEvents($skipTransitions = true);
        }

        $game->gamestate->nextState("back");
    }

    public function zombie(int $playerId): void
    {
        $this->actBack();
    }
}
