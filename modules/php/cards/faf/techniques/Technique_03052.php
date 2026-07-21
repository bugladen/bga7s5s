<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\techniques\Technique;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Technique_03052 extends Technique
{
    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("Look at Adversary's Hand");
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah))
        {
            return false;
        }

        if (! $theah->game->globals->get(Game::IN_DUEL, false))
        {
            return false;
        }

        // Gambling Technique: actor must have gambled for their combat card this round.
        if (! $theah->game->globals->get(Game::DUEL_GAMBLED, false))
        {
            return false;
        }

        $owner = $this->getOwningCharacter($theah);
        $actor = $theah->getDuelRoundActor();
        if ($owner === null || $actor === null || $actor->Id !== $owner->Id)
        {
            return false;
        }

        $adversary = $theah->getDuelRoundOpponent();
        if ($adversary === null)
        {
            return false;
        }

        // WHY: Empty hand makes "look" a no-op — hide the technique rather than a useless prompt.
        $hand = $theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
        return count($hand) > 0;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveTechnique && $event->techniqueId == $this->Id)
        {
            $owner = $this->getOwningCharacter($event->theah);
            $adversary = $event->theah->getDuelRoundOpponent();
            $game = $event->theah->game;

            $game->notify->all("message", clienttranslate('${card_inject_code}: ${player_name} looks at ${opponent_name}\'s hand.'), [
                "card_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($owner->ControllerId),
                "opponent_name" => $game->getPlayerNameById($adversary->ControllerId),
            ]);

            // WHY: "Look at" is private (not public Reveal like Technique_03043). Actor acknowledges.
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, "03052", $this->Id);
            $event->theah->queueEvent($transition);

            $this->setUsed($event->theah, true);
        }

        // EventTechniqueCanceled handler not needed
    }

    public function getArgsFromTechnique(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromTechnique($game, $state, $stateName);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03052)
        {
            $adversary = $game->theah->getDuelRoundOpponent();
            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $adversary->ControllerId);
            $cards = [];
            foreach ($hand as $card)
            {
                $cards[] = $card->getPropertyArray($game);
            }
            $args['cards'] = $cards;
            $args['opponentName'] = $game->getPlayerNameById($adversary->ControllerId);
        }

        return $args;
    }

    public function actFromTechniquePass(Game $game, int $state): void
    {
        parent::actFromTechniquePass($game, $state);

        if ($state == States::DUEL_CHOOSE_TECHNIQUE_03052)
        {
            $game->gamestate->nextState();
        }
    }
}
