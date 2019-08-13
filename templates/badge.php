<!doctype html>
<html <?php language_attributes(); ?>>
  <head>
    <meta charset="<?php bloginfo('charset') ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no">
    <meta name="robots" content="noindex,nofollow">
    <title><?php wp_title() ?></title>
    <?php wp_head(); ?>
  </head>
<body>
  <main class="container">
    <div class="row">
      <?php
      if( $badge_image = get_post_meta( get_the_ID(), 'badge_image', true ) ){
        $image = wp_get_attachment_image_src( $badge_image, 'full' );
        echo '<div class="column column-25"><img src="' . $image[0] . '" class="badge-image" /></div>';
      }
      ?>
      <div class="column column-75">
        <?php
        if( have_posts() ): while( have_posts() ) : the_post(); ?>
          <h1><?php the_title() ?></h1>

          <?php the_content() ?>
        <?php endwhile; else : ?>
        <p>No badges found!</p>
        <?php endif; ?>
      </div>
    </div>
    <hr />
    <div class="row footer">
      <div class="column">
        <p class="small">This page serves to document the criteria for Netmind's <em><?= get_the_title( $post->ID ) ?></em> Badge. For more information, please visit <a href="<?= site_url() ?>">the <?= get_bloginfo( 'name' ) ?> website</a>.<br/>&copy; Copyright <?= date('Y') ?> <?= get_bloginfo( 'name' ) ?>. All rights reserved.</p>
      </div>
    </div>
  </main>
  <?php wp_footer() ?>
</body>
</html>