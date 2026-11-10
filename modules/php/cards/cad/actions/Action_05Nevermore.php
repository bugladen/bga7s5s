<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\actions;

use Bga\GameFramework\UserException;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\actions\SchemeCityAction;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventActionTriggered;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class Action_05Nevermore extends SchemeCityAction
{
    public function __construct()
    {
        parent::__construct();

        // WHY: Prototype CAD — plain strings, no clienttranslate.
        $this->Name = "Draw cards equal to Artifacts you control";
        $this->RequiresPerformerSelected = true;
    }

    /**
     * Artifacts the player currently controls (equipped / owned in play).
     */
    private function countControlledArtifacts(Theah $theah, int $playerId): int
    {
        $count = 0;
        foreach ($theah->getAllCards() as $card)
        {
            if ($card instanceof Attachment
                && $card->ControllerId == $playerId
                && $card->hasTrait("Artifact")
                && ! $card->FakeAttachment)
            {
                $count++;
            }
        }

        return $count;
    }

    private function countDrawableCards(Theah $theah, int $playerId): int
    {
        $deck = $theah->game->getGameDeckObject();
        $factionDeck = $theah->game->getPlayerFactionDeckName($playerId);
        $discardPile = $theah->game->getPlayerDiscardDeckName($playerId);

        return $deck->countCardsInLocation($factionDeck) + $deck->countCardsInLocation($discardPile);
    }

    public function isAvailableToPlayer(int $playerId, Theah $theah, bool $overrideInHandCheck = false): bool
    {
        if (! parent::isAvailableToPlayer($playerId, $theah, $overrideInHandCheck))
        {
            return false;
        }

        // WHY: Drawing 0 is a no-op — require ≥1 controlled Artifact and ≥1 drawable card.
        if ($this->countControlledArtifacts($theah, $playerId) < 1)
        {
            return false;
        }

        if ($this->countDrawableCards($theah, $playerId) < 1)
        {
            return false;
        }

        return count($this->getPerformersForAction($playerId, $theah)) > 0;
    }

    /**
     * @return list<Character>
     */
    public function getPerformersForAction(int $playerId, Theah $theah): array
    {
        $performers = parent::getPerformersForAction($playerId, $theah);

        // WHY: Leader City Action = mechanical trait gate (not ISorcererAbility) —
        // same discipline as Action_02014 / Action_03061 Hero.
        return array_values(array_filter(
            $performers,
            fn(Character $performer) => $performer->hasTrait("Leader")
        ));
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY: Pattern H — no HD sub-state. Framework already picked the Leader performer;
        // draw count is determined entirely from controlled Artifacts.
        if ($event instanceof EventActionTriggered && $event->actionId == $this->Id)
        {
            $game = $event->theah->game;
            $playerId = $event->playerId;
            $owner = $this->getOwningCard($event->theah);

            $performerId = (int) $game->globals->get(Game::CHOSEN_PERFORMER);
            $performer = $event->theah->getCharacterById($performerId);
            if ($performer === null || $performer->ControllerId != $playerId)
            {
                throw new UserException("Invalid performer");
            }

            if (! $performer->hasTrait("Leader"))
            {
                throw new UserException("Performer must be a Leader");
            }

            if (! $event->theah->cardInCity($performer))
            {
                throw new UserException("Performer must be at a City location.");
            }

            $artifactCount = $this->countControlledArtifacts($event->theah, $playerId);
            if ($artifactCount < 1)
            {
                throw new UserException("You do not control any Artifacts.");
            }

            $drawable = $this->countDrawableCards($event->theah, $playerId);
            $actualDraws = min($artifactCount, $drawable);
            if ($actualDraws < 1)
            {
                throw new UserException("There are no cards left to draw.");
            }

            $game->notify->all("message", '${scheme_inject_code}: ${player_name} draws ${draw_count} card(s) (Artifacts controlled: ${artifact_count}).', [
                "scheme_inject_code" => $owner->getInjectCode(),
                "player_name" => $game->getPlayerNameById($playerId),
                "draw_count" => $actualDraws,
                "artifact_count" => $artifactCount,
            ]);

            for ($i = 0; $i < $actualDraws; $i++)
            {
                $drawEvent = EventFactory::createCardDrawnEvent($playerId, $owner->getInjectCode());
                $event->theah->queueEvent($drawEvent);
            }

            $actionResolvedEvent = EventFactory::createActionResolvedEvent($playerId);
            $event->theah->queueEvent($actionResolvedEvent);
        }
    }
}
