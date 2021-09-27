<?php

$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['directory_list'] = array_merge(
	$cfg['directory_list'],
	[
		'../../extensions/Flow',

	]
);

$cfg['exclude_analysis_directory_list'] = array_merge(
	$cfg['exclude_analysis_directory_list'],
	[
		'../../extensions/Flow',
	]
);

// This branch is for both master and MediaWiki 1.36. But
// Title::castFromPageReference() and RecentChange::getPage() are introduced in 1.37.
$cfg['suppress_issue_types'][] = 'UnusedPluginSuppression';

return $cfg;
