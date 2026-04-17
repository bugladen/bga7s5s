<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\tac;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\tac\reactions\Reaction_02053;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\States;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _02053 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    /** @var array<string> Discard pile locations already processed this Dusk */
    public array $ProcessedPiles = [];

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Under Cover of the Night');
        $this->Image = "02053.jpg";
        $this->ExpansionName = "tac";
        $this->ExpansionNumber = 2;
        $this->CardNumber = 53;

        $this->initializeFaction('Neutral');

        $this->Initiative = 3;
        $this->PanacheModifier = 1;

        $this->Traits = [
            clienttranslate('Cunning'),
            clienttranslate('Sabotage')
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [City Docks].</p><hr><p><b>City Reaction:</b> At the beginning of Dusk • Your performer does not move <b>Home</b> during Dusk. Then, send up to one card from each discard pile to <b>The Locker</b>.</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_02053(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // Resolve effect: Add a Renown to City Docks
        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. A Renown will be added to The Docks.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $addEvent = EventFactory::createReknownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($addEvent);
        }

        if ($event instanceof EventDuskPhaseEnd)
        {
            $this->ProcessedPiles = [];
            $this->IsUpdated = true;
        }
    }

    public function eventCheck($event)
    {
        parent::eventCheck($event);

        // Block performer from moving home if they have the Under Cover of the Night condition
        if ($event instanceof EventCardMoved && $event->toLocation == Game::LOCATION_PLAYER_HOME)
        {
            $card = $event->theah->getCardById($event->cardId);
            if ($card instanceof Character && $card->hasCondition(Game::UNDER_COVER_OF_THE_NIGHT))
            {
                $card->removeCondition(Game::UNDER_COVER_OF_THE_NIGHT);
                $card->IsUpdated = true;
                throw new UserException($event->theah->game->translate("{$this->getInjectCode()}: {$card->getInjectCode()} stays in the city under cover of the night."));
            }
        }
    }

    /**
     * Get the next unprocessed discard pile location name, or null if all done.
     */
    private function getNextDiscardPile(Game $game): ?string
    {
        // Check each player's discard pile
        $playerIds = $game->theah->getDBObject()->getPlayerIds();
        foreach ($playerIds as $player)
        {
            $discardName = $game->getPlayerDiscardDeckName($player['id']);
            if (!in_array($discardName, $this->ProcessedPiles))
            {
                $discardCards = $game->theah->getCardObjectsAtLocation($discardName);
                if (count($discardCards) > 0)
                {
                    return $discardName;
                }
            }
        }

        // Check city discard pile
        if (!in_array(Game::LOCATION_CITY_DISCARD, $this->ProcessedPiles))
        {
            $cityCards = $game->theah->getCardObjectsAtLocation(Game::LOCATION_CITY_DISCARD);
            if (count($cityCards) > 0)
            {
                return Game::LOCATION_CITY_DISCARD;
            }
        }

        return null;
    }

    public function argsFromCard(Game $game, int $state, string $stateName, string $internalId): array
    {
        $args = parent::argsFromCard($game, $state, $stateName, $internalId);

        if ($state == States::DUSK_PHASE_BEGIN_02053)
        {
            $pileName = $this->getNextDiscardPile($game);
            $cards = [];
            if ($pileName !== null)
            {
                $discardCards = $game->theah->getCardObjectsAtLocation($pileName);
                foreach ($discardCards as $card)
                {
                    $cards[] = $card->getPropertyArray($game);
                }
            }

            $args["cards"] = $cards;

            // Resolve a display label for the current discard pile
            if ($pileName === Game::LOCATION_CITY_DISCARD)
            {
                $args["discardPileLabel"] = $game->translate("City Discard Pile");
            }
            else
            {
                $playerIds = $game->theah->getDBObject()->getPlayerIds();
                foreach ($playerIds as $player)
                {
                    if ($game->getPlayerDiscardDeckName($player['id']) == $pileName)
                    {
                        $args["discardPileLabel"] = sprintf($game->translate("%s's Discard Pile"), $game->getPlayerNameById($player['id']));
                        break;
                    }
                }
            }
        }

        return $args;
    }

    public function actFromCardWithId(Game $game, int $state, string $stateName, string $internalId, int $id): void
    {
        parent::actFromCardWithId($game, $state, $stateName, $internalId, $id);

        if ($state == States::DUSK_PHASE_BEGIN_02053)
        {
            $pileName = $this->getNextDiscardPile($game);
            if ($pileName === null)
            {
                throw new UserException($game->translate("No discard pile to select from."));
            }

            $card = $game->theah->getCardById($id);
            if ($card === null)
            {
                $card = $game->getCardObjectFromDb($id);
            }
            if ($card === null)
            {
                throw new UserException($game->translate("Card not found."));
            }

            if ($card->Location != $pileName)
            {
                throw new UserException($game->translate("Card is not in the current discard pile."));
            }

            if ($pileName === Game::LOCATION_CITY_DISCARD)
            {
                $removeEvent = EventFactory::createCardRemovedFromCityDiscardPileEvent($this->ControllerId, $card->Id);
                $game->theah->queueEvent($removeEvent);

                // Move to city locker
                $deck = $game->getGameDeckObject();
                $deck->moveCard($card->Id, Game::LOCATION_CITY_LOCKER);
                $card->Location = Game::LOCATION_CITY_LOCKER;
                $card->IsUpdated = true;

                $game->notify->all("cardSentToCityLocker", clienttranslate('${scheme_inject_code}: ${card_inject_code} is sent to the City Locker.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "card_inject_code" => $card->getInjectCode(),
                    "card" => $card->getPropertyArray($game),
                ]);
            }
            else
            {
                $game->notify->all("message", clienttranslate('${scheme_inject_code}: ${card_inject_code} is sent to The Locker from the discard pile.'), [
                    "scheme_inject_code" => $this->getInjectCode(),
                    "card_inject_code" => $card->getInjectCode(),
                ]);

                $removeEvent = EventFactory::createCardRemovedFromPlayerDiscardPileEvent($card->OwnerId, $card->Id);
                $game->theah->queueEvent($removeEvent);

                $lockerEvent = EventFactory::createCardSentToLockerEvent($card->OwnerId, $card->Id);
                $game->theah->queueEvent($lockerEvent);
            }

            $this->ProcessedPiles[] = $pileName;
            $this->IsUpdated = true;

            $game->gamestate->nextState("");
        }
    }

    public function actFromCardPass(Game $game, int $state, string $stateName, string $internalId): void
    {
        parent::actFromCardPass($game, $state, $stateName, $internalId);

        if ($state == States::DUSK_PHASE_BEGIN_02053)
        {
            $pileName = $this->getNextDiscardPile($game);
            if ($pileName !== null)
            {
                $this->ProcessedPiles[] = $pileName;
                $this->IsUpdated = true;
            }

            $game->gamestate->nextState("");
        }
    }
}
