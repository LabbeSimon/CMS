var meta = document.querySelector('meta[name="cms-alerte"]');

if (meta && meta.content) {
    var bandeau = document.createElement('div');
    bandeau.className = 'cms-alerte';
    bandeau.setAttribute('role', 'status');
    bandeau.setAttribute('data-niveau', meta.getAttribute('data-niveau') || 'info');

    var etiquette = document.createElement('span');
    etiquette.className = 'cms-alerte-etiquette';
    etiquette.textContent = 'Alerte';
    bandeau.appendChild(etiquette);

    var texte = document.createElement('span');
    texte.textContent = meta.content;
    bandeau.appendChild(texte);

    var lien = meta.getAttribute('data-lien');
    if (lien) {
        var a = document.createElement('a');
        a.href = lien;
        a.textContent = 'En savoir plus';
        bandeau.appendChild(a);
    }

    document.body.insertBefore(bandeau, document.body.firstChild);
}
