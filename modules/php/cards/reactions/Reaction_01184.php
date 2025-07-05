<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventClaimOccuring;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01184 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Count only Performers and En Garge Characters');
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to count only the Performer and En Garde Characters for this Claim: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Count only Performer and En Garde Characters'), 'specialCount');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Pass'), 'pass');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventClaimOccuring && $this->isAvailable())
        {
            $claude = $this->getOwningCharacter($event->theah);
            if ($claude->isControlled())
            {
                if ($event->theah->cardInCity($claude) && $event->location == $claude->Location && $claude->ControllerId)
                {
                    $transition = EventFactory::createReactionTransitionEvent($claude->ControllerId, $claude->Id, $this->Id);
                    $event->theah->queueEvent($transition);
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'specialCount')
        {
            $claude = $this->getOwningCard($game->theah);
            $game->globals->set(Game::CLAIM_TYPE, Game::CLAUDE_CLAIM_TYPE);
            $game->globals->set(Game::CLAUD_ID, $claude->Id);

            $game->notifyAllPlayers('message', clienttranslate('<strong>${card_name}:</strong> ${player_name} used Reaction to count only the Performer and En Garde Characters for Claims at his location for the rest of the turn.'), [
                'i18n' => ['card_name'],
                'player_name' => $game->getActivePlayerName(),
                'card_name' => $claude->Name,
            ]);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");        
    }

}