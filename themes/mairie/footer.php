    </main>

    <?php $contact = (array) cms_config_get('contact', []); ?>

    <footer>
        <div class="dedans pied-colonnes">
            <div>
                <p class="footer-nom"><?php echo e(cms_config_get('page_title', '')); ?></p>
                <?php if (!empty($contact['adresse'])): ?>
                    <p><?php echo e($contact['adresse']); ?></p>
                <?php endif; ?>
                <?php if (!empty($contact['telephone'])): ?>
                    <p><a href="tel:<?php echo e(preg_replace('/\s+/', '', $contact['telephone'])); ?>"><?php echo e($contact['telephone']); ?></a></p>
                <?php endif; ?>
                <?php if (!empty($contact['email'])): ?>
                    <p><a href="mailto:<?php echo e($contact['email']); ?>"><?php echo e($contact['email']); ?></a></p>
                <?php endif; ?>
            </div>

            <?php if (!empty($contact['horaires'])): ?>
                <div>
                    <p class="pied-titre">Horaires d'ouverture</p>
                    <p><?php echo e($contact['horaires']); ?></p>
                </div>
            <?php endif; ?>

            <div>
                <p class="pied-titre">Le site</p>
                <ul class="footer-liens">
                    <?php foreach (cms_nav_items() as $libelle => $lien): ?>
                        <li><a href="<?php echo e(cms_url($lien)); ?>"><?php echo e($libelle); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <p class="dedans footer-mentions">
            <?php echo e(cms_config_get('footer_text', '')); ?> —
            site accessible : structure de titres, contrastes et navigation au clavier conformes au RGAA.
        </p>
        <?php echo cms_plugin_scripts(); ?>

        <?php do_action("footer"); ?>
    </footer>
</body>
</html>
