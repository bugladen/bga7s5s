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
    }

    public function resetCard()
    {
        parent::resetCard();
        $this->ModifiedResolve = $this->Resolve;
        $this->ModifiedCombat = $this->Combat;
        $this->ModifiedFinesse = $this->Finesse;
        $this->ModifiedInfluence = $this->Influence;
    }

    public function canChallenge(): bool
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

            $event->theah->game->notifyAllPlayers("characterWounded", clienttranslate('${target_inject_code} has received ${wounds} wound(s) due to: ${reason}'), [
                'i18n' => ['reason'],
                "target_inject_code" => $this->getInjectCode(),
                "characterId" => $this->Id,
                "wounds" => $event->wounds,
                "reason" => $event->reason,
                'resolve' => $this->ModifiedResolve
            ]);

            if ($this->Wounds >= $this->ModifiedResolve)
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

            $this->IsUpdated = true;

            $event->theah->game->notifyAllPlayers("characterHealed", clienttranslate('${target_inject_code} has healed ${wounds} wound(s) due to: ${reason}'), [
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
            $this->IsUpdated = true;
        }
    }

    public function unEquipAllAttachments(Theah $theah)
    {
        foreach ($this->Attachments as $attachmentId)
        {
            $attachment = $theah->getCardById($attachmentId);

            $unattached = EventFactory::createAttachmentUnequippedEvent($this->ControllerId, $this->Id, $attachment->Id);
            $theah->queueEvent($unattached);

            if ($attachment instanceof CityAttachment)
                $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($this->ControllerId, $attachment->Id, $attachment->Location, $this->Id, $asEffect = true);
            else
                $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location, $this->Id, $asEffect = true);

            $theah->queueEvent($discardEvent);
        }
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

        $properties['attachments'] = $this->Attachments;

        $properties['type'] = 'Character';

        return $properties;
    }

}