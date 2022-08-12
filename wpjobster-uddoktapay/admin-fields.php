<?php // UddoktaPay Settings

Redux::setSection($opt_name, array(
	'title'      => __('UddoktaPay', 'wpjobster'),
	'desc'       => __('UddoktaPay Settings', 'wpjobster'),
	'id'         => 'uddoktapay-settings',
	'subsection' => true,
	'fields'     => array_merge(

		wpj_get_gateway_default_fields(
			array(
				'gateway_id'      => 'uddoktapay',
				'gateway_name'    => 'UddoktaPay',
				'gateway_version' => '1.0',
				'license'         => false,
				'public_key'      => false,
				'secret_key'      => false,
				'new_fields'      => array(
					array(
						'unique_id' => 'uddoktapay-settings-section',
						'type'      => 'section',
						'title'     => esc_html__('Gateway Settings', 'wpjobster'),
						'indent'    => true,
					),
					array(
						'unique_id' => 'wpjobster_uddoktapay_api_key',
						'type'      => 'text',
						'title'     => __('UddoktaPay API KEY', 'wpjobster')
					),
					array(
						'unique_id' => 'wpjobster_uddoktapay_api_url',
						'type'      => 'text',
						'title'     => __('UddoktaPay API URL', 'wpjobster')
					)
				)
			)
		)
	)
));

do_action('wpj_after_admin_uddoktapay_settings_fields');
