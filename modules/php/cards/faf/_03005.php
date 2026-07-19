<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03005;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _03005 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate("No Mercy");
        $this->Image = "03005.jpg";
        $this->ExpansionName = "faf";
        $this->ExpansionNumber = 3;
        $this->CardNumber = 5;

        $this->initializeFaction("Vodacce");

        $this->Initiative = 91;
        $this->PanacheModifier = -1;

        $this->Traits = [
            clienttranslate("Villainous"),
            clienttranslate("Duress"),
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The Grand Bazaar] and [The City Forum]</p><p>Put a <b>Gang</b>, <b>Crime</b>, or <b>Villainous</b> card from your discard into your hand.</p><hr><p><b>Reaction:</b> After your <b>Red Hand</b>'s challenge is refused • Claim that location.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03005(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. A Renown will be added to The Grand Bazaar and The City Forum. ${player_name} will then search their discard pile for a Gang, Crime, or Villainous card.'), [
                "scheme_inject_code" => $this->getInjectCode(),
                "player_name" => $event->theah->game->getPlayerNameById($event->playerId),
            ]);

            $bazaar = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($bazaar);

            $forum = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($forum);

            $transition = EventFactory::createTransitionEvent($event->playerId, $this->Id, "03005");
            $transition->priority = Event::MEDIUM_PRIORITY;
            $event->theah->queueEvent($transition);
        }
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_03005)
        {
            $playerId = $game->getActivePlayerId();
            $card = $game->getCardObjectFromDb($id);
            if (! $card)
            {
                throw new UserException($game->translate("Invalid card"));
            }

            if (! $this->cardMatchesTraits($card))
            {
                throw new UserException($game->translate("Card must have the Gang, Crime, or Villainous trait."));
            }

            $deck = $game->getGameDeckObject($playerId);
            $discardPileName = $game->getPlayerDiscardDeckName($playerId);
            $cardObjects = $deck->getCardsInLocation($discardPileName);
            if (! in_array($card->Id, array_column($cardObjects, 'id')))
            {
                throw new UserException($game->translate("Card is not in your discard pile."));
            }

            $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($playerId, $card->Id);
            $game->theah->eventCheck($removeEvent);

            $addEvent = EventFactory::createCardAddedToHandEvent($playerId, $card->Id);
            $game->theah->eventCheck($addEvent);

            $game->theah->queueEvent($removeEvent);
            $game->theah->queueEvent($addEvent);

            $game->gamestate->nextState("");
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::PLANNING_PHASE_RESOLVE_SCHEMES_03005)
        {
            $playerId = $game->getActivePlayerId();
            $deck = $game->getGameDeckObject($playerId);
            $discardPileName = $game->getPlayerDiscardDeckName($playerId);
            $cardObjects = $deck->getCardsInLocation($discardPileName);
            foreach ($cardObjects as $row)
            {
                $card = $game->getCardObjectFromDb($row['id']);
                if ($card && $this->cardMatchesTraits($card))
                {
                    throw new UserException($game->translate("There is a Gang, Crime, or Villainous card in your discard pile that you must choose."));
                }
            }

            $game->gamestate->nextState("");
        }
    }

    private function cardMatchesTraits($card): bool
    {
        return $card->hasTrait("Gang") || $card->hasTrait("Crime") || $card->hasTrait("Villainous");
    }
}
