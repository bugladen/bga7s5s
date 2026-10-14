<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\cad;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\cad\reactions\Reaction_05SnowWhite;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;

class _05SnowWhite extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = "Wildcall";
        $this->Image = "05SnowWhite.v4.jpg";
        $this->ExpansionName = "cad";
        $this->ExpansionNumber = 5;
        // WHY: Stub CardNumber=13 matches CAD sequence after William/Orel/Volk (10–12).
        // Class routing uses `_05SnowWhite`; footer CAD-US8 is set designation, not this id.
        $this->CardNumber = 13;

        $this->initializeFaction("Ussura");

        $this->Initiative = 56;
        $this->PanacheModifier = 0;

        $this->Traits = [
            "Dar Matushki",
            "Command",
        ];

        // WHY: Prototype CAD — plain strings, no clienttranslate (hide from translators).
        $this->Text = "<p>Add a Renown to [The City Forum] and [The Grand Bazaar].</p>
<hr />
<p><b>Sorcerer Reaction:</b> At the end of Planning • Muster Orel or Volk at your <b>Home</b> from out of play.</p>
<p>Your <b>Animals</b> do not count towards [Crew Cap].</p>
<p>(Until the end of the day.)</p>";

        $this->resetCard();

        $this->Reactions = [
            new Reaction_05SnowWhite(),
        ];
    }

    /**
     * Passive: Animals do not count toward Crew Cap while this chosen scheme is at Home.
     * WHY: "(Until the end of the day.)" — schemes go to the Locker at Dusk, so
     * LOCATION_PLAYER_HOME naturally ends the exemption without a separate dusk clear.
     */
    public function isAnimalsExemptFromCrewCap(): bool
    {
        return $this->Location == Game::LOCATION_PLAYER_HOME;
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $game = $event->theah->game;

            $game->notify->all("message", '${scheme_inject_code} now resolves. A Renown will be added to The City Forum and The Grand Bazaar.', [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $forum = EventFactory::createRenownAddedToLocationEvent(
                $this->ControllerId,
                Game::LOCATION_CITY_FORUM,
                1,
                $this->getInjectCode()
            );
            $event->theah->queueEvent($forum);

            $bazaar = EventFactory::createRenownAddedToLocationEvent(
                $this->ControllerId,
                Game::LOCATION_CITY_BAZAAR,
                1,
                $this->getInjectCode()
            );
            $event->theah->queueEvent($bazaar);
        }
    }
}
