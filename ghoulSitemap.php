<?php
/**
 * Генератор карты сайта
 * https://support.google.com/webmasters/answer/183668?hl=ru
 *
 * @docs https://github.com/ghoulru/modx_ghoul_sitemap
 *
 * @version 1.0.6
 * @copyright 22/01/2021 ghoul.ru
 * 
 * Параметры вызова сниппета
 * значения отмеченные * - по умолчанию
 * @param bool showHidden , 0*|1-показывать скрытые разделы
 * @param string styleURI  - путь до стиля карты сайта, файл sitemap.xsl
 * @param bool httpScheme, http*|https
 *
 * @param float defaultPriority 0.5* - по дефолту приоритет страниц
 * @param string priorityChilds - приоритеты для дочерних разделов
 * 10=0.5,21=0.3,15=0.7 - где идет "ИД раздела=приоритет подразделов,ИД раздела=приоритет подразделов"
 * @param priorityTemplate - приоритет для шаблонов
 * 1=0.5,2=0.7 - где "ИД шаблона=приоритет подразделов,ИД шаблона=приоритет подразделов"
 * @param string priorityTv - TV параметр  для приоритета, откуда береться значение, если 0 - страница исключается
 *
 * @param float defaultChangefreq - changefreq по дефолту, если гне задан, то определяется по времени изменения ресурса
 * @param string changefreqTv - TV параметр для поля changefreq
 * @param string changefreqTamplate - changefreq для шаблонов, елси не задан changefreqTv для ресурса
 * 1=0.5,2=0.7 - где "ИД шаблона=changefreq подразделов,ИД шаблона=приоритет подразделов"
 *
 * @param string imageTvs - TV параметры для сбора картинок,
 *  - нет поддержки вывода названия изображения
 * @param string migxImage - пара параметров TV=imageName, разделитель запятая
 *  - где TV- это название TV параметра
 *  - imageName - наименование поля изображения в настройках MIGX
 * @param int noLastmod 0*|1 - не выводить lastmod
 *
 * @param exclude - ID ресурсов для исключения, через запятую
 *
 */
$timeStart = microtime(true);
$tblPrefix = $modx->getOption(xPDO::OPT_TABLE_PREFIX);
$siteStartPageId = $modx->getOption('site_start', null, 1);

global $showLastmod;
$showLastmod = (!isset($noLastmod) || !intval($noLastmod));


$siteURL = (
	isset($httpScheme)
		? $httpScheme
		: 'http'
) . '://' . $_SERVER['HTTP_HOST'] . '/';

if (!defined('GHOUL_SM_TIME_NOW'))
	define('GHOUL_SM_TIME_NOW', time());
if (!defined('GHOUL_SM_EDITED_HOUR'))
	define('GHOUL_SM_EDITED_HOUR',  GHOUL_SM_TIME_NOW - 60 * 60);
if (!defined('GHOUL_SM_EDITED_DAY'))
	define('GHOUL_SM_EDITED_DAY', GHOUL_SM_TIME_NOW - 60 * 60 * 24);
if (!defined('GHOUL_SM_EDITED_WEEK'))
	define('GHOUL_SM_EDITED_WEEK', GHOUL_SM_TIME_NOW - 60 * 60 * 24 * 7);
if (!defined('GHOUL_SM_EDITED_MONTH'))
	define('GHOUL_SM_EDITED_MONTH', GHOUL_SM_TIME_NOW - 60 * 60 * 24 * 7 * 30);
if (!defined('GHOUL_SM_EDITED_YEAR'))
	define('GHOUL_SM_EDITED_YEAR', GHOUL_SM_TIME_NOW - 60 * 60 * 24 * 7 * 365);

$childsPriority = [];
if (isset($priorityChilds) && trim($priorityChilds) != '') {
	$tmp = explode(',', trim($priorityChilds, ' `'));

	foreach ($tmp as $item) {
		list($k, $v) = explode('=', trim($item));
		$childsPriority[ $k ] = $v;
	}
}


$priorityForTemplate = [];
if (isset($priorityTemplate) && trim($priorityTemplate) != '') {
	$tmp = explode(',', trim($priorityTemplate, ' `'));
	
	foreach ($tmp as $item) {
		list($k, $v) = explode('=', trim($item), 2);
		$priorityForTemplate[ $k ] = $v;
	}
}
$changefreqForTemplate = [];
if (isset($changefreqTamplate) && trim($changefreqTamplate) != '') {
	$tmp = explode(',', trim($changefreqTamplate, ' `'));

	foreach ($tmp as $item) {
		list($k, $v) = explode('=', trim($item), 2);
		$changefreqForTemplate[ $k ] = $v;
	}
}
//print_r($priorityForTemplate);
//print_r($changefreqForTemplate);

$siteContent = [];



