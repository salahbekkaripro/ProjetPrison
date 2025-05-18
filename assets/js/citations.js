// ✅ citations.js complet avec fonction initQuotes()
function initQuotes() {
  const quoteEl = document.getElementById('quote');
  const countEl = document.getElementById('quote-count');
  const nextBtn = document.getElementById('next-quote');
  const prevBtn = document.getElementById('prev-quote');

  if (!quoteEl || !countEl || !nextBtn || !prevBtn) return;

  const quotes = [
    "« On peut enfermer le corps, mais jamais l’esprit. » – Nelson Mandela",
    "« La liberté, c’est ce que l’on fait avec ce qui nous a été fait. » – Jean-Paul Sartre",
    "« Derrière les barreaux, l’encre devient une clé. » – Inconnu",
    "« Celui qui ouvre une porte d’école, ferme une prison. » – Victor Hugo",
    "« La parole libère, même quand le silence enferme. » – Inconnu",
    "« Tant qu’il y aura de la mémoire, il y aura de l’espoir. » – Primo Levi",
    "« Même enchaîné, celui qui pense est libre. » – Sénèque",
    "« Les murs n’empêchent pas les mots de voler. » – Inconnu",
    "« Les nuits sont longues, mais les pensées plus fortes. » – Inconnu",
    "« L’homme libre est celui qui garde sa dignité, même en cage. » – Inconnu",
    "« Le silence en prison est plus bruyant que le vacarme dehors. » – Inconnu",
    "« L’obscurité n’efface pas les rêves. » – Inconnu",
    "« La lumière ne s’éteint pas quand on ferme la porte. » – Inconnu",
    "« Écrire, c’est respirer à travers les murs. » – Inconnu",
    "« L’isolement forge l’âme ou la brise. » – Inconnu",
    "« L’homme est né pour penser, même entre quatre murs. » – Inconnu",
    "« Une cellule ne contient pas une conscience éveillée. » – Inconnu",
    "« L’injustice est la prison de ceux qui ne l’acceptent pas. » – Inconnu",
    "« Les chaînes visibles ne sont rien à côté de celles qu’on ne voit pas. » – Inconnu",
    "« Le stylo est plus dangereux qu’une lime. » – Inconnu",
    "« L’attente est le mur le plus dur. » – Inconnu",
    "« En prison, le passé cogne plus fort que les murs. » – Inconnu",
    "« Le regard reste libre, même derrière les barreaux. » – Inconnu",
    "« La rage peut devenir écriture, la douleur devient pensée. » – Inconnu",
    "« Ce que la société rejette, la pensée le recueille. » – Inconnu"
  ];
  

  let currentIndex = 0;
  let interval;

  function updateQuote(index) {
    quoteEl.style.opacity = '0';
    quoteEl.style.transform = 'translateY(20px)';
    setTimeout(() => {
      quoteEl.textContent = quotes[index];
      countEl.textContent = `${index + 1} / ${quotes.length}`;
      quoteEl.style.opacity = '1';
      quoteEl.style.transform = 'translateY(0)';
    }, 250);
  }

  function showNext() {
    currentIndex = (currentIndex + 1) % quotes.length;
    updateQuote(currentIndex);
  }

  function showPrev() {
    currentIndex = (currentIndex - 1 + quotes.length) % quotes.length;
    updateQuote(currentIndex);
  }

  nextBtn.addEventListener('click', () => {
    clearInterval(interval);
    showNext();
    interval = setInterval(showNext, 8000);
  });

  prevBtn.addEventListener('click', () => {
    clearInterval(interval);
    showPrev();
    interval = setInterval(showNext, 8000);
  });

  updateQuote(currentIndex);
  interval = setInterval(showNext, 8000);
}

// Pour transition.js
window.initQuotes = initQuotes;
