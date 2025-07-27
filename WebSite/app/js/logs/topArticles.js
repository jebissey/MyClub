document.addEventListener('DOMContentLoaded', function () {
    const authorSpans = document.querySelectorAll('.author-name');
    const titleSpans = document.querySelectorAll('.article-title');

    function fetchArticleInfo(articleId) {
        return fetch(`/api/author/${articleId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau: ' + response.status);
                }
                return response.json();
            });
    }

    authorSpans.forEach((span, index) => {
        const articleId = span.getAttribute('data-article-id');
        const titleSpan = titleSpans[index]; // Le span de titre correspondant

        if (articleId) {
            fetchArticleInfo(articleId)
                .then(data => {
                    if (data.author && data.author.length > 0) {
                        span.textContent = data.author[0].PersonName || '(Non spécifié)';

                        if (titleSpan && data.author[0].ArticleTitle) {
                            titleSpan.textContent = data.author[0].ArticleTitle;
                        } else if (titleSpan) {
                            titleSpan.textContent = '(Sans titre)';
                        }
                    } else {
                        span.textContent = '(Non spécifié)';
                        if (titleSpan) {
                            titleSpan.textContent = '(Sans titre)';
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur lors de la récupération des informations:', error);
                    span.textContent = 'Erreur';
                    if (titleSpan) {
                        titleSpan.textContent = 'Erreur';
                    }
                });
        }
    });
});