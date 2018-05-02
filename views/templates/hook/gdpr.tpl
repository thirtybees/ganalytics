<style>
	.eupopup-container {
		background-color: {$GA_EU_BGCOLOR};
		color: {$GA_EU_TEXTCOLOR};
	}
	.eupopup-body {
		color: {$GA_EU_TEXTCOLOR};
	}
	.eupopup-button_1 {
		padding: 5px 10px;
		border-radius: 2px;
		background-color: {$GA_EU_BTNCOLOR};
		color: {$GA_EU_BTNTEXTCOLOR};
		display: inline-block;
	}

</style>
<script>
	{literal}
		$(document).euCookieLawPopup().init({
		  popupPosition : 'bottom',
		  colorStyle : 'default',
		  compactStyle : false,
		  popupTitle : '',
		  popupText : {/literal}'{$GA_EU_CONTENT}'{literal},
		  buttonContinueTitle : {/literal}'{$GA_EU_BTNTXT}'{literal},
		  buttonLearnmoreTitle : '',
		  buttonLearnmoreOpenInNewWindow : true,
		  agreementExpiresInDays : 30,
		  autoAcceptCookiePolicy : false,
		  htmlMarkup : null
		});	

		$(document).bind("user_cookie_consent_changed", function(event, object) {
		  // true or false
		});
	{/literal}
</script>
