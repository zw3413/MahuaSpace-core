<?php
	/* login template */

	$log_in_button = apply_filters( 'manga_login_button', '<input type="submit" name="wp-submit" class="button button-primary button-large wp-submit" value="' . esc_html__( 'Log In', WP_MANGA_TEXTDOMAIN ) . '">' );

?>

<!-- Modal -->
<?php if(get_option( 'users_can_register' )){?>
<div class="wp-manga-section">
    <input type="hidden" name="bookmarking" value="0"/>
    <div class="modal fade" id="form-login" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="login" class="login">
                        <h3>
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" tabindex="-1"><?php echo esc_html__( 'Sign in', WP_MANGA_TEXTDOMAIN ); ?></a>
                        </h3>
                        <p class="message login"></p>
						<?php do_action( 'login_head' ); ?>
						<?php do_action( 'login_enqueue_scripts' ); ?>
						<?php
						if(!wp_script_is('login_nocaptcha_google_api') && class_exists('LoginNocaptcha')){
							?>
							<script type="text/javascript" src="https://www.google.com/recaptcha/api.js?hl=en_GB&amp;ver=5.0.4"></script>
							<?php
						}
						?>
                        <form name="loginform" id="loginform" method="post">
                            <p>
                                <label><?php echo esc_html__( 'Username or Email Address *', WP_MANGA_TEXTDOMAIN ); ?>
                                    <br> <input type="text" name="log" class="input user_login" value="" size="20">
                                </label>
                            </p>
                            <p>
                                <label><?php echo esc_html__( 'Password *', WP_MANGA_TEXTDOMAIN ); ?>
                                    <br> <input type="password" autocomplete="" name="pwd" class="input user_pass" value="" size="20">
                                </label>
                            </p>
                            <p>
								<?php do_action( 'login_form' ); ?>
                            </p>
                            <p class="forgetmenot">
                                <label>
                                    <input name="rememberme" type="checkbox" id="rememberme" value="forever"><?php echo esc_html__( 'Remember Me ', WP_MANGA_TEXTDOMAIN ); ?>
                                </label>
                            </p>
                            <p class="submit">
								<?php echo $log_in_button; ?>
                                <input type="hidden" name="redirect_to" value="<?php echo esc_url( home_url( '/' ) ) . 'wp-admin/'; ?>">
                                <input type="hidden" name="testcookie" value="1">
                            </p>
                        </form>
                        <p class="nav">
                            <a href="javascript:avoid(0)" class="to-reset"><?php echo esc_html__( 'Lost your password?', WP_MANGA_TEXTDOMAIN ); ?></a>
                        </p>
                        <p class="backtoblog">
                            <a href="javascript:void(0)"><?php echo esc_html__( '&larr; Back to ', WP_MANGA_TEXTDOMAIN ); ?><?php echo esc_attr( get_bloginfo( 'name' ) ); ?></a>
                        </p>
                    </div>
                </div>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="form-sign-up" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="sign-up" class="login">
                        <h3>
                            <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" tabindex="-1"><?php echo esc_html__( 'Sign Up', WP_MANGA_TEXTDOMAIN ); ?></a>
                        </h3>
                        <p class="message register"><?php echo esc_html__( 'Register For This Site.', WP_MANGA_TEXTDOMAIN ); ?></p>
                        <form name="registerform" id="registerform" novalidate="novalidate">
                            <p>
                                <label><?php echo esc_html__( 'Username *', WP_MANGA_TEXTDOMAIN ); ?>
                                    <br>
                                    <input type="text" name="user_sign-up" class="input user_login" value="" size="20">
                                </label>
                            </p>
                            <p>
                                <label><?php echo esc_html__( 'Email Address *', WP_MANGA_TEXTDOMAIN ); ?>
                                    <br>
                                    <input type="email" name="email_sign-up" class="input user_email" value="" size="20">
                                </label>
                            </p>
                            <p>
                                <label><?php echo esc_html__( 'Password *', WP_MANGA_TEXTDOMAIN ); ?><br>
                                    <input type="password" name="pass_sign-up" autocomplete="" class="input user_pass" value="" size="25">
                                </label>
                            </p>
                            <p>
								<?php do_action( 'register_form' ); ?>
                            </p>

                            <input type="hidden" name="redirect_to" value="">
                            <p class="submit">
                                <input type="submit" name="wp-submit" class="button button-primary button-large wp-submit" value="<?php esc_html_e( 'Register', WP_MANGA_TEXTDOMAIN ); ?>">
                            </p>
                        </form>
                        <p class="nav">
                            <a href="javascript:void(0)" class="to-login"><?php echo esc_html__( 'Log in', WP_MANGA_TEXTDOMAIN ); ?></a>
                            |
                            <a href="javascript:void(0)" class="to-reset"><?php echo esc_html__( 'Lost your password?', WP_MANGA_TEXTDOMAIN ); ?></a>
                        </p>
                        <p class="backtoblog">
                            <a href="javascript:void(0)"><?php echo esc_html__( '&larr; Back to ', WP_MANGA_TEXTDOMAIN ); ?><?php echo esc_attr( get_bloginfo( 'name' ) ); ?></a>
                        </p>
                    </div>
                </div>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="form-reset" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <div id="reset" class="login">
                        <h3>
                            <a href="javascript:void(0)" class="to-reset"><?php echo esc_html__( 'Lost your password?', WP_MANGA_TEXTDOMAIN ); ?></a>
                        </h3>
                        <p class="message reset"><?php echo esc_html__( 'Please enter your username or email address. You will receive a link to create a new password via email.', WP_MANGA_TEXTDOMAIN ); ?></p>
                        <form name="resetform" id="resetform" method="post">
                            <p>
                                <label><?php echo esc_html__( 'Username or Email Address', WP_MANGA_TEXTDOMAIN ); ?>
                                    <br>
                                    <input type="text" name="user_reset" id="user_reset" class="input" value="" size="20">
                                </label>
                            </p>
                            <p class="submit">
                                <input type="submit" name="wp-submit" class="button button-primary button-large wp-submit" value="<?php esc_html_e( 'Get New Password', WP_MANGA_TEXTDOMAIN ); ?>">
                                <input type="hidden" name="testcookie" value="1">
                            </p>
                        </form>
                        <p>
                            <a class="backtoblog" href="javascript:void(0)"><?php echo esc_html__( '&larr; Back to  ', WP_MANGA_TEXTDOMAIN ); ?><?php echo esc_attr( get_bloginfo( 'name' ) ); ?></a>
                        </p>
                    </div>
                </div>
                <div class="modal-footer"></div>
            </div>
        </div>
    </div>

    <script type="text/javascript">

		function wpMangaSubmitSwitch() {
			jQuery('.g-recaptcha-response').each(function () {
				var submitBtn = jQuery(this).parents('form').find('input[type="submit"]');

				if (jQuery(this).val() !== '') {
					submitBtn.prop('disabled', false);
				} else {
					submitBtn.prop('disabled', true);
				}
			});
		}

		jQuery(function ($) {
			$(document).ready(function () {

				$('.modal form').each(function () {
					if ($(this).find('.g-recaptcha').length !== 0) {
						var submitBtn = $(this).find('input[type="submit"]');

						if (typeof submitBtn !== 'undefined') {
							submitBtn.prop('disabled', true);
						}
					}
				});

				var gRecaptcha = $('#form-login .g-recaptcha, #form-sign-up .g-recaptcha, #form-reset .g-recaptcha');
				gRecaptcha.attr('data-callback', 'wpMangaSubmitSwitch');
				gRecaptcha.attr('data-expired-callback', 'wpMangaSubmitSwitch');
				gRecaptcha.attr('data-error', 'wpMangaSubmitSwitch');
			});
		});

    </script>

</div>
<?php }
