<?php get_header(); ?>
    <h2><?php the_archive_title(); ?></h2>
    <?php if ( have_posts() ) : ?>
        <ul>
        <?php while ( have_posts() ) : the_post(); ?>
            <li><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></li>
        <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>İçerik bulunamadı.</p>
    <?php endif; ?>
<?php get_footer(); ?>