<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelGambleCardsRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Reaction_02042 extends CardReaction
{
    private array $revealedSorceryIds = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Put a revealed Gambled Sorcery into your hand');
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventDuelGambleCardsRevealed && $this->isAvailable())
        {
            $ivy = $this->getOwningCharacter($event->theah);
            if ($ivy === null || !$event->theah->cardInCity($ivy)) {
                return;
            }

            $actor = $event->theah->getCharacterById($event->actorId);
            if ($actor === null) {
                return;
            }

            if ($actor->ControllerId != $ivy->ControllerId) {
                return;
            }

            if ($actor->Location != $ivy->Location) {
                return;
            }

            $sorcerers = array_filter(
                $event->theah->getCharactersAtLocationByPlayerId($actor->Location, $ivy->ControllerId),
                fn($c) => $c->hasTrait('Sorcerer')
            );
            if (empty($sorcerers)) {
                return;
            }

            $sorceryIds = [];
            foreach ($event->revealedCardIds as $cardId)
            {
                $card = $event->theah->game->getCardObjectFromDb($cardId);
                if ($card !== null && $card->hasTrait('Sorcery'))
                {
                    $sorceryIds[] = $cardId;
                }
            }

            if (empty($sorceryIds)) {
                return;
            }

            $this->revealedSorceryIds = $sorceryIds;
            $ivy->IsUpdated = true;

            $transition = EventFactory::createReactionTransitionEvent($ivy->ControllerId, $ivy->Id, $this->Id);
            $event->theah->queueEvent($transition);
        }
    }

    public function getReactionDescription(Theah $theah): string
    {
        return parent::getReactionDescription($theah) . $theah->game->translate('${you} may put a revealed Sorcery into your hand (hover for details): ');
    }

    public function getReactionButtonProperties(Theah $theah): array
    {
        $array = parent::getReactionButtonProperties($theah);
        $game = $theah->game;

        foreach ($this->revealedSorceryIds as $cardId)
        {
            $card = $game->getCardObjectFromDb($cardId);
            if ($card !== null)
            {
                $button = $this->createButtonProperty($game, $card->Name, "takeSorcery-$cardId");
                $button['card'] = $card->getPropertyArray($game);
                $array[] = $button;
            }
        }

        $array[] = $this->createButtonProperty($game, $game->translate('Pass'), 'pass');
        return $array;
    }

    public function performReaction(Game $game, int $state, string $internalId, string $reactionId): void
    {
        parent::performReaction($game, $state, $internalId, $reactionId);

        if ($reactionId === 'pass')
        {
            $this->revealedSorceryIds = [];
            $ivy = $this->getOwningCharacter($game->theah);
            if ($ivy) {
                $ivy->IsUpdated = true;
            }
            $game->gamestate->nextState('done');
            return;
        }

        if (str_starts_with($reactionId, 'takeSorcery-'))
        {
            $cardId = (int) substr($reactionId, strlen('takeSorcery-'));

            if (!in_array($cardId, $this->revealedSorceryIds, true)) {
                $game->gamestate->nextState('done');
                return;
            }

            $ivy = $this->getOwningCharacter($game->theah);
            $card = $game->getCardObjectFromDb($cardId);

            $game->notify->all("message", clienttranslate('${ivy_inject_code}: ${player_name} has Gambled and puts ${card_inject_code} into their hand.'), [
                "ivy_inject_code" => $ivy->getInjectCode(),
                "player_name" => $game->getPlayerNameById($ivy->ControllerId),
                "card_inject_code" => $card->getInjectCode(),
            ]);

            $addToHandEvent = EventFactory::createCardAddedToHandEvent($ivy->ControllerId, $cardId);
            $game->theah->queueEvent($addToHandEvent);

            $currentCount = $game->globals->get(Game::GAMBLE_REVEAL_COUNT, 2);
            $game->globals->set(Game::GAMBLE_REVEAL_COUNT, $currentCount - 1);

            $this->revealedSorceryIds = [];
            $this->setUsed($game->theah, true);
            $ivy->IsUpdated = true;
            $game->gamestate->nextState('done');
            return;
        }

        $game->gamestate->nextState('done');
    }
}
