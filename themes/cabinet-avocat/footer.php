    </main>

    <footer>
        <p class="footer-nom"><?php echo e(cms_config_get('page_title', '')); ?></p>
        <p><?php echo e(cms_config_get('footer_text', '')); ?></p>
        <?php echo cms_plugin_scripts(); ?>

        <?php do_action("footer"); ?>
    </footer>
</body>
</html>
