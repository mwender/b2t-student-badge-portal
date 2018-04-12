<?php

namespace B2TBadges\fns\content;

// More help here: https://iconicwp.com/blog/add-custom-page-account-area-woocommerce/

/**
 * Classes tab content
 */
function classes_endpoint_content() {
?>
<h2>Classes/Exams</h2>
<?php
$content = do_shortcode( '[snippet slug="classes-exams-tab"]' ) ;
echo ( $content )? $content : '<pre><strong>MISSING CONTENT</strong><br/>Edit the content here by adding a <a href="' . admin_url( 'edit.php?post_type=snippet' ) . '">Snippet</a> called "Classes/Exams Tab".</pre>';
?>
<div id="classes">
  <table class="classes">
    <thead>
      <tr>
        <th style="width: 75%;">Class/Exam</th>
        <th style="width: 25%;">Date Completed</th>
      </tr>
    </thead>
    <tbody>
      <tr data-ts="0001" class="alert-row">
        <td colspan="2"><div class="alert alert-info">One moment. Loading...</div></td>
      </tr>
    </tbody>
  </table>
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
<?php
$content = do_shortcode( '[snippet slug="certification-program-tab"]' ) ;
echo ( $content )? $content : '<pre><strong>MISSING CONTENT</strong><br/>Edit the content here by adding a <a href="' . admin_url( 'edit.php?post_type=snippet' ) . '">Snippet</a> called "Certification Program Tab".</pre>';
?>
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