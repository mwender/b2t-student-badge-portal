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
global $post;
$content = get_field( 'class_exams_tab' );
echo ( $content )? apply_filters( 'the_content', $content ) : '<pre><strong>MISSING CONTENT</strong><br/>Add content here by editing the "Class Exams Tab" field for this page. [<a href="' . get_edit_post_link( $post->ID ) . '">Edit</a>]</pre>';
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
global $post;
$content = apply_filters( 'the_content', get_post_meta($post->ID, 'certification_program_tab', true) );
$content = get_field('certification_program_tab');
echo ( $content )? $content : '<pre><strong>MISSING CONTENT</strong><br/>Add content here by editing the "Certification Program Tab" field for this page. [<a href="' . get_edit_post_link( $post->ID ) . '">Edit</a>]</pre>';
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