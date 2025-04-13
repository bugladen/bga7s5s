<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Character extends Card
{
    public string $Title;
    public int $Resolve;
    public int $ModifiedResolve;
    public int $Wounds;
    public int $Combat;
    public int $ModifiedCombat;
    public int $Finesse;
    public int $ModifiedFinesse;
    public int $Influence;
    public int $ModifiedInfluence;

    public bool $DashedCombat;
    public bool $DashedFinesse;
    public bool $DashedInfluence;

    public Array $Attachments = [];

    public function __construct()
    {
        parent::__construct();

        $this->Resolve = 0;
        $this->ModifiedResolve = 0;
        $this->Wounds = 0;
        $this->Combat = 0;
        $this->ModifiedCombat = 0;
        $this->Finesse = 0;
        $this->ModifiedFinesse = 0;
        $this->Influence = 0;
        $this->ModifiedInfluence = 0;

        $this->DashedCombat = false;
        $this->DashedFinesse = false;
        $this->DashedInfluence = false;
    }

    public function resetModifiedCharacterStats()
    {
        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
    }

    public function canChallenge(): bool
    {
        return ! $this->Engaged;
    }

    public function canIntervene() : bool
    {
        return ! $this->Engaged;
    }

    public function hasWhenRevealedEffect() : bool
    {
        return false;
    }

    public function getParleyDiscount(bool $parleying) : int
    {
        return $parleying ? $this->ModifiedInfluence : 0;
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment) : int
    {
        $discount = 0;
        foreach ($this->Attachments as $attachmentId)
        {
            $attachment = $theah->getCardById($attachmentId);
            if ($attachment instanceof Attachment)
            {
                $discount += $attachment->getEquipDiscount($theah, $performer, $attachment);
            }
        
        }
        return $discount;
    }

    public function getPressureInfluenceValue(): int
    {
        return $this->ModifiedInfluence;
    }

    public function getReactionFromHandDiscount(Theah $theah, CardReaction $reaction) : int
    {
        $discount = parent::getReactionFromHandDiscount($theah, $reaction);
        
        foreach ($this->Attachments as $attachmentId)
        {
            $attachment = $theah->getCardById($attachmentId);
            if ($attachment instanceof Attachment)
            {
                $discount += $attachment->getReactionFromHandDiscount($theah, $reaction);
            }
        
        }
        return $discount;
    }

    public function addAttachment(Attachment $attachment)
    {
        $this->ModifiedResolve += $attachment->ResolveModifier;
        $this->ModifiedCombat += $attachment->CombatModifier;
        $this->ModifiedFinesse += $attachment->FinesseModifier;
        $this->ModifiedInfluence += $attachment->InfluenceModifier;

        $this->Attachments[] = $attachment->Id;
        $this->IsUpdated = true;
    }

    public function removeAttachment(Attachment $attachment)
    {
        $index = array_search($attachment->Id, $this->Attachments);
        if ($index !== false) {
            $this->ModifiedResolve -= $attachment->ResolveModifier;
            $this->ModifiedCombat -= $attachment->CombatModifier;
            $this->ModifiedFinesse -= $attachment->FinesseModifier;
            $this->ModifiedInfluence -= $attachment->InfluenceModifier;

            unset($this->Attachments[$index]);
        }
    }

    public function handleEvent($event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventGenerateChallengeThreat && $event->actorId == $this->Id)
        {
            switch ($event->statUsed)
            {
                case Game::CHALLENGE_STAT_COMBAT:
                    $event->threat += $this->ModifiedCombat;
                    $event->explanations[] = clienttranslate("{$this->Name} adds {$this->ModifiedCombat} Threat from their Combat Stat.");
                    break;
                case Game::CHALLENGE_STAT_FINESSE:
                    $event->threat += $this->ModifiedFinesse;
                    $event->explanations[] = clienttranslate("{$this->Name} adds {$this->ModifiedFinesse} Threat from their Finesse Stat.");
                    break;
                case Game::CHALLENGE_STAT_INFLUENCE:
                    $event->threat += $this->ModifiedInfluence;
                    $event->explanations[] = clienttranslate("{$this->Name} adds {$this->ModifiedInfluence} Threat from their Influence Stat.");
                    break;
            }
        }

        if ($event instanceof EventCharacterWounded && $event->characterId == $this->Id)
        {
            $this->Wounds += $event->wounds;
            
            $this->ModifiedResolve -= $event->wounds;    
            if ($this->ModifiedResolve < 0) 
            {
                $this->ModifiedResolve = 0;
            }

            $this->IsUpdated = true;

            $event->theah->game->notifyAllPlayers("characterWounded", clienttranslate('${target_name} has received ${wounds} wound(s) due to: ${reason} 
            <p>${target_name}\'s new Resolve: ${resolve}'), [
                "target_name" => "<strong>{$this->Name}</strong>",
                "characterId" => $this->Id,
                "wounds" => $event->wounds,
                "reason" => $event->reason,
                'resolve' => $this->ModifiedResolve
            ]);

            if ($this->ModifiedResolve == 0)
            {
                $destroyEvent = EventFactory::createCharacterDestroyedEvent($this->ControllerId, $this->Id, $event->reason);
                $event->theah->queueEvent($destroyEvent);
            }
        }

        if ($event instanceof EventCharacterHealed && $event->characterId == $this->Id)
        {
            $this->ModifiedResolve += $event->wounds;
            if ($this->ModifiedResolve > $this->Resolve) 
            {
                $this->ModifiedResolve = $this->Resolve;
            }
            
            $this->Wounds -= $event->wounds;
            $actualHealed = $event->wounds;
            if ($this->Wounds < 0) 
            {
                $actualHealed = $event->wounds - abs($this->Wounds);
                $this->Wounds = 0;
            }

            $this->IsUpdated = true;

            $event->theah->game->notifyAllPlayers("characterHealed", clienttranslate('${target_name} has healed ${wounds} wound(s) due to: ${reason} 
            <p>${target_name}\'s new Resolve: ${resolve}'), [
                "target_name" => "<strong>{$this->Name}</strong>",
                "characterId" => $this->Id,
                "wounds" => $actualHealed,
                "reason" => $event->reason,
                'resolve' => $this->ModifiedResolve
            ]);
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $this->clearConditions();
            $this->resetModifiedCharacterStats();
            $this->Wounds = 0;
            $this->IsUpdated = true;
        }
    }

    public function getPropertyArray(): array
    {
        $properties = parent::getPropertyArray();

        //Add character specific properties
        $properties['title'] = $this->Title;
        $properties['resolve'] = $this->Resolve;
        $properties['modifiedResolve'] = $this->ModifiedResolve;
        $properties['wounds'] = $this->Wounds;
        $properties['combat'] = $this->Combat;
        $properties['modifiedCombat'] = $this->ModifiedCombat;
        $properties['finesse'] = $this->Finesse;
        $properties['modifiedFinesse'] = $this->ModifiedFinesse;
        $properties['influence'] = $this->Influence;
        $properties['modifiedInfluence'] = $this->ModifiedInfluence;

        $properties['dashedCombat'] = $this->DashedCombat;
        $properties['dashedFinesse'] = $this->DashedFinesse;
        $properties['dashedInfluence'] = $this->DashedInfluence;

        $properties['attachments'] = $this->Attachments;

        $properties['type'] = 'Character';

        return $properties;
    }
}