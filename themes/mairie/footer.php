    </main>

    <footer>
        <div class="dedans">
            <p class="footer-nom"><?php echo e(cms_config_get('page_title', '')); ?></p>
            <p><?php echo e(cms_config_get('footer_text', '')); ?></p>

            <ul class="footer-liens">
                <?php foreach (cms_nav_items() as $libelle => $lien): ?>
                    <li><a href="<?php echo e(cms_url($lien)); ?>"><?php echo e($libelle); ?></a></li>
                <?php endforeach; ?>
            </ul>

            <p class="footer-mentions">
                Ce site est accessible : il respecte la structure de titres, les contrastes
                et la navigation au clavier exigés par le RGAA.
            </p>
        </div>
        <?php do_action('footer'); ?>
    </footer>
</body>
</html>
