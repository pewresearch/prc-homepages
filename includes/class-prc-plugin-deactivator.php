<?php
namespace PRC\Platform\Homepages;

use DEFAULT_TECHNICAL_CONTACT;

class Plugin_Deactivator {

	public static function deactivate() {
		flush_rewrite_rules();

		wp_mail(
			DEFAULT_TECHNICAL_CONTACT,
			'PRC Homepages Deactivated',
			'The PRC Homepages plugin has been deactivated on ' . get_site_url()
		);
	}
}
