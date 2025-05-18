document.addEventListener('DOMContentLoaded', function () {
  const addForm = document.getElementById('form-ajout');
  const messageBox = document.getElementById('message-box');
  const table = document.getElementById('stock-table');

  // 🔁 Affiche un message temporaire
  function showMessage(text, isSuccess = true) {
    messageBox.textContent = text;
    messageBox.style.color = isSuccess ? 'lime' : 'red';
    messageBox.style.display = 'block';
    setTimeout(() => { messageBox.style.display = 'none'; }, 3000);
  }

  // ✅ Ajout d’objet
  if (addForm) {
    addForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const formData = new FormData(addForm);

      fetch('ajax/traitement_ajout_objet.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showMessage("✅ Objet ajouté !");
          setTimeout(() => location.reload(), 800);
        } else {
          showMessage(data.error || "❌ Erreur lors de l’ajout.", false);
        }
      });
    });
  }

  // ✏️ Modification
  table.querySelectorAll('.btn-modif').forEach(btn => {
    btn.addEventListener('click', () => {
      const row = btn.closest('tr');
      const id = btn.dataset.id;
      const nom = row.querySelector('.input-nom').value;
      const desc = row.querySelector('.input-desc').value;
      const prix = row.querySelector('.input-prix').value;
      const interdit = row.querySelector('.input-interdit').checked ? 1 : 0;

      const formData = new URLSearchParams();
      formData.append('id', id);
      formData.append('nom', nom);
      formData.append('description', desc);
      formData.append('prix', prix);
      formData.append('interdit', interdit);

      fetch('ajax/modifier_objet.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData.toString()
      })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          showMessage("✅ Objet modifié !");
        } else {
          showMessage(data.error || "❌ Erreur lors de la modification.", false);
        }
      });
    });
  });

  // 🗑️ Suppression
table.querySelectorAll('.btn-suppr').forEach(btn => {
  btn.addEventListener('click', () => {
    if (!confirm("Supprimer cet objet ?")) return;

    const id = btn.dataset.id;
    console.log("🧪 ID à supprimer:", id); // ← Ajoute ceci pour voir ce qu’il vaut

    fetch('ajax/supprimer_objet.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'id=' + encodeURIComponent(id)
    })
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        showMessage("🗑️ Objet supprimé !");
        setTimeout(() => location.reload(), 800);
      } else {
        showMessage(data.error || "❌ Erreur lors de la suppression.", false);
      }
    })
    .catch(() => {
      showMessage("❌ Problème de communication avec le serveur.", false);
    });
  });
});

});