$q = "SELECT
id, parent pid, pagetitle, uri, editedon, template
FROM {$tblPrefix}site_content WHERE
searchable = 1
&&
contentType = 'text/html'
&&
context_key = 'web'
";

if (!isset($showHidden) || !$showHidden)
	$q .= " && published = 1";

if (isset($exclude) && trim($exclude) != '') {
	$excludeIds =  array_map('intval', explode(',', $exclude));

	if (!empty($excludeIds))
		$q .= " && id NOT IN (".implode(',', $excludeIds).")";
}
$q .= " ORDER BY id, menuindex";
//pre($q);
$stm = $modx->query($q);
if ($stm && $stm->rowCount()) {
	while ($res = $stm->fetch(PDO::FETCH_ASSOC)) {
		$res['id']  = intval($res['id']);
		$res['pid'] = intval($res['pid']);
		
		$siteContent[ $res['id'] ] = $res;
	}
}

/*
 * Выбираем ТВ параметры и их значения для страниц
 */
$contentTvValues = [];

$tvNames = [];
if (isset($priorityTv) && $priorityTv != '')
	$tvNames['priority'] = trim($priorityTv);
if (isset($changefreqTv) && $changefreqTv != '')
	$tvNames['changefreq'] = trim($changefreqTv);

/*
 * Изображения
 */
$imageTvNames = [];
$imgAllowed = false;
if (isset($imageTvs) && $imageTvs != '') {
	$imageTvNames = explode(',', trim($imageTvs));
	$imageTvNames = array_map('trim', $imageTvNames);

	if (!empty($imageTvNames)) {
		$imgAllowed = true;
		foreach ($imageTvNames as $imageTvName) {
			$tvNames[$imageTvName] = $imageTvName;
		}
	}
}
$migxTvData = [];
if (isset($migxImage) && $migxImage != '') {
	$tmp = explode(',', trim($migxImage));
	$tmp = array_map('trim', $tmp);
	foreach ($tmp as $item) {
		list($k, $v) = explode('=', trim($item));
		$migxTvData[ $k ] = $v;
		$tvNames[ $k ] = $k;
	}
}


	


$tvNamesFromTv = array_flip($tvNames);
//pre($tvNames);
//pre($tvNamesFromTv);
if (!empty($tvNames)) {
	$qTv = "SELECT
	TV.name, CV.contentid id, CV.value
	FROM {$tblPrefix}site_tmplvar_contentvalues as CV, {$tblPrefix}site_tmplvars as TV
	WHERE
	TV.name IN ('".implode("','", $tvNames)."')
	&&
	CV.tmplvarid = TV.id
	";
//	pre($qTv);
	$stmTv = $modx->query($qTv);
	if ($stmTv && $stmTv->rowCount()) {
		while ($res = $stmTv->fetch(PDO::FETCH_ASSOC)) {
//			pre($res);
			
			if (!isset($contentTvValues[ $res['id'] ]))
				$contentTvValues[ $res['id'] ] = [];
			
			$contentTvValues[ $res['id'] ][ $tvNamesFromTv[$res['name']] ] = $res['value'];
			
		}
	}
}

$defaultPriority = isset($defaultPriority) ? $defaultPriority : '0.5';
$changefreqDefault = null;
if (isset($defaultChangefreq))
	$changefreqDefault = trim($defaultChangefreq);

