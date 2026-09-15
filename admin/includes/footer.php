    </div>
</div>
<script>
    window.HANKO = {
        baseUrl: <?= json_encode(BASE_URL, JSON_UNESCAPED_SLASHES) ?>,
        csrf: <?= json_encode(csrf_token()) ?>
    };
</script>
<script src="<?= h(base_url('assets/js/admin.js')) ?>"></script>
</body>
</html>
