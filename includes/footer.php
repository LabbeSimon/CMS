<?php
require_once __DIR__ . '/bootstrap.php';

$footerText = cms_config_get('footer_text', 'CMS. Tous droits reserves.');
?>
    </main>

    <footer>
        <p>&copy; <?php echo e($footerText); ?></p>
        <?php do_action('footer'); ?>
    </footer>
</body>
</html>