$sitemap = $indexPage = [];
if (!empty($siteContent)) {
	foreach ($siteContent as $id => $itemContent) {
		
		$pid = $itemContent['pid'];
		
		if (!isset($sitemap[$pid]))
			$sitemap[$pid] = [];
		
		$changefreq = 'never';
		
		if (isset($contentTvValues[ $id ][ 'changefreq' ]))
			$changefreq = $contentTvValues[ $id ][ 'changefreq' ];
		elseif (isset($changefreqForTemplate[ $itemContent['template'] ]))
			$changefreq = $changefreqForTemplate[ $itemContent['template'] ];
		elseif (isset($changefreqDefault))
			$changefreq = $defaultChangefreq;
		else {
			if ($itemContent['editedon'] >= GHOUL_SM_EDITED_HOUR)
				$changefreq = 'hour';
			elseif ($itemContent['editedon'] >= GHOUL_SM_EDITED_DAY)
				$changefreq = 'daily';
			elseif ($itemContent['editedon'] >= GHOUL_SM_EDITED_WEEK)
				$changefreq = 'weekly';
			elseif ($itemContent['editedon'] >= GHOUL_SM_EDITED_MONTH)
				$changefreq = 'monthly';
			elseif ($itemContent['editedon'] >= GHOUL_SM_EDITED_YEAR)
				$changefreq = 'yearly';
		}
		
		
		

		if (isset($contentTvValues[ $id ][ 'priority' ]))
			$priority = $contentTvValues[ $id ][ 'priority' ];
		elseif (isset($priorityForTemplate[ $itemContent['template'] ]))
			$priority = $priorityForTemplate[ $itemContent['template'] ];
		elseif (isset($childsPriority[ $pid ]))
			$priority = $childsPriority[ $pid ];
		else
			$priority = $defaultPriority;
		
		
		$images = [];
		if ($imgAllowed && !empty($imageTvNames) && isset($contentTvValues[ $id ])) {

			foreach ($contentTvValues[ $id ] as $tv_name => $tv_value) {

				//простые поля
				if (in_array($tv_name, $imageTvNames)) {
					$images[] = ghoul_sm_imgPath($tv_value, $siteURL);
				}
				//для MIGX
				if (array_key_exists($tv_name, $migxTvData)) {
					$arr = json_decode($tv_value, true);

					if (!empty($arr)) {
						foreach ($arr as $_arrItm) {
							if (isset($_arrItm[ $migxTvData[ $tv_name ]]) && $_arrItm[ $migxTvData[ $tv_name ]] != '')
								$images[] = ghoul_sm_imgPath($_arrItm[ $migxTvData[ $tv_name ]], $siteURL);
						}
					}
				}
			}
		}
		
		$d = [
			'id'         => $id,
			'url'        => $siteURL . ghoul_sm_escape(ltrim($itemContent['uri'], '/')),
			'lastmod'    => date('Y-m-d', $itemContent['editedon']),
			'changefreq' => $changefreq,
			'priority'   => $priority
		];
		
		if (!empty($images))
			$d['images'] = $images;
		

		
		if ($itemContent['id'] == $siteStartPageId) {
			
			$d['url']   = $siteURL;
			$d['changefreq'] = $changefreq;//isset($contentTvValues[ $id ][ 'changefreq' ]) ? $contentTvValues[ $id ][ 'changefreq' ] : 'daily';
			$d['priority'] = $priority;//isset($contentTvValues[ $id ][ 'priority' ]) ? $contentTvValues[ $id ][ 'priority' ] : '1.0';
			
			$indexPage = $d;
		}
		elseif ($priority) {
			$sitemap[ $pid ][] = $d;
		}
	}
}

//pre($indexPage);
//pre($sitemap, 1, '<hr>');
function ghoul_sitemap($items, $sitemap) {
	$c = '';
	foreach ($items as $item) {
		$c .= ghoul_sm_urlItem($item);
		
		if (isset($sitemap[ $item['id'] ]))
			$c .= ghoul_sitemap($sitemap[ $item['id'] ], $sitemap);
	}
	
	return $c;
}
function ghoul_sm_urlItem($item) {
	global $showLastmod;
	$c = '<url>' . "\n";
	$c .= '<loc>' . $item['url'] . '</loc>' . "\n";
	if ($showLastmod)
		$c .= '<lastmod>' . $item['lastmod'] . '</lastmod>' . "\n";
	if (isset($item['changefreq']))
		$c .= '<changefreq>' . $item['changefreq'] . '</changefreq>' . "\n";
	$c .= '<priority>' . ($item['priority']) . '</priority>' . "\n";
	
	if (!empty($item['images'])) {
		foreach ($item['images'] as $image) {
			$c .= '<image:image>' . "\n";
			$c .= '<image:loc>' . $image .'</image:loc>' . "\n";
			$c .= '</image:image>' . "\n";
		}
	}
	
	$c .= '</url>' . "\n";
	
	
	
	return $c;
}
function ghoul_sm_imgPath($url, $siteURL) {
	if (stripos($url, 'http') !== false)
		return $url;
	
	$url = $siteURL . ghoul_sm_escape(ltrim($url, '/'));
	
	return $url;
}

function ghoul_sm_escape($str) {
	$str = str_replace('&', '&amp;', $str);
	$str = str_replace("'", '&apos;', $str);
	$str = str_replace('""', '&quot;', $str);
	$str = str_replace('>', '&gt;', $str);
	$str = str_replace('<', '&lt;', $str);
	return $str;
}



$c = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
if (isset($styleURI) && $styleURI != '') {
//	$filePath = $_SERVER['DOCUMENT_ROOT'] . '/'. ltrim($styleURI, '/');
//	print_r($filePath);
	if (file_exists($_SERVER['DOCUMENT_ROOT'] . '/'. ltrim($styleURI, '/')))
		$c .= '<?xml-stylesheet type="text/xsl" href="/'.ltrim($styleURI, '/').'"?>' . "\n";
}
$c .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'.(
	(isset($imgAllowed) && intval($imgAllowed)) ? ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' : ''
	).'>' . "\n";

if (!empty($indexPage)) {
	$c .= ghoul_sm_urlItem($indexPage);
}
if (!empty($sitemap) && isset($sitemap[0]))
	$c .= ghoul_sitemap($sitemap[0], $sitemap);


$c .= '</urlset>';
$tt = round( (microtime(true) - $timeStart), 3 );
//pre($tt);
return $c;