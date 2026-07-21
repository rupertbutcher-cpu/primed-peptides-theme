document.addEventListener('DOMContentLoaded', function () {

    // Category tab filtering
    const tabs = document.querySelectorAll('.cat-pill');
    const cards = document.querySelectorAll('.product-card');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');

            const cat = tab.getAttribute('data-cat');
            cards.forEach(function (card) {
                if (cat === 'all' || card.getAttribute('data-cat') === cat) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });

});
