jQuery(document).ready(function ($) {

	var loadingScreen = '<div class="modal-loading-screen"><div class="fa-5x"><i class="fas fa-spinner fa-spin"></i></div></div>';

	//Reg submit
	$(document).on('click', 'form#registerform input[name="wp-submit"]', function (e) {

		e.preventDefault();

		var user_login = $("#registerform input[name='user_sign-up']").val();
		var user_pass = $("#registerform input[name='pass_sign-up']").val();
		var user_email = $("#registerform input[name='email_sign-up']").val();
		var gRecaptcha = $('#registerform .g-recaptcha-response').val();

		var modalContent = $(this).parents('.modal-content');

		if (user_login == '') {
			$('p.message.register').text(wpMangaLogin.messages.username_cannot_empty);
			return false;
		}

		if (user_email == '') {
			$('p.message.register').text(wpMangaLogin.messages.email_cannot_empty);
			return false;
		}

		if (user_pass == '') {
			$('p.message.sign-up').text(wpMangaLogin.messages.password_cannot_empty);
			return false;
		}

		jQuery.ajax({
			url: wpMangaLogin.admin_ajax,
			type: 'POST',
			data: {
				action: 'wp_manga_signup',
				user_login: user_login,
				user_pass: user_pass,
				user_email: user_email,
				'g-recaptcha-response': gRecaptcha,
			},
			beforeSend: function () {
				modalContent.append(loadingScreen);
			},
			success: function (response) {
				if (response.success) {
					$(document).trigger('madara_signup_successful');
					
					$('form#registerform').remove();
					$('p.message.register').html(response.data);
				} else {
					$('p.message.register').html(response.data);

					// Reset recaptcha on failure
					if( $('#loginform .g-recaptcha-response').length > 0 ){
						grecaptcha.reset();
					}
				}
			},
			complete: function () {
				var loading = modalContent.find('.modal-loading-screen');

				if (typeof loading !== 'undefined') {
					loading.remove();
				}
			},
		});

		return false;

	});

	//Login submit
	$(document).on('click', 'form#loginform input[name="wp-submit"]', function (e) {

		e.preventDefault();

		if( $( 'body' ).hasClass( 'logged-in' ) ){
			return;
		}

		var user_login = $("#loginform input[name='log']").val();
		var user_pass = $("#loginform input[name='pwd']").val();
		var rememberme = $("#loginform input[name='rememberme']").val();
		var isBookmarking = $('input[name="bookmarking"]').val();
		var loginItems = $('.c-modal_item');
		var gRecaptcha = $('#loginform .g-recaptcha-response').val();

		var modalContent = $(this).parents('.modal-content');

		if (user_login == '') {
			$('p.message.login').text(wpMangaLogin.messages.please_enter_username);
			return false;
		}

		if (user_pass == '') {
			$('p.message.login').text(wpMangaLogin.messages.please_enter_password);
			return false;
		}

		jQuery.ajax({
			url: wpMangaLogin.admin_ajax,
			type: 'POST',
			data: {
				action: 'wp_manga_signin',
				login: user_login,
				pass: user_pass,
				rememberme: rememberme,
				"g-recaptcha-response": gRecaptcha,
			},
			beforeSend: function () {
				modalContent.append(loadingScreen);
			},
			success: function (response) {
				if (response.success == true) {

					$('.modal#form-login').modal('hide');

					$(document).trigger('manga-login-success');

					$( 'body' ).addClass( 'logged-in' );

					if( typeof madara_ajax_maybe_buy_content !== 'undefined' ){
						madara_ajax_maybe_buy_content.token = response.data.token;
					}

					if (loginItems.length != 0) {
						var loginSection = loginItems.html();
					}

					jQuery.ajax({
						type: 'POST',
						url: wpMangaLogin.admin_ajax,
						data: {
							action: 'wp-manga-get-user-section'
						},
						beforeSend: function () {
							loginItems.html('<i class="fas fa-spinner fa-spin"></i>');
						},
						success: function (response) {
							if (response.success) {
								loginItems.empty();
								loginItems.append(response.data);
							} else if( typeof loginSection !== 'undefined' ) {
								loginItems.html(loginSection);
							}
						},
					});

					if (isBookmarking == 1) {
						$('.wp-manga-action-button').trigger('click');
					}

				} else if (typeof response.data !== 'undefined') {
					$('p.message.login').html(response.data);

					// Reset recaptcha on failure
					if( $('#loginform .g-recaptcha-response').length > 0 ){
						grecaptcha.reset();
					}

				} else {
					$('p.message.login').text(wpMangaLogin.messages.invalid_username_or_password);

					// Reset recaptcha on failure
					if( $('#loginform .g-recaptcha-response').length > 0 ){
						grecaptcha.reset();
					}
				}
			},
			complete: function (xhr, response) {
				var loading = modalContent.find('.modal-loading-screen');

				if (typeof loading !== 'undefined') {
					loading.remove();
				}
				
				if(response == 'error'){
					alert(wpMangaLogin.messages.server_error);
				}
			},
		});
		return false;
	});

	$(document).on('click', 'form#resetform input[name="wp-submit"]', function (e) {

		e.preventDefault();

		var user = $('input[name="user_reset"]').val();

		if (user == '') {
			$('p.message.reset').text(wpMangaLogin.messages.username_or_email_cannot_be_empty);
			return false;
		}

		var modalContent = $(this).parents('.modal-content');

		jQuery.ajax({
			url: wpMangaLogin.admin_ajax,
			type: 'POST',
			data: {
				action: 'wp_manga_reset',
				user: user,
			},
			beforeSend: function () {
				modalContent.append(loadingScreen);
			},
			success: function (response) {
				if (response.success) {
					$('form#resetform').fadeOut();
					$('p.message.reset').text(response.data);

					setInterval(function(){
						$('form#resetform').fadeIn();
						$('p.message.reset').text('');
					}, 10000);
				} else {
					$('p.message.reset').html(response.data);
				}
			},
			complete: function () {
				var loading = modalContent.find('.modal-loading-screen');

				if (typeof loading !== 'undefined') {
					loading.remove();
				}
			},
		});

	});

	$('#resetpasswordform').on( 'submit', function(e){

		e.preventDefault();

		var self = $(this);
		var pass_1 = self.find( 'input[name="pass_1"]').val(),
			pass_2 = self.find( 'input[name="pass_2"]').val(),
			user = self.find( 'input[name="user"]').val(),
			key = self.find( 'input[name="key"]' ).val(),
			message = $('#form-reset-password .message.reset-password' );

		var loadingScreen = '<div class="modal-loading-screen"><div class="fa-5x"><i class="fas fa-spinner fa-spin"></i></div></div>';

		var modalContent = self.parents('.modal-content');

		if( pass_1 == '' || pass_2 == '' ){
			message.text( wpMangaLogin.messages.please_fill_all_fields );
			return false;
		}

		if( pass_1.length < 12 ){
			message.text( wpMangaLogin.messages.password_cannot_less_than_12);
			return false;
		}

		if( pass_1 !== pass_2 ){
			message.text( wpMangaLogin.messages.password_doesnot_match );
			return false;
		}

		$.ajax({
			url : wpMangaLogin.admin_ajax,
			method : 'POST',
			data : {
				action : 'wp_manga_reset_password',
				user : user,
				new_password : window.btoa( pass_1 ),
				key : key,
			},
			beforeSend : function(){
				modalContent.append(loadingScreen);
			},
			success : function( response ){
				if( response.success ){
					message.text( response.data.message );
					self.find( 'input' ).remove();

					setInterval( function(){
						self.remove();
					}, 5000 );
				}
			},
			complete : function(){
				modalContent.find('.modal-loading-screen').remove();
			}
		});

	} );

	$(document).on('click', '.to-login', function (e) {
		e.preventDefault();
		$('.modal').modal('hide');
		setTimeout(function () {
			$('#form-login').modal('show');
		}, 500);
	});

	$(document).on('click', '.to-reset', function (e) {
		e.preventDefault();
		$('.modal').modal('hide');
		setTimeout(function () {
			$('#form-reset').modal('show');
		}, 500);
	});

	$(document).on('click', '.backtoblog', function (e) {
		$('.modal').modal('hide');
	});

});
