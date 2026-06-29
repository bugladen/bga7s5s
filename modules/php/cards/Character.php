<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\EventFactory;
use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\Event;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterDestroyed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterHealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterWounded;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\Theah;

abstract class Character extends Card implements IHasTechniques
{
    use TechniqueTrait;
    
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

    public bool $IsDying;

    // Cards like Sorte Deck can heal wounds as a reaction.  This is the number of wounds that have been healed as a reaction.
    // Used to prevent the character from queueing up its destroyed event if they have been healed as a reaction.
    public int $WoundsHealedIncoming = 0;

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

        $this->IsDying = false;

        $this->CardBackImage = "img/cards/backs/approach.jpg";
    }

    public function resetCard()
    {
        parent::resetCard();
        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
    }

    public function canChallenge(Theah $theah): bool
    {
        return $this->isControlled();
    }

    public function canIntervene() : bool
    {
        return $this->isControlled();
    }

    public function hasWhenRevealedEffect() : bool
    {
        return false;
    }

    public function getParleyDiscount(Theah $theah, Character $performer, bool $parleying, Array &$explanations) : int
    {
        $discount = parent::getParleyDiscount($theah, $performer, $parleying, $explanations);
        if ($performer->Id == $this->Id && $parleying)
        {
            $discount += $this->ModifiedInfluence;
            $explanations[] = sprintf($theah->game->translate("%s: -%d for Influence."), $this->getInjectCode(), $this->ModifiedInfluence);
        }

        return $discount;
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment, Array &$explanations) : int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment, $explanations);
        
        foreach ($this->Attachments as $attachmentId)
        {
            $attachment = $theah->getCardById($attachmentId);
            if ($attachment instanceof Attachment)
            {
                $discount += $attachment->getEquipDiscount($theah, $performer, $attachment, $explanations);
            }
        
        }
        return $discount;
    }

    public function getCombatPressureValue(Theah $theah, string $location): int
    {
        return $this->ModifiedCombat;
    }

    public function getFinessePressureValue(Theah $theah, string $location): int
    {
        return $this->ModifiedFinesse;
    }

    public function getInfluencePressureValue(Theah $theah, string $location): int
    {
        return $this->ModifiedInfluence;
    }

    public function getResolvePressureValue(Theah $theah, string $location): int
    {
        return $this->ModifiedResolve;
    }

    public function setLockedValues(Theah $theah)
    {
        foreach ($this->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            if (! $attachment)
                continue;

            if ($attachment->ResolveLocked)
            {
                $this->ModifiedResolve = $attachment->ResolveLockedValue;
            }
            if ($attachment->CombatLocked)
            {
                $this->ModifiedCombat = $attachment->CombatLockedValue;
            }
            if ($attachment->FinesseLocked)
            {
                $this->ModifiedFinesse = $attachment->FinesseLockedValue;
            }
            if ($attachment->InfluenceLocked)
            {
                $this->ModifiedInfluence = $attachment->InfluenceLockedValue;
            }
        }

    }

    public function addAttachment(Theah $theah, Attachment $attachment)
    {
        $this->ModifiedResolve += $attachment->ResolveModifier;

        if (!$this->DashedCombat)
        {
            $this->ModifiedCombat += $attachment->CombatModifier;
        }
        if (!$this->DashedFinesse)
        {
            $this->ModifiedFinesse += $attachment->FinesseModifier;
        }
        if (!$this->DashedInfluence)
        {
            $this->ModifiedInfluence += $attachment->InfluenceModifier;
        }

        $this->Attachments[] = $attachment->Id;
        $this->setLockedValues($theah);

        $this->IsUpdated = true;
    }

    public function removeAttachment(Theah $theah, Attachment $attachment)
    {
        $index = array_search($attachment->Id, $this->Attachments);
        if ($index !== false) {
            unset($this->Attachments[$index]);

            $this->ModifiedResolve -= $attachment->ResolveModifier;

            if ($this->DashedCombat) $this->ModifiedCombat = 0;
            if ($this->DashedFinesse) $this->ModifiedFinesse = 0;
            if ($this->DashedInfluence) $this->ModifiedInfluence = 0;

            if (!$this->DashedCombat)
            {
                $this->ModifiedCombat -= $attachment->CombatModifier;
            }
            if (!$this->DashedFinesse)
            {
                $this->ModifiedFinesse -= $attachment->FinesseModifier;
            }
            if (!$this->DashedInfluence)
            {
                $this->ModifiedInfluence -= $attachment->InfluenceModifier;
            }

            $this->setLockedValues($theah);

            $this->Attachments = array_values($this->Attachments);
            $this->IsUpdated = true;
        }
    }

    public function handleEvent(Event $event)
    {
        parent::handleEvent($event);

        if ($event instanceof EventGenerateChallengeThreat && $event->actorId == $this->Id)
        {
            switch ($event->statUsed)
            {
                case Game::STAT_COMBAT:
                    $event->adversaryThreat += $this->ModifiedCombat;
                    $event->explanations[] = sprintf($event->theah->game->translate("%s adds %d Threat from their Combat Stat."), $this->Name, $this->ModifiedCombat);
                    break;
                case Game::STAT_FINESSE:
                    $event->adversaryThreat += $this->ModifiedFinesse;
                    $event->explanations[] = sprintf($event->theah->game->translate("%s adds %d Threat from their Finesse Stat."), $this->Name, $this->ModifiedFinesse);
                    break;
                case Game::STAT_INFLUENCE:
                    $event->adversaryThreat += $this->ModifiedInfluence;
                    $event->explanations[] = sprintf($event->theah->game->translate("%s adds %d Threat from their Influence Stat."), $this->Name, $this->ModifiedInfluence);
                    break;
            }
        }

        if ($event instanceof EventCharacterWounded && $event->characterId == $this->Id)
        {
            $this->Wounds += $event->wounds;            
            $this->IsUpdated = true;

            $event->theah->game->notify->all("characterWounded", clienttranslate('${target_inject_code} has received ${wounds} wound(s) due to: ${reason}'), [
                'i18n' => ['reason'],
                "target_inject_code" => $this->getInjectCode(),
                "characterId" => $this->Id,
                "wounds" => $event->wounds,
                "reason" => $event->reason,
                'resolve' => $this->ModifiedResolve
            ]);

            if ($this->Wounds >= $this->ModifiedResolve + $this->WoundsHealedIncoming)
            {
                $this->IsDying = true;

                $this->unEquipAllAttachments($event->theah);
                
                //Send this to the locker
                $destroyEvent = EventFactory::createCharacterDestroyedEvent($this->ControllerId, $this->Id, $event->reason);
                $event->theah->queueEvent($destroyEvent);
            }
        }

        if ($event instanceof EventCharacterHealed && $event->characterId == $this->Id)
        {
            $this->Wounds -= $event->wounds;
            $actualHealed = $event->wounds;
            if ($this->Wounds < 0) 
            {
                $actualHealed = $event->wounds - abs($this->Wounds);
                $this->Wounds = 0;
            }

            $this->WoundsHealedIncoming -= $event->wounds;
            if ($this->WoundsHealedIncoming < 0)
            {
                $this->WoundsHealedIncoming = 0;
            }
            $this->IsUpdated = true;

            $event->theah->game->notify->all("characterHealed", clienttranslate('${target_inject_code} has healed ${wounds} wound(s) due to: ${reason}'), [
                'i18n' => ['reason'],
                "target_inject_code" => $this->getInjectCode(),
                "characterId" => $this->Id,
                "wounds" => $actualHealed,
                "reason" => $event->reason,
                'resolve' => $this->ModifiedResolve
            ]);
        }

        if ($event instanceof EventCharacterDestroyed && $event->characterId == $this->Id)
        {
            $this->IsDying = false;
            $this->WoundsHealedIncoming = 0;
            $this->IsUpdated = true;
        }
    }

    public function unEquipAllAttachments(Theah $theah)
    {
        foreach ($this->Attachments as $attachmentId)
        {
            $attachment = $theah->getCardById($attachmentId);
            if ($attachment == null)
                continue;

            $unattached = EventFactory::createAttachmentUnequippedEvent($this->ControllerId, $this->Id, $attachment->Id);
            $theah->queueEvent($unattached);

            if ($attachment instanceof CityAttachment)
                $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($this->ControllerId, $attachment->Id, $attachment->Location, $this->Id);
            else
                $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location, $this->Id);

            $theah->queueEvent($discardEvent);
        }
    }

    public function hasWeaponEquipped(Theah $theah): bool
    {
        foreach ($this->Attachments as $attachmentId)
        {
            $attachment = $theah->getCardById($attachmentId);
            if ($attachment instanceof Attachment && $attachment->hasTrait("Weapon"))
            {
                return true;
            }
        }
        
        return false;
    }

    public function hasEngardeWeaponEquipped(Theah $theah): bool
    {
        foreach ($this->Attachments as $attachmentId)
        {
            $attachment = $theah->getCardById($attachmentId);
            if ($attachment instanceof Attachment && $attachment->hasTrait("Weapon") && !$attachment->Engaged)
            {
                return true;
            }
        }

        return false;
    }

    public function getPropertyArray(Game $game): array
    {
        $properties = parent::getPropertyArray($game);

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

        $properties['attachments'] = array_values($this->Attachments);

        $properties['type'] = 'Character';

        return $properties;
    }

}