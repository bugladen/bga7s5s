<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\bas;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\bas\maneuvers\Maneuver_04050;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;

class _04050 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate('Severing Arc');
        $this->Image = '04050.jpg';
        $this->ExpansionName = 'bas';
        $this->ExpansionNumber = 4;
        $this->CardNumber = 50;

        $this->initializeFaction("Ussura");

        $this->WealthCost = 1;

        $this->Riposte = 0;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            clienttranslate("Flourish"),
            clienttranslate("Dismemberment"),
            clienttranslate("Unique")
        ];

        $this->Text = clienttranslate("<p>After the adversary performs a <b>Technique</b>, if this card is in your dueling line, they suffer a wound.</p>
<p><b>Duelist Maneuver:</b> +1[Thrust]. Engage the adversary and each of their equipped attachments.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_04050(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        // WHY EventResolveTechnique: "after … performs" = successful completion.
        // Cancel on TechniqueActivated deletes technique events before Resolve fires.
        if ($event instanceof EventResolveTechnique
            && $event->inDuel
            && $this->Location == Game::LOCATION_DUELING_LINE
            && $event->theah->game->globals->get(Game::IN_DUEL))
        {
            $theah = $event->theah;
            $game = $theah->game;

            $actor = $theah->getCharacterById($event->actorId);
            if ($actor === null || $actor->ControllerId == $this->ControllerId)
            {
                return;
            }

            // WHY challenger/defender: confirm actor is our duel adversary, not some
            // other opposing character who somehow resolved a Technique mid-duel.
            $challengerId = $theah->getDuelChallengerId();
            $defenderId = $theah->getDuelDefenderId();
            $ourParticipantId = null;
            if ($theah->getCharacterById($challengerId)->ControllerId == $this->ControllerId)
            {
                $ourParticipantId = $challengerId;
            }
            elseif ($theah->getCharacterById($defenderId)->ControllerId == $this->ControllerId)
            {
                $ourParticipantId = $defenderId;
            }

            if ($ourParticipantId === null)
            {
                return;
            }

            $adversaryId = $theah->getDuelOpponentId($ourParticipantId);
            if ($event->actorId != $adversaryId)
            {
                return;
            }

            if ($game->characterIsInDiscardOrLocker($actor))
            {
                return;
            }

            $game->notify->all("message", clienttranslate('${card_inject_code}: After the adversary performed a Technique, they suffer a wound.'), [
                "card_inject_code" => $this->getInjectCode(),
            ]);

            $woundEvent = EventFactory::createCharacterBeingWoundedEvent(
                $adversaryId,
                $this->Id,
                1,
                $this->getInjectCode(),
                $this->Id
            );
            $theah->eventCheck($woundEvent);
            $theah->queueEvent($woundEvent);
        }
    }
}
