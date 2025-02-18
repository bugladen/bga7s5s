<?php

namespace Bga\Games\SeventhSeaCityOfFiveSails\theah;

use Bga\Games\SeventhSeaCityOfFiveSails\Game;
use Bga\Games\SeventhSeaCityOfFiveSails\cards\Character;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventApproachCharacterPlayed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventAttachmentEquipped;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardAddedToCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDrawn;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardMoved;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromCityDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardDiscardedFromHand;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardEngaged;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCardRemovedFromPlayerDiscardPile;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventChallengeIssued;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterIntervened;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCharacterRecruited;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventCityCardAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateCombatCardStats;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateManeuverValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelCalculateTechniqueValues;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventDuelPlayerGambled;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventGenerateChallengeThreat;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventLocationClaimed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventManeuverActivated;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventPlayerLosesReknown;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownAddedToLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventReknownRemovedFromLocation;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveManeuver;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventResolveTechnique;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeCardRevealed;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventSchemeMovedToCity;
use Bga\Games\SeventhSeaCityOfFiveSails\theah\events\EventTechniqueActivated;

trait EventHub
{
    public function handleEvent($event)
    {
        switch (true) {

            case $event instanceof EventApproachCharacterPlayed:
                $this->cards[$event->character->Id] = $event->character;

                $event->character->Location = $event->location;
                $event->character->IsUpdated = true;

                // Notify players of selected character
                $this->game->notifyAllPlayers("approachCharacterPlayed", clienttranslate('${player_name} plays ${character_name} as their Approach Character.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "character_name" => "<span style='font-weight:bold'>{$event->character->Name}</span>",
                    "character" => $event->character->getPropertyArray(),
                ]);
                break;

            case $event instanceof EventAttachmentEquipped:
                $performer = $this->cards[$event->performer->Id];
                $attachment = $this->cards[$event->attachment->Id];

                $performer->addAttachment($attachment);
                $performer->IsUpdated = true;

                $attachment->ControllerId = $event->playerId;
                $attachment->AttachedToId = $performer->Id;
                $attachment->Location = $performer->Location;
                $attachment->IsUpdated = true;
                
                // Notify players of recruited character
                $this->game->notifyAllPlayers("attachmentEquipped", clienttranslate('${player_name} equipped ${attachment_name} to ${performer_name} at a discount of ${discount} for a cost of ${cost} Wealth.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "attachment_name" => "<span style='font-weight:bold'>{$attachment->Name}</span>",
                    "performer_name" => "<span style='font-weight:bold'>{$performer->Name}</span>",
                    "attachment" => $attachment->getPropertyArray(),
                    "performerId" => $performer->Id,
                    "discount" => $event->discount,
                    "cost" => $event->cost,
                ]);

                break;


            case $event instanceof EventCardDrawn:
                $event->card->Location = Game::LOCATION_HAND;
                $event->card->IsUpdated = true;

                $this->game->notifyPlayer($event->playerId, "drawCard", 'You drew ${card_name} because of ${reason}.', [
                    "card_name" => "<span style='font-weight:bold'>{$event->card->Name}</span>",
                    "card" => $event->card->getPropertyArray(),
                    "reason" => $event->reason,
                ]);

                // Notify players that card has been added to hand
                $this->game->notifyAllPlayers("drawCardMessage", clienttranslate('${player_name} drew a card into their hand because of ${reason}.'), [
                    "playerId" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "reason" => $event->reason,
                ]);
                break;
                
            case $event instanceof EventCardAddedToHand:
                $event->card->Location = Game::LOCATION_HAND;
                $event->card->IsUpdated = true;

                // Notify players that card has been added to hand
                $this->game->notifyAllPlayers("cardAddedToHand", clienttranslate('${player_name} added ${card_name} into their hand.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "card_name" => "<span style='font-weight:bold'>{$event->card->Name}</span>",
                    "card" => $event->card->getPropertyArray(),
                ]);
                break;

            case $event instanceof EventCardAddedToCityDiscardPile:
                $this->game->notifyAllPlayers("cardAddedToCityDiscardPile", clienttranslate('${card_name} added to City Discard pile from ${location}.'), [
                    "card_name" => $event->card->Name,
                    "cardId" => $event->card->Id,
                    "location" => $event->fromLocation,
                ]);
                break;

            case $event instanceof EventCardDiscardedFromHand:
                $this->game->notifyAllPlayers('cardDiscardedFromHand',
                    clienttranslate('${player_name} discarded ${card_name}.'), [
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "card_name" => "<strong>{$event->card->Name}</strong>",
                    "playerId" => $event->playerId,
                    "card" => $event->card->getPropertyArray(),
                ]);
                break;

            case $event instanceof EventCardEngaged:
                $card = $this->cards[$event->card->Id];
                $card->Engaged = true;
                $card->IsUpdated = true;

                $this->game->notifyAllPlayers("cardEngaged", clienttranslate('${player_name} Engages ${card_name}.'), [
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "card_name" => "<strong>{$event->card->Name}</strong>",
                    "cardId" => $event->card->Id,
                ]);
                break;                

            case $event instanceof EventCardMoved:
                $card = $this->cards[$event->card->Id];
                $card->Location = $event->toLocation;
                if ($card instanceof Character) {
                    $card->Engaged = $event->Engage;

                    foreach ($card->Attachments as $attachmentId) {
                        $attachment = $this->cards[$attachmentId];
                        $attachment->Location = $event->toLocation;
                        $attachment->IsUpdated = true;
                    }
                }
                $card->IsUpdated = true;

                $this->game->notifyAllPlayers("cardMoved", clienttranslate('${card_name} moved from ${fromLocation} to ${toLocation}.'), [
                    "card_name" => "<strong>{$event->card->Name}</strong>",
                    "cardId" => $event->card->Id,
                    "fromLocation" => $event->fromLocation,
                    "toLocation" => $event->toLocation,
                    "engage" => $event->Engage,
                ]);
                break;

            case $event instanceof EventCardRemovedFromCityDiscardPile:
                $this->game->notifyAllPlayers("cardRemovedFromCityDiscardPile", clienttranslate('${card_name} removed from City Discard pile.'), [
                    "card_name" => $event->card->Name,
                    "card" => $event->card->getPropertyArray(),
                ]);    
                break;
    
            case $event instanceof EventCardRemovedFromPlayerDiscardPile:
                $this->game->notifyAllPlayers("cardRemovedFromPlayerDiscardPile", clienttranslate('${card_name} removed from ${player_name}\'s discard pile.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "card_name" => $event->card->Name,
                    "card" => $event->card->getPropertyArray(),
                ]);
                break;

            case $event instanceof EventCharacterRecruited:
                $character = $this->cards[$event->character->Id];
                $character->ControllerId = $event->playerId;
                $character->IsUpdated = true;

                // Notify players of recruited character
                $this->game->notifyAllPlayers("characterRecruited", clienttranslate('${player_name} recruits ${character_name} at a discount of ${discount} for a cost of ${cost} Wealth.'), [
                    "player_id" => $event->playerId,
                    "player_name" => $this->game->getPlayerNameById($event->playerId),
                    "character_name" => "<span style='font-weight:bold'>{$event->character->Name}</span>",
                    "characterId" => $event->character->Id,
                    "discount" => $event->discount,
                    "cost" => $event->cost,
                ]);
                break;

            case $event instanceof EventCityCardAddedToLocation:
                // Add the card to the world
                $this->cards[$event->card->Id] = $event->card;

                $event->card->Location = $event->location;
                $event->card->IsUpdated = true;
                
                // Notify players that card has been played
                $this->game->notifyAllPlayers("cityCardAddedToLocation", clienttranslate('${card_name} added to ${location} from the city deck'), [
                    "card_name" => "<span style='font-weight:bold'>{$event->card->Name}</span>",
                    "location" => $event->location,
                    "card" => $event->card->getPropertyArray()
                ]);
                break;

            case $event instanceof EventLocationClaimed:
                {
                    $this->cityLocations[$event->location]->Controller = $event->playerId;

                    $this->game->notifyAllPlayers("locationClaimed", clienttranslate('${player_name} chose ${card_name} to Claim ${location_name}. Influence Totals: ${totals}'), [
                        "player_name" => $this->game->getPlayerNameById($event->playerId),
                        "card_name" => "<strong>{$event->performer->Name}</strong>",
                        "location_name" => "<strong>{$event->performer->Location}</strong>",
                        "totals" => $event->totalsExplanation,
                        "playerId" => $event->playerId,
                        "location" => $event->performer->Location,
                    ]);
            
                    break;
                }
    
            case $event instanceof EventPlayerLosesReknown:
                $playerId = $event->playerId;
                $reknown = $this->db->getPlayerReknown($playerId);
                if ($reknown > 0) 
                {
                    $reknown -= $event->amount;
                    $this->db->setPlayerReknown($playerId, $reknown);

                    // Notify players that the player has lost reknown
                    $this->game->notifyAllPlayers("playerReknownUpdated", clienttranslate('${player_name} loses ${amount} reknown.'), [
                        "player_id" => $event->playerId,
                        "player_name" => $this->game->getPlayerNameById($playerId),
                        "amount" => $event->amount,
                    ]);
                }   

                break;

            case $event instanceof EventReknownAddedToLocation:

                //Update the reknown for the location in the database
                $reknown = $this->game->getReknownForLocation($event->location) + $event->amount;
                $this->game->setReknownForLocation($event->location, $reknown);

                $this->cityLocations[$event->location]->Reknown += $event->amount;

                // Notify players that the player has lost reknown
                $this->game->notifyAllPlayers("reknownAddedToLocation", clienttranslate('${amount} reknown ADDED to ${location} ${source}.'), [
                    "location" => $event->location,
                    "amount" => $event->amount,
                    "source" => empty($event->source) ? "" : "from {$event->source}",
                ]);

                break;

            case $event instanceof EventReknownRemovedFromLocation:

                //Update the reknown for the location in the database
                $reknown = $this->game->getReknownForLocation($event->location) - $event->amount;
                $this->game->setReknownForLocation($event->location, $reknown);

                $this->cityLocations[$event->location]->Reknown -= $event->amount;

                // Notify players that the player has lost reknown
                $this->game->notifyAllPlayers("reknownRemovedFromLocation", clienttranslate('${amount} reknown REMOVED from ${location} ${source}.'), [
                    "location" => $event->location,
                    "amount" => $event->amount,
                    "source" => empty($event->source) ? "" : "from {$event->source}",
                ]);

                break;

            case $event instanceof EventSchemeCardRevealed:
                $this->cards[$event->scheme->Id] = $event->scheme;

                $event->scheme->Location = $event->location;
                $event->scheme->IsUpdated = true;

                // Notify players of selected scheme
                $this->game->notifyAllPlayers("approachSchemePlayed", clienttranslate('${player_name} plays ${scheme_name} as their Approach Scheme.'), [
                    "player_name" => $event->playerName,
                    "scheme_name" => "<span style='font-weight:bold'>{$event->scheme->Name}</span>",
                    "player_id" => $event->playerId,
                    "scheme" => $event->scheme->getPropertyArray(),
                ]);

                break;

        case $event instanceof EventSchemeMovedToCity:
            $event->scheme->Location = $event->location;
            $event->scheme->IsUpdated = true;
            
            //Card is now in city
            $this->cards[$event->scheme->Id] = $event->scheme;
            break;

            case $event instanceof EventChallengeIssued:
                $handler = function ($theah, EventChallengeIssued $event)
                {
                    $challenger = $this->cards[$event->challenger->Id];
                    $challenger->addCondition(GAME::DUEL_CHALLENGER);
                    $challenger->IsUpdated = true;
                    
                    $defender = $this->cards[$event->defender->Id];
                    $defender->addCondition(GAME::DUEL_DEFENDER);
                    $defender->IsUpdated = true;
                    
                    $message = '${player_name} has chosen to have ${challenger_name} Challenge ${defender_name}. ';
                    if ($event->activatedTechnique) $message .= '${player_name} will activate Technique ${technique_name}. for the Challenge.';
                    
                    $theah->game->notifyAllPlayers("challengeIssued", clienttranslate($message), [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "challenger_name" => "<strong>{$event->challenger->Name}</strong>",
                        "defender_name" => "<strong>{$event->defender->Name}</strong>",
                        "technique_name" => "<strong>{$event->activatedTechnique?->Name}</strong>",
                        "challengerId" => $event->challenger->Id,
                        "defenderId" => $event->defender->Id,
                    ]);
                };
                $handler($this, $event);
                break;
                    
            case $event instanceof EventCharacterIntervened:
                $handler = function ($theah, EventCharacterIntervened $event)
                {
                    $this->game->notifyAllPlayers("message", clienttranslate('${player_name} has chosen to have ${intervener_name} INTERVENE in the Challenge in place of ${target_name}.'), [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "intervener_name" => "<strong>{$event->newTarget->Name}</strong>",
                        "target_name" => "<strong>{$event->oldTarget->Name}</strong>",
                    ]);
                };
                $handler($this, $event);
                break;
                        
            case $event instanceof EventGenerateChallengeThreat:
                $handler = function ($theah, EventGenerateChallengeThreat $event)
                {
                    foreach ($event->explanations as $explanation) {
                        $theah->game->notifyAllPlayers("message", clienttranslate($explanation));
                    }
                    
                    $theah->game->globals->set(Game::CHALLENGE_THREAT, $event->threat);
                    $actor = $theah->cards[$event->actorId];
                    
                    $theah->game->notifyAllPlayers("message", clienttranslate('${player_name} has generated ${threat} total Threat for the Challenge.'), [
                        "player_name" => $theah->game->getPlayerNameById($actor->ControllerId),
                        "threat" => $event->threat,
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventTechniqueActivated:
                $handler = function ($theah, EventTechniqueActivated $event)
                {
                    $technique = $theah->getTechniqueById($event->techniqueId);
                    $technique->setActive($theah, true);
                    $technique->setUsed($theah, true);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventResolveTechnique:
                $handler = function ($theah, EventResolveTechnique $event)
                {
                    $technique = $theah->getTechniqueById($event->techniqueId);
                    if ($technique->Active && $event->inDuel)
                    {
                        $duelId = $theah->game->globals->get(Game::DUEL_ID);
                        $round = $theah->game->globals->get(Game::DUEL_ROUND);
                        $name = substr(addslashes($technique->Name), 0, 500);
                        $sql = "UPDATE duel_round SET technique_id = '{$event->techniqueId}', technique_name ='$name' WHERE duel_id = $duelId AND round = $round";
                        $event->theah->game->DbQuery($sql);    
                    }
                };
                $handler($this, $event);
                break;
                
            case $event instanceof EventDuelCalculateTechniqueValues:
                $handler = function ($theah, EventDuelCalculateTechniqueValues $event)
                {
                    $duelId = $theah->game->globals->get(Game::DUEL_ID);
                    $round = $theah->game->globals->get(Game::DUEL_ROUND);
                    $actor = $theah->cards[$event->actorId];
                    $technique = $theah->getTechniqueById($event->techniqueId);

                    foreach ($event->explanations as $explanation) {
                        $theah->game->notifyAllPlayers("message", clienttranslate($explanation));
                    }

                    $results = $theah->getDBObject()->updateRoundWithCombatStats($duelId, $round, "technique", $event->riposte, $event->parry, $event->thrust);
                    $effects = "";
                    if ($results["parry"] > 0) $effects .= "<p>Parry +{$results["parry"]}";
                    if ($results["riposte"] > 0) $effects .= "<p>Riposte +{$results["riposte"]}";
                    if ($results["thrust"] > 0) $effects .= "<p>Thrust +{$results["thrust"]}";
                    if ($results["endingChallengerThreatBefore"] != $results["endingChallengerThreatAfter"])
                        $effects .= "<p>Challenger Threat went from {$results["endingChallengerThreatBefore"]} to {$results["endingChallengerThreatAfter"]}. ";
                    if ($results["endingDefenderThreatBefore"] != $results["endingDefenderThreatAfter"])
                        $effects .= "<p>Defender Threat went from {$results["endingDefenderThreatBefore"]} to {$results["endingDefenderThreatAfter"]}. ";
                    $theah->game->notifyAllPlayers("updateRoundWithCombatStats", clienttranslate('${character_name} has activated the Technique ${strong_effect_name} 
                    with the following effects: ${effects}'), [
                        "round" => $round,
                        "mode" => "technique",
                        "character_name" => $actor->Name,
                        "strong_effect_name" => "<strong>{$technique->Name}</strong>",
                        "effect_name" => $technique->Name,
                        "effects" => $effects,
                        "riposte" => $results["riposte"],
                        "parry" => $results["parry"],
                        "thrust" => $results["thrust"],
                        "endingChallengerThreatBefore"  => $results["endingChallengerThreatBefore"],
                        "endingDefenderThreatBefore"  => $results["endingDefenderThreatBefore"],
                        "endingChallengerThreatAfter"  => $results["endingChallengerThreatAfter"],
                        "endingDefenderThreatAfter"  => $results["endingDefenderThreatAfter"],
                    ]);                    
                };    
                $handler($this, $event);
                break;

            case $event instanceof EventManeuverActivated:
                $handler = function (Theah $theah, EventManeuverActivated $event)
                {
                    $maneuver = $theah->getManeuverById($event->maneuverId);
                    $maneuver->setActive($theah, true);
                    $maneuver->setUsed($theah, true);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventResolveManeuver:
                $handler = function (Theah $theah, EventResolveManeuver $event)
                {
                    $maneuver = $theah->getManeuverById($event->maneuverId);
                    if ($maneuver->Active)
                    {
                        $duelId = $theah->game->globals->get(Game::DUEL_ID);
                        $round = $theah->game->globals->get(Game::DUEL_ROUND);
                        $name = substr(addslashes($maneuver->Name), 0, 500);
                        $sql = "UPDATE duel_round SET maneuver_id = '{$event->maneuverId}', maneuver_name = '$name' WHERE duel_id = $duelId AND round = $round";
                        $event->theah->game->DbQuery($sql);    
                    }
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuelCalculateManeuverValues:
                $handler = function ($theah, EventDuelCalculateManeuverValues $event)
                {
                    $duelId = $theah->game->globals->get(Game::DUEL_ID);
                    $round = $theah->game->globals->get(Game::DUEL_ROUND);
                    $actor = $theah->cards[$event->actorId];
                    $maneuver = $theah->getManeuverById($event->maneuverId);

                    foreach ($event->explanations as $explanation) {
                        $theah->game->notifyAllPlayers("message", clienttranslate($explanation));
                    }

                    $results = $theah->getDBObject()->updateRoundWithCombatStats($duelId, $round, "maneuver", $event->riposte, $event->parry, $event->thrust);
                    $effects = "";
                    if ($results["parry"] > 0) $effects .= "<p>Parry +{$results["parry"]}";
                    if ($results["riposte"] > 0) $effects .= "<p>Riposte +{$results["riposte"]}";
                    if ($results["thrust"] > 0) $effects .= "<p>Thrust +{$results["thrust"]}";
                    if ($results["endingChallengerThreatBefore"] != $results["endingChallengerThreatAfter"])
                        $effects .= "<p>Challenger Threat went from {$results["endingChallengerThreatBefore"]} to {$results["endingChallengerThreatAfter"]}. ";
                    if ($results["endingDefenderThreatBefore"] != $results["endingDefenderThreatAfter"])
                        $effects .= "<p>Defender Threat went from {$results["endingDefenderThreatBefore"]} to {$results["endingDefenderThreatAfter"]}. ";
                    $theah->game->notifyAllPlayers("updateRoundWithCombatStats", clienttranslate('${character_name} has activated the Maneuver ${strong_effect_name} 
                    with the following effects: ${effects}'), [
                        "round" => $round,
                        "mode" => "maneuver",
                        "character_name" => $actor->Name,
                        "strong_effect_name" => "<strong>{$maneuver->Name}</strong>",
                        "effect_name" => $maneuver->Name,
                        "effects" => $effects,
                        "riposte" => $results["riposte"],
                        "parry" => $results["parry"],
                        "thrust" => $results["thrust"],
                        "endingChallengerThreatBefore"  => $results["endingChallengerThreatBefore"],
                        "endingDefenderThreatBefore"  => $results["endingDefenderThreatBefore"],
                        "endingChallengerThreatAfter"  => $results["endingChallengerThreatAfter"],
                        "endingDefenderThreatAfter"  => $results["endingDefenderThreatAfter"],
                    ]);                    
                };    
                $handler($this, $event);
                break;    

            case $event instanceof EventDuelCalculateCombatCardStats:
                $handler = function ($theah, EventDuelCalculateCombatCardStats $event)
                {
                    $duelId = $theah->game->globals->get(Game::DUEL_ID);
                    $round = $theah->game->globals->get(Game::DUEL_ROUND);
                    $card = $theah->cards[$event->combatCardId];
                    $playerId = $card->ControllerId;
                    $playerName = $theah->game->getPlayerNameById($playerId);

                    foreach ($event->explanations as $explanation) {
                        $theah->game->notifyAllPlayers("message", clienttranslate($explanation));
                    }

                    $results = $theah->getDBObject()->updateRoundWithCombatStats($duelId, $round, "combat", $event->riposte, $event->parry, $event->thrust);
                    $effects = "<p>Parry +{$results["parry"]}";
                    $effects .= "<p>Riposte +{$results["riposte"]}";
                    $effects .= "<p>Thrust +{$results["thrust"]}";
                    if ($results["endingChallengerThreatBefore"] != $results["endingChallengerThreatAfter"])
                        $effects .= "<p>Challenger Threat went from {$results["endingChallengerThreatBefore"]} to {$results["endingChallengerThreatAfter"]}. ";
                    if ($results["endingDefenderThreatBefore"] != $results["endingDefenderThreatAfter"])
                        $effects .= "<p>Defender Threat went from {$results["endingDefenderThreatBefore"]} to {$results["endingDefenderThreatAfter"]}. ";
                    $theah->game->notifyAllPlayers("updateRoundWithCombatStats", clienttranslate('${player_name} has played ${effect_name} as their Combat Card 
                    with the following effects: ${effects}'), [
                        "round" => $round,
                        "mode" => "combat",
                        "player_name" => $playerName,
                        "playerId" => $playerId,
                        "effect_name" => "<strong>{$card->Name}</strong>",
                        "combatCardId" => $card->Id,
                        "effects" => $effects,
                        "riposte" => $results["riposte"],
                        "parry" => $results["parry"],
                        "thrust" => $results["thrust"],
                        "endingChallengerThreatBefore"  => $results["endingChallengerThreatBefore"],
                        "endingDefenderThreatBefore"  => $results["endingDefenderThreatBefore"],
                        "endingChallengerThreatAfter"  => $results["endingChallengerThreatAfter"],
                        "endingDefenderThreatAfter"  => $results["endingDefenderThreatAfter"],
                    ]);
                };
                $handler($this, $event);
                break;

            case $event instanceof EventDuelPlayerGambled:
                $handler = function($theah, EventDuelPlayerGambled $event) {
                    $card = $theah->game->getCardObjectFromDb($event->chosenCardId);
                    $theah->cards[$event->chosenCardId] = $card;
                    $theah->game->notifyAllPlayers("message", clienttranslate('${player_name} has gambled with ${card_name}.'), [
                        "player_name" => $theah->game->getPlayerNameById($event->playerId),
                        "card_name" => "<strong>{$card->Name}</strong>",
                    ]);
                };
                $handler($this, $event);
                break;
        }
    }
}