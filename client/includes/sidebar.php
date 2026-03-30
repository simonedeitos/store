<?php
$clientUser = getClientUser();
$station = getCurrentStation();
?>
<div class="client-sidebar" id="clientSidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo">
            <i class="bi bi-broadcast"></i>
            <span>AirDirector</span>
        </div>
    </div>

    <?php if ($station): ?>
    <div class="sidebar-station">
        <div class="station-name"><?= h($station['station_name']) ?></div>
        <div class="station-badge <?= true ? 'online' : 'offline' ?>">
            <span class="badge-dot"></span>
            <span data-lang="common.online">Online</span>
        </div>
    </div>
    <?php endif; ?>

    <!-- Connected Users -->
    <div class="sidebar-section">
        <div class="sidebar-section-title" data-lang="users.connected_users">Utenti Collegati</div>
        <div id="connectedUsersList" class="connected-users-list">
            <div class="text-muted small ps-2" data-lang="users.no_users">Nessun utente collegato</div>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="user-info">
            <i class="bi bi-person-circle me-2"></i>
            <span><?= h($clientUser['name'] ?? $clientUser['display_name'] ?? '') ?></span>
        </div>
        <div class="d-flex gap-2 mt-2">
            <!-- Language Switcher -->
            <div class="dropdown flex-grow-1">
                <button class="btn btn-sm btn-outline-secondary w-100 dropdown-toggle" type="button" id="langDropdown" data-bs-toggle="dropdown">
                    <i class="bi bi-translate me-1"></i>
                    <span id="currentLangLabel"><?= strtoupper(getUserLanguage()) ?></span>
                </button>
                <ul class="dropdown-menu" id="langDropdownMenu">
                    <!-- Populated by JS -->
                </ul>
            </div>
            <a href="<?= CLIENT_SITE_URL ?>/logout.php" class="btn btn-sm btn-outline-danger" title="Logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </div>
</div>
