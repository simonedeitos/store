<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    window.CLIENT_CONFIG = {
        wsUrl: '<?= WS_SERVER_URL ?>',
        siteUrl: '<?= CLIENT_SITE_URL ?>',
        stationToken: '<?= h(getCurrentStation()['token'] ?? '') ?>',
        userType: '<?= h(getClientUser()['user_type'] ?? '') ?>',
        userId: <?= (int)(getClientUser()['id'] ?? 0) ?>,
        userName: '<?= h(getClientUser()['name'] ?? getClientUser()['display_name'] ?? '') ?>',
        lang: '<?= h(getUserLanguage()) ?>'
    };
</script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/language.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/websocket.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/webrtc.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/player-control.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/playlist-queue.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/archive-control.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/countdown.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/audio-monitor.js"></script>
<script src="<?= CLIENT_SITE_URL ?>/assets/js/app.js"></script>
</body>
</html>
