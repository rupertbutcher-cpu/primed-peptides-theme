<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <strong style="color:white;font-size:1.2rem;letter-spacing:-0.02em;">primed<span style="color:#6bb5e3;font-size:0.65em;display:block;letter-spacing:0.15em;font-weight:600;">PEPTIDES</span></strong>
            <p>Premium quality peptides. UK made, stabilised formulations with 12 month shelf life. For research use only.</p>
        </div>
        <div class="footer-col">
            <h4>Products</h4>
            <ul>
                <li><a href="<?php echo home_url('/?cat=recovery'); ?>">Recovery & Repair</a></li>
                <li><a href="<?php echo home_url('/?cat=cognitive'); ?>">Cognitive & Wellness</a></li>
                <li><a href="<?php echo home_url('/?cat=performance'); ?>">Performance</a></li>
                <li><a href="<?php echo home_url('/?cat=aesthetic'); ?>">Aesthetic & Skin</a></li>
                <li><a href="<?php echo home_url('/?cat=hormonal'); ?>">Hormonal Support</a></li>
                <li><a href="<?php echo home_url('/?cat=accessories'); ?>">Accessories</a></li>
            </ul>
        </div>
        <div class="footer-col">
            <h4>Company</h4>
            <ul>
                <li><a href="<?php echo home_url('/about'); ?>">About Us</a></li>
                <li><a href="<?php echo home_url('/contact'); ?>">Contact</a></li>
                <li><a href="<?php echo home_url('/refund_returns'); ?>">Shipping & Returns</a></li>
                <li><a href="<?php echo home_url('/privacy-policy'); ?>">Privacy Policy</a></li>
                <li><a href="<?php echo home_url('/terms-conditions'); ?>">Terms & Conditions</a></li>
            </ul>
        </div>
    </div>

    <div class="disclaimer container">
        <strong>Research Use Only:</strong> All products sold by Primed Peptides are intended for research and laboratory use only. They are not intended for human consumption, veterinary use, or any other purpose. By purchasing, you confirm you are a qualified researcher and agree to our terms of use.
    </div>

    <div class="footer-bottom">
        <span>&copy; <?php echo date('Y'); ?> Primed Peptides. All rights reserved.</span>
        <span>Designed &amp; built in the UK</span>
    </div>
</footer>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tabs = document.querySelectorAll('.cat-pill');
    var cards = document.querySelectorAll('.product-card');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('active'); });
            tab.classList.add('active');
            var cat = tab.getAttribute('data-cat');
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
</script>
<?php wp_footer(); ?>
</body>
</html>
