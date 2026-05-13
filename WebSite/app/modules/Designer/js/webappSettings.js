/**
 * webappSettings.js — Point d'entrée du module.
 *
 * Responsabilité unique : instancier les classes, les connecter entre elles
 * et exposer ce qui doit l'être sur window.
 *
 * Aucune logique métier ici ; tout est délégué aux classes spécialisées.
 */

import { SectionManager }       from './SectionManager.js';
import { ArticleStatusManager } from './ArticleStatusManager.js';
import { LatestStatusManager }  from './LatestStatusManager.js';
import { ImageUploadManager }   from './ImageUploadManager.js';
import { NavbarColorManager }   from './NavbarColorManager.js';
import { ColorWheelManager }    from './ColorWheelManager.js';

// ── Instanciation ──────────────────────────────────────────────────────────

const articleStatus = new ArticleStatusManager();
const latestStatus  = new LatestStatusManager();
const navbarColors  = new NavbarColorManager();

/**
 * La roue a besoin :
 *  - de lire les couleurs courantes  → getter sur les inputs navbar
 *  - d'appliquer une harmonie        → NavbarColorManager.applyColors + mise à jour des inputs
 */
const colorWheel = new ColorWheelManager(
    // getColors : retourne [bgHex, inkHex, iconHex]
    () => [
        document.getElementById('input-navbar-bg')?.value   ?? '#212529',
        document.getElementById('input-navbar-ink')?.value  ?? '#ffffff',
        document.getElementById('input-navbar-icon')?.value ?? '#ffc107',
    ],
    // onHarmonyApplied : met à jour les inputs puis délègue au manager
    (cols) => {
        const ids = ['input-navbar-bg', 'input-navbar-ink', 'input-navbar-icon'];
        const lbls = ['label-navbar-bg', 'label-navbar-ink', 'label-navbar-icon'];

        ids.forEach((id, i) => {
            const inp = document.getElementById(id);
            const lbl = document.getElementById(lbls[i]);
            if (inp) {
                inp.value = cols[i];
                if (lbl) lbl.textContent = cols[i];
                inp.dispatchEvent(new Event('input'));
            }
        });

        navbarColors.applyColors({ bg: cols[0], ink: cols[1], icon: cols[2] });
    }
);

const sectionManager = new SectionManager((key) => {
    if (key === 'article') articleStatus.refresh();
    if (key === 'latest')  latestStatus.refresh();
});

// ── Initialisation ─────────────────────────────────────────────────────────

articleStatus.init();
latestStatus.init();
navbarColors.init();
colorWheel.init();
ImageUploadManager.initAll(['home', 'logo', 'banner']);

// Déclenche tinymce.triggerSave() à la soumission du formulaire
document.getElementById('settingsForm')?.addEventListener('submit', () => {
    if (typeof tinymce !== 'undefined') tinymce.triggerSave();
});

// Bouton de sélection de langue
document.getElementById('saveLanguage')?.addEventListener('click', () => {
    const lang    = document.getElementById('languageSelect').value;
    const useLang = document.getElementById('useLanguage').checked ? 1 : 0;
    window.location.href = `/settings-language?lang=${encodeURIComponent(lang)}&use_language=${useLang}`;
});

// ── Exposition globale (attributs onclick dans le HTML) ────────────────────

window.activateSection = (key) => sectionManager.activate(key);