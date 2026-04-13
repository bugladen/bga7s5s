/**
 *------
 * BGA framework: Gregory Isabelli & Emmanuel Colin & BoardGameArena
 * SeventhSeaCityOfFiveSails implementation : © Edward Mittelstedt bugbucket@comcast.net
 *
 * This code has been produced on the BGA studio platform for use on http://boardgamearena.com.
 * See http://en.boardgamearena.com/#!doc/Studio for more information.
 * -----
 */

 define(['dojo', 'dojo/_base/declare'], (dojo, declare) => {
return declare('seventhseacityoffivesails.notifications', null, {

    setupNotifications: function()
    {
        debug( 'notifications subscriptions setup' );
        
        const notifs = [
            ['schemeMovedToCity', 500],
            ['actionUsed', 1],
            ['approachCardsReceived', 1000],
            ['approachCharacterPlayed', 2000],
            ['approachSchemePlayed', 2000],
            ['attachmentEquipped', 1000],
            ['attachmentUnequipped', 1000],
            ['cardAddedToCityDeck', 500],
            ['cardAddedToCityDiscardPile', 500],
            ['cardAddedToHand', 2000],
            ['cardAddedToPlayerDiscardPile', 500],
            ['cardDiscardedFromHand', 500],
            ['cardRemovedFromHand', 500],
            ['cardDiscardedFromPlay', 500],
            ['cardRemovedFromPlay', 500],
            ['cardEngaged', 1000],
            ['cardEngarded', 1000],
            ['cardMoved', 1000],
            ['cardRemovedFromCityDiscardPile', 500],
            ['cardRemovedFromPlayerDiscardPile', 500],
            ['cardRemovedFromLocker', 500],
            ['catsEmbargoTargetChosen', 500],
            ['catsEmbargoTargetRemoved', 1],
            ['challengeIssued', 500],
            ['challengeRejected', 500],
            ['challengeCancelled', 500],
            ['challengerSwapped', 500],
            ['characterDestroyed', 1000],
            ['characterHealed', 1000],
            ['characterCombatModified', 1],
            ['characterFinesseModifed', 1],
            ['characterInfluenceModified', 1],
            ['characterIntervened', 500],
            ['cardMustered', 1000],
            ['characterRecruited', 1000],
            ['characterWounded', 1000],
            ['cityCardAddedToLocation', 1000],
            ['cityDiscardShuffled', 500],
            ['crystalEyeTargetChosen', 500],
            ['crystalEyeTargetRemoved', 500],
            ['defenderSwapped', 500],
            ['drawCard', 2000],
            ['drawCardMessage', 100],
            ['duelActorSwapped', 500],
            ['duelEnd', 500],
            ['duelStarted', 500],
            ['factionResolveCardDraw', 1000],
            ['factionResolveCardDrawPublic', 500],
            ['firstPlayer', 2000],
            ['locationClaimed', 500],
            ['parleyInterveneListUpdated', 1],
            ['sirensScreamUsedListUpdated', 1],
            ['catsEmbargoUpdated', 1],
            ['locationUncontrolled', 500],
            ['maryamBenuPleromaAbilityUsed', 500],
            ['maryamBenuPleromaAbilityRemoved', 500],
            ['carmellaAbilityUsed', 1],
            ['carmellaAbilityRemoved', 1],
            ['indomitableWillConditionStarted', 500],
            ['indomitableWillConditionEnded', 500],
            ['maneuverUsed', 1],
            ['newDay', 1000],
            ['newDuelRound', 500],
            ['panacheModified', 1000],
            ['playerDiscardShuffled', 500],
            ['playerReknownUpdated', 500],
            ['playLeader', 1500],
            ['reactionUsed', 1],
            ['reknownAddedToLocation', 500],
            ['reknownRemovedFromLocation', 500],
            ['reknownUpdatedOnCard', 500],
            ['cardSentToLocker', 500],
            ['actionAdded', 1],
            ['actionRemoved', 1],
            ['techniqueAdded', 1],
            ['techniqueRemoved', 1],
            ['techniqueUsed', 1],
            ['traitAdded', 1],
            ['traitRemoved', 1],
            ['updateRoundThreats', 500],
            ['updateRoundWithCombatStats', 500],
            ['yevgeniAdversaryChosen', 500],
            ['yevgeniAdversaryRemoved', 1],
        ];

        notifs.forEach((notif) => {
            dojo.subscribe(notif[0], this, `notif_${notif[0]}`);
            if (this.animationManager.animationsActive()) 
                this.notifqueue.setSynchronous(notif[0], notif[1]);
            else
                this.notifqueue.setSynchronous(notif[0], 1);
        });

        this.notifqueue.setIgnoreNotificationCheck( 'drawCardMessage', (notif) => (notif.args.playerId == this.player_id) );
        this.notifqueue.setIgnoreNotificationCheck( 'crystalEyeTargetMessage', (notif) => (this.player_id == notif.args.targetplayerId || this.player_id == notif.args.choosingPlayerId) );
    },  

    notif_playLeader: async function( notif )
    {
        debug( 'notif_playLeader' );
        debug( notif );

        const args = notif.args;

        this.gamedatas.players[args.player_id].leader = args.leader;

        this.createHome(
            args.player_id, 
            args.player_color, 
            args.leader
        );
        $(`${args.player_id}-home-anchor`).setAttribute('data-location', this.LOCATION_PLAYER_HOME);                

        const target = this.getTargetElementForLocation(this.LOCATION_PLAYER_HOME, args.player_id);
        const cardId = this.createCardId(args.leader, this.LOCATION_PLAYER_HOME);
        this.createCard(cardId, args.leader, target);

        // Animate the card growing from nothing to full size with a pulse
        const cardImage = $(`${cardId}_image`);
        if (cardImage && this.animationManager && this.animationManager.animationsActive()) {
            cardImage.style.transition = 'none';
            await cardImage.animate([
                { transform: 'scale(0)', opacity: 0 },
                { transform: 'scale(1.1)', opacity: 1 },
                { transform: 'scale(1)', opacity: 1 }
            ], {
                duration: 500,
                easing: 'ease-out'
            }).finished;
        }

        // Update the player panel
        //Fishing for what is currently called overall_player_board_${playerId}
        const element = this.bga.playerPanels.getElement(args.player_id).parentElement.parentElement.parentElement;
        element.classList.add(`_7sfs-home-${args.leader.faction.toLowerCase()}`);

        dojo.addClass( `${args.player_id}-score-seal`, `_7sfs-seal-score _7sfs-seal-${args.leader.faction.toLowerCase()}-score` );
        $(`${args.player_id}-score-crewcap`).innerHTML = args.leader.crewCap;
        $(`${args.player_id}-score-panache`).innerHTML = args.leader.panache;

        // Discard Pile
        dojo.style(`${args.player_id}-discard`, 'cursor', 'zoom-in');
        dojo.connect($(`${args.player_id}-discard`), 'onclick', this, 'onPlayerDiscardClicked');

        // Locker Pile
        dojo.style(`${args.player_id}-locker`, 'cursor', 'zoom-in');
        dojo.connect($(`${args.player_id}-locker`), 'onclick', this, 'onPlayerLockerClicked');

        var translated = dojo.string.substitute(
            _("${player_name} has selected <strong>${leader_name}</strong> as their leader"),
            {
                player_name: args.player_name,
                leader_name: args.leader.name
            }
        );
        $('pagemaintitletext').innerHTML = translated;
    },

    notif_actionUsed: function( notif )
    {
        debug( 'notif_actionUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.ownerId];
        if (card)
        {
            //Get the action from the card
            const action = card.actions.find(action => action.id === args.actionId);
            if (action)
            {
                action.available = ! args.used;
                this.createTooltipForCard(card);
            }       
        }
    },

    notif_maneuverUsed: function( notif )
    {
        debug( 'notif_maneuverUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.ownerId];
        if (card)
        {
            //Get the maneuver from the card
            const maneuver = card.maneuvers.find(maneuver => maneuver.id === args.maneuverId);
            if (maneuver)
            {
                maneuver.available = ! args.used;
                this.createTooltipForCard(card);
            }
        }
    },

    notif_reactionUsed: function( notif )
    {
        debug( 'notif_reactionUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.ownerId];
        if (card)
        {
            //Get the reaction from the card
            const reaction = card.reactions.find(reaction => reaction.id === args.reactionId);
            if (reaction)
            {
                reaction.available = ! args.used;
                this.createTooltipForCard(card);
            }
        }
    },

    notif_maneuverAdded: function( notif )
    {
        debug( 'notif_maneuverAdded' );
        debug( notif );
        
        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Add the maneuver to the card
            card.maneuvers.push(args.maneuver);
            this.createTooltipForCard(card);
        }
    },

    notif_maneuverRemoved: function( notif )
    {
        debug( 'notif_maneuverRemoved' );
        debug( notif );
        
        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Remove the maneuver from the card
            card.maneuvers = card.maneuvers.filter(maneuver => maneuver.id !== args.maneuverId);
            this.createTooltipForCard(card);
        }
    },

    notif_techniqueAdded: function( notif )
    {
        debug( 'notif_techniqueAdded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Add the technique to the card
            card.techniques.push(args.technique);
            this.createTooltipForCard(card);
        }
    },

    notif_techniqueRemoved: function( notif )
    {
        debug( 'notif_techniqueRemoved' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Remove the technique from the card
            card.techniques = card.techniques.filter(technique => technique.id !== args.techniqueId);
            this.createTooltipForCard(card);
        }
    },

    notif_actionAdded: function( notif )
    {
        debug( 'notif_actionAdded' );
        debug( notif );
        
        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Add the action to the card
            card.actions.push(args.action);
            this.createTooltipForCard(card);
        }
    },

    notif_actionRemoved: function( notif )
    {
        debug( 'notif_actionRemoved' );
        debug( notif );
        
        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Remove the action from the card
            card.actions = card.actions.filter(action => action.id !== args.actionId);
            this.createTooltipForCard(card);
        }
    },

    notif_techniqueUsed: function( notif )
    {
        debug( 'notif_techniqueUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.ownerId];
        if (card)
        {
            //Get the technique from the card
            const technique = card.techniques.find(technique => technique.id === args.techniqueId);
            if (technique)
            {
                technique.available = ! args.used;
                this.createTooltipForCard(card);
            }
        }
    },

    notif_traitAdded: function( notif )
    {
        debug( 'notif_traitAdded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Add the trait to the card
            card.traits.push(args.trait);
            this.createTooltipForCard(card);
        }
    },

    notif_traitRemoved: function( notif )
    {
        debug( 'notif_traitRemoved' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            //Remove the trait from the card
            card.traits = card.traits.filter(trait => trait !== args.trait);
            this.createTooltipForCard(card);
        }
    },

    notif_approachSchemePlayed: async function( notif )
    {
        debug( 'notif_approachSchemePlayed' );
        debug( notif );

        const args = notif.args;
        const cardId = `${args.player_id}-${args.scheme.id}`;
        const anchorId = `${args.player_id}-scheme-anchor`;
        const anchor = $(anchorId);
        
        if (anchor && this.animationManager && this.animationManager.animationsActive()) {
            // FLIP Animation: First - record positions of all existing siblings
            const siblings = Array.from(anchor.parentElement.children);
            const firstRects = new Map();
            siblings.forEach(sibling => {
                firstRects.set(sibling, sibling.getBoundingClientRect());
            });
            
            // Create the new card
            this.createCard(cardId, args.scheme, anchorId);
            const cardElement = $(cardId);
            const cardImage = $(`${cardId}_image`);
            
            // FLIP: Last - get new positions after DOM change
            // FLIP: Invert & Play - animate from old positions to new
            const animations = [];
            
            siblings.forEach(sibling => {
                const firstRect = firstRects.get(sibling);
                const lastRect = sibling.getBoundingClientRect();
                const deltaX = firstRect.left - lastRect.left;
                
                if (deltaX !== 0) {
                    sibling.style.transition = 'none';
                    animations.push(
                        sibling.animate([
                            { transform: `translateX(${deltaX}px)` },
                            { transform: 'translateX(0)' }
                        ], {
                            duration: 400,
                            easing: 'ease-out'
                        }).finished
                    );
                }
            });
            
            // Animate the new card growing
            if (cardImage) {
                cardImage.style.transition = 'none';
                animations.push(
                    cardImage.animate([
                        { transform: 'scale(0)', opacity: 0 },
                        { transform: 'scale(1)', opacity: 1 }
                    ], {
                        duration: 400,
                        easing: 'ease-out'
                    }).finished
                );
            }
            
            await Promise.all(animations);
        } else {
            // Fallback if no animation manager
            this.createCard(cardId, args.scheme, anchorId);
        }

        var translated = dojo.string.substitute(
            _("${player_name} has selected <strong>${scheme_name}</strong> as their Scheme today"),
            {
                player_name: args.player_name,
                scheme_name: args.scheme.name
            }
        );
        $('pagemaintitletext').innerHTML = translated;
    },

    notif_approachCharacterPlayed: async function (notif) 
    {
        debug( 'notif_approachCharacterPlayed' );
        debug( notif );

        const args = notif.args;

        const cardId = `${args.player_id}-${args.character.id}`;
        this.createCard(cardId, args.character, `${args.player_id}-home-anchor`);

        // Animate the card growing from nothing to full size
        const cardElement = $(cardId);
        if (cardElement && this.animationManager && this.animationManager.animationsActive()) {
            await cardElement.animate([
                { transform: 'scale(0)', opacity: 0 },
                { transform: 'scale(1)', opacity: 1 }
            ], {
                duration: 400,
                easing: 'ease-out'
            }).finished;
        }

        var translated = dojo.string.substitute(
            _("${player_name} has selected <strong>${character_name}</strong> as their Approach Character today"),
            {
                player_name: args.player_name,
                character_name: args.character.name
            }
        );
        $('pagemaintitletext').innerHTML = translated;
    },

    notif_panacheModified: function( notif )
    {
        debug( 'notif_panacheModified' );
        debug( notif );

        const args = notif.args;
        $(`${args.playerId}-score-panache`).innerHTML = args.panache;
        $(`${args.playerId}-panache`).innerHTML = args.panache;
    },

    notif_approachCardsReceived: function( notif )
    {
        debug( 'notif_approachCardsReceived' );
        debug( notif );

        notif.args.cards.forEach((card) => {
            this.addCardToDeck(this.approachDeck, card);
        });            
    },

    notif_attachmentEquipped: async function( notif )
    {
        debug( 'notif_attachmentEquipped' );
        debug( notif );

        const args = notif.args;
        const attachment = args.attachment;
        const performer = this.cardProperties[args.performerId];

        //See if the card came from the hand
        const cardExists = this.factionHand.getCards().some(c => c.id === attachment.id);
        if (cardExists)
        {
            const card = this.cardProperties[attachment.id];
            this.factionHand.removeCard(card);
        }
        else if (this.cardProperties[attachment.id] != undefined)
        {
            const oldCard = this.cardProperties[attachment.id];

            //Destroy the old card element
            dojo.destroy(oldCard.divId);
        }

        $(`${performer.controllerId}-score-hand-count`).innerHTML = args.handCount;

        this.attachCard(performer, attachment);
        this.cardProperties[attachment.id] = attachment;

        performer.modifiedResolve = args.modifiedResolve;
        performer.modifiedCombat = args.modifiedCombat;
        performer.modifiedFinesse = args.modifiedFinesse;
        performer.modifiedInfluence = args.modifiedInfluence;

        //Create a placeholder html element in front of the performer
        const placeholderId = "equip-placeholder";
        dojo.place(`<div id="${placeholderId}"></div>`, performer.divId, 'before');

        //Destroy old character element
        dojo.destroy(performer.divId);

        //Create the new character element    
        this.createCard(performer.divId, performer, placeholderId);

        // Add pop scale animation to the newly created element
        const newElement = $(performer.divId);
        if (newElement && this.animationManager && this.animationManager.animationsActive()) {
            await newElement.animate([
                { transform: 'scale(0.8)' },
                { transform: 'scale(1.1)' },
                { transform: 'scale(1)' }
            ], {
                duration: 300,
                easing: 'ease-out'
            }).finished;
        }

        //Destroy the placeholder
        dojo.destroy(placeholderId);
    },

    notif_attachmentUnequipped: function( notif )
    {
        debug( 'notif_attachmentUnequipped' );
        debug( notif );

        const args = notif.args;
        const attachment = this.cardProperties[args.attachmentId];
        const character = this.cardProperties[args.characterId];

        if (attachment)
        {
            attachment.attachmentIndex = null;
            attachment.attachedToId = null;
        }

        if (character)
        {
            character.modifiedResolve = args.modifiedResolve;
            character.modifiedCombat = args.modifiedCombat;
            character.modifiedFinesse = args.modifiedFinesse;
            character.modifiedInfluence = args.modifiedInfluence;
        }

        if (attachment && character)
        {
            this.unattachCard(character, attachment);
            
            //Create a placeholder html element in front of the performer
            const placeholderId = "unequip-placeholder";
            dojo.place(`<div id="${placeholderId}"></div>`, character.divId, 'before');

            //Destroy attachment element
            dojo.destroy(attachment.divId);

            //Destroy old character element
            dojo.destroy(character.divId);

            //Create the new attachment element    
            this.createCard(attachment.divId, attachment, placeholderId);

            //Create the new character element    
            this.createCard(character.divId, character, placeholderId);

            //Destroy the placeholder
            dojo.destroy(placeholderId);
        }
    },

    notif_factionResolveCardDraw: function( notif )
    {
        debug( 'notif_factionResolveCardDraw' );
        debug( notif );

        notif.args.cards.forEach((card) => {
            this.addCardToDeck(this.factionHand, card);
        });

        // Show faction hand when cards are drawn
        dojo.removeClass('factionHand-placeholder', 'hidden');
        
        // Trigger floating check after revealing the placeholder
        if (this.checkFloatingHand) {
            this.checkFloatingHand();
        }

        $(`${this.player_id}-score-hand-count`).innerHTML = this.factionHand.getCards().length;
    },

    notif_factionResolveCardDrawPublic: function( notif )
    {
        debug( 'notif_factionResolveCardDrawPublic' );
        debug( notif );

        if (notif.args.playerId == this.player_id)
        {
            return;
        }

        const element = $(`${notif.args.playerId}-score-hand-count`);
        const handCount = parseInt(element.innerHTML);
        element.innerHTML = handCount + notif.args.count;
    },

    notif_cardAddedToHand: function( notif )
    {
        debug( 'notif_cardAddedToHand' );
        debug( notif );

        if (notif.args.player_id == this.player_id)
        {
            this.addCardToDeck(this.factionHand, notif.args.card);
        }

        $(`${notif.args.player_id}-score-hand-count`).innerHTML = notif.args.handCount;
    },

    notif_drawCard: function( notif )
    {
        debug( 'notif_drawCard' );
        debug( notif );

        const args = notif.args;

        const card = args.card;
        this.cardProperties[card.id] = card;
        this.addCardToDeck(this.factionHand, card);
        
        // Ensure faction hand is visible and floating check is triggered
        dojo.removeClass('factionHand-placeholder', 'hidden');
        if (this.checkFloatingHand) {
            this.checkFloatingHand();
        }
        
        $(`${this.player_id}-score-hand-count`).innerHTML = this.factionHand.getCards().length;

    },


    notif_drawCardMessage: function( notif )
    {
        debug( 'notif_drawCardMessage' );
        debug( notif );

        $(`${notif.args.playerId}-score-hand-count`).innerHTML = notif.args.count;

    },

    notif_cardAddedToCityDeck: function( notif )
    {
        debug( 'notif_cardAddedToCityDeck' );
        debug( notif );

        const args = notif.args;

        let card = this.cardProperties[args.cardId];
        if (card)
        {
            card.location = this.LOCATION_CITY_DECK;

            dojo.destroy(card.divId);
            card.divId = null;
            delete this.cardProperties[args.cardId];
        }
    },

    notif_cardAddedToCityDiscardPile: async function( notif )
    {
        debug( 'notif_cardAddedToCityDiscardPile' );
        debug( notif );

        const args = notif.args;

        let card = this.cardProperties[args.cardId];
        if (card)
        {
            card.location = this.LOCATION_CITY_DISCARD;

            const cardElement = $(card.divId);
            
            // Animate the card shrinking to nothing
            if (cardElement && this.animationManager && this.animationManager.animationsActive()) {
                await cardElement.animate([
                    { transform: 'scale(1)', opacity: 1 },
                    { transform: 'scale(0)', opacity: 0 }
                ], {
                    duration: 400,
                    easing: 'ease-in'
                }).finished;
            }

            dojo.destroy(card.divId);
            card.divId = null;
        }
        else
        {
            card = args.card;
        }

        this.gamedatas.cityDiscard.push(card);

    },

    notif_cardAddedToPlayerDiscardPile: function( notif )
    {
        debug( 'notif_cardAddedToPlayerDiscardPile' );
        debug( notif );

        const args = notif.args;
        let card = args.card;
        this.cardProperties[card.id] = card;

        card.location = this.LOCATION_PLAYER_DISCARD;
        const player = this.gamedatas.players[args.playerId];
        player.discard.push(card);
    },
    

    notif_cardDiscardedFromPlay: async function( notif )
    {
        debug( 'notif_cardDiscardedFromPlay' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];        
        card.location = this.LOCATION_PLAYER_DISCARD;

        const cardElement = $(card.divId);
        
        // Animate the card shrinking to nothing
        if (cardElement && this.animationManager && this.animationManager.animationsActive()) {
            await cardElement.animate([
                { transform: 'scale(1)', opacity: 1 },
                { transform: 'scale(0)', opacity: 0 }
            ], {
                duration: 400,
                easing: 'ease-in'
            }).finished;
        }

        dojo.destroy(card.divId);
        card.divId = null;

        const player = this.gamedatas.players[args.playerId];
        player.discard.push(card);
    },

    notif_cardDiscardedFromHand: function( notif )
    {
        debug( 'notif_cardDiscardedFromHand' );
        debug( notif );

        const args = notif.args;
        let card = args.card;
        this.cardProperties[card.id] = card;

        $(`${args.playerId}-score-hand-count`).innerHTML = args.handCount;

        if (args.playerId == this.player_id)
        {
            this.factionHand.removeCard(card);
        }

        card.location = this.LOCATION_PLAYER_DISCARD;
        const player = this.gamedatas.players[args.playerId];
        player.discard.push(card);
    },

    notif_cardRemovedFromHand: function( notif )
    {
        debug( 'notif_cardRemovedFromHand' );
        debug( notif );

        const args = notif.args;

        if (notif.args.playerId == this.player_id)
        {
            const card = this.cardProperties[args.cardId];
            if (card) this.factionHand.removeCard(card);
        }

        $(`${notif.args.playerId}-score-hand-count`).innerHTML = args.handCount;
    },

    notif_cardRemovedFromPlay: async function( notif )
    {
        debug( 'notif_cardRemovedFromPlay' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.location = args.toLocation;

            const cardElement = $(card.divId);
            
            // Animate the card shrinking to nothing
            if (cardElement && this.animationManager && this.animationManager.animationsActive()) {
                await cardElement.animate([
                    { transform: 'scale(1)', opacity: 1 },
                    { transform: 'scale(0)', opacity: 0 }
                ], {
                    duration: 400,
                    easing: 'ease-in'
                }).finished;
            }

            dojo.destroy(card.divId);
            card.divId = null;
        }
    },


    notif_cardMoved: async function( notif )
    {
        debug( 'notif_cardMoved' );
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        const oldDivId = card.divId;
        const oldElement = $(oldDivId);
        
        card.engaged = args.engage;
        card.location = args.toLocation;

        // Get the destination target (returns string ID, need to convert to DOM element)
        const cardId = this.createCardId(card, args.toLocation);
        const targetId = this.getTargetElementForLocation(args.toLocation, card.controllerId);
        const targetElement = $(targetId);

        // Animate the old element to the destination, then replace it with the new element
        if (oldElement && targetElement && this.animationManager && this.animationManager.animationsActive()) {
            await this.animationManager.slideAndAttach(oldElement, targetElement);
        }

        // Destroy the old card element after animation completes
        dojo.destroy(oldDivId);

        // Create the new card element at the destination
        this.createCard(cardId, card, targetId);
    },

    notif_cardEngaged: function( notif )
    {
        debug( 'notif_cardEngaged' );
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        card.engaged = true;
        
        const cardElement = $(`${card.divId}_image`);
        dojo.addClass(cardElement, '_7sfs-engaged');
        // CSS transition on ._7sfs-card handles the smooth rotation animation
    },

    notif_cardEngarded: function( notif )
    {
        debug( 'notif_cardEngarded' );
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        card.engaged = false;
        
        const cardElement = $(`${card.divId}_image`);
        dojo.removeClass(cardElement, '_7sfs-engaged');
        // CSS transition on ._7sfs-card handles the smooth rotation animation
    },

    notif_cardMustered: async function (notif) 
    {
        debug( 'notif_cardMustered' );
        debug( notif );

        const args = notif.args;

        const cardId = this.createCardId(args.card, args.location);
        const target = this.getTargetElementForLocation(args.location, args.player_id);
        this.createCard(cardId, args.card, target);

        // Animate the card growing from nothing to full size
        const cardElement = $(cardId);
        if (cardElement && this.animationManager && this.animationManager.animationsActive()) {
            await cardElement.animate([
                { transform: 'scale(0)', opacity: 0 },
                { transform: 'scale(1)', opacity: 1 }
            ], {
                duration: 400,
                easing: 'ease-out'
            }).finished;
        }
    },

    notif_characterRecruited: function( notif )
    {
        debug( 'notif_characterRecruited' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        card.controllerId = args.player_id;

        //Remove from this.cardProperties
        delete this.cardProperties[args.characterId];
        dojo.destroy(card.divId);

        const cardId = this.createCardId(card, card.location);
        const target = this.getTargetElementForLocation(card.location, card.controllerId);
        this.createCard(cardId, card, target);
    },

    notif_characterWounded: async function( notif )
    {
        debug( 'notif_characterWounded' );
        debug( notif );

        const args = notif.args;

        if (args.wounds == 0)
            return;

        const card = this.cardProperties[args.characterId];
        if (card.wounds == 0)
        {
            const characterImage = $(`${card.divId}_image`);
            const woundChip = `${card.divId}_wounds`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: woundChip,
                class: '_7sfs-wound-chip',
            }),  characterImage, 'last');
            this.addTippyTooltip( woundChip, `<div class='_7sfs-basic-tooltip'>${_("Wounds")}</div>` );
        }
        
        card.wounds += args.wounds;
        card.modifiedResolve = args.resolve;

        const woundChip = $(`${card.divId}_wounds`);

        // Pulse the element before changing the value
        if (woundChip && this.animationManager && this.animationManager.animationsActive()) {
            await woundChip.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.4)' },
                { transform: 'scale(1)' }
            ], {
                duration: 300,
                easing: 'ease-in-out'
            }).finished;
        }

        woundChip.innerHTML = card.wounds;

        const element = $(`${card.divId}_resolve_value`);
        element.innerHTML = card.modifiedResolve;
        if (card.modifiedResolve != card.resolve || card.wounds > 0)
        {

            // Pulse the element before changing the value
            if (element && this.animationManager && this.animationManager.animationsActive()) {
                await element.animate([
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.4)' },
                    { transform: 'scale(1)' }
                ], {
                    duration: 300,
                    easing: 'ease-in-out'
                }).finished;
            }

            dojo.addClass(element, '_7sfs-modified-stat-value');
        }
    },

    notif_characterHealed: async function( notif )
    {
        debug( 'notif_characterHealed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];

        card.wounds -= args.wounds;
        if (card.wounds < 0)
            card.wounds = 0;
        
        const woundChip = $(`${card.divId}_wounds`);

        // Pulse the element before changing the value
        if (woundChip && this.animationManager && this.animationManager.animationsActive()) {
            await woundChip.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.4)' },
                { transform: 'scale(1)' }
            ], {
                duration: 300,
                easing: 'ease-in-out'
            }).finished;
        }

        woundChip.innerHTML = card.wounds;
        if (card.wounds == 0)
            dojo.destroy(woundChip);

        card.modifiedResolve = args.resolve;

        const element = $(`${card.divId}_resolve_value`);
        element.innerHTML = card.modifiedResolve;
        if (card.modifiedResolve == card.resolve && card.wounds == 0)
        {
            // Pulse the element before changing the value
            if (element && this.animationManager && this.animationManager.animationsActive()) {
                await element.animate([
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.4)' },
                    { transform: 'scale(1)' }
                ], {
                    duration: 300,
                    easing: 'ease-in-out'
                }).finished;
            }

            dojo.removeClass(element, '_7sfs-modified-stat-value');
        }
    },

    notif_characterDestroyed: async function( notif )
    {
        debug( 'notif_characterDestroyed' );
        debug( notif );

        const args = notif.args;        
        const card = this.cardProperties[args.characterId];
        if (card)
        {
            card.location = this.LOCATION_PLAYER_LOCKER;
            card.engaged = false;

            const cardElement = $(card.divId);
            const cardImage = $(`${card.divId}_image`);
            
            // Animate the card shrinking to nothing
            if (cardImage && cardElement && this.animationManager && this.animationManager.animationsActive()) {
                // Disable CSS transition on the image element
                cardImage.style.transition = 'none';
                
                // Animate both the image shrinking and the container collapsing
                await Promise.all([
                    cardImage.animate([
                        { transform: 'scale(1)', opacity: 1 },
                        { transform: 'scale(0)', opacity: 0 }
                    ], {
                        duration: 400,
                        easing: 'ease-in',
                        fill: 'forwards'
                    }).finished,
                    cardElement.animate([
                        { width: cardElement.offsetWidth + 'px', marginLeft: '25px', marginRight: '5px' },
                        { width: '0px', marginLeft: '0px', marginRight: '0px' }
                    ], {
                        duration: 400,
                        easing: 'ease-in',
                        fill: 'forwards'
                    }).finished
                ]);
            }

            dojo.destroy(card.divId);
            card.divId = null;
        }

        const player = this.gamedatas.players[args.playerId];
        player.locker.push(card);
    },

    notif_characterCombatModified: function( notif )
    {
        debug( 'notif_characterCombatModified' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        card.modifiedCombat = args.newCombat;

        const element = $(`${card.divId}_combat_value`);
        element.innerHTML = card.modifiedCombat;
        if (card.modifiedCombat != card.combat)
            dojo.addClass(element, '_7sfs-modified-stat-value');
        else
            dojo.removeClass(element, '_7sfs-modified-stat-value');
    },

    notif_characterFinesseModifed: function( notif )
    {
        debug( 'notif_characterFinesseModifed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        card.modifiedFinesse = args.newFinesse;

        const element = $(`${card.divId}_finesse_value`);
        element.innerHTML = card.modifiedFinesse;
        if (card.modifiedFinesse != card.finesse)
            dojo.addClass(element, '_7sfs-modified-stat-value');
        else
            dojo.removeClass(element, '_7sfs-modified-stat-value');
    },

    notif_characterInfluenceModified: function( notif )
    {
        debug( 'notif_characterInfluenceModified' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        card.modifiedInfluence = args.newInfluence;

        const element = $(`${card.divId}_influence_value`);
        element.innerHTML = card.modifiedInfluence;
        if (card.modifiedInfluence != card.influence)
            dojo.addClass(element, '_7sfs-modified-stat-value');
        else
            dojo.removeClass(element, '_7sfs-modified-stat-value');
    },

    notif_cardSentToLocker: async function( notif )
    {
        debug( 'notif_cardSentToLocker' );
        debug( notif );

        const args = notif.args;
        let card = this.cardProperties[args.card.id];
        if (card)
        {
            if (card.cardNumber == 150 && card.expansionName === '_7s5s') {
                this.removeForumInterveneList();
            }

            card.location = this.LOCATION_PLAYER_LOCKER;

            const cardElement = $(card.divId);
            const cardImage = $(`${card.divId}_image`);
            
            // Animate the card shrinking to nothing
            if (cardImage && cardElement && this.animationManager && this.animationManager.animationsActive()) {
                // Disable CSS transition on the image element
                cardImage.style.transition = 'none';
                
                // Animate both the image shrinking and the container collapsing
                await Promise.all([
                    cardImage.animate([
                        { transform: 'scale(1)', opacity: 1 },
                        { transform: 'scale(0)', opacity: 0 }
                    ], {
                        duration: 400,
                        easing: 'ease-in',
                        fill: 'forwards'
                    }).finished,
                    cardElement.animate([
                        { width: cardElement.offsetWidth + 'px', marginLeft: '25px', marginRight: '5px' },
                        { width: '0px', marginLeft: '0px', marginRight: '0px' }
                    ], {
                        duration: 400,
                        easing: 'ease-in',
                        fill: 'forwards'
                    }).finished
                ]);
            }

            dojo.destroy(card.divId);
            card.divId = null;    
        }
        else
        {
            card = args.card;
            this.cardProperties[card.id] = card;
        }

        const player = this.gamedatas.players[args.playerId];
        player.locker.push(card);
    },

    notif_cardRemovedFromLocker: function( notif )
    {
        debug( 'notif_cardRemovedFromLocker' );
        debug( notif );

        const args = notif.args;
        const player = this.gamedatas.players[args.playerId];
        player.locker = player.locker.filter((c) => c.id !== args.cardId);
    },

    notif_newDay: async function( notif )
    {
        debug( 'notif_newDay' );
        debug( notif );

        const args = notif.args;

        const dayElement = $('day-indicator');
        
        // Pulse the element before changing the value
        if (dayElement && this.animationManager && this.animationManager.animationsActive()) {
            await dayElement.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.4)' },
                { transform: 'scale(1)' }
            ], {
                duration: 300,
                easing: 'ease-in-out'
            }).finished;
        }

        dayElement.innerHTML = args.day;
        dojo.style('day-indicator', 'display', 'block');
    },

    notif_cityCardAddedToLocation: async function( notif )
    {
        debug( 'notif_cityCardAddedToLocation' );
        debug( notif );

        const args = notif.args;

        const card = args.card;
        const target = this.getTargetElementForLocation(args.location, card.controllerId);
        const cardId = this.createCardId(card, args.location);
        this.createCard(cardId, card, target);

        // Animate the card growing from nothing to full size
        const cardElement = $(cardId);
        if (cardElement && this.animationManager && this.animationManager.animationsActive()) {
            await cardElement.animate([
                { transform: 'scale(0)', opacity: 0 },
                { transform: 'scale(1)', opacity: 1 }
            ], {
                duration: 400,
                easing: 'ease-out'
            }).finished;
        }
    },

    notif_playerReknownUpdated: async function( notif )
    {
        debug( 'notif_playerReknownUpdated' );
        debug( notif );

        const args = notif.args;
        const element = $(`${args.player_id}-score-reknown`);
        // Pulse the element before changing the value
        if (element && this.animationManager && this.animationManager.animationsActive()) {
            await element.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.4)' },
                { transform: 'scale(1)' }
            ], {
                duration: 300,
                easing: 'ease-in-out'
            }).finished;
        }

        element.innerHTML = args.total;
    },

    notif_reknownUpdatedOnCard: async function( notif )
    {
        debug( 'notif_reknownUpdatedOnCard' );
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        const divId = `${card.divId}-reknown`;
        
        // Pulse the existing element before destroying it
        const existingElement = $(divId);
        if (existingElement && this.animationManager && this.animationManager.animationsActive()) {
            await existingElement.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.4)' },
                { transform: 'scale(1)' }
            ], {
                duration: 300,
                easing: 'ease-in-out'
            }).finished;
        }
        
        //Delete the old element if exists
        if (existingElement) {                
            dojo.destroy(divId);
        } 

        if (args.total > 0)
        {
            dojo.place( this.format_block( 'jstpl_reknown_chip', {
                id: divId,
                amount: args.total,
            }),  `${card.divId}_image`, 'last');
            
            // Pulse the new element
            const newElement = $(divId);
            if (newElement && this.animationManager && this.animationManager.animationsActive()) {
                await newElement.animate([
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.4)' },
                    { transform: 'scale(1)' }
                ], {
                    duration: 300,
                    easing: 'ease-in-out'
                }).finished;
            }
        }
    },

    notif_reknownAddedToLocation: async function( notif )
    {
        debug( 'notif_reknownAddedToLocation' );
        debug( notif );

        const args = notif.args;
        //Find the image element with the attribute data-location that matches arg.location
        const imageElement = dojo.query(`[data-location="${args.location}"]`)[0];
        //Find the element with the class _7sfs-city-reknown-chip that is a child of the element's parent
        const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
        
        // Pulse the element before changing the value
        if (reknownElement && this.animationManager && this.animationManager.animationsActive()) {
            await reknownElement.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.4)' },
                { transform: 'scale(1)' }
            ], {
                duration: 300,
                easing: 'ease-in-out'
            }).finished;
        }
        
        const reknown = parseInt(reknownElement.innerHTML) + args.amount;
        reknownElement.innerHTML = reknown;
    },

    notif_reknownRemovedFromLocation: async function( notif )
    {
        debug( 'notif_reknownRemovedFromLocation' );
        debug( notif );

        const args = notif.args;
        //Find the image element with the attribute data-location that matches arg.location
        const imageElement = dojo.query(`[data-location="${args.location}"]`)[0];
        //Find the element with the class _7sfs-city-reknown-chip that is a child of the element's parent
        const reknownElement = dojo.query('._7sfs-city-reknown-chip', imageElement.parentElement)[0];
        
        // Pulse the element before changing the value
        if (reknownElement && this.animationManager && this.animationManager.animationsActive()) {
            await reknownElement.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(1.4)' },
                { transform: 'scale(1)' }
            ], {
                duration: 300,
                easing: 'ease-in-out'
            }).finished;
        }
        
        const reknown = parseInt(reknownElement.innerHTML) - args.amount;
        reknownElement.innerHTML = reknown;
    },

    notif_firstPlayer: async function( notif )
    {
        debug( 'notif_firstPlayer' );
        debug( notif );

        //Remove any existing first player classes
        dojo.query('._7sfs-first-player-home').removeClass('_7sfs-first-player-home');
        dojo.query('._7sfs-first-player-score').removeClass('_7sfs-first-player-score');

        //Add the new classes
        const args = notif.args;
        dojo.addClass(`${args.playerId}-first-player`, '_7sfs-first-player-home');
        dojo.removeClass(`${args.playerId}-score-seal-first-player`, '_7sfs-first-player-hidden');
        dojo.addClass(`${args.playerId}-score-seal-first-player`, '_7sfs-first-player-score');

        // Pulse the first player elements
        const homeElement = $(`${args.playerId}-first-player`);
        const scoreElement = $(`${args.playerId}-score-seal-first-player`);
        
        const animations = [];
        if (homeElement && this.animationManager && this.animationManager.animationsActive()) {
            animations.push(
                homeElement.animate([
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.3)' },
                    { transform: 'scale(1)' }
                ], {
                    duration: 400,
                    easing: 'ease-in-out'
                }).finished
            );
        }
        if (scoreElement && this.animationManager && this.animationManager.animationsActive()) {
            animations.push(
                scoreElement.animate([
                    { transform: 'scale(1)' },
                    { transform: 'scale(1.3)' },
                    { transform: 'scale(1)' }
                ], {
                    duration: 400,
                    easing: 'ease-in-out'
                }).finished
            );
        }
        
        if (animations.length > 0) {
            await Promise.all(animations);
        }

        var translated = dojo.string.substitute(
            _("${player_name} is now the First Player"),
            {
                player_name: args.player_name
            }
        );
        $('pagemaintitletext').innerHTML = translated;
    },

    notif_cardRemovedFromCityDiscardPile: function ( notif )
    {
        debug( 'notif_cardRemovedFromCityDiscardPile' );
        debug( notif );

        const args = notif.args;
        this.gamedatas.cityDiscard = this.gamedatas.cityDiscard.filter((c) => c.id !== args.card.id);
    },

    notif_cardRemovedFromPlayerDiscardPile: function ( notif )
    {
        debug( 'notif_cardRemovedFromPlayerDiscardPile' );
        debug( notif );

        const args = notif.args;
        const player = this.gamedatas.players[args.player_id];
        player.discard = player.discard.filter((c) => c.id !== args.card.id);
    },

    notif_yevgeniAdversaryChosen: function( notif )
    {
        debug( 'notif_yevgeniAdversaryChosen' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        card.conditions.push(this.ADVERSARY_OF_YEVGENI);

        const imageElement = dojo.query('._7sfs-card', card.divId)[0];
        const id = `${card.divId}_yevgeni_adversary`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: id,
            class: '_7sfs-yevgeni-adversary-chip',
        }),  imageElement, 'last');

        this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("Chosen Adversary of Yevgeni")}</div>` );
    },

    notif_yevgeniAdversaryRemoved: function( notif )
    {
        debug( 'notif_yevgeniAdversaryRemoved' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.ADVERSARY_OF_YEVGENI);

            const id = `${card.divId}_yevgeni_adversary`;
            dojo.destroy(id);    
        }
    },

    notif_maryamBenuPleromaAbilityUsed: function( notif )
    {
        debug( 'notif_maryamBenuPleromaAbilityUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        card.conditions.push(this.MARYAM_BENU_PLEROMA_ABILITY_USED);

        const imageElement = dojo.query('._7sfs-card', card.divId)[0];
        const id = `${card.divId}_maryam_benu_pleroma_ability_used`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: id,
            class: '_7sfs-maryam-benu-pleroma-ability-used-chip',
        }),  imageElement, 'last');

        this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("Maryam Benu Pleroma Ability Used")}</div>` );
    },

    notif_maryamBenuPleromaAbilityRemoved: function( notif )
    {
        debug( 'notif_maryamBenuPleromaAbilityRemoved' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.MARYAM_BENU_PLEROMA_ABILITY_USED);

            const id = `${args.cardId}_maryam_benu_pleroma_ability_used`;
            dojo.destroy(id);    
        }
    },

    notif_carmellaAbilityUsed: function( notif )
    {
        debug( 'notif_carmellaAbilityUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions.push(this.CARMELLA_ABILITY_USED);

            const imageElement = dojo.query('._7sfs-card', card.divId)[0];
            const id = `${card.divId}_carmella_ability_used`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: '_7sfs-carmella-ability-used-chip',
            }),  imageElement, 'last');

            this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("Carmella's once-per-Day ability has been used")}</div>` );
        }
    },

    notif_carmellaAbilityRemoved: function( notif )
    {
        debug( 'notif_carmellaAbilityRemoved' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.CARMELLA_ABILITY_USED);

            const id = `${card.divId}_carmella_ability_used`;
            dojo.destroy(id);
        }
    },

    notif_indomitableWillConditionStarted: function( notif )
    {
        debug( 'notif_indomitableWillConditionStarted' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        card.conditions.push(this.INDOMITABLE_WILL_CONDITION);

        const imageElement = dojo.query('._7sfs-card', card.divId)[0];
        const id = `${card.divId}_indomitable_will_condition`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: id,
            class: '_7sfs-indomitable-will-condition-chip',
        }),  imageElement, 'last');

        this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("This character has Indomitable Will")}</div>` );
    },

    notif_indomitableWillConditionEnded: function( notif )
    {
        debug( 'notif_indomitableWillConditionEnded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.INDOMITABLE_WILL_CONDITION);

            const id = `${args.cardId}_indomitable_will_condition`;
            dojo.destroy(id);    
        }
    },

    notif_crystalEyeTargetChosen: function( notif )
    {
        debug( 'notif_crystalEyeTargetChosen' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        card.conditions.push(this.CRYSTAL_EYE_TARGET);

        const div = this.approachDeck.getItemDivId(args.cardId);
        const id = `${args.cardId}_crystal_eye_target`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: id,
            class: '_7sfs-crystal-eye-target-chip',
        }),  div, 'last');

        this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("Chosen Target for Crystal Eye")}</div>` );
    },

    notif_crystalEyeTargetRemoved: function( notif )
    {
        debug( 'notif_crystalEyeTargetRemoved' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.CRYSTAL_EYE_TARGET);
        }

        const id = `${args.cardId}_crystal_eye_target`;
        dojo.destroy(id);    
    },

    notif_catsEmbargoTargetChosen: function( notif )
    {
        debug( 'notif_catsEmbargoTargetChosen' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions.push(this.CATS_EMBARGO_TARGET);

            const cardElement = this.factionHand.getCardElement(card);
            if (cardElement) {
                const id = `${args.cardId}_cats_embargo_target`;
                dojo.place( this.format_block( 'jstpl_generic_chip', {
                    id: id,
                    class: '_7sfs-cats-embargo-target-chip',
                }),  cardElement, 'last');
        
                this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("Target for Cat's Embargo")}</div>` );
            }
        }
    },

    notif_catsEmbargoTargetRemoved: function( notif )
    {
        debug( 'notif_catsEmbargoTargetRemoved' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.CATS_EMBARGO_TARGET);

            const id = `${args.cardId}_cats_embargo_target`;
            dojo.destroy(id);    
        }
    },

    notif_schemeMovedToCity: async function( notif )
    {
        debug( 'notif_schemeMovedToCity');
        debug( notif );

        const args = notif.args;

        const card = this.cardProperties[args.cardId];
        const oldDivId = card.divId;
        const oldElement = $(oldDivId);

        card.location = args.location;
        this.gamedatas.homeCards = this.gamedatas.homeCards.filter((scheme) => scheme.id !== card.id);

        const targetId = this.getTargetElementForLocation(args.location, card.controllerId);
        const targetElement = $(targetId);

        if (oldElement && targetElement && this.animationManager && this.animationManager.animationsActive()) {
            await this.animationManager.slideAndAttach(oldElement, targetElement);
        }

        dojo.destroy(oldDivId);

        const cardId = this.createCardId(card, args.location);
        this.createCard(cardId, card, targetId);
    },

    notif_locationClaimed: function( notif )
    {
        debug( 'notif_locationClaimed' );
        debug( notif );

        const args = notif.args;
        //Find the image element with the attribute data-location that matches arg.location
        const imageElement = dojo.query(`[data-location="${args.location}"]`)[0];

        const player = this.gamedatas.players[args.playerId];

        dojo.place( this.format_block( 'jstpl_location_control_chip', {
            id: imageElement.id,
            player_color: player.color,
        }),  imageElement, 'before');
    },

    notif_locationUncontrolled: function( notif )
    {
        debug( 'notif_locationUncontrolled' );
        debug( notif );

        const args = notif.args;
        const imageElement = dojo.query(`[data-location="${args.location}"]`)[0];
        const id = `${imageElement.id}-location-control-chip`;
        dojo.destroy(id);
    },
    
    notif_challengeIssued: function( notif )
    {
        debug( 'notif_challengeIssued' );
        debug( notif );

        const args = notif.args;
        const challenger = this.cardProperties[args.challengerId];
        challenger.conditions.push(this.CHALLENGER);
        const challengerImage = $(`${challenger.divId}_image`);
        const challengerChipId = `${challenger.divId}_challenger`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: challengerChipId,
            class: '_7sfs-challenger-chip',
        }),  challengerImage, 'last');
        this.addTippyTooltip( challengerChipId, `<div class='_7sfs-basic-tooltip'>${_("Duel Challenger")}</div>` );

        const defender = this.cardProperties[args.defenderId];
        defender.conditions.push(this.DEFENDER);
        const defenderImage = $(`${defender.divId}_image`);
        const defenderChipId = `${defender.divId}_defender`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: defenderChipId,
            class: '_7sfs-defender-chip',
        }),  defenderImage, 'last');
        this.addTippyTooltip( defenderChipId, `<div class='_7sfs-basic-tooltip'>${_("Duel Defender")}</div>` );
    },

    notif_challengerSwapped: function( notif )
    {
        debug( 'notif_challengerSwapped' );
        debug( notif );

        const args = notif.args;

        const oldChallenger = this.cardProperties[args.oldChallengerId];
        oldChallenger.conditions = oldChallenger.conditions.filter(condition => condition !== this.CHALLENGER);
        const oldChallengerChipId = `${oldChallenger.divId}_challenger`;
        dojo.destroy(oldChallengerChipId);

        const newChallenger = this.cardProperties[args.newChallengerId];
        newChallenger.conditions.push(this.CHALLENGER);
        const challengerImage = $(`${newChallenger.divId}_image`);
        const challengerChipId = `${newChallenger.divId}_challenger`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: challengerChipId,
            class: '_7sfs-challenger-chip',
        }),  challengerImage, 'last');
        this.addTippyTooltip( challengerChipId, `<div class='_7sfs-basic-tooltip'>${_("Duel Challenger")}</div>` );
    },

    notif_defenderSwapped: function( notif )
    {
        debug( 'notif_defenderSwapped' );
        debug( notif );

        const args = notif.args;

        const oldDefender = this.cardProperties[args.oldDefenderId];
        oldDefender.conditions = oldDefender.conditions.filter(condition => condition !== this.DEFENDER);
        const oldDefenderChipId = `${oldDefender.divId}_defender`;
        dojo.destroy(oldDefenderChipId);

        const newDefender = this.cardProperties[args.newDefenderId];
        newDefender.conditions.push(this.DEFENDER);
        const defenderImage = $(`${newDefender.divId}_image`);
        const defenderChipId = `${newDefender.divId}_defender`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: defenderChipId,
            class: '_7sfs-defender-chip',
        }),  defenderImage, 'last');
        this.addTippyTooltip( defenderChipId, `<div class='_7sfs-basic-tooltip'>${_("Duel Defender")}</div>` );
    },

    notif_characterIntervened: function( notif )
    {
        debug( 'notif_characterIntervened' );
        debug( notif );

        const args = notif.args;

        const oldTarget = this.cardProperties[args.oldTargetId];
        oldTarget.conditions = oldTarget.conditions.filter(condition => condition !== this.DEFENDER);
        const oldDefenderChipId = `${oldTarget.divId}_defender`;
        dojo.destroy(oldDefenderChipId);

        const newTarget = this.cardProperties[args.newTargetId];
        newTarget.conditions.push(this.DEFENDER);
        const defenderImage = $(`${newTarget.divId}_image`);
        const defenderChipId = `${newTarget.divId}_defender`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: defenderChipId,
            class: '_7sfs-defender-chip',
        }),  defenderImage, 'last');
        this.addTippyTooltip( defenderChipId, `<div class='_7sfs-basic-tooltip'>${_("Duel Defender")}</div>` );
    },

    notif_duelStarted: function( notif )
    {
        debug( 'notif_duelStarted' );
        debug( notif );

        const args = notif.args;

        this.inDuel = true;
        this.duelRound = 0;
        this.displayDuelTable();
        
        if (this.player_id == args.challengingPlayerId || this.player_id == args.defendingPlayerId)
        {
            // Move faction hand placeholder to bottom of duel rows
            dojo.place('factionHand-placeholder', 'duel', 'after');
            // Re-check floating state after moving placeholder
            if (this.checkFloatingHand) this.checkFloatingHand();
        }
    },

    notif_newDuelRound: function( notif )
    {
        debug( 'notif_newDuelRound' );
        debug( notif );

        const args = notif.args;
        this.duelRound = args.round;
        this.displayDuelRow(args);

    },

    notif_duelActorSwapped: function( notif )
    {
        debug( 'notif_duelActorSwapped' );
        debug( notif );

        const args = notif.args;
        let divId = `duel_round_${args.round}_actor`
        dojo.empty(divId);
        this.createCard(`duel_${args.round}_${args.actor.id}`, args.actor, divId, true);

        if (dojo.hasClass(`duel_round_${args.round}_starting_challenger_threat_row`, '_7sfs-duel-acting-character'))
        {
            divId = `duel_round_${args.round}_challenger_name`;
            $(divId).innerHTML = args.actor.name;
        }
        else
        {
            divId = `duel_round_${args.round}_defender_name`;
            $(divId).innerHTML = args.actor.name;
        }
    },

    notif_updateRoundWithCombatStats: function( notif )
    {
        debug( 'notif_updateRoundWithCombatStats' );
        debug( notif );

        const args = notif.args;

        if (args.mode == 'combat')
        {
            const combatCard = args.combatCard;
            const divId = `duel_round_${args.round}_combat`;
            if ($(divId).innerHTML == 'Not Chosen')
            {
                $(divId).innerHTML = '';
            }

            dojo.place( this.format_block('jstpl_row_combat_card', { 
                round: args.round,
                id: combatCard.id,
                image: this.getCardImageUrlRoot(combatCard.image) + combatCard.image 
            }),  divId, 'last');

            const cardDivId = `duel_round_${args.round}_combat_card_${combatCard.id}`;
            this.addTippyTooltip(cardDivId, `<img class="_7sfs-card-tooltip-img" src="${this.getCardImageUrlRoot(combatCard.image) + combatCard.image}" />`, this.CARD_TOOLTIP_DELAY);
    
            if (args.gambled)
            {
                dojo.addClass(cardDivId, '_7sfs-engaged');
                dojo.addClass(cardDivId, '_7sfs-duel-row-combat-card-gambled');
            }
            else if (this.player_id == combatCard.controllerId)
            {
                this.factionHand.removeCard(combatCard);
            }
            $(`${combatCard.controllerId}-score-hand-count`).innerHTML = args.handCount;
        }
        else
        {
            var element = $(`duel_round_${args.round}_${args.mode}`);
            if (element.innerHTML == 'Not Chosen')
            {
                element.innerHTML = '';
            }

            //Make sure there is no duplicate
            const effectName = `${args.cardName}: ${args.effectName}`;
            if (! element.innerHTML.includes(effectName))
            {
                element.innerHTML += `<p>${effectName}</p>`;
            }
        }

        let riposte = 0;
        let parry = 0;
        let thrust = 0;

        if (args.mode == 'combat')
        {
            const combatCard = args.combatCard;
            let currentRiposte = $(`duel_round_${args.round}_combat_riposte`).innerHTML;
            let currentParry = $(`duel_round_${args.round}_combat_parry`).innerHTML;
            let currentThrust = $(`duel_round_${args.round}_combat_thrust`).innerHTML;

            if (currentRiposte == '&mdash;')
            {
                riposte = combatCard.dashedRiposte ? '&mdash;' : args.riposte;
            }
            else if (combatCard.dashedRiposte && currentRiposte == '0')
            {
                riposte = '&mdash;';
            }
            else
            {
                riposte = parseInt(currentRiposte) + parseInt(args.riposte);
            }

            if (currentParry == '&mdash;')
            {
                parry = combatCard.dashedParry ? '&mdash;' : args.parry;
            }
            else if (combatCard.dashedParry && currentParry == '0')
            {
                parry = '&mdash;';
            }
            else
            {
                parry = parseInt(currentParry) + parseInt(args.parry);
            }

            if (currentThrust == '&mdash;')
            {
                thrust = combatCard.dashedThrust ? '&mdash;' : args.thrust;
            }
            else if (combatCard.dashedThrust && currentThrust == '0')
            {
                thrust = '&mdash;';
            }
            else
            {
                thrust = parseInt(currentThrust) + parseInt(args.thrust);
            }

            $(`duel_round_${args.round}_combat_riposte`).innerHTML = riposte;
            $(`duel_round_${args.round}_combat_riposte`).style.color = riposte == '&mdash;' ? 'red' : 'black';

            $(`duel_round_${args.round}_combat_parry`).innerHTML = parry;
            $(`duel_round_${args.round}_combat_parry`).style.color = parry == '&mdash;' ? 'red' : 'black';

            $(`duel_round_${args.round}_combat_thrust`).innerHTML = thrust;
            $(`duel_round_${args.round}_combat_thrust`).style.color = thrust == '&mdash;' ? 'red' : 'black';
        }
        else
        {
            riposte = parseInt($(`duel_round_${args.round}_${args.mode}_riposte`).innerHTML) + parseInt(args.riposte);
            parry = parseInt($(`duel_round_${args.round}_${args.mode}_parry`).innerHTML) + parseInt(args.parry);
            thrust = parseInt($(`duel_round_${args.round}_${args.mode}_thrust`).innerHTML) + parseInt(args.thrust);
    
            $(`duel_round_${args.round}_${args.mode}_riposte`).innerHTML = riposte;
            $(`duel_round_${args.round}_${args.mode}_parry`).innerHTML = parry;
            $(`duel_round_${args.round}_${args.mode}_thrust`).innerHTML = thrust;
        }

        $(`duel_round_${args.round}_ending_challenger_threat`).innerHTML = args.endingChallengerThreatAfter;
        $(`duel_round_${args.round}_ending_defender_threat`).innerHTML = args.endingDefenderThreatAfter;
        $(`duel_round_${args.round}_wounds`).innerHTML = args.wounds;

        if (args.endingChallengerThreatAfter > 0)
            dojo.addClass(`duel_round_${args.round}_ending_challenger_threat`, '_7sfs-threat-chip-threatened');
        else
            dojo.removeClass(`duel_round_${args.round}_ending_challenger_threat`, '_7sfs-threat-chip-threatened');

        if (args.challengerThreatIsLethal == 1)
            $(`duel_round_${args.round}_ending_challenger_threat`).innerHTML += '<span class="_7sfs-lethal">&#9760;</span>';

        if (args.endingDefenderThreatAfter > 0)
            dojo.addClass(`duel_round_${args.round}_ending_defender_threat`, '_7sfs-threat-chip-threatened');
        else
            dojo.removeClass(`duel_round_${args.round}_ending_defender_threat`, '_7sfs-threat-chip-threatened');

        if (args.defenderThreatIsLethal == 1)
            $(`duel_round_${args.round}_ending_defender_threat`).innerHTML += '<span class="_7sfs-lethal">&#9760;</span>';

        dojo.removeClass(`duel_round_${args.round}_${args.mode}`, '_7sfs-ability-not-chosen');
        dojo.removeClass(`duel_round_${args.round}_${args.mode}_stats`, '_7sfs-ability-not-chosen');
    },

    notif_updateRoundThreats: function( notif )
    {
        debug( 'notif_updateRoundThreats' );
        debug( notif );

        const args = notif.args;

        $(`duel_round_${args.round}_ending_challenger_threat`).innerHTML = args.challenger_threat;
        if (args.challenger_threat > 0)
            dojo.addClass(`duel_round_${args.round}_ending_challenger_threat`, '_7sfs-threat-chip-threatened');
        else
            dojo.removeClass(`duel_round_${args.round}_ending_challenger_threat`, '_7sfs-threat-chip-threatened');

        if (args.challengerThreatIsLethal == 1)
            $(`duel_round_${args.round}_ending_challenger_threat`).innerHTML += '<span class="_7sfs-lethal">&#9760;</span>';
    
        $(`duel_round_${args.round}_ending_defender_threat`).innerHTML = args.defender_threat;
        if (args.defender_threat > 0)
            dojo.addClass(`duel_round_${args.round}_ending_defender_threat`, '_7sfs-threat-chip-threatened');
        else
            dojo.removeClass(`duel_round_${args.round}_ending_defender_threat`, '_7sfs-threat-chip-threatened');

        if (args.defenderThreatIsLethal == 1)
            $(`duel_round_${args.round}_ending_defender_threat`).innerHTML += '<span class="_7sfs-lethal">&#9760;</span>';
        
        $(`duel_round_${args.round}_wounds`).innerHTML = args.wounds;
    },

    notif_challengeRejected: function( notif )
    {
        debug( 'notif_challengeRejected' );
        debug( notif );

        const args = notif.args;

        const challenger = this.cardProperties[args.challengerId];
        if (challenger)
        {
            challenger.conditions = challenger.conditions.filter(condition => condition !== this.CHALLENGER);
            const challengerChipId = `${challenger.divId}_challenger`;
            dojo.destroy(challengerChipId);
        }

        const defender = this.cardProperties[args.defenderId];
        if (defender)
        {
            defender.conditions = defender.conditions.filter(condition => condition !== this.DEFENDER);
            const defenderChipId = `${defender.divId}_defender`;
            dojo.destroy(defenderChipId);
        }
    },

    notif_challengeCancelled: function( notif )
    {
        debug( 'notif_challengeCancelled' );
        debug( notif );

        const args = notif.args;

        const challenger = this.cardProperties[args.challengerId];
        if (challenger)
        {
            challenger.conditions = challenger.conditions.filter(condition => condition !== this.CHALLENGER);
            const challengerChipId = `${challenger.divId}_challenger`;
            dojo.destroy(challengerChipId);
        }

        const defender = this.cardProperties[args.defenderId];
        if (defender)
        {
            defender.conditions = defender.conditions.filter(condition => condition !== this.DEFENDER);
            const defenderChipId = `${defender.divId}_defender`;
            dojo.destroy(defenderChipId);
        }
    },

    notif_duelEnd: function( notif )
    {
        debug( 'notif_duelEnd' );
        debug( notif );

        const args = notif.args;

        this.inDuel = false;
        dojo.destroy('duel');

        const challenger = this.cardProperties[args.challengerId];
        if (challenger)
        {
            challenger.conditions = challenger.conditions.filter(condition => condition !== this.CHALLENGER);
            const challengerChipId = `${challenger.divId}_challenger`;
            dojo.destroy(challengerChipId);
        }

        const defender = this.cardProperties[args.defenderId];
        if (defender)
        {
            defender.conditions = defender.conditions.filter(condition => condition !== this.DEFENDER);
            const defenderChipId = `${defender.divId}_defender`;
            dojo.destroy(defenderChipId);
        }

        // Move faction hand placeholder back to top of page (after choose_container)
        if (!this.isSpectator)
        {
            dojo.place('factionHand-placeholder', 'choose_container', 'after');
            // Re-check floating state after moving placeholder
            if (this.checkFloatingHand) this.checkFloatingHand();
        }
    },

    notif_cityDiscardShuffled: function( notif )
    {
        debug( 'notif_cityDiscardShuffled' );
        debug( notif );

        //Clear the city discard pile
        this.gamedatas.cityDiscard = [];
    },

    notif_playerDiscardShuffled: function( notif )
    {
        debug( 'notif_playerDiscardShuffled' );
        debug( notif );

        const args = notif.args;
        const player = this.gamedatas.players[args.playerId];
        player.discard = [];
    },

    notif_parleyInterveneListUpdated: function( notif )
    {
        debug( 'notif_parleyInterveneListUpdated' );
        debug( notif );

        const args = notif.args;
        this.displayForumInterveneList(args.interveneList);
    },

    notif_sirensScreamUsedListUpdated: function( notif )
    {
        debug( 'notif_sirensScreamUsedListUpdated' );
        debug( notif );

        const args = notif.args;
        this.displaySirensScreamUsedList(args.cardId, args.usedList);
    },

    notif_catsEmbargoUpdated: function( notif )
    {
        debug( 'notif_catsEmbargoUpdated' );
        debug( notif );

        const args = notif.args;
        this.displayCatsEmbargoCardName(args.cardId, args.embargoedCardName);
    },

})
});