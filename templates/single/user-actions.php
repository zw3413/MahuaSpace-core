<div class="manga-action">
	<?php if($manga_comment){?>
	<div class="count-comment">
		<div class="action_icon">
			<a href="#manga-discussion"><i class="icon ion-md-chatbubbles"></i></a>
		</div>
		<div class="action_detail">
			<?php 
			if(isset($wp_manga_settings['default_comment']) && $wp_manga_settings['default_comment'] == 'disqus'){
				?>
				<span class="disqus-comment-count" data-disqus-url="<?php echo esc_url(get_permalink());?>"><?php esc_html_e('Comments', WP_MANGA_TEXTDOMAIN);?></span>
				<?php 
			} else {
				$comments_count = wp_count_comments( get_the_ID() ); ?>
				<span><?php 
				
				if(function_exists('wp_manga_number_format_short')){
					printf( _n( '%s comment', '%s comments', wp_manga_number_format_short($comments_count->approved), WP_MANGA_TEXTDOMAIN ), wp_manga_number_format_short($comments_count->approved) );
				} else {
					printf(esc_html__('%d comment', WP_MANGA_TEXTDOMAIN),  $comments_count);
				}
				?></span>
			<?php } ?>
		</div>
	</div>
	<?php }?>
	<?php
	
	if($user_bookmark){?>
	<div class="add-bookmark">
		<?php
			$wp_manga_functions->bookmark_link_e();
		?>
	</div>
	<?php } ?>
	<?php do_action( 'madara_single_manga_action' ); ?>
</div>