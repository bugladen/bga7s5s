<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\cards;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\reactions\CardReaction;
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

    public function getParleyDiscount(Character $performer, bool $parleying) : int
    {
        $discount = parent::getParleyDiscount($performer, $parleying);
        if ($performer->Id == $this->Id && $parleying)
        {
            $discount += $this->ModifiedInfluence;
        }

        return $discount;
    }

    public function getEquipDiscount(Theah $theah, Character $performer, Attachment $attachment) : int
    {
        $discount = parent::getEquipDiscount($theah, $performer, $attachment);
        
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

    public function getCombatPressureValue(): int
    {
        return $this->ModifiedCombat;
    }

    public function getFinessePressureValue(): int
    {
        return $this->ModifiedFinesse;
    }

    public function getInfluencePressureValue(): int
    {
        return $this->ModifiedInfluence;
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
            $this->ModifiedResolve = $this->getModifiedResolve($event->theah);
            $this->IsUpdated = true;

            $event->theah->game->notifyAllPlayers("characterWounded", clienttranslate('${target_inject_code} has received ${wounds} wound(s) due to: ${reason} 
            <p>New Resolve: ${resolve}'), [
                'i18n' => ['reason'],
                "target_inject_code" => $this->getInjectCode(),
                "characterId" => $this->Id,
                "wounds" => $event->wounds,
                "reason" => $event->reason,
                'resolve' => $this->ModifiedResolve
            ]);

            if ($this->ModifiedResolve <= 0)
            {
                $this->IsDying = true;
                
                //Unequip all attachments
                foreach ($this->Attachments as $attachmentId)
                {
                    $attachment = $event->theah->getCardById($attachmentId);

                    $unattached = EventFactory::createAttachmentUnequippedEvent($this->ControllerId, $this->Id, $attachment->Id);
                    $event->theah->queueEvent($unattached);

                    if ($attachment instanceof CityAttachment)
                        $discardEvent = EventFactory::createCardAddedToCityDiscardPileEvent($this->ControllerId, $attachment->Id, $attachment->Location);
                    else
                        $discardEvent = EventFactory::createCardDiscardedFromPlayEvent($attachment->OwnerId, $attachment->Id, $attachment->Location);

                    $event->theah->queueEvent($discardEvent);
                }

                //Send this to the locker
                $destroyEvent = EventFactory::createCharacterDestroyedEvent($this->ControllerId, $this->Id, $event->reason);
                $event->theah->queueEvent($destroyEvent);
            }
        }

        if ($event instanceof EventCharacterHealed && $event->characterId == $this->Id)
        {
            $this->ModifiedResolve += $event->wounds;
            $modifiedResolve = $this->getModifiedResolve($event->theah);
            if ($this->ModifiedResolve > $modifiedResolve) 
            {
                $this->ModifiedResolve = $modifiedResolve;
            }
            
            $this->Wounds -= $event->wounds;
            $actualHealed = $event->wounds;
            if ($this->Wounds < 0) 
            {
                $actualHealed = $event->wounds - abs($this->Wounds);
                $this->Wounds = 0;
            }

            $this->IsUpdated = true;

            $event->theah->game->notifyAllPlayers("characterHealed", clienttranslate('${target_inject_code} has healed ${wounds} wound(s) due to: ${reason} 
            <p>New Resolve: ${resolve}'), [
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

    public function getModifiedResolve(Theah $theah): int
    {
        $resolve = $this->Resolve - $this->Wounds;
        foreach ($this->Attachments as $attachmentId)
        {
            $attachment = $theah->getAttachmentById($attachmentId);
            $resolve += $attachment->ResolveModifier;
        }

        return $resolve;
    }
}