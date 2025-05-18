function playOverlayAnimation({ message, soundId, redirectTo }) {
    const overlay = document.getElementById('universal-overlay');
    const flash = document.getElementById('flash-overlay');
    const audio = document.getElementById(soundId);
    let i = 0;
  
    if (!overlay || !flash || !audio) return;
  
    overlay.textContent = '';
    overlay.classList.add('show');
    audio.play();
  
    const typewriter = setInterval(() => {
      if (i < message.length) {
        overlay.textContent += message[i];
        i++;
      } else {
        clearInterval(typewriter);
  
        setTimeout(() => {
          overlay.remove();
          flash.classList.add('show');
  
          setTimeout(() => {
            window.location.href = redirectTo;
          }, 700);
        }, 1000);
      }
    }, 100);
  }
  