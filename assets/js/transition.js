document.addEventListener('DOMContentLoaded', () => {
  const transition = document.getElementById('page-transition');
  const app = document.getElementById('app');

  function waitForRegisterScript(retries = 10) {
    const rawScript = document.getElementById("register-script-content");
    if (!rawScript) {
      if (retries > 0) {
        setTimeout(() => waitForRegisterScript(retries - 1), 100);
      }
      return;
    }

    if (!rawScript.textContent.includes("initRegisterPage")) return;

    try {
      // Encapsule le script dans une IIFE pour éviter les conflits de variables globales
      (function () {
        eval(rawScript.textContent);
      })();
    } catch (e) {
      console.error("Erreur dans le script register :", e);
      return;
    }

    function tryInitCall(retry = 5) {
      setTimeout(() => {
        if (typeof initRegisterPage === "function") {
          initRegisterPage();
        } else if (retry > 0) {
          tryInitCall(retry - 1);
        }
      }, 25);
    }

    tryInitCall();
  }

  function startTransitionOut() {
    const audio = new Audio('/ProjetPrison/assets/sounds/transition.mp3');
    audio.volume = 0.3;
    audio.play().catch(() => {});
    document.body.classList.add('transition-out');
    transition.style.display = 'block';

    const glow = document.getElementById('glow-border-overlay');
    if (glow) {
      glow.style.transition = 'none';
      glow.style.opacity = '0.7';
      setTimeout(() => {
        glow.style.transition = 'opacity 1.5s ease';
        glow.style.opacity = '0';
      }, 2500);
    }
  }

  function endTransitionIn() {
    document.body.classList.remove('transition-out');
    transition.style.display = 'none';
  }

  function isIndex(href) {
    return href.endsWith('/index.php') || href === '/ProjetPrison/' || href === '/ProjetPrison/index.php';
  }

  function loadQuotesIfNeeded(href) {
    if (isIndex(href) && typeof initQuotes === 'function') {
      setTimeout(initQuotes, 0);
    }
  }

  function reloadInlineScripts(container) {
    container.querySelectorAll("script").forEach(oldScript => {
      const newScript = document.createElement("script");
      if (oldScript.src) {
        newScript.src = oldScript.src;
      } else {
        newScript.textContent = oldScript.textContent;
      }
      oldScript.replaceWith(newScript);
    });
  }

  function loadAndRunScriptOnce(path, globalInitFunction, fileName) {
    const existing = document.querySelector(`script[src*="${fileName}"]`);
    if (existing) existing.remove();

    const script = document.createElement('script');
    script.src = path;
    script.onload = () => {
      if (typeof window[globalInitFunction] === 'function') {
        console.log(`✅ ${globalInitFunction} chargé après transition`);
        window[globalInitFunction]();
      }
    };
    document.body.appendChild(script);
  }

  document.addEventListener('click', function (e) {
    const link = e.target.closest('a[href]');
    if (!link) return;

    const url = link.getAttribute('href');
    if (
      url.startsWith('#') ||
      url.startsWith('javascript') ||
      url.includes('logout') ||
      link.hasAttribute('download') ||
      link.target === '_blank'
    ) return;

    e.preventDefault();
    startTransitionOut();

    setTimeout(() => {
      fetch(url)
        .then(res => res.text())
        .then(html => {
          const parser = new DOMParser();
          const doc = parser.parseFromString(html, 'text/html');
          const newContent = doc.getElementById('app');

          if (newContent) {
            app.innerHTML = newContent.innerHTML;
            reloadInlineScripts(app);

            const currentPath = new URL(url, window.location.origin).pathname;

            if (currentPath.endsWith('validate_post.php')) {
              loadAndRunScriptOnce('/ProjetPrison/assets/js/validate-post.js', 'initValidatePostPage', 'validate-post.js');
            }

            if (currentPath.endsWith('notifications.php')) {
              loadAndRunScriptOnce('/ProjetPrison/assets/js/notifications.js', 'initNotificationsPage', 'notifications.js');
            }

            const rawScript = newContent.querySelector('#register-script-content');
            if (rawScript && !document.getElementById('register-script-content')) {
              const injectedScript = document.createElement('script');
              injectedScript.id = 'register-script-content';
              injectedScript.type = 'text/template';
              injectedScript.textContent = rawScript.textContent;
              document.body.appendChild(injectedScript);
              setTimeout(waitForRegisterScript, 50);
            }

            history.pushState(null, '', url);
            loadQuotesIfNeeded(url);
          } else {
            window.location.href = url;
          }
        })
        .catch(() => window.location.href = url)
        .finally(() => endTransitionIn());
    }, 400);
  });

  window.addEventListener('popstate', () => {
    fetch(location.href)
      .then(res => res.text())
      .then(html => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const newContent = doc.getElementById('app');
        if (newContent) {
          app.innerHTML = newContent.innerHTML;
          reloadInlineScripts(app);

          setTimeout(() => {
            if (typeof initValidatePostPage === "function") initValidatePostPage();
          }, 0);

          loadQuotesIfNeeded(location.href);
        } else {
          window.location.reload();
        }
      });
  });

  setTimeout(() => {
    transition.style.display = 'none';
  }, 400);

  loadQuotesIfNeeded(location.href);
  setTimeout(waitForRegisterScript, 50);
});
