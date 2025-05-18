<?php
session_start();
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/check_role.php';

checkRole('admin');
require_user_login();
require_once '../../includes/head.php';
require_once '../../includes/header.php';?>
<?php require_once '../../includes/navbar.php'; ?>
<head>
    <title>Historique des actions admin</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/font-awesome.min.css">
    <link rel="stylesheet" href="../../assets/css/fontawesome.min.css">
    <link rel="stylesheet" href="../../assets/css/solid.min.css">
    <link rel="stylesheet" href="../../assets/css/brands.min.css">
    <link rel="stylesheet" href="../../assets/css/all.min.css">
    <script src="../../assets/js/jquery.min.js"></script>
    <script src="../../assets/js/script.js"></script>
</head>

<style>
.container {
    max-width: 1200px;
    margin: 50px auto;
}

.logs-header {
    background: rgba(255,255,255,0.05);
    border: 1px solid rgba(255,165,0,0.2);
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    color: white;
    text-align: center;
}

.filters {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    position: relative;
}

.filters select, .filters button {
    padding: 10px;
    border-radius: 10px;
    background: rgba(92, 92, 92, 0.11);
    color: rgb(255, 255, 255);
    border: none;
}

.filters select {
    color: rgb(255, 255, 255);
}

.filters select option {
    color: white;
    background: rgba(20,20,20,0.9);
}

.logs-table {
    width: 100%;
    color: white;
    border-collapse: collapse;
}

