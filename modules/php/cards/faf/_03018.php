<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards\faf;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\reactions\Reaction_03018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\faf\techniques\Technique_03018;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasReactions;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\IHasTechniques;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\ReactionTrait;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuskPhaseEnd;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveScheme;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

class _03018 extends Scheme implements IHasReactions
{
    use ReactionTrait;

    public function __construct()
    {
        parent::__construct();
        $this->Name = clienttranslate('Suffer Not the Wicked');
        $this->Image = '03018.jpg';
        $this->ExpansionName = 'faf';
        $this->ExpansionNumber = 3;
        $this->CardNumber = 18;

        $this->initializeFaction('Eisen');

        $this->Initiative = 41;
        $this->PanacheModifier = 0;

        $this->Traits = [
            clienttranslate('Heroic'),
            clienttranslate('Finale')
        ];

        $this->Text = clienttranslate("<p>Add a Renown to [The Grand Bazaar] and [The City Forum]</p>
<hr />
<p><b>Reaction:</b> When a challenge issued by your <b>Zealot</b> or <b>Hunter</b> is refused • Wound the refusing character.</p>
<p>Your wounded characters gain: <b>Technique:</b> If your adversary is a <b>Sorcerer</b> • +1[Thrust]</p>");

        $this->resetCard();

        $this->Reactions = [
            new Reaction_03018(),
        ];
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventResolveScheme && $event->scheme->Id == $this->Id)
        {
            $event->theah->game->notify->all("message", clienttranslate('${scheme_inject_code} now resolves. A Renown is added to [The Grand Bazaar] and [The City Forum].'), [
                "scheme_inject_code" => $this->getInjectCode(),
            ]);

            $bazaar = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_BAZAAR, 1, $this->getInjectCode());
            $event->theah->queueEvent($bazaar);

            $forum = EventFactory::createRenownAddedToLocationEvent($this->ControllerId, Game::LOCATION_CITY_FORUM, 1, $this->getInjectCode());
            $event->theah->queueEvent($forum);

            $this->syncAllTechniques($event->theah);
        }

        if ($event instanceof EventCharacterWounded && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character != null && $character->ControllerId == $this->ControllerId)
            {
                // WHY unconditional grant: scheme's handleEvent may run before the
                // Character's own handler that increments Wounds. The character
                // will be wounded post-event regardless of handler order, and
                // grantTechnique is idempotent.
                $this->grantTechnique($event->theah, $character);
            }
        }

        if ($event instanceof EventCharacterHealed && $this->Location == Game::LOCATION_PLAYER_HOME)
        {
            $character = $event->theah->getCharacterById($event->characterId);
            if ($character != null && $character->ControllerId == $this->ControllerId)
            {
                // WHY characterHandled branch: handler order isn't guaranteed.
                // If the Character already processed the heal, Wounds is the
                // post-state value. Otherwise Wounds is still pre-state and we
                // compute the post-state from the event delta.
                $futureWounds = $event->characterHandled
                    ? $character->Wounds
                    : max(0, $character->Wounds - $event->wounds);
                if ($futureWounds == 0)
                {
                    $this->revokeTechnique($event->theah, $character);
                }
            }
        }

        if ($event instanceof EventDuskPhaseEnd)
        {
            $this->removeAllTechniques($event->theah);
        }
    }

    private function syncAllTechniques(Theah $theah): void
    {
        foreach ($theah->getCharactersInPlayByPlayerId($this->ControllerId) as $character)
        {
            if ($character->Wounds > 0)
            {
                $this->grantTechnique($theah, $character);
            }
        }
    }

    private function grantTechnique(Theah $theah, Character $character): void
    {
        if (! ($character instanceof IHasTechniques))
        {
            return;
        }
        if ($theah->game->characterIsInDiscardOrLocker($character))
        {
            return;
        }
        if ($character->IsDying)
        {
            return;
        }
        if ($character->getTechniqueByClassId("Technique_03018") !== null)
        {
            return;
        }

        $technique = new Technique_03018();
        $technique->setId("Technique_03018");
        $technique->setOwnerId($character->Id);
        $character->addTechnique($technique, $theah->game, $notify = false);
        $character->IsUpdated = true;

        $theah->game->notify->all('techniqueAdded', clienttranslate('${scheme_inject_code}: ${character_inject_code} has gained Technique: ${technique_name}.'), [
            'i18n' => ['technique_name'],
            'scheme_inject_code' => $this->getInjectCode(),
            'character_inject_code' => $character->getInjectCode(),
            'characterId' => $character->Id,
            'technique' => $technique->getPropertyArray($theah->game),
            'technique_name' => $technique->Name,
        ]);
    }

    private function revokeTechnique(Theah $theah, Character $character): void
    {
        if (! ($character instanceof IHasTechniques))
        {
            return;
        }
        $existing = $character->getTechniqueByClassId("Technique_03018");
        if ($existing === null)
        {
            return;
        }

        $character->removeTechnique($existing, $theah->game, $notify = false);
        $character->IsUpdated = true;

        $theah->game->notify->all('techniqueRemoved', clienttranslate('${scheme_inject_code}: ${character_inject_code} has lost Technique: ${technique_name}.'), [
            'i18n' => ['technique_name'],
            'scheme_inject_code' => $this->getInjectCode(),
            'character_inject_code' => $character->getInjectCode(),
            'characterId' => $character->Id,
            'techniqueId' => $existing->Id,
            'technique_name' => $existing->Name,
        ]);
    }

    private function removeAllTechniques(Theah $theah): void
    {
        foreach ($theah->getCharactersInPlayByPlayerId($this->ControllerId) as $character)
        {
            $this->revokeTechnique($theah, $character);
        }
    }
}
