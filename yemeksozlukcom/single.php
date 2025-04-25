<?php get_header(); ?>
<?php
if(get_post_type() == 'tarif'):
    get_template_part('template-parts/recipe-single');
else:
    the_content();
endif;
get_footer();
?>