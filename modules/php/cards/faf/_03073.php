<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\maneuvers\Maneuver_03073;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasManeuvers;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ManeuverTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;

class _03073 extends Risk implements IHasManeuvers
{
    use ManeuverTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Victorious");
        $this->Image = '03073.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 73;

        $this->initializeFaction('Neutral');

        $this->WealthCost = 0;

        $this->Riposte = 0;
        $this->DashedRiposte = true;
        $this->Parry = 2;
        $this->Thrust = 3;

        $this->Traits = [
            clienttranslate("Virtue"),
            clienttranslate("Glory"),
            clienttranslate("Triumph")
        ];

        $this->Text = clienttranslate("<p><b>Forced:</b> After your adversary is destroyed, if this card is in your dueling line • Draw a card.</p>
        <p><b>Gambling Maneuver:</b> +1[Thrust].</p>");

        $this->resetCard();

        $this->Maneuvers = [
            new Maneuver_03073(),
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

            // WHY: Challenger/defender ids are duel-stable; getDuelRoundActor/Opponent are round-relative
            // and may not identify the surviving participant at destroy time. Same lookup as Glorious (_03033).
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

            $game->notify->all("message", clienttranslate('${card_inject_code}: Forced — after your adversary is destroyed, draw a card.'), [
                "card_inject_code" => $this->getInjectCode(),
            ]);

            $drawEvent = EventFactory::createCardDrawnEvent($this->ControllerId, $this->getInjectCode());
            $theah->queueEvent($drawEvent);
        }
    }
}
