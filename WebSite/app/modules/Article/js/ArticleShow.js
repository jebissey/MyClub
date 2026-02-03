import CarouselManager from './CarouselManager.js';
import OrderReplyManager from './OrderReplyManager.js';
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

        const isEditPage = window.location.pathname.includes('/article/edit/');

        if (isEditPage && document.getElementById('editor-container') && this.articleId) {
            this.initCarouselManager();
        } else if (document.getElementById('edit-toggle-btn') && this.articleId) {
            this.initCarouselManager();
        }

        if (document.getElementById('reply-survey-btn') && this.articleId) {
            this.initSurveyReplyManager();
        }
        if (document.getElementById('reply-order-btn') && this.articleId) {
            this.initOrderReplyManager();
        }
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
    initOrderReplyManager() {
        try {
            this.orderReplyManager = new OrderReplyManager(this.articleId, this.userEmail);
            this.orderReplyManager.init();
            console.log('Order reply manager initialized');
        } catch (error) {
            console.error('Error initializing order reply manager:', error);
        }
    }


    // Méthodes utilitaires accessibles publiquement

    async refreshCarousel() {
        if (this.carouselManager) {
            await this.carouselManager.loadCarouselItems();
        }
    }

    openSurveyModal() {
        if (this.surveyReplyManager) {
            this.surveyReplyManager.handleReplyClick();
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const config = {
        articleId: document.body.dataset.articleId || window.ARTICLE_ID,
        userEmail: document.body.dataset.userEmail || window.USER_EMAIL
    };

    const articleShow = new ArticleShow(config);
    articleShow.init();

    window.articleShow = articleShow;
});

export default ArticleShow;