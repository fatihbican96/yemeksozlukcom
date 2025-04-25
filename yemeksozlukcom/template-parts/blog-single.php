<article itemscope itemtype="https://schema.org/Article">
    <h1 itemprop="headline"><?php the_title(); ?></h1>
    <span style="display:none" itemprop="author"><?php the_author(); ?></span>
    <span style="display:none" itemprop="datePublished"><?php echo get_the_date('c'); ?></span>
    <?php if(has_post_thumbnail()) { the_post_thumbnail('large', ['itemprop'=>'image']); } ?>
    <div itemprop="articleBody">
        <?php the_content(); ?>
    </div>
    <?php
    if (comments_open() || get_comments_number()) {
        comments_template();
    }
    ?>
</article>