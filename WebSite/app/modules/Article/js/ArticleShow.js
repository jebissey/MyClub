import CarouselManager from './CarouselManager.js';
import SurveyReplyManager from './SurveyReplyManager.js';

class ArticleShow {
    constructor(config = {}) {
        this.articleId = config.articleId || window.ARTICLE_ID || document.body.dataset.articleId;
        this.userEmail = config.userEmail || window.USER_EMAIL || document.body.dataset.userEmail;
        
        this.carouselManager = null;
        this.surveyReplyManager = null;
    }

    init() {
        if (!this.articleId) {
            console.warn('Article ID not found. Some features may not work.');
        }

        // Détecter si on est sur la page d'édition
        const isEditPage = window.location.pathname.includes('/article/edit/');

        // Initialiser le gestionnaire de galerie seulement sur la page d'édition
        if (isEditPage && document.getElementById('editor-container') && this.articleId) {
            this.initCarouselManager();
        } else if (document.getElementById('edit-toggle-btn') && this.articleId) {
            // Ancienne logique pour compatibilité (si besoin d'un mode édition inline)
            this.initCarouselManager();
        }

        // Initialiser le gestionnaire de sondages si le bouton de réponse existe
        if (document.getElementById('reply-survey-btn') && this.articleId) {
            this.initSurveyReplyManager();
        }

        // Autres initialisations spécifiques à la page article
        this.setupAdditionalFeatures();
    }

    initCarouselManager() {
        try {
            this.carouselManager = new CarouselManager(this.articleId);
            this.carouselManager.init();
            console.log('Carousel manager initialized');
        } catch (error) {
            console.error('Error initializing carousel manager:', error);
        }
    }

    initSurveyReplyManager() {
        try {
            this.surveyReplyManager = new SurveyReplyManager(this.articleId, this.userEmail);
            this.surveyReplyManager.init();
            console.log('Survey reply manager initialized');
        } catch (error) {
            console.error('Error initializing survey reply manager:', error);
        }
    }

    setupAdditionalFeatures() {
        // Ajouter ici d'autres fonctionnalités spécifiques à la page article
        // Par exemple : système de commentaires, partage social, etc.
    }

    // Méthodes utilitaires accessibles publiquement
    
    /**
     * Recharge les éléments de la galerie
     */
    async refreshCarousel() {
        if (this.carouselManager) {
            await this.carouselManager.loadCarouselItems();
        }
    }

    /**
     * Ouvre le modal de sondage
     */
    openSurveyModal() {
        if (this.surveyReplyManager) {
            this.surveyReplyManager.handleReplyClick();
        }
    }
}

// Initialisation automatique au chargement du DOM
document.addEventListener('DOMContentLoaded', () => {
    // Configuration depuis les attributs data ou variables globales
    const config = {
        articleId: document.body.dataset.articleId || window.ARTICLE_ID,
        userEmail: document.body.dataset.userEmail || window.USER_EMAIL
    };

    const articleShow = new ArticleShow(config);
    articleShow.init();

    // Exposer l'instance globalement pour debugging/interaction console
    window.articleShow = articleShow;
});

export default ArticleShow;