<?php
/**
 * NewArticles extension
 **
 * @file
 * @ingroup Extensions
 * @author Dror S. [FFS]
 * @copyright © 2015 Dror S. & Kol-Zchut Ltd.
 * @license GNU General Public Licence 2.0 or later
 */

$wgExtensionCredits['special'][] = array(
	'name' => 'NewArticles',
	'author' => array(
		'Dror S. [FFS] ([http://www.kolzchut.org.il Kol-Zchut])',
	),
	'version'  => '0.1.1',
	'license-name' => 'GPL-2.0+',
	'url' => 'https://github.com/kolzchut/mediawiki-extensions-WRNewArticles',
	'descriptionmsg' => 'newarticles-desc',
	'path' => __FILE__
);

/* Setup */
$wgMessagesDirs['NewArticles'] = __DIR__ . '/i18n';

// Special Page
$wgAutoloadClasses['SpecialNewArticles'] = __DIR__ . '/SpecialNewArticles.php';
$wgSpecialPages['NewArticles'] = 'SpecialNewArticles';
$wgExtensionMessagesFiles['NewArticlesAlias'] = __DIR__ . '/NewArticles.alias.php';


/* Configuration */