.logs-table th, .logs-table td {
    padding: 12px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.logs-table thead {
    background: rgba(255,165,0,0.1);
}

.action-badge {
    padding: 5px 10px;
    border-radius: 8px;
    font-size: 0.9em;
}

.ban { background: rgba(255,0,0,0.2); color: #ff5555; }
.unban { background: rgba(0,255,0,0.2); color: #00ff88; }
.promote { background: rgba(0,128,255,0.2); color: #55aaff; }
.demote { background: rgba(44, 44, 44, 0.83); color:rgba(182, 182, 182, 0.63); }
.warn { background: rgba(255,255,0,0.2); color: yellow; }
.unknown { background: rgba(128,128,128,0.2); color: grey; }

#autocomplete-list div:hover {
    background: rgba(255, 165, 0, 0.2);
    color: #fff;
}

#loading-bar {
    position: fixed;
    top: 0;
    left: 0;
    height: 3px;
    background: linear-gradient(to right, #ffa500, #ff5555);
    width: 0%;
    transition: width 0.4s ease;
    z-index: 9999;
}

.toast {
    display: none;
    position: fixed;
    bottom: 30px;
    right: 30px;
    padding: 15px 25px;
    border-radius: 8px;
    font-weight: bold;
    z-index: 99;
}

.toast.success {
    background-color: #28a745;
    color: white;
}

.toast.error {
    background-color: #dc3545;
    color: white;
}

@keyframes toastFadeIn {
    0% { opacity: 0; }
    100% { opacity: 1; }
}

#notif-toast {
    animation: toastFadeIn 0.5s ease-out;
    display: block;
}
</style>

<div class="container">
    <div class="logs-header">
        <?php if (!empty($_GET['user'])): ?>
            <h2>🗂️ Historique des actions pour <span style="color: #ffa500;"><?= htmlspecialchars($_GET['user']) ?></span></h2>
        <?php else: ?>
            <h2>🗂️ Historique des actions admin</h2>
        <?php endif; ?>
    </div>

    <form method="get" class="filters">
        <div style="position: relative;">
            <input type="text" name="search_user" id="search_user" placeholder="🔍 Rechercher un utilisateur..." value="<?= htmlspecialchars($_GET['search_user'] ?? '') ?>" autocomplete="off" style="padding: 10px; border-radius: 12px; background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 165, 0, 0.25); color: #ffccaa; backdrop-filter: blur(6px); font-weight: bold; transition: all 0.3s ease; width: 250px;">
            <div id="autocomplete-list" style="position: absolute; top: 100%; left: 0; right: 0; background: rgba(20, 20, 20, 0.95); border-radius: 12px; overflow: hidden; margin-top: 5px; z-index: 999; max-height: 200px; overflow-y: auto;"></div>
        </div>

        <select name="action_type">
            <option value="">Toutes les actions</option>
            <option value="ban_user" <?= ($_GET['action_type'] ?? '') === 'ban_user' ? 'selected' : '' ?>>🛑 Bans</option>
            <option value="unban_user" <?= ($_GET['action_type'] ?? '') === 'unban_user' ? 'selected' : '' ?>>✅ Débans</option>
            <option value="promote_user" <?= ($_GET['action_type'] ?? '') === 'promote_user' ? 'selected' : '' ?>>🛡️ Promos admin</option>
            <option value="demote_user" <?= ($_GET['action_type'] ?? '') === 'demote_user' ? 'selected' : '' ?>>🔻 Rétrogradations</option>
            <option value="warn_user" <?= ($_GET['action_type'] ?? '') === 'warn_user' ? 'selected' : '' ?>>⚠️ Avertissements</option>
        </select>

        <select name="period">
            <option value="">Toute période</option>
            <option value="today" <?= ($_GET['period'] ?? '') === 'today' ? 'selected' : '' ?>>Aujourd'hui</option>
            <option value="week" <?= ($_GET['period'] ?? '') === 'week' ? 'selected' : '' ?>>Cette semaine</option>
            <option value="month" <?= ($_GET['period'] ?? '') === 'month' ? 'selected' : '' ?>>Ce mois</option>
        </select>

        <select name="user_type" id="user_type">
            <option value="">Type d'utilisateur</option>
            <option value="user" <?= ($_GET['user_type'] ?? '') === 'user' ? 'selected' : '' ?>>Utilisateur normal</option>
            <option value="admin" <?= ($_GET['user_type'] ?? '') === 'admin' ? 'selected' : '' ?>>Administrateur</option>
        </select>

        <select name="specific_user" id="specific_user">
            <option value="">Sélectionner un utilisateur</option>
            <?php
            if (!empty($_GET['user_type'])) {
                $userType = $_GET['user_type'];
                if ($userType === 'admin') {
                    $stmtUsers = $pdo->prepare("SELECT username FROM users WHERE role = 'admin' ORDER BY username ASC");
                } elseif ($userType === 'user') {
                    $stmtUsers = $pdo->prepare("SELECT username FROM users WHERE role = 'user' OR role IS NULL OR role = '' ORDER BY username ASC");
                }

                $stmtUsers->execute();
                $userList = $stmtUsers->fetchAll();

                foreach ($userList as $user) {
                    $selected = (($_GET['specific_user'] ?? '') === $user['username']) ? 'selected' : '';
                    echo "<option value=\"" . htmlspecialchars($user['username']) . "\" $selected>" . htmlspecialchars($user['username']) . "</option>";
                }
            }
            ?>
        </select>

        <div style="display: flex; gap: 10px;">
<button type="button" id="resetBtn" style="background: rgba(255,50,50,0.2);">Reset</button>
        </div>
    </form>

    <?php
    $where = [];
    $params = [];

    if (!empty($_GET['search_user'])) {
        $where[] = "target_username LIKE :search_user";
        $params[':search_user'] = '%' . $_GET['search_user'] . '%';
    }

    if (!empty($_GET['specific_user'])) {
        $where[] = "target_username = :specific_user";
        $params[':specific_user'] = $_GET['specific_user'];
    } elseif (!empty($_GET['user'])) {
        $where[] = "target_username = :user";
        $params[':user'] = $_GET['user'];
    }

    if (!empty($_GET['action_type'])) {
        $where[] = "action_type = :action_type";
        $params[':action_type'] = $_GET['action_type'];
    }

    if (!empty($_GET['period'])) {
        switch ($_GET['period']) {
            case 'today':
                $where[] = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $where[] = "YEARWEEK(created_at, 1) = YEARWEEK(NOW(), 1)";
                break;
            case 'month':
                $where[] = "YEAR(created_at) = YEAR(NOW()) AND MONTH(created_at) = MONTH(NOW())";
                break;
        }
    }

    $sql = "SELECT * FROM admin_logs";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY created_at DESC LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll();
    ?>

    <?php if (empty($logs)): ?>
        <p style="color:white; text-align:center;">Aucune action trouvée pour ces filtres.</p>
    <?php else: ?>
        <?php if (!empty($_GET['user']) || !empty($_GET['specific_user'])): ?>
            <p style="text-align:center; margin-bottom:20px;">
        <a href="logs.php" style="color:#00ffcc; font-weight:bold;">🔙 Voir tous les logs</a>
    </p>
<?php endif; ?>

<p style="color: #ffa500; text-align: center; margin-bottom: 20px;">
    <?= count($logs) ?> résultat<?= count($logs) > 1 ? 's' : '' ?> trouvé<?= count($logs) > 1 ? 's' : '' ?>.
</p>

        <table class="logs-table">
            <thead>
                <tr>
                    <th> Admin</th>
                    <th>🛠️ Action</th>
                    <th> Utilisateur visé</th>
                    <th>📅 Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['admin_username']) ?></td>
                        <td>
                            <?php
                            $action = match($log['action_type']) {
                                'ban_user' => '<span class="action-badge ban">🚫 Bannissement</span>',
                                'unban_user' => '<span class="action-badge unban">✅ Débannissement</span>',
                                'promote_user' => '<span class="action-badge promote">🚀 Promotion</span>',
                                'demote_user' => '<span class="action-badge demote">🔻 Rétrogradation</span>',
                                'warn_user' => '<span class="action-badge warn">⚠️ Avertissement</span>',
                                default => '<span class="action-badge unknown">❓ Inconnue</span>',
                            };
                            echo $action;
                            ?>
                        </td>
                        <td>
    <a href="logs.php?user=<?= urlencode($log['target_username']) ?>" style="color:#ffa500; text-decoration:none;">
        <?= htmlspecialchars($log['target_username']) ?>
    </a>
</td>
                        <td><?= date('d/m/Y H:i', strtotime($log['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
    
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.filters');
    let logsContainer = document.querySelector('.logs-table').parentNode;
    const searchInput = document.getElementById('search_user');
    const autocompleteList = document.getElementById('autocomplete-list');
    const loadingBar = document.getElementById('loading-bar');

    function reloadLogs(params = '') {
    const tbody = document.querySelector('.logs-table tbody');
    // 🔥 Vider immédiatement en mettant "Chargement..."
    tbody.innerHTML = `
        <tr>
            <td colspan="4" style="padding: 20px; color: #aaa; font-size: 16px; text-align: center;">
                ⏳ Chargement des logs...
            </td>
        </tr>
    `;

    loadingBar.style.width = '0%';
    loadingBar.style.display = 'block';
    setTimeout(() => loadingBar.style.width = '50%', 100);

    fetch('logs.php' + (params ? '?' + params : ''))
        .then(r => r.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newTbody = doc.querySelector('.logs-table tbody');

            if (newTbody && newTbody.children.length > 0) {
                tbody.innerHTML = newTbody.innerHTML;
            } else {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="4" style="padding: 30px; color: #ffa500; font-weight: bold; font-size: 18px; text-align: center;">
                            🚫 Aucun log trouvé pour cet utilisateur.
                        </td>
                    </tr>
                `;
            }

            loadingBar.style.width = '100%';
            setTimeout(() => loadingBar.style.display = 'none', 400);

            bindFilterButtons();
        })
        .catch(error => {
            console.error(error);
            loadingBar.style.width = '100%';
            setTimeout(() => loadingBar.style.display = 'none', 400);
        });
}


    function loadUsers(role) {
    fetch('fetch_users_by_role.php?role=' + encodeURIComponent(role))
        .then(r => r.json())
        .then(users => {
            const specificUserSelect = document.getElementById('specific_user');
            specificUserSelect.innerHTML = '<option value="">Sélectionner un utilisateur</option>';
            users.forEach(u => {
                const opt = document.createElement('option');
                opt.value = u;
                opt.textContent = u;
                specificUserSelect.appendChild(opt);
            });

            // 🔥 après avoir rechargé les utilisateurs, recharger aussi les logs avec la sélection
            const params = new URLSearchParams(new FormData(document.querySelector('.filters'))).toString();
            reloadLogs(params);
        })
        .catch(console.error);
}

    function bindFilterButtons() {
        const filterBtn = document.getElementById('filterBtn');
        const resetBtn = document.getElementById('resetBtn');
        const userTypeSelect = document.getElementById('user_type');
        const form = document.querySelector('.filters');



        resetBtn.addEventListener('click', () => {
            form.reset();
            reloadLogs();
        });

        const allSelects = form.querySelectorAll('select');

allSelects.forEach(select => {
    select.addEventListener('change', () => {
        const params = new URLSearchParams(new FormData(form)).toString();
        reloadLogs(params);
    });
});


userTypeSelect.addEventListener('change', function(e) {
    const role = e.target.value;
    if (role) {
        loadUsers(role);
    } else {
        // Si aucun type sélectionné, on vide aussi la liste
        const specificUserSelect = document.getElementById('specific_user');
        specificUserSelect.innerHTML = '<option value="">Sélectionner un utilisateur</option>';
    }
});

    }

    bindFilterButtons(); // 1ère fois au chargement
    if (userTypeSelect.value) {
        loadUsers(userTypeSelect.value);
    }

    // 🔥 Pour que changer d'utilisateur recharge immédiatement les logs aussi
document.getElementById('specific_user').addEventListener('change', function() {
    const params = new URLSearchParams(new FormData(document.querySelector('.filters'))).toString();
    reloadLogs(params);
});

    // Gestion de l'autocomplete
    searchInput.addEventListener('input', function() {
        const query = this.value;
        if (query.length < 2) return autocompleteList.innerHTML = '';
        fetch('search_users.php?q=' + encodeURIComponent(query))
            .then(r => r.json())
            .then(data => {
                autocompleteList.innerHTML = '';
                data.forEach(user => {
                    const div = document.createElement('div');
                    div.textContent = user;
                    div.style.padding = '10px';
                    div.style.cursor = 'pointer';
                    div.style.borderBottom = '1px solid rgba(255,165,0,0.2)';
                    div.addEventListener('click', () => {
                        searchInput.value = user;
                        autocompleteList.innerHTML = '';
                    });
                    autocompleteList.appendChild(div);
                });
            })
            .catch(console.error);
    });

    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !autocompleteList.contains(e.target)) {
            autocompleteList.innerHTML = '';
        }
    });
    // 🔥 Quand la page se recharge, si user_type est déjà sélectionné, on recharge la liste d'utilisateurs automatiquement
const userTypeSelectInit = document.getElementById('user_type');
if (userTypeSelectInit && userTypeSelectInit.value) {
    loadUsers(userTypeSelectInit.value);
}

});
</script
