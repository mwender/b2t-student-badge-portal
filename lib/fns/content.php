<?php

namespace B2TBadges\fns\content;

// More help here: https://iconicwp.com/blog/add-custom-page-account-area-woocommerce/

/**
 * Classes tab content
 */
function classes_endpoint_content() {
?>
<h2>Classes</h2>
<p>Please see below for a historical listing of classes and exams that you have completed with B2T Training.</p>
<?php
}
add_action( 'woocommerce_account_classes_endpoint', __NAMESPACE__ . '\\classes_endpoint_content' );

/**
 * Certification tab content
 */
function certification_endpoint_content() {
?>
<h2>Certification Program</h2>
<p>Use this page to track your progress towards earning badges and certifications:</p>
<div id="tabs">
    <ul>
        <li><a href="#badges">Badges</a></li>
        <li><a href="#certifications">Certifications</a></li>
    </ul>
    <div id="badges">
        <div class="alert alert-info">One moment. Loading...</div>
        <div id="badge-display">
        </div>
    </div>
    <div id="certifications">
        <div id="certificate-display">
        </div>
    </div>
</div>

<?php
}
add_action( 'woocommerce_account_certification_endpoint', __NAMESPACE__ . '\\certification_endpoint_content' );