<?php
// Utilize TypeRocket for building the Badge CPT
$badges = tr_post_type( 'Badge', 'Badges' );
$badges->setIcon('trophy');
$badges->setArgument('hierarchical', false);
$badges->setArgument('description', 'Create Open Badges compatible badge definitions');
$badges->setArgument('delete_with_user', false);
$badges->setArgument('public', false);
$badges->setArgument('show_ui', true);
$badges->setArgument('show_in_admin_bar', false);
$badges->setArgument('show_in_rest', true);
$badges->setArgument('menu_position', 85);
$badges->setArgument('publicly_queryable', true);
$badges->setArgument('exclude_from_search', true);
$badges->setArgument('has_archive', true);
$badges->setArgument('can_export', true);
$badges->setArgument('rewrite', true);
$badges->setArgument('supports', ['title','revisions']);

tr_meta_box('Badge Details')->apply( $badges );

function add_meta_content_badge_details(){
  $form = tr_form();
  echo $form->text('Description');

}

$badges->setTitlePlaceholder( 'Enter the badge name here' );

$badges->setTitleForm(function(){
  $form = tr_form();
  echo $form->image('Badge Image');
  echo $form->image('Badge Image Grayscale');
  $editor = $form->editor('post_content');
  echo $editor->setLabel('Criteria');
});

/**
 * Loads the template when viewing a Badge CPT.
 */
function load_badge_template( $template ){
  global $wp_query;

  if( 'badge' != $wp_query->query_vars['post_type'] )
    return $template;

  $badge_template = dirname( __FILE__ ) . '/../../templates/badge.php';
  if( file_exists( $badge_template ) )
    return $badge_template;

  return $template;
}
add_action( 'template_include', 'load_badge_template' );

function remove_adminbar_on_badge_template($bool){
    global $wp_query;

    if( ! is_admin() && 'badge' == $wp_query->query_vars['post_type'] )
        return false;

    return $bool;
}
add_filter( 'show_admin_bar', 'remove_adminbar_on_badge_template' );