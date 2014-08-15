<?php
/*
** Zabbix
** Copyright (C) 2001-2014 Zabbix SIA
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


/**
 * Shows surrogate screen filled with graphs generated by selected graph prototype or preview of graph prototype.
 */
class CScreenLldGraph extends CScreenLldGraphBase {

	/**
	 * @var array
	 */
	protected $createdGraphIds = array();

	/**
	 * @var array
	 */
	protected $graphPrototype = null;

	/**
	 * Returns screen items for surrogate screen
	 *
	 * @return array
	 */
	protected function getSurrogateScreenItems() {
		$createdGraphIds = $this->getCreatedGraphIds();
		return $this->getGraphsForSurrogateScreen($createdGraphIds);
	}

	/**
	 * Retrieves graphs created for graph prototype given as resource for this screen item
	 * and returns array of the graph IDs.
	 *
	 * @return array
	 */
	protected function getCreatedGraphIds() {
		if (!$this->createdGraphIds) {
			$graphPrototype = $this->getGraphPrototype();

			if ($graphPrototype) {
				// Get all created (discovered) graphs for host of graph prototype.
				$allCreatedGraphs = API::Graph()->get(array(
					'output' => array('name'),
					'hostids' => array($graphPrototype['discoveryRule']['hostid']),
					'selectGraphDiscovery' => array('graphid', 'parent_graphid'),
					'filter' => array('flags' => ZBX_FLAG_DISCOVERY_CREATED),
				));

				// Collect those graph IDs where parent graph is graph prototype selected for
				// this screen item as resource.
				foreach ($allCreatedGraphs as $graph) {
					if ($graph['graphDiscovery']['parent_graphid'] == $graphPrototype['graphid']) {
						$this->createdGraphIds[$graph['graphid']] = $graph['name'];
					}
				}
				natsort($this->createdGraphIds);
				$this->createdGraphIds = array_keys($this->createdGraphIds);
			}
		}

		return $this->createdGraphIds;
	}

	/**
	 * Makes graph screen items from given graph IDs.
	 *
	 * @param array $graphIds
	 *
	 * @return array
	 */
	protected function getGraphsForSurrogateScreen(array $graphIds) {
		$screenItemTemplate = $this->getScreenItemTemplate(SCREEN_RESOURCE_GRAPH);

		$screenItems = array();
		foreach ($graphIds as $graphId) {
			$screenItem = $screenItemTemplate;

			$screenItem['resourceid'] = $graphId;
			$screenItem['screenitemid'] = $graphId;

			$screenItems[] = $screenItem;
		}

		return $screenItems;
	}

	/**
	 * Resolves and retrieves effective graph prototype used in this screen item.
	 *
	 * @return mixed
	 */
	protected function getGraphPrototype() {
		if ($this->graphPrototype === null) {
			$options = array();
			$screen = $this->getScreen(array('templateid'));

			if (($this->screenitem['dynamic'] == SCREEN_DYNAMIC_ITEM || $screen['templateid']) && $this->hostid) {
				// This branch is taken if screen item is 1) dynamic or 2) in template screen. This means that real
				// graph prototype must be looked up by "name" of graph prototype used as resource ID for this screen
				// item and by current host - either from host selection dropdown or from URL when accessing host
				// screen from Monitoring/Latest data.
				$frontGraphPrototype = API::GraphPrototype()->get(array(
					'output' => array('name'),
					'graphids' => array($this->screenitem['resourceid'])
				));
				$frontGraphPrototype = reset($frontGraphPrototype);

				$options['hostids'] = array($this->hostid);
				$options['filter'] = array('name' => $frontGraphPrototype['name']);
			}
			else {
				// Otherwise just use resource ID given to to this screen item.
				$options['graphids'] = array($this->screenitem['resourceid']);
			}

			$defaultOptions = array(
				'output' => array('graphid', 'name', 'graphtype', 'show_legend', 'show_3d', 'templated'),
				'selectDiscoveryRule' => array('hostid')
			);
			$options = zbx_array_merge($defaultOptions, $options);

			$selectedGraphPrototype = API::GraphPrototype()->get($options);
			$this->graphPrototype = reset($selectedGraphPrototype);
		}

		return $this->graphPrototype;
	}

	/**
	 * Returns output for preview of graph prototype.
	 *
	 * @return CTag
	 */
	protected function getPreviewOutput() {
		$graphPrototype = $this->getGraphPrototype();

		switch ($graphPrototype['graphtype']) {
			case GRAPH_TYPE_NORMAL:
			case GRAPH_TYPE_STACKED:
				$url = 'chart3.php';
				break;

			case GRAPH_TYPE_EXPLODED:
			case GRAPH_TYPE_3D_EXPLODED:
			case GRAPH_TYPE_3D:
			case GRAPH_TYPE_PIE:
				$url = 'chart7.php';
				break;

			case GRAPH_TYPE_BAR:
			case GRAPH_TYPE_COLUMN:
			case GRAPH_TYPE_BAR_STACKED:
			case GRAPH_TYPE_COLUMN_STACKED:
				$url = 'chart_bar.php';
				break;

			default:
				show_error_message(_('Graph prototype not found.'));
				exit;
		}

		$graphPrototypeItems = API::GraphItem()->get(array(
			'output' => array(
				'gitemid', 'itemid', 'sortorder', 'flags', 'type', 'calc_fnc',  'drawtype', 'yaxisside', 'color'
			),
			'graphids' => array($graphPrototype['graphid'])
		));

		$queryParams = array(
			'items' => $graphPrototypeItems,
			'graphtype' => $graphPrototype['graphtype'],
			'period' => 3600,
			'legend' => $graphPrototype['show_legend'],
			'graph3d' => $graphPrototype['show_3d'],
			'width' => $this->screenitem['width'],
			'height' => $this->screenitem['height'],
			'name' => $graphPrototype['name']
		);

		$url .= '?'.http_build_query($queryParams);

		$img = new CImg($url);
		$img->preload();

		return new CSpan($img);
	}

	/**
	 * Returns content to be shown when there are no items for surrogate screen.
	 *
	 * @return CTag
	 */
	protected function getNoScreenItemsOutput() {
		return new CTableInfo(_('No LLD created graphs found.'));
	}
}
