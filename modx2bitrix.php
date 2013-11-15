<?php
/**
 * MODx Snippet for export MODx docs to Bitrix or another CMS from template
 * Compatible with MODx Evo
 * Author: slashinin.andrey@gmail.com
 */

// Params
$startId  = isset($startId) ? intval($startId) : 0;
$depth    = isset($depth) ? intval($depth) : 5;
$fileTemplate = isset($fileTemplate) ? trim($fileTemplate) : 'm2b_page';
$rewrite  = isset($rewrite) ? $rewrite : true;
$ignore   = isset($ignore) ? trim($ignore) : '';
$path = isset($path) ? $_SERVER['DOCUMENT_ROOT'] . '/' . $path : $_SERVER['DOCUMENT_ROOT'];

// Check save path
if (!is_dir($path) || !is_writable($path)) {
	echo 'Directory ' . $path .' is not exists or not writable';
	die();
}

// Get template chunk
$template = $modx->getChunk($fileTemplate);
if (!$template) {
	echo 'Chunk ' . $fileTemplate . ' not found!';
	die();
}

// Ignored IDs
if (strlen($ignore) > 0) {
	if ( substr_count($ignore, ',') > 0 ) {
		$ignore = explode(',', $ignore);	
	} else {
		$ignore = array($ignore);
	}
}

// Get child 
$childs = $modx->getChildIds($startId, $depth);

// Statisctis
$stat['all'] 	 = count($childs);
$stat['ok'] 	 = 0;
$stat['replace'] = 0;
$stat['ignore']  = 0;
$stat['skip']    = 0;
$stat['error']   = 0;

// Let's go )
foreach ($childs as $alias => $id) 
{
	// Check ignored
	if (in_array( (string) $id, $ignore)) {
		$stat['ignore'] ++ ;
		continue;
	}

	// File name and path
	$filePath = explode('/', $alias);
	$fileName = $filePath[ count($filePath) - 1 ] . '.php';
	unset($filePath[count($filePath) - 1]);
	$filePath = $path . '/' . implode('/', $filePath);

	// Check path 
	if (!is_dir($filePath)) {
		if (!mkdir($filePath,0775,true)) {
			echo "Cant't create folder {$filePath}";
			$stat['error'] ++;
			continue;
		}
	}

	// Get doc detail
	$document = $modx->getDocument($id,'id,content,pagetitle,longtitle,description,alias,published,pub_date,introtext');
	
	// Replace from
	$from = array(
		'[+id+]',
		'[+content+]',
		'[+pagetitle+]',
		'[+longtitle+]',
		'[+description+]',
		'[+alias+]',
		'[+published+]',
		'[+pub_date+]',
		'[+introtext+]'
	);

	// Replace to
	$to = array(
		$document['id'],
		$document['content'],
		$document['pagetitle'],
		$document['longtitle'],
		$document['description'],
		$document['alias'],
		$document['published'],
		$document['pub_date'],
		$document['introtext']
	);

	// Main content with template
	$content = str_replace($from, $to, $template);

	// Check )
	if (file_exists($filePath . '/'. $fileName)) {
		if ($rewrite === true) {
			$stat['replace'] ++;
			@unlink($filePath . '/'. $fileName);	
		} else {
			$stat['skip'] ++;
			continue;
		}
	} else {
		// Inc stat
		$stat['ok'] ++;
	}

	// Save file to disk
	file_put_contents($filePath . '/'. $fileName, $content);	
}

// Show stats
echo 'Statistics'."<br />\n";
echo '-------------'."<br />\n";
echo '- All docs: '.$stat['all']."<br />\n";
echo '- Created: '.(int)$stat['ok']."<br />\n";
echo '- Replaced: '.(int)$stat['replace']."<br />\n";
echo '- Skipped: '.(int)$stat['skip']."<br />\n";
echo '- Ignored: '.(int)$stat['ignore']."<br />\n";
echo '- Errors: '.(int)$stat['error']."<br />\n";
echo '-------------'."<br />\n";
echo 'All ok, cap )';

// Game Over
?>