<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\ActionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\actions\Action_03063;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasActions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _03063 extends Scheme implements IHasActions
{
    use ActionTrait;

    public function __construct()
    {
        parent::__construct();

        $this->Name = clienttranslate("Smuggling Run");
        $this->Image = '03063.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 63;

        $this->initializeFaction('Neutral');

        $this->Initiative = 52;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate('Cunning'),
            clienttranslate('Crime')
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The Grand Bazaar] and [The City Docks]</p>
<hr />
<p>When an opponent equips a card to a character opposing your <b>Scoundrel</b>, it gains +1 cost.</p>
<p><b>Scoundrel City Action:</b> Move a Renown or an available attachment from your performer's location to a different <b>City</b> location.</p>");

        $this->resetCard();

        $this->Actions = [
            new Action_03063(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. Renown will be added to The Grand Bazaar and The City Docks.'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $bazaar = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($bazaar);

            $docks = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_DOCKS, 1, $this->getInjectCode());
            $event->theah->queueEvent($docks);
        }
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment, array &$explanations): int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment, $explanations);

        // WHY: Chosen schemes sit at Home all day; only then is this tax in effect.
        if ($this->Location != Game::LOCATION_PLAYER_HOME)
        {
            return $discount;
        }

        // WHY: Printed "When an opponent equips" means the equipping player — use the
        // attachment's controller. Do NOT use $performer->ControllerId for this gate:
        // CanEquipToOpponents (Shackles / Legion's Caress) sets CHOSEN_PERFORMER to the
        // equip *target*, so a performer-based check falsely taxes you for equipping onto
        // an opponent while your Scoundrel is at that location.
        if ($attachment->ControllerId == $this->ControllerId || $attachment->ControllerId == 0)
        {
            return $discount;
        }

        // WHY: "...to a character opposing your Scoundrel" — $performer is the equip
        // target in both normal and CanEquipToOpponents flows. Home shares one location
        // string across players; opposing only applies in the city (Makepeace _01092).
        if (! $performer->isNotControlledByPlayer($this->ControllerId) || ! $theah->cardInCity($performer))
        {
            return $discount;
        }

        foreach ($theah->getCharactersAtLocation($performer->Location) as $character)
        {
            if ($character->ControllerId == $this->ControllerId && $character->hasTrait("Scoundrel"))
            {
                $discount -= 1;
                $explanations[] = sprintf(
                    $theah->game->translate("%s: +1 because an opponent is equipping onto a character opposing your Scoundrel."),
                    $this->getInjectCode()
                );
                break;
            }
        }

        return $discount;
    }
}
