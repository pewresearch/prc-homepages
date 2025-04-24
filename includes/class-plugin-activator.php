<?php
namespace PRC\Platform\Homepages;

use DEFAULT_TECHNICAL_CONTACT;

class Plugin_Activator {
	public static function activate() {
		flush_rewrite_rules();

		wp_mail(
			DEFAULT_TECHNICAL_CONTACT,
			'PRC Homepages Activated',
			'The PRC Homepages plugin has been activated on ' . get_site_url()
		);
	}
}
