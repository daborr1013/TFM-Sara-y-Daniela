const characters = [
    { image: '../../../media/images/jane.png', name: 'Jane Eyre' },
    { image: '../../../media/images/rochester.png', name: 'Sr. Rochester' },
    { image: '../../../media/images/bertha.png', name: 'Bertha Mason' },
    { image: '../../../media/images/helen.png', name: 'Helen Burns' },
    { image: '../../../media/images/diana.png', name: 'Diana Rivers' },
    { image: '../../../media/images/mary.png', name: 'Mary Rivers' },
    { image: '../../../media/images/johnRivers.png', name: 'St. John Rivers' },
    { image: '../../../media/images/ingram.png', name: 'Blanche Ingram' }
];
const totalPairs = characters.length;

let cards = [];
let flippedCards = [];
let pairsFound = 0;
let cardElements = [];
let gameActive = true;
let currentFocusIndex = -1;

function shuffle(array) {
    return array.slice().sort(() => Math.random() - 0.5);
}

function createCard(character, index) {
    const card = document.createElement('button');
    card.classList.add('card');
    card.setAttribute('aria-label', `Carta ${index + 1} - ${character.name}`);
    card.setAttribute('aria-pressed', 'false');
    card.setAttribute('type', 'button');

    const imageContainer = document.createElement('div');
    imageContainer.classList.add('symbol');
    
    const img = document.createElement('img');
    img.src = character.image;
    img.alt = character.name;
    
    imageContainer.appendChild(img);
    card.appendChild(imageContainer);

    card.addEventListener('click', () => flipCard(card, index));
    card.addEventListener('keydown', (e) => {
        if (e.key === ' ' || e.key === 'Enter') {
            e.preventDefault();
            flipCard(card, index);
        } else if (e.key === 'ArrowRight' || e.key === 'ArrowDown') {
            e.preventDefault();
            focusNextCard(index);
        } else if (e.key === 'ArrowLeft' || e.key === 'ArrowUp') {
            e.preventDefault();
            focusPreviousCard(index);
        }
    });
    
    return card;
}

function focusNextCard(currentIndex) {
    const nextIndex = (currentIndex + 1) % cardElements.length;
    cardElements[nextIndex].focus();
}

function focusPreviousCard(currentIndex) {
    const prevIndex = (currentIndex - 1 + cardElements.length) % cardElements.length;
    cardElements[prevIndex].focus();
}

function announceMessage(message) {
    const statusElement = document.getElementById('game-status');
    if (statusElement) {
        statusElement.textContent = message;
    }
}

function flipCard(card, index) {
    if (!gameActive || flippedCards.length >= 2 
        || flippedCards.includes(card) 
        || card.classList.contains('flipped')) {
        return;
    }

    card.classList.add('flipped');
    card.setAttribute('aria-pressed', 'true');
    flippedCards.push(card);

    if (flippedCards.length === 2) {
        gameActive = false;
        setTimeout(checkMatch, 1000);
    }
}

function checkMatch() {
    const [card1, card2] = flippedCards;
    const img1 = card1.querySelector('img').src; 
    const img2 = card2.querySelector('img').src;

    if (img1 === img2) {
        card1.setAttribute('disabled', 'disabled');
        card2.setAttribute('disabled', 'disabled');
        card1.setAttribute('aria-pressed', 'true');
        card2.setAttribute('aria-pressed', 'true');
        pairsFound++;
        document.getElementById('pairs-count').textContent = pairsFound;
        announceMessage(`¡Pareja encontrada! Parejas: ${pairsFound}/${totalPairs}`);

        if (pairsFound === totalPairs){
            gameActive = false;
            announceMessage('¡WOW! ¡Has encontrado todas las parejas! Presiona Reiniciar Juego para jugar de nuevo.');
            if (typeof guardarProgreso === 'function') {
                guardarProgreso(6, 100, 'Juego de Memoria');
            }
        }
    } else {
        card1.classList.remove('flipped');
        card2.classList.remove('flipped');
        card1.setAttribute('aria-pressed', 'false');
        card2.setAttribute('aria-pressed', 'false');
        announceMessage('No es pareja. Intenta de nuevo.');
    }

    flippedCards = [];
    gameActive = true;
}

function initializeGame() {
    cards = shuffle([...characters, ...characters]);
    pairsFound = 0;
    flippedCards = [];
    gameActive = true;
    cardElements = [];

    const gameContainer = document.querySelector('.memory-game');
    if(!gameContainer) return; // Prevents error if script loads early or on wrong page
    gameContainer.innerHTML = '';

    cards.forEach((character, index) => {
        const cardElement = createCard(character, index);
        gameContainer.appendChild(cardElement);
        cardElements.push(cardElement);
    });

    const pairsCountEl = document.getElementById('pairs-count');
    if(pairsCountEl) pairsCountEl.textContent = '0';
    announceMessage(`Juego iniciado. Total de parejas: ${totalPairs}. Usa las flechas para navegar y Enter o Espacio para seleccionar.`);
}

// Wait for DOM to be ready
document.addEventListener('DOMContentLoaded', () => {
    initializeGame();

    const restartBtn = document.getElementById('restart-btn');
    if (restartBtn) {
        restartBtn.addEventListener('click', () => {
            initializeGame();
            if (cardElements.length > 0) {
                cardElements[0].focus();
            }
        });
    }
});
