<?php

namespace B2TBadges\fns\content;

// More help here: https://iconicwp.com/blog/add-custom-page-account-area-woocommerce/

/**
 * Classes tab content
 */
function classes_endpoint_content() {
?>
<h2>Classes</h2>
<p>Please see below for a historical listing of classes that you have completed with B2T Training.</p>
<div id="classes">
    <div class="alert alert-info">One moment. Loading...</div>
</div>
<?php
}
add_action( 'woocommerce_account_classes_endpoint', __NAMESPACE__ . '\\classes_endpoint_content' );

/**
 * Certification tab content
 */
function certification_endpoint_content() {
?>
<h2>Certification Program</h2>
<p>Use this page to track your progress towards earning badges and certifications. For more on the badges, certifications, and program as a whole, please visit the <a href="<?= site_url('/business-analyst-certification-program/') ?>" target="_blank">B2T Certification</a> and <a href="<?= site_url('/business-analyst-certification-program/faqs/') ?>" target="_blank">FAQ</a> pages on our website. </p>
<p>Once you have completed a badge or certification, we will issue you a badge in <a href="https://openbadges.org/about/" target="_blank">the Open Badges format</a>. You will be able to share your accomplishment via your <a href="https://backpack.openbadges.org/backpack/welcome" target="_blank">Mozilla Backpack</a> account. Setup <a href="https://backpack.openbadges.org/backpack/welcome" target="_blank">your Backpack account now</a> to start saving and sharing your badges.</p>
<p>Please note, the <a href="<?= site_url('/my-account/classes/') ?>" target="_blank">class</a> criteria below might not reflect the name of the specific class you took. We have mapped any custom class variation to the standard course and given credit accordingly.</p>

<p>If you have any questions, please <a href="mailto:certification@b2ttraining.com">email</a> or call us at 866.675.2125.</p>
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