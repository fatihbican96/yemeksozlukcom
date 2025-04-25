<?php get_header(); ?>
<h2>Son Tarifler</h2>
<div class="recipe-list">
<?php
$args = [ 'post_type' => 'tarif', 'posts_per_page' => 10 ];
$tarifler = new WP_Query($args);
if($tarifler->have_posts()):
  while($tarifler->have_posts()): $tarifler->the_post();
    get_template_part('template-parts/recipe-card');
  endwhile;
  wp_reset_postdata();
else:
  echo "<p>Henüz tarif eklenmemiş.</p>";
endif;
?>
</div>
<?php get_footer(); ?>