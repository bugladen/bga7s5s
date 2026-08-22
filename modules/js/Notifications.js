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
            ['approachCharacterPlayed', 1000],
            ['approachSchemePlayed', 1000],
            ['attachmentEquipped', 1000],
            ['attachmentUnequipped', 1000],
            ['cardAddedToCityDeck', 500],
            ['cardAddedToCityDiscardPile', 500],
            ['cardAddedToHand', 500],
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
            ['characterResolveModified', 1],
            ['characterIntervened', 500],
            ['cardMustered', 1000],
            ['characterRecruited', 1000],
            ['characterWounded', 1000],
            ['cityCardAddedToLocation', 1000],
            ['cityDiscardShuffled', 500],
            ['crystalEyeTargetChosen', 500],
            ['crystalEyeTargetRemoved', 500],
            ['defenderSwapped', 500],
            ['drawCard', 500],
            ['drawCardMessage', 100],
            ['duelActorSwapped', 500],
            ['duelEnd', 500],
            ['duelStarted', 500],
            ['duelStatChanged', 500],
            ['factionResolveCardDraw', 500],
            ['factionResolveCardDrawPublic', 500],
            ['firstPlayer', 1000],
            ['locationClaimed', 500],
            ['parleyInterveneListUpdated', 1],
            ['sirensScreamUsedListUpdated', 1],
            ['crabsInABucketUsedListUpdated', 1],
            ['locationActionUsedListUpdated', 1],
            ['catsEmbargoUpdated', 1],
            ['locationUncontrolled', 500],
            ['maryamBenuPleromaAbilityUsed', 500],
            ['maryamBenuPleromaAbilityRemoved', 500],
            ['carmellaAbilityUsed', 1],
            ['carmellaAbilityRemoved', 1],
            ['silverSpineAbilityUsed', 500],
            ['silverSpineAbilityRemoved', 500],
            ['indomitableWillConditionStarted', 500],
            ['indomitableWillConditionEnded', 500],
            ['contemptAndHatredConditionStarted', 1],
            ['contemptAndHatredConditionEnded', 1],
            ['solineElGatoConditionStarted', 1],
            ['solineElGatoConditionEnded', 1],
            ['epeeSanglanteConditionStarted', 1],
            ['epeeSanglanteConditionEnded', 1],
            ['forgedForBattleConditionStarted', 1],
            ['forgedForBattleConditionEnded', 1],
            ['harpoonConditionStarted', 1],
            ['harpoonConditionEnded', 1],
            ['lodestoneConditionStarted', 1],
            ['lodestoneConditionEnded', 1],
            ['maneuverUsed', 1],
            ['newDay', 1000],
            ['newDuelRound', 500],
            ['panacheModified', 1000],
            ['playerDiscardShuffled', 500],
            ['playerReknownUpdated', 500],
            ['playLeader', 1500],
            ['reactionUsed', 1],
            ['renownAddedToLocation', 500],
            ['renownRemovedFromLocation', 500],
            ['reknownUpdatedOnCard', 500],
            ['cardSentToLocker', 500],
            ['cardSentToCityLocker', 500],
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

        // WHY: Seal FLIP (same as drawCard) — origin is the player-panel seal.
        await this.animateCardFromPlayerSeal($(cardId), args.player_id);

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
            //Find the first instance of the trait and remove it
            const index = card.traits.indexOf(args.trait);
            if (index !== -1)
            {
                card.traits.splice(index, 1);
            }
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

            // WHY: Seal FLIP for the new scheme (same as drawCard / playLeader).
            if (cardElement) {
                animations.push(this.animateCardFromPlayerSeal(cardElement, args.player_id, { duration: 400 }));
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

        // WHY: Seal FLIP (same as drawCard / playLeader) instead of scale-from-zero.
        await this.animateCardFromPlayerSeal($(cardId), args.player_id, { duration: 400 });

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

        if ( ! attachment || ! character)
        {
            return;
        }

        this.unattachCard(character, attachment);

        // WHY: Do not destroy/recreate the character — dojo.place on a stale
        // character.divId caused the ownerDocument crash. Patch the live
        // character in place, then re-seat the attachment as a city-row sibling
        // so the following discard/sink notification can animate it away.
        if (attachment.divId)
        {
            dojo.destroy(attachment.divId);
            attachment.divId = null;
        }

        const characterElement = character.divId ? $(character.divId) : null;
        if (characterElement)
        {
            const resolveEl = $(`${character.divId}_resolve_value`);
            if (resolveEl)
            {
                resolveEl.innerHTML = character.modifiedResolve;
                if (character.modifiedResolve != character.resolve || character.wounds > 0)
                    dojo.addClass(resolveEl, '_7sfs-modified-stat-value');
                else
                    dojo.removeClass(resolveEl, '_7sfs-modified-stat-value');
            }

            const combatEl = $(`${character.divId}_combat_value`);
            if (combatEl)
            {
                combatEl.innerHTML = character.modifiedCombat;
                if (character.modifiedCombat != character.combat)
                    dojo.addClass(combatEl, '_7sfs-modified-stat-value');
                else
                    dojo.removeClass(combatEl, '_7sfs-modified-stat-value');
            }

            const finesseEl = $(`${character.divId}_finesse_value`);
            if (finesseEl)
            {
                finesseEl.innerHTML = character.modifiedFinesse;
                if (character.modifiedFinesse != character.finesse)
                    dojo.addClass(finesseEl, '_7sfs-modified-stat-value');
                else
                    dojo.removeClass(finesseEl, '_7sfs-modified-stat-value');
            }

            const influenceEl = $(`${character.divId}_influence_value`);
            if (influenceEl)
            {
                influenceEl.innerHTML = character.modifiedInfluence;
                if (character.modifiedInfluence != character.influence)
                    dojo.addClass(influenceEl, '_7sfs-modified-stat-value');
                else
                    dojo.removeClass(influenceEl, '_7sfs-modified-stat-value');
            }

            const attachmentCount = character.attachedCards?.length ?? 0;
            characterElement.style.setProperty('--attachment-count', attachmentCount);
            if (attachmentCount === 0)
            {
                dojo.removeClass(character.divId, '_7sfs-attachment-container');
            }

            character.attachedCards?.forEach((att) => {
                if (att.divId && $(att.divId))
                {
                    $(att.divId).style.setProperty('--attachment-index', att.attachmentIndex);
                }
            });
        }

        // WHY: attachedToId is already null, so createAttachmentCard places as a
        // sibling ('before') without _7sfs-attached-card absolute positioning —
        // card sits in the city row in front of the character for the discard fly.
        const anchorId = characterElement
            ? character.divId
            : this.getTargetElementForLocation(
                character.location || attachment.location,
                character.controllerId || attachment.controllerId
            );
        if (anchorId && $(anchorId))
        {
            const divId = this.createCardId(
                attachment,
                character.location || attachment.location
            );
            this.createCard(divId, attachment, anchorId);
        }
    },

    notif_factionResolveCardDraw: async function( notif )
    {
        debug( 'notif_factionResolveCardDraw' );
        debug( notif );

        // WHY: Unhide + float before add so HandStock lays out against a visible
        // container and the destination rect for the slide is correct (same as notif_drawCard).
        dojo.removeClass('factionHand-placeholder', 'hidden');
        if (this.checkFloatingHand) {
            this.checkFloatingHand();
        }

        notif.args.cards.forEach((card) => {
            this.addCardToDeck(this.factionHand, card);
        });

        // WHY: Same seal FLIP as notif_drawCard — hand cards only get their
        // background after addCardToDeck, so animate the already-styled elements.
        // Parallel for multi-draw (panache) so total wait stays ~500ms.
        if (this.animationManager && this.animationManager.animationsActive()) {
            const animations = [];
            notif.args.cards.forEach((card) => {
                const cardElement = this.factionHand.getCardElement(card);
                if (cardElement) {
                    animations.push(this.animateCardFromPlayerSeal(cardElement, this.player_id));
                }
            });
            if (animations.length > 0) {
                await Promise.all(animations);
            }
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
            // WHY: Constanzo (setupTable_01006) adds a thug to hand during setup,
            // when the hand is still hidden. Reveal before addCard so HandStock
            // lays out against a visible container — adding into display:none
            // leaves the card invisible even after a later unhide. The normal
            // panache draw path unhides in notif_factionResolveCardDraw instead.
            dojo.removeClass('factionHand-placeholder', 'hidden');
            this.addCardToDeck(this.factionHand, notif.args.card);
            if (this.checkFloatingHand) {
                this.checkFloatingHand();
            }
        }

        $(`${notif.args.player_id}-score-hand-count`).innerHTML = notif.args.handCount;
    },

    notif_drawCard: async function( notif )
    {
        debug( 'notif_drawCard' );
        debug( notif );

        const args = notif.args;

        const card = args.card;
        this.cardProperties[card.id] = card;

        // WHY: Unhide + float before add so HandStock lays out against a visible
        // container and the destination rect for the slide is correct.
        dojo.removeClass('factionHand-placeholder', 'hidden');
        if (this.checkFloatingHand) {
            this.checkFloatingHand();
        }

        this.addCardToDeck(this.factionHand, card);

        // WHY: Same gate/pattern as notif_cardMoved —
        // FLIP-animate the already-styled hand card from the score-panel seal.
        // bga-cards addCard({ fromElement }) looked like no animation because
        // applyFactionHandCardStyle (background image) runs after create, so the
        // library was sliding a transparent empty card.
        await this.animateCardFromPlayerSeal(this.factionHand.getCardElement(card), this.player_id);

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

            // WHY: Fly into the city discard icon (no reparent — pile is a tiny marker).
            await this.animateCardToElement(cardElement, $('city-discard'), { duration: 400 });

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
        if ( ! card)
        {
            return;
        }

        card.location = this.LOCATION_PLAYER_DISCARD;

        const cardElement = card.divId ? $(card.divId) : null;
        const discardElement = $(`${args.playerId}-discard`);

        // WHY: Same fly+shrink as city discard — attachmentUnequipped leaves the
        // card as a city-row sibling so this has a real from-rect to animate from.
        if (cardElement && discardElement)
        {
            await this.animateCardToElement(cardElement, discardElement, { duration: 400 });
        }
        else if (cardElement && this.animationManager && this.animationManager.animationsActive())
        {
            await cardElement.animate([
                { transform: 'scale(1)', opacity: 1 },
                { transform: 'scale(0)', opacity: 0 }
            ], {
                duration: 400,
                easing: 'ease-in'
            }).finished;
        }

        if (card.divId)
        {
            dojo.destroy(card.divId);
            card.divId = null;
        }

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
        if (args.controllerId !== undefined) {
            card.controllerId = args.controllerId;
        }

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

    notif_characterRecruited: async function( notif )
    {
        debug( 'notif_characterRecruited' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        const oldDivId = card.divId;
        const oldElement = $(oldDivId);

        card.controllerId = args.player_id;

        // WHY: Recruiting claims a city merc — for the recruiting player the
        // target switches from the right of the city-image to my-cards on the
        // left. Reuse slideAndAttach (same as notif_cardMoved) so the card
        // visibly crosses the city-image instead of teleporting.
        const cardId = this.createCardId(card, card.location);
        const targetId = this.getTargetElementForLocation(card.location, card.controllerId);
        const targetElement = $(targetId);

        if (oldElement && targetElement && this.animationManager && this.animationManager.animationsActive()) {
            await this.animationManager.slideAndAttach(oldElement, targetElement);
        }

        dojo.destroy(oldDivId);
        this.createCard(cardId, card, targetId);
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
            const lockerElement = $(`${args.playerId}-locker`);

            // WHY: Fly into the player locker icon; collapse width so city-row siblings don't jump on destroy.
            if (cardElement && lockerElement && this.animationManager && this.animationManager.animationsActive()) {
                if (cardImage) {
                    cardImage.style.transition = 'none';
                }

                await Promise.all([
                    this.animateCardToElement(cardElement, lockerElement, { duration: 400 }),
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

    notif_characterResolveModified: function( notif )
    {
        debug( 'notif_characterResolveModified' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.characterId];
        card.modifiedResolve = args.newResolve;

        const element = $(`${card.divId}_resolve_value`);
        element.innerHTML = card.modifiedResolve;
        // WHY: Wounds also mark Resolve as modified visually (see createCard / wound notifs).
        if (card.modifiedResolve != card.resolve || card.wounds > 0)
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
            const lockerElement = $(`${args.playerId}-locker`);

            // WHY: Fly into the player locker icon; collapse width so row siblings don't jump on destroy.
            if (cardElement && lockerElement && this.animationManager && this.animationManager.animationsActive()) {
                if (cardImage) {
                    cardImage.style.transition = 'none';
                }

                await Promise.all([
                    this.animateCardToElement(cardElement, lockerElement, { duration: 400 }),
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

    notif_cardSentToCityLocker: function( notif )
    {
        debug( 'notif_cardSentToCityLocker' );
        debug( notif );

        const args = notif.args;
        this.gamedatas.cityLocker.push(args.card);
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

        // WHY: City deck lives under the UL tower — FLIP from that origin (same math as seal draws).
        await this.animateCardFromElement($(cardId), $('city-ul-tower'), { duration: 400 });
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

    notif_renownAddedToLocation: async function( notif )
    {
        debug( 'notif_renownAddedToLocation' );
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

    notif_renownRemovedFromLocation: async function( notif )
    {
        debug( 'notif_renownRemovedFromLocation' );
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

        // Hide turn-order badge on the new first player; restore it on everyone else (>2 player games only).
        // Refresh each badge value from args.turnOrders so the rotation is reflected.
        const turnOrderBadgesToPulse = [];
        if (Object.keys(this.gamedatas.players).length > 2) {
            for (const pid in this.gamedatas.players) {
                if (args.turnOrders && args.turnOrders[pid] !== undefined) {
                    const badge = $(`${pid}-score-turn-order`);
                    if (badge) badge.innerHTML = args.turnOrders[pid];
                    this.gamedatas.players[pid].turn_order = args.turnOrders[pid];
                }
                const isNewFirst = pid == args.playerId;
                dojo.style(`${pid}-score-turn-order`, 'display', isNewFirst ? 'none' : 'inline-block');
                if (!isNewFirst) {
                    turnOrderBadgesToPulse.push($(`${pid}-score-turn-order`));
                }
            }
        }

        // Pulse the first player elements
        const homeElement = $(`${args.playerId}-first-player`);
        const scoreElement = $(`${args.playerId}-score-seal-first-player`);

        const animations = [];
        const pulseKeyframes = [
            { transform: 'scale(1)' },
            { transform: 'scale(1.3)' },
            { transform: 'scale(1)' }
        ];
        const pulseOptions = { duration: 400, easing: 'ease-in-out' };
        const animationsActive = this.animationManager && this.animationManager.animationsActive();
        if (homeElement && animationsActive) {
            animations.push(homeElement.animate(pulseKeyframes, pulseOptions).finished);
        }
        if (scoreElement && animationsActive) {
            animations.push(scoreElement.animate(pulseKeyframes, pulseOptions).finished);
        }
        if (animationsActive) {
            for (const badge of turnOrderBadgesToPulse) {
                if (badge) animations.push(badge.animate(pulseKeyframes, pulseOptions).finished);
            }
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
        this.refreshTooltipForCard(card);
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
            this.refreshTooltipForCard(card);
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
        this.refreshTooltipForCard(card);
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
            this.refreshTooltipForCard(card);
        }
    },

    notif_silverSpineAbilityUsed: function( notif )
    {
        debug( 'notif_silverSpineAbilityUsed' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions.push(this.SILVER_SPINE_ABILITY_USED);

            const imageElement = dojo.query('._7sfs-card', card.divId)[0];
            const id = `${card.divId}_silver_spine_ability_used`;
            dojo.place( this.format_block( 'jstpl_generic_chip', {
                id: id,
                class: '_7sfs-silver-spine-ability-used-chip',
            }),  imageElement, 'last');

            this.addTippyTooltip( id, `<div class='_7sfs-basic-tooltip'>${_("Silver Spine's once-per-Day ability has been used")}</div>` );
            this.refreshTooltipForCard(card);
        }
    },

    notif_silverSpineAbilityRemoved: function( notif )
    {
        debug( 'notif_silverSpineAbilityRemoved' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.SILVER_SPINE_ABILITY_USED);

            // WHY card.divId, not args.cardId: the chip was placed with id
            // `${card.divId}_silver_spine_ability_used`. card.divId is the full
            // DOM id (e.g. `${controllerId}-${cardId}`), not the bare card id —
            // using args.cardId here silently no-ops because the element doesn't
            // exist under that id.
            const id = `${card.divId}_silver_spine_ability_used`;
            dojo.destroy(id);
            this.refreshTooltipForCard(card);
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
            this.refreshTooltipForCard(card);
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
            this.refreshTooltipForCard(card);
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
        this.refreshTooltipForCard(card);
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

            const id = `${card.divId}_indomitable_will_condition`;
            dojo.destroy(id);
            this.refreshTooltipForCard(card);
        }
    },

    notif_contemptAndHatredConditionStarted: function( notif )
    {
        debug( 'notif_contemptAndHatredConditionStarted' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            if (!card.conditions.includes(this.CONTEMPT_AND_HATRED_CONDITION))
                card.conditions.push(this.CONTEMPT_AND_HATRED_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_contemptAndHatredConditionEnded: function( notif )
    {
        debug( 'notif_contemptAndHatredConditionEnded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.CONTEMPT_AND_HATRED_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_solineElGatoConditionStarted: function( notif )
    {
        debug( 'notif_solineElGatoConditionStarted' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            if (!card.conditions.includes(this.SOLINE_EL_GATO_CONDITION))
                card.conditions.push(this.SOLINE_EL_GATO_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_solineElGatoConditionEnded: function( notif )
    {
        debug( 'notif_solineElGatoConditionEnded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.SOLINE_EL_GATO_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_epeeSanglanteConditionStarted: function( notif )
    {
        debug( 'notif_epeeSanglanteConditionStarted' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            if (!card.conditions.includes(this.EPEE_SANGLANTE_CONDITION))
                card.conditions.push(this.EPEE_SANGLANTE_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_epeeSanglanteConditionEnded: function( notif )
    {
        debug( 'notif_epeeSanglanteConditionEnded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.EPEE_SANGLANTE_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_forgedForBattleConditionStarted: function( notif )
    {
        debug( 'notif_forgedForBattleConditionStarted' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            if (!card.conditions.includes(this.FORGED_FOR_BATTLE_CONDITION))
                card.conditions.push(this.FORGED_FOR_BATTLE_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_forgedForBattleConditionEnded: function( notif )
    {
        debug( 'notif_forgedForBattleConditionEnded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.FORGED_FOR_BATTLE_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_harpoonConditionStarted: function( notif )
    {
        debug( 'notif_harpoonConditionStarted' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            if (!card.conditions.includes(this.HARPOON_CONDITION))
                card.conditions.push(this.HARPOON_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_harpoonConditionEnded: function( notif )
    {
        debug( 'notif_harpoonConditionEnded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.HARPOON_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_lodestoneConditionStarted: function( notif )
    {
        debug( 'notif_lodestoneConditionStarted' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            if (!card.conditions.includes(this.LODESTONE_CONDITION))
                card.conditions.push(this.LODESTONE_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_lodestoneConditionEnded: function( notif )
    {
        debug( 'notif_lodestoneConditionEnded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.LODESTONE_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_shacklesConditionStarted: function( notif )
    {
        debug( 'notif_shacklesConditionStarted' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            if (!card.conditions.includes(this.SHACKLES_CONDITION))
                card.conditions.push(this.SHACKLES_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_shacklesConditionEnded: function( notif )
    {
        debug( 'notif_shacklesConditionEnded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.SHACKLES_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_fatesSilenceConditionStarted: function( notif )
    {
        debug( 'notif_fatesSilenceConditionStarted' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            if (!card.conditions.includes(this.FATES_SILENCE_CONDITION))
                card.conditions.push(this.FATES_SILENCE_CONDITION);
            this.refreshTooltipForCard(card);
        }
    },

    notif_fatesSilenceConditionEnded: function( notif )
    {
        debug( 'notif_fatesSilenceConditionEnded' );
        debug( notif );

        const args = notif.args;
        const card = this.cardProperties[args.cardId];
        if (card)
        {
            card.conditions = card.conditions.filter(condition => condition !== this.FATES_SILENCE_CONDITION);
            this.refreshTooltipForCard(card);
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
        this.refreshTooltipForCard(card);
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
            this.refreshTooltipForCard(card);
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
            this.refreshTooltipForCard(card);
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
            card.conditions = card.conditions.filter(condition => condition !== this.CATS_EMBARGO_TARGET && condition !== this.OLD_CATS_EMBARGO_TARGET);

            const id = `${args.cardId}_cats_embargo_target`;
            dojo.destroy(id);
            this.refreshTooltipForCard(card);
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

        const existingChipId = `${imageElement.id}-location-control-chip`;
        if ($(existingChipId)) {
            dojo.destroy(existingChipId);
        }

        dojo.place( this.format_block( 'jstpl_location_control_chip', {
            id: imageElement.id,
            player_color: player.color,
        }),  imageElement, 'last');
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
        this.refreshTooltipForCard(challenger);

        const defender = this.cardProperties[args.defenderId];
        defender.conditions.push(this.DEFENDER);
        const defenderImage = $(`${defender.divId}_image`);
        const defenderChipId = `${defender.divId}_defender`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: defenderChipId,
            class: '_7sfs-defender-chip',
        }),  defenderImage, 'last');
        this.addTippyTooltip( defenderChipId, `<div class='_7sfs-basic-tooltip'>${_("Duel Defender")}</div>` );
        this.refreshTooltipForCard(defender);
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
        this.refreshTooltipForCard(oldChallenger);

        const newChallenger = this.cardProperties[args.newChallengerId];
        newChallenger.conditions.push(this.CHALLENGER);
        const challengerImage = $(`${newChallenger.divId}_image`);
        const challengerChipId = `${newChallenger.divId}_challenger`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: challengerChipId,
            class: '_7sfs-challenger-chip',
        }),  challengerImage, 'last');
        this.addTippyTooltip( challengerChipId, `<div class='_7sfs-basic-tooltip'>${_("Duel Challenger")}</div>` );
        this.refreshTooltipForCard(newChallenger);
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
        this.refreshTooltipForCard(oldDefender);

        const newDefender = this.cardProperties[args.newDefenderId];
        newDefender.conditions.push(this.DEFENDER);
        const defenderImage = $(`${newDefender.divId}_image`);
        const defenderChipId = `${newDefender.divId}_defender`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: defenderChipId,
            class: '_7sfs-defender-chip',
        }),  defenderImage, 'last');
        this.addTippyTooltip( defenderChipId, `<div class='_7sfs-basic-tooltip'>${_("Duel Defender")}</div>` );
        this.refreshTooltipForCard(newDefender);
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
        this.refreshTooltipForCard(oldTarget);

        const newTarget = this.cardProperties[args.newTargetId];
        newTarget.conditions.push(this.DEFENDER);
        const defenderImage = $(`${newTarget.divId}_image`);
        const defenderChipId = `${newTarget.divId}_defender`;
        dojo.place( this.format_block( 'jstpl_generic_chip', {
            id: defenderChipId,
            class: '_7sfs-defender-chip',
        }),  defenderImage, 'last');
        this.addTippyTooltip( defenderChipId, `<div class='_7sfs-basic-tooltip'>${_("Duel Defender")}</div>` );
        this.refreshTooltipForCard(newTarget);
    },

    notif_duelStarted: function( notif )
    {
        debug( 'notif_duelStarted' );
        debug( notif );

        const args = notif.args;

        this.inDuel = true;
        this.duelRound = 0;
        this.displayDuelTable(args.challengeStat);
        
        if (this.player_id == args.challengingPlayerId || this.player_id == args.defendingPlayerId)
        {
            // Move faction hand placeholder to bottom of duel rows
            dojo.place('factionHand-placeholder', 'duel_wrapper', 'after');
            // Re-check floating state after moving placeholder
            if (this.checkFloatingHand) this.checkFloatingHand();
        }
    },

    notif_duelStatChanged: function( notif )
    {
        debug( 'notif_duelStatChanged' );
        debug( notif );

        const args = notif.args;
        const target = $('duel_stat_value');
        if (!target) return;

        dojo.empty(target);
        const statClass = (args.duelStat || '').toLowerCase();
        dojo.place(`<div class="_7sfs-card-${statClass}-image"></div>`, target);
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
            if (!args.statsAddedToExistingCombatCard)
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

                // WHY the hand removal is not tied to args.gambled: that flag is per
                // duel round, not per card. Broken-Time (01077) stages an additional
                // combat card in the owner's hand (it has to, in case the player wants
                // to use a Maneuver on it), so in a round where the player also gambled
                // the extra card would stay visible in hand forever.
                //
                // WHY getCardElement and not a getCards() id comparison: getCardElement
                // resolves `factionhand-card-${id}` via the DOM, so it is immune to the
                // id arriving as a number here and a string in the stock. A genuinely
                // gambled card was never in hand, so it resolves to null and is skipped.
                if (this.player_id == combatCard.controllerId
                    && this.factionHand.getCardElement(combatCard))
                {
                    this.factionHand.removeCard(combatCard);
                }
                $(`${combatCard.controllerId}-score-hand-count`).innerHTML = args.handCount;
            }
        }
        else
        {
            var element = $(`duel_round_${args.round}_${args.mode}`);
            if (element)
            {
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

        // WHY starting chips: ThreatModified also updates starting_* (maneuver/technique
        // rebuild ending from starting). Keep the starting column in sync with the DB.
        if (args.starting_challenger_threat !== undefined)
        {
            $(`duel_round_${args.round}_starting_challenger_threat`).innerHTML = args.starting_challenger_threat;
            if (args.starting_challenger_threat > 0)
                dojo.addClass(`duel_round_${args.round}_starting_challenger_threat`, '_7sfs-threat-chip-threatened');
            else
                dojo.removeClass(`duel_round_${args.round}_starting_challenger_threat`, '_7sfs-threat-chip-threatened');
        }

        if (args.starting_defender_threat !== undefined)
        {
            $(`duel_round_${args.round}_starting_defender_threat`).innerHTML = args.starting_defender_threat;
            if (args.starting_defender_threat > 0)
                dojo.addClass(`duel_round_${args.round}_starting_defender_threat`, '_7sfs-threat-chip-threatened');
            else
                dojo.removeClass(`duel_round_${args.round}_starting_defender_threat`, '_7sfs-threat-chip-threatened');
        }

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
            this.refreshTooltipForCard(challenger);
        }

        const defender = this.cardProperties[args.defenderId];
        if (defender)
        {
            defender.conditions = defender.conditions.filter(condition => condition !== this.DEFENDER);
            const defenderChipId = `${defender.divId}_defender`;
            dojo.destroy(defenderChipId);
            this.refreshTooltipForCard(defender);
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
            this.refreshTooltipForCard(challenger);
        }

        const defender = this.cardProperties[args.defenderId];
        if (defender)
        {
            defender.conditions = defender.conditions.filter(condition => condition !== this.DEFENDER);
            const defenderChipId = `${defender.divId}_defender`;
            dojo.destroy(defenderChipId);
            this.refreshTooltipForCard(defender);
        }
    },

    notif_duelEnd: function( notif )
    {
        debug( 'notif_duelEnd' );
        debug( notif );

        const args = notif.args;

        this.inDuel = false;
        dojo.destroy('duel_wrapper');

        const challenger = this.cardProperties[args.challengerId];
        if (challenger)
        {
            challenger.conditions = challenger.conditions.filter(condition => condition !== this.CHALLENGER);
            const challengerChipId = `${challenger.divId}_challenger`;
            dojo.destroy(challengerChipId);
            this.refreshTooltipForCard(challenger);
        }

        const defender = this.cardProperties[args.defenderId];
        if (defender)
        {
            defender.conditions = defender.conditions.filter(condition => condition !== this.DEFENDER);
            const defenderChipId = `${defender.divId}_defender`;
            dojo.destroy(defenderChipId);
            this.refreshTooltipForCard(defender);
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

    notif_crabsInABucketUsedListUpdated: function( notif )
    {
        debug( 'notif_crabsInABucketUsedListUpdated' );
        debug( notif );

        const args = notif.args;
        this.displayCrabsInABucketUsedList(args.cardId, args.usedList);
    },

    notif_locationActionUsedListUpdated: function( notif )
    {
        debug( 'notif_locationActionUsedListUpdated' );
        debug( notif );

        const args = notif.args;
        this.displayLocationActionUsedList(args.actionId, args.locationName, args.usedList);
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