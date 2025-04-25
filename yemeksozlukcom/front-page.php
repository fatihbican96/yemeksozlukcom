<?php get_header(); ?>

<div class="main-content home-main">
  <div class="home-hero-ad">
    <img src="https://via.placeholder.com/970x90?text=Banner+Reklam" alt="Reklam" />
  </div>

  <div class="home-grid">
    <div class="home-maincol">
      <div class="block block-recipes">
        <div class="block-header">
          <h2>En Yeni Tarifler</h2>
          <a href="<?php echo home_url('/tarif/'); ?>" class="more-link">Daha Fazla</a>
        </div>
        <div class="block-recipes-list">
          <?php
          $tarifler = new WP_Query([
            'post_type' => 'tarif',
            'posts_per_page' => 9
          ]);
          if($tarifler->have_posts()):
            while($tarifler->have_posts()): $tarifler->the_post();
              get_template_part('template-parts/recipe-card');
            endwhile; wp_reset_postdata();
          endif;
          ?>
        </div>
      </div>

      <section class="homepage-about">
        <h2>Yemek Sözlük: Lezzet Tutkunlarının Buluşma Noktası!</h2>
        <p>
          Yemeksozluk.com, mutfakta harikalar yaratmak isteyen herkes için kapsamlı bir yemek platformudur! 400’den fazla yazarımızın katkılarıyla binlerce tarif, beslenme faydaları ve mutfak ipuçlarıyla dolu geniş bir arşive sahibiz.<br>
          <b>Sen de Tariflerini Paylaş!</b>
          <br>
          Kendi tariflerini paylaşarak mutfak kültürüne katkıda bulunmak ister misin? Yemek Sözlük’te tarif göndermek tamamen <b>ücretsiz</b>.<br>
          <b>Instagram, Facebook, Tiktok, Twitter & YouTube hesaplarımızda buluşalım!</b><br>
          Mutfakta keşfe çıkmak için doğru yerdesin!
        </p>
      </section>

      <div class="block block-blog">
        <div class="block-header">
          <h2>Blog</h2>
          <a href="<?php echo home_url('/blog/'); ?>" class="more-link">Daha Fazla</a>
        </div>
        <div class="block-blog-list">
          <?php
          $blog = new WP_Query([
            'post_type' => 'post',
            'posts_per_page' => 6
          ]);
          if($blog->have_posts()):
            while($blog->have_posts()): $blog->the_post();
              ?>
              <div class="blog-card">
                <?php if(has_post_thumbnail()) : ?>
                  <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail('medium'); ?></a>
                <?php else: ?>
                  <a href="<?php the_permalink(); ?>"><img src="https://via.placeholder.com/300x200?text=Blog" alt="<?php the_title_attribute(); ?>" /></a>
                <?php endif; ?>
                <a class="blog-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                <div class="blog-meta">
                  <span class="blog-author">
                    <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>">
                      <?php the_author(); ?>
                    </a>
                  </span>
                  <?php echo function_exists('ys_fav_button') ? ys_fav_button(get_the_ID()) : ''; ?>
                </div>
              </div>
            <?php
            endwhile; wp_reset_postdata();
          endif;
          ?>
        </div>
      </div>
    </div>

    <aside class="home-sidebar">
      <div class="sidebar-block popular-recipes">
        <h3>Popüler Tarifler</h3>
        <ul>
          <?php
          $populer = new WP_Query([
            'post_type' => 'tarif',
            'posts_per_page' => 6,
            'meta_key' => 'goruntulenme',
            'orderby' => 'meta_value_num',
            'order' => 'DESC'
          ]);
          if($populer->have_posts()):
            while($populer->have_posts()): $populer->the_post();
              ?>
              <li>
                <?php if(has_post_thumbnail()) : ?>
                  <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail([70,70]); ?></a>
                <?php else: ?>
                  <a href="<?php the_permalink(); ?>"><img src="https://via.placeholder.com/70x70?text=Tarif" /></a>
                <?php endif; ?>
                <div class="pop-recipe-info">
                  <a class="pop-title" href="<?php the_permalink(); ?>"><?php the_title(); ?></a><br>
                  <span class="pop-author">
                    <a href="<?php echo get_author_posts_url(get_the_author_meta('ID')); ?>">
                      <?php the_author(); ?>
                    </a>
                  </span>
                  <?php echo function_exists('ys_fav_button') ? ys_fav_button(get_the_ID()) : ''; ?>
                </div>
              </li>
              <?php
            endwhile; wp_reset_postdata();
          endif;
          ?>
        </ul>
      </div>
      <div class="sidebar-block sidebar-ad">
        <img src="https://via.placeholder.com/336x280?text=Reklam" alt="Reklam" />
      </div>
      <div class="sidebar-block sidebar-cta">
        <h4>Yemek Tarifi Gönder</h4>
        <p>Nefis, lezzetli yemek tarifi mi var? Hemen gönder, sitemizle birlikte yayınlayalım.</p>
        <a href="<?php echo home_url('/tarif-ekle/'); ?>" class="cta-btn">Tarif Gönder</a>
      </div>
      <div class="sidebar-block sidebar-ad">
        <img src="https://via.placeholder.com/336x280?text=Reklam" alt="Reklam" />
      </div>
      <div class="sidebar-block sidebar-categories">
        <h4>Kategoriler</h4>
        <div class="cat-tags">
          <?php
          $terms = get_terms([
            'taxonomy' => 'yemek_turu',
            'hide_empty' => false,
            'number' => 20,
          ]);
          if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
              echo '<a href="'.get_term_link($term).'">'.$term->name.'</a> ';
            }
          }
          ?>
        </div>
      </div>
      <div class="sidebar-block sidebar-cta">
        <h4>Yazı Gönder</h4>
        <p>Sağlıklı beslenme, mekan önerileri ve daha fazla blog yazını hemen gönder, isminle birlikte yayınlayalım.</p>
        <a href="<?php echo home_url('/blog-yaz/'); ?>" class="cta-btn">Oluşturun</a>
      </div>
    </aside>
  </div>
</div>

<?php get_footer(); ?>