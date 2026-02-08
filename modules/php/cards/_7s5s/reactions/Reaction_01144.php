<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\_7s5s\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhaseHighDrama;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_01144 extends CardReaction
{
    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Filling The Ranks");
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may choose to fill your ranks: ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);

        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Fill Ranks'), 'fillRanks');
        $array[] = $this->createButtonProperty($theah->game, $theah->game->translate('Decline'), 'decline');

        return $array;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventPhaseHighDrama) 
        {
            $owner = $this->getOwningCard($event->theah);
            if ($owner->Location == Game::LOCATION_PLAYER_HOME)
            {
                $game = $event->theah->game;
                list($playerIdWithLeastCharacters, $lowestCount) = $game->getPlayerControllingFewestCharacters();

                if ($playerIdWithLeastCharacters == $owner->ControllerId) 
                {
                    $characters = $game->theah->getAllCards();
                    $characters = array_filter($characters, fn($card) => $card instanceof Character && ! $card->isControlled() && $game->theah->cardInCity($card));
                    if (count($characters) > 0)
                    {
                        $transition = EventFactory::createReactionTransitionEvent($owner->ControllerId, $owner->Id, $this->Id);
                        $event->theah->queueEvent($transition);
                    }
                }
            }
        }
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId == 'fillRanks')
        {
            $players = $game->loadPlayersBasicInfos();
    
            // Get the higest stat for the player's leader
            $owner = $this->getOwningCard($game->theah);
            $leader = $game->theah->getLeaderByPlayerId($owner->ControllerId);
            $discount = max($leader->ModifiedCombat, $leader->ModifiedFinesse, $leader->ModifiedInfluence);

            list($playerIdWithLeastCharacters, $lowestCount) = $game->getPlayerControllingFewestCharacters();

            //Set the discount for recruiting a mercenary.
            $game->globals->set(Game::DISCOUNT, $discount);

            $game->notify->all("message", clienttranslate('${scheme_inject_code} Leader Reaction: ${player_name} has the least (non-tied) amount of characters in play (${amount}).
            They may now Recruit a mercenary at a discount of their Leader\'s highest stat.'), [
                "scheme_inject_code" => $owner->getInjectCode(),
                "amount" => $lowestCount,
                "player_name" => $players[$owner->ControllerId]['player_name'],
            ]);

            //Transition to the state where player can choose a mercenary to recruit.
            $transition = EventFactory::createTransitionEvent($owner->ControllerId, $owner->Id, '01144', $this->Id);
            $game->theah->queueEvent($transition);

            $this->setUsed($game->theah, true);
        }

        $game->gamestate->nextState("done");
    }

    public function getArgsFromReaction(Game $game, int $state, string $stateName): array
    {
        $args = parent::getArgsFromReaction($game, $state, $stateName);

        if ($state == States::HIGH_DRAMA_BEGINNING_01144)
        {
            $args["discount"] = $game->globals->get(GAME::DISCOUNT);
        }

        if ($state == States::HIGH_DRAMA_BEGINNING_01144_2)
        {
            $args["mercenaryId"] = $game->globals->get(Game::CHOSEN_CARD);
            $args["discount"] = $game->globals->get(GAME::DISCOUNT);
        }

        return $args;
    }

    public function actFromReactionWithId(Game $game, int $state, string $stateName, int $id): void
    {
        parent::actFromReactionWithId($game, $state, $stateName, $id);

        if ($state == States::HIGH_DRAMA_BEGINNING_01144)
        {
            $mercenary = $game->theah->getCharacterById($id);
            if ($mercenary == null)
            {
                throw new \BgaUserException($game->translate("Invalid character"));
            }

            if ($mercenary->isControlled())
            {
                throw new \BgaUserException($game->translate("Character is already controlled"));
            }

            if (! $game->theah->cardInCity($mercenary))
            {
                throw new \BgaUserException($game->translate("Character is not in the city"));
            }

            $game->globals->set(Game::CHOSEN_CARD, $mercenary->Id);

            $game->gamestate->nextState("mercenaryChosen");    
        }
    }

    public function actFromReactionWithIds(Game $game, int $state, string $stateName, array $ids): void
    {
        parent::actFromReactionWithIds($game, $state, $stateName, $ids);

        if ($state == States::HIGH_DRAMA_BEGINNING_01144_2)
        {
            $recruitId = $game->globals->get(Game::CHOSEN_CARD);
            $game->actRecruitMercenary($recruitId, json_encode($ids));

            $recruit = $game->theah->getCharacterById($recruitId);
            $owner = $this->getOwningCard($game->theah);
            $moveEvent = EventFactory::createCardMovingEvent($owner->ControllerId, $recruitId, $recruit->Location, Game::LOCATION_PLAYER_HOME, false, $owner->Id, $this->Id);
            $game->theah->eventCheck($moveEvent);
            $game->theah->queueEvent($moveEvent);

            $game->gamestate->nextState();
        }
    }
}