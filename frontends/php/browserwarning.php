<?php
/*
** Zabbix
** Copyright (C) 2001-2019 Zabbix SIA
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License
** along with this program; if not, write to the Free Software
** Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
**/


require_once dirname(__FILE__).'/include/gettextwrapper.inc.php';
require_once dirname(__FILE__).'/include/func.inc.php';
require_once dirname(__FILE__).'/include/html.inc.php';
require_once dirname(__FILE__).'/include/defines.inc.php';
require_once dirname(__FILE__).'/include/classes/mvc/CView.php';
require_once dirname(__FILE__).'/include/classes/html/CObject.php';
require_once dirname(__FILE__).'/include/classes/html/CTag.php';
require_once dirname(__FILE__).'/include/classes/html/CLink.php';
require_once dirname(__FILE__).'/include/classes/helpers/CBrandHelper.php';
require_once dirname(__FILE__).'/include/html.inc.php';
require_once dirname(__FILE__).'/include/classes/core/CAssetsFileCache.php';

$assets_prefix = '';

if (ZBX_WEBCACHE_PATH) {
	$assets_service = new CAssetsFileCache(__DIR__);

	if (!$assets_service->boot()) {
		header('Location: cachewarning.php');

		exit;
	}

	$assets_prefix = ZBX_WEBCACHE_PATH.'/'.$assets_service->cache_tag.'/';
}

$browserWarningForm = new CView('general.browserwarning', compact('assets_prefix'));
$browserWarningForm->render();
