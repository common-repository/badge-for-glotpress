<?php
/*
	Plugin Name: Badge for GlotPress
	Description: Generate badges for GloPress in your projects
	Version: 1.0
	Author: Bastien Ho
	Contributors: bastho, agencenous
	Author URI: https://apps.avecnous.eu/produit/badge-for-glotpress/?mtm_campaign=wp-plugin&mtm_kwd=badge-for-glotpress&mtm_medium=dashboard&mtm_source=author
	License: GPLv2
	Text Domain: badge-for-glotpress
	Domain Path: /languages/
	Tags:
 */

use weibo_php_badge\Badge;
add_action('wp_ajax_badge4glotpress', 'Badge4GlotPress');
add_action('wp_ajax_nopriv_badge4glotpress', 'Badge4GlotPress');
add_action( 'init', 'Badge4GlotPress_rewrites_init' );
add_filter( 'query_vars', 'Badge4GlotPress_query_var' );
add_action( 'wp', 'Badge4GlotPress_check_query' );

function Badge4GlotPress_rewrites_init(){
		add_rewrite_rule(
				'glotpress/badge/(.*)/(.*)-(.*)?\.svg$',
				'index.php?gp_badge=true&project_path=$matches[1]&info=$matches[2]&locale=$matches[3]',
				'top' );
		add_rewrite_rule(
				'glotpress/badge/(.*)/(.*)\.svg$',
				'index.php?gp_badge=true&project_path=$matches[1]&info=$matches[2]',
				'top' );

}

function Badge4GlotPress_query_var($qv){
		$qv[] = 'gp_badge';
		$qv[] = 'project_path';
		$qv[] = 'info';
		$qv[] = 'locale';
		return $qv;
}

function Badge4GlotPress_check_query(){
		$qv = get_query_var('gp_badge');
		if($qv){
				Badge4GlotPress(true);
		}

}
function Badge4GlotPress($pretty=false){
	$project_path = filter_input(INPUT_GET, 'project_path');
	$locale = filter_input(INPUT_GET, 'locale');
	$info = filter_input(INPUT_GET, 'info');
	if($pretty){
		$project_path = get_query_var('project_path');
		$locale = get_query_var('locale');
		$info = get_query_var('info');
	}

	$project_info_map = array(
		'translation_sets' => array('label'=>__('Translations', 'badge-for-glotpress'), 'type'=>'array'),
		'sub_projects' => array('label'=>__('Sub Projects', 'badge-for-glotpress'), 'type'=>'array'),
	);
	$set_info_map = array(
		'all_count' => array('label'=>__('Strings', 'badge-for-glotpress'), 'type'=>'info'),
		'untranslated_count' => array('label'=>__('Remaining', 'badge-for-glotpress'), 'type'=>'less_is_better'),
		'percent_translated' => array('label'=>__('Translated', 'badge-for-glotpress'), 'type'=>'percent'),
		'waiting_count' => array('label'=>__('Waiting', 'badge-for-glotpress'), 'type'=>'less_is_better'),
		'warnings_count' => array('label'=>__('Warnings', 'badge-for-glotpress'), 'type'=>'less_is_better'),
		'fuzzy_count' => array('label'=>__('Fuzzy', 'badge-for-glotpress'), 'type'=>'less_is_better'),
	);

	$url = site_url('glotpress/api/projects/'.esc_attr($project_path)) ;
	$response = wp_remote_get( $url );
	if ( is_array( $response ) && ! is_wp_error( $response ) ) {
		$data = json_decode($response['body']);
		if(isset($data->translation_sets) && is_array($data->translation_sets)){
			require(__DIR__.'/Badge.php');
			$Badge = new Badge();
			$Badge->imageFontFile = __DIR__.'/verdana.ttf';
			$icon = file_get_contents(__DIR__.'/translation.svg');

			// Project Part
			if(!$locale){
				if(!isset($project_info_map[$info])){
					$info	= array_keys($project_info_map)[0];
				}
				$color='333';
				$value = $tr_set->$info;

				switch($project_info_map[$info]['type']){
					case	'array':
					$value = count($data->$info);
					$color = '06e';
					break;
				}
				$params = array(
					array($project_info_map[$info]['label'], '555'),
					array($value, $color),
				);
				$Badge->svg($params, $icon);
				exit;
			}

			// Locale Set Part
			foreach($data->translation_sets as $tr_set){
				if($tr_set->locale == $locale){
					if(!isset($set_info_map[$info])){
						$info	= array_keys($set_info_map)[0];
					}

					$color='333';
					$value = $tr_set->$info;

					switch($set_info_map[$info]['type']){
						case	'info':
						$color = '06e';
						break;

						case	'percent':
						$value = (int) $value;
						if($value == 100){
							$color = '0e0';
						}
						elseif($value >= 75){
							$color = '390';
						}
						elseif($value >= 50){
							$color = '880';
						}
						elseif($value >= 30){
							$color = 'c90';
						}
						elseif($value >= 25){
							$color = 'f90';
						}
						else{
							$color = '900';
						}
						$value=$value.'%';
						break;

						case 'less_is_better':
						$value = (int) $value;
						if($value == 0){
							$color = '0e0';
						}
						elseif($value >= 1){
							$color = '880';
						}
						elseif($value >= 10){
							$color = 'c90';
						}
						elseif($value >= 20){
							$color = 'f70';
						}
						elseif($value >= 30){
							$color = 'c30';
						}
						else{
							$color = '900';
						}
						break;

						default:
						break;
					}
					$params = array(
						array($set_info_map[$info]['label'], '555'),
						array(strtoupper($locale), '444'),
						array($value, $color),
					);
					$Badge->svg($params, $icon);
					exit;
				}
			}
		}

	}
	exit;
}
