<?php
/**
 * @package WordPress
 * @subpackage Prepped_and_Polished_Theme
 */

get_header(); ?>

<?php if (have_posts()) : ?>

	<?php while (have_posts()) : the_post(); ?>
    
    <?php
    ob_start();
    		the_content();
    		$old_content = ob_get_clean();
    		$new_content = strip_tags($old_content);
    ?>
    
		<div id="post-<?php the_ID(); ?>" class="article">
		  <?php if( (function_exists('news_link')) && (post_custom('news_link_url')) ) : ?>
		    <header>
					<h2><?php echo $new_content; ?> <a href="<?php news_link(); ?>" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
					<p><?php the_time('F j'); ?></p>
				</header>
			<?php else : ?>
			  
			<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
			
			<div class="section">
				<?php the_content('Read More &rarr;'); ?>
			</div>
      
			<dl class="aside">
			  <dt class="date"><?php the_time('F jS, Y') ?></dt>
			  <?php the_tags('<dt class="tags">Tagged</dt><dd>',', ','</dd>'); ?>
			  <dt class="category">Posted in</dt>
			  <dd><?php the_category(', ') ?></dd>
			  <dt class="comments">Comments (<?php comments_popup_link('0', '1', '%', '', 'Comments are off for this post'); ?>)</dt>
			</dl>
			<?php endif; ?>
		</div>
    
	<?php endwhile; ?>

	<?php if (show_posts_nav()) : ?>
	<ol class="navigation green">
		<li class="next"><?php next_posts_link('&larr; Older Entries') ?></li>
		<li class="previous"><?php previous_posts_link('Newer Entries &rarr;') ?></li>
	</ol>
	<?php endif; ?>

<?php else : ?>

	<h2>Not Found</h2>
	<p>Sorry, but you are looking for something that isn't here.</p>
	<?php get_search_form(); ?>

<?php endif; ?>

<?php get_footer(); ?>