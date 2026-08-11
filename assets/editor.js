/*
 * Barre d'outils de l'editeur.
 *
 * Insere des balises autour de la selection, ou au point d'insertion.
 * Aucune dependance : la zone de saisie reste un textarea ordinaire, ce
 * qui garantit que le contenu enregistre est exactement ce qui est
 * affiche a l'ecran.
 */
(function () {
    'use strict';

    function inserer(zone, avant, apres) {
        var debut = zone.selectionStart;
        var fin = zone.selectionEnd;
        var selection = zone.value.slice(debut, fin);

        zone.setRangeText(avant + selection + apres, debut, fin, 'end');

        // Sans selection, on place le curseur entre les deux balises
        if (selection === '') {
            zone.selectionStart = zone.selectionEnd = debut + avant.length;
        }

        zone.focus();
    }

    document.addEventListener('click', function (ev) {
        var bouton = ev.target.closest ? ev.target.closest('[data-inserer]') : null;

        if (!bouton) {
            return;
        }

        ev.preventDefault();

        var groupe = bouton.closest('[data-editeur]');
        if (!groupe) {
            return;
        }

        var zone = document.getElementById(groupe.getAttribute('data-editeur'));
        if (!zone) {
            return;
        }

        var morceaux = bouton.getAttribute('data-inserer').split('|');
        inserer(zone, morceaux[0] || '', morceaux[1] || '');
    });

    function rafraichirApercu() {
        var cadre = document.querySelector('.apercu iframe');
        if (cadre) {
            cadre.src = cadre.src;
        }
    }

    // Recharge l'apercu sans quitter la page
    document.addEventListener('click', function (ev) {
        var bouton = ev.target.closest ? ev.target.closest('[data-rafraichir]') : null;

        if (!bouton) {
            return;
        }

        ev.preventDefault();
        rafraichirApercu();
    });

    /*
     * Enregistrement automatique.
     *
     * Le bouton « Enregistrer » disparait des que JavaScript est actif :
     * on enregistre 1,2 s apres la derniere frappe. Sans JavaScript, le
     * bouton reste et le formulaire fonctionne comme avant — le CMS ne
     * doit jamais dependre d'un script pour rester utilisable.
     */
    document.addEventListener('DOMContentLoaded', function () {
        var formulaires = document.querySelectorAll('form[data-autosave]');

        Array.prototype.forEach.call(formulaires, function (form) {
            var statut = form.querySelector('[data-statut]')
                || document.querySelector('[data-statut]');
            var minuteur = null;
            var enVol = false;
            var enAttente = false;

            // Le bouton n'a plus de raison d'etre
            Array.prototype.forEach.call(form.querySelectorAll('.si-sans-js'), function (el) {
                el.hidden = true;
            });

            function dire(texte, etat) {
                if (statut) {
                    statut.textContent = texte;
                    statut.className = 'statut' + (etat ? ' ' + etat : '');
                }
            }

            function envoyer() {
                if (enVol) {
                    enAttente = true;
                    return;
                }

                enVol = true;
                dire('Enregistrement…', 'en-cours');

                var donnees = new FormData(form);
                donnees.append('autosave', '1');

                fetch(form.action, {
                    method: 'POST',
                    body: donnees,
                    credentials: 'same-origin'
                })
                    .then(function (r) { return r.json(); })
                    .then(function (json) {
                        enVol = false;

                        if (json && json.ok) {
                            dire('Enregistré à ' + json.heure, 'ok');
                            rafraichirApercu();
                        } else {
                            dire(json && json.erreur ? json.erreur : "Échec de l'enregistrement", 'erreur');
                        }

                        if (enAttente) {
                            enAttente = false;
                            planifier();
                        }
                    })
                    .catch(function () {
                        enVol = false;
                        dire('Hors ligne — modifications non enregistrées', 'erreur');
                    });
            }

            function planifier() {
                dire('Modifications non enregistrées', 'en-attente');
                clearTimeout(minuteur);
                minuteur = setTimeout(envoyer, 1200);
            }

            form.addEventListener('input', planifier);
            form.addEventListener('change', planifier);

            // Ne pas perdre une frappe de derniere seconde
            window.addEventListener('beforeunload', function (ev) {
                if (minuteur) {
                    clearTimeout(minuteur);
                    envoyer();
                    ev.preventDefault();
                    ev.returnValue = '';
                }
            });

            dire('Enregistrement automatique actif', 'ok');
        });
    });
})();
