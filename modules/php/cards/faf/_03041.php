<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03041;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPhasePlanningEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _03041 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    // WHY: Forced draws N then discards equal N — persist so the discard sub-state
    // knows the exact count after Academic check / deck-availability clamp.
    public int $cardsToDiscard = 0;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Proper Study');
        $this->Image = '03041.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 41;

        $this->initializeFaction('Castille');

        $this->Initiative = 68;
        $this->PanacheModifier = 1;

        $this->Traits = [
            clienttranslate('Alquimia'),
            clienttranslate('Scholarship')
        ];

        $this->Text = clienttranslate("<p>Add a Renown to <b>City Docks</b> and <b>The Grand Bazaar</b>.</p>
<hr />
<p><b>Forced:</b> At the end of Planning • Draw two cards, or three cards instead if you control an <b>Academic</b>. Then, discard an equal number of cards.</p>
<p><b>City Reaction:</b> After you claim a location • Move a Renown from that location to a different location.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03041(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The City Docks and The Grand Bazaar.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $docks = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($docks);

            $bazaar = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($bazaar);
        }

        // WHY: Chosen schemes sit at LOCATION_PLAYER_HOME until Dusk sends them to the locker
        // (same gate as Cat's Embargo _01098 Forced).
        if ($event instanceof EventPhasePlanningEnd && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $drawCount = $this->controlsAcademic($event->theah) ? 3 : 2;
            $available = $this->countDrawableCards($event->theah, $this->ControllerId);
            $actualDraws = min($drawCount, $available);

            $playerName = $event->theah->game->getPlayerNameById($this->ControllerId);

            if ($actualDraws == 0)
            {
                $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name}\'s Forced ability triggers, but there are no cards left to draw.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $playerName,
                ]);
                return;
            }

            if ($drawCount == 3)
            {
                $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name}\'s Forced ability triggers (controls an Academic). They will draw ${draw_count} card(s), then discard an equal number.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $playerName,
                    "draw_count" => $actualDraws,
                ]);
            }
            else
            {
                $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code}: ${player_name}\'s Forced ability triggers. They will draw ${draw_count} card(s), then discard an equal number.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "player_name" => $playerName,
                    "draw_count" => $actualDraws,
                ]);
            }

            for ($i = 0; $i < $actualDraws; $i++)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($this->ControllerId, $this->getInjectCode());
                $event->theah->queueEvent($drawEvent);
            }

            $this->cardsToDiscard = $actualDraws;
            $this->IsUpdated = true;

            $transition = EventFactory::createTransitionEvent($this->ControllerId, $this->Id, "03041");
            $event->theah->queueEvent($transition);
        }
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_END_03041)
        {
            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->ControllerId);
            // WHY: Clamp to hand size — if draws somehow undershot, "equal number" cannot exceed hand.
            $args['cardsToDiscard'] = min($this->cardsToDiscard, count($hand));
        }

        return $args;
    }

    public function actFromCardWithIds(Game $game, int $state, string $stateName, string $internalId, array $ids): void
    {
        parent::actFromCardWithIds($game, $state, $stateName, $internalId, $ids);

        if ($state == States::PLANNING_PHASE_END_03041)
        {
            $hand = $game->theah->getCardObjectsAtLocation(Game::LOCATION_HAND, $this->ControllerId);
            $required = min($this->cardsToDiscard, count($hand));

            if (count($ids) != $required)
            {
                throw new UserException(sprintf($game->translate("You must discard exactly %d card(s)."), $required));
            }

            foreach ($ids as $id)
            {
                $card = $game->theah->getCardById((int)$id);
                if ($card === null || $card->Location != Game::LOCATION_HAND || $card->ControllerId != $this->ControllerId)
                {
                    throw new UserException($game->translate("Card must be in your hand."));
                }
            }

            foreach ($ids as $id)
            {
                $discardEvent = EventFactory::createCardDiscardedFromHandEvent(
                    $this->ControllerId,
                    (int)$id,
                    $this->Id,
                    $asPayment = false,
                    $asPlayed = false,
                    $asEffect = true
                );
                $game->theah->queueEvent($discardEvent);
            }

            $this->cardsToDiscard = 0;
            $this->IsUpdated = true;

            $game->gamestate->nextState("");
        }
    }

    private function controlsAcademic(Theah $theah): bool
    {
        foreach ($theah->getCharactersInPlayByPlayerId($this->ControllerId) as $character)
        {
            if ($character instanceof Character && $character->hasTrait("Academic"))
            {
                return true;
            }
        }

        return false;
    }

    private function countDrawableCards(Theah $theah, int $playerId): int
    {
        $deck = $theah->game->getGameDeckObject();
        $factionDeck = $theah->game->getPlayerFactionDeckName($playerId);
        $discardPile = $theah->game->getPlayerDiscardDeckName($playerId);

        return $deck->countCardsInLocation($factionDeck) + $deck->countCardsInLocation($discardPile);
    }
}
