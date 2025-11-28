<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails;

use Bga\Games\SeventhSeaCityOfFiveSails\cards\Attachment;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Leader;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Risk;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Scheme;

class DeckValidator
{
    public static function validate(Game $game, mixed $deck, &$errors): bool
    {
        if (! isset($deck->name))
        {
            $errors[] = clienttranslate('The deck has no name.');
        }

        if (! isset($deck->faction))
        {
            $errors[] = clienttranslate('The deck has no faction.');
            return false;
        }

        $validFactions = [ "Castille", "Eisen", "Montaigne", "Ussura", "Vodacce", "Neutral" ];
        if (! in_array($deck->faction, $validFactions))
        {
            $errors[] = sprintf(clienttranslate('The deck has an invalid faction: %s.'), $deck->faction);
            return false;
        }

        $faction = $deck->faction;
        $uniquelyNamedCards = [];

        self::validateLeader($game, $deck, $faction, $uniquelyNamedCards, $errors);

        self::validateApproachDeck($game, $deck, $faction, $uniquelyNamedCards, $errors);

        self::validateFactionDeck($game, $deck, $faction, $errors);
        
        return count($errors) == 0;
    }

    private static function validateLeader(Game $game, mixed $deck, string $faction, array &$uniquelyNamedCards, &$errors): bool
    {
        if (! isset($deck->leader))
        {
            $errors[] = clienttranslate('The deck has no leader.');
            return false;
        }

        // Check if the class exists before attempting to instantiate it
        // This prevents fatal errors that cannot be caught by try-catch
        $className = $game->getCardClassName($deck->leader);
        
        if (! class_exists($className))
        {
            $errors[] = sprintf(clienttranslate('The leader card %s is not available.'), $deck->leader);
            return false;
        }

        $card = $game->instantiateCard($deck->leader);
        if (! $card instanceof Leader)
        {
            $errors[] = sprintf(clienttranslate('The leader card %s is not of type Leader.'), $deck->leader);
            return false;
        }

        if (!$card->hasFaction($faction) && !$card->hasFaction("Neutral"))
        {
            $errors[] = sprintf(clienttranslate('The leader card %s does not belong to the %s faction and is not Neutral.'), $deck->leader, $faction);
            return false;
        }

        $uniquelyNamedCards[] = $card->Name;  

        return true;
    }

    private static function validateApproachDeck(Game $game, mixed $deck, string $faction, array &$uniquelyNamedCards, &$errors): bool
    {
        if (! isset($deck->approach_deck))
        {
            $errors[] = clienttranslate('The deck has no approach deck.');
            return false;
        }

        foreach ($deck->approach_deck as $approachCard)
        {
            $className = $game->getCardClassName($approachCard);
            if (! class_exists($className))
            {
                $errors[] = sprintf(clienttranslate('The approach card %s is not available.'), $approachCard);
                continue;
            }

            $card = $game->instantiateCard($approachCard);
            if (! $card instanceof Scheme && ! $card instanceof Character)
            {
                $errors[] = sprintf(clienttranslate('The approach card %s is not of type Scheme or Character.'), $approachCard);
            }

            if ($card->hasTrait("Brute"))
            {
                $errors[] = sprintf(clienttranslate('The approach card %s has the Brute trait, which is not allowed in the Approach deck.'), $approachCard);
            }

            if (!$card->hasFaction($faction) && !$card->hasFaction("Neutral"))
            {
                $errors[] = sprintf(clienttranslate('The approach card %s does not belong to the %s faction and is not Neutral.'), $approachCard, $faction);
            }

            if (in_array($card->Name, $uniquelyNamedCards))
            {
                $errors[] = sprintf(clienttranslate('There is more than one card with the name "%s" in the Approach deck.'), $card->Name);
            }

            $uniquelyNamedCards[] = $card->Name;
        }

        return count($errors) == 0;
    }

    private static function validateFactionDeck(Game $game, mixed $deck, string $faction, &$errors): bool
    {
        if (! isset($deck->faction_deck))
        {
            $errors[] = clienttranslate('The deck has no faction deck.');
            return false;
        }

        $uniqueCardIds = [];

        foreach ($deck->faction_deck as $factionCard)
        {
            $cardId = $factionCard->id;
            $cardCount = $factionCard->count;

            $className = $game->getCardClassName($factionCard->id);
            if (! class_exists($className))
            {
                $errors[] = sprintf(clienttranslate('The faction card %s is not available.'), $factionCard->id);
                continue;
            }

            $card = $game->instantiateCard($factionCard->id);
            if (! $card instanceof Character && ! $card instanceof Risk && ! $card instanceof Attachment)
            {
                $errors[] = sprintf(clienttranslate('The faction card %s - "%s" is not of type Character, Risk or Attachment.'), $factionCard->id, $card->Name);
            }

            if (in_array($cardId, $uniqueCardIds))
            {
                $errors[] = sprintf(clienttranslate('There is more than one line item entry for card %s - "%s" in the Faction deck.'), $factionCard->id, $card->Name);
            }

            if (!$card->hasFaction($faction) && !$card->hasFaction("Neutral"))
            {
                $errors[] = sprintf(clienttranslate('The faction card %s - "%s" does not belong to the %s faction and is not Neutral.'), $factionCard->id, $card->Name, $faction);
            }

            if ($card instanceof Character && ! $card->hasTrait("Brute"))
            {
                $errors[] = sprintf(clienttranslate('The faction card %s - "%s" is a Character and must have the Brute trait to be included in the Faction deck.'), $factionCard->id, $card->Name);
            }   

            if ($cardCount > 2)
            {
                $errors[] = sprintf(clienttranslate('The faction card %s - "%s" can only be included in the Faction deck up to 2 times.'), $factionCard->id, $card->Name);
            }

            if ($card->hasTrait("Unique") && $cardCount > 1)
            {
                $errors[] = sprintf(clienttranslate('The faction card %s - "%s" is a Unique card and can only be included in the Faction deck once.'), $factionCard->id, $card->Name);
            }

            $uniqueCardIds[] = $cardId;
        }

        return count($errors) == 0;
    }
}