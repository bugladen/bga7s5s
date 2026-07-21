<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03033;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;

class _03033 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Glorious");
        $this->Image = '03033.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 33;

        $this->initializeFaction("Montaigne");

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            clienttranslate("Virtue"),
            clienttranslate("Flourish")
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> After your adversary is destroyed, if this card is in your dueling line • Your participant heals a wound.</p>
<p><b>Gambling Maneuver:</b> If your participant has equal or greater [Influence] than the adversary • Wound the adversary.</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_03033(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventCharacterDestroyed
            && $this->Location == Game::LOCATION_DUELING_LINE
            && $event->theah->game->globals->get(Game::IN_DUEL))
        {
            $theah = $event->theah;
            $game = $theah->game;
            $challengerId = $theah->getDuelChallengerId();
            $defenderId = $theah->getDuelDefenderId();
            $destroyedId = $event->characterId;

            $participantId = null;
            if ($destroyedId == $defenderId && $theah->getCharacterById($challengerId)->ControllerId == $this->ControllerId)
            {
                $participantId = $challengerId;
            }
            elseif ($destroyedId == $challengerId && $theah->getCharacterById($defenderId)->ControllerId == $this->ControllerId)
            {
                $participantId = $defenderId;
            }

            if ($participantId === null)
            {
                return;
            }

            $participant = $theah->getCharacterById($participantId);
            if ($game->characterIsInDiscardOrLocker($participant) || $participant->Wounds <= 0)
            {
                return;
            }

            $game->notify->all("message", clienttranslate('${card_inject_code}: Forced — after your adversary is destroyed, your participant heals a wound.'), [
                "card_inject_code" => $this->getInjectCode(),
            ]);

            $healEvent = EventFactory::createCharacterBeingHealedEvent($participantId, $this->Id, 1, $this->getInjectCode(), $this->Id);
            $theah->queueEvent($healEvent);
        }
    }
}
