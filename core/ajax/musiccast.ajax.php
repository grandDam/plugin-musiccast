<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');

	if (!isConnect()) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	ajax::init();

	if (init('action') == 'syncNetwork') {
        musiccast::syncNetwork();
		ajax::success();
	}

	if (init('action') == 'getQueue') {
		$mcast = musiccast::byId(init('id'));
		if (!is_object($mcast)) {
			ajax::success();
		}
		ajax::success($mcast->getQueue());
	}

	if (init('action') == 'playTrack') {
        $mcast = musiccast::byId(init('id'));
		if (!is_object($mcast)) {
			ajax::success();
		}
		ajax::success($mcast->playTrack(init('position')));
	}

	if (init('action') == 'removeTrack') {
        $mcast = musiccast::byId(init('id'));
		if (!is_object($mcast)) {
			ajax::success();
		}
		ajax::success($mcast->removeTrack(init('position')));
	}

	if (init('action') == 'emptyQueue') {
        $mcast = musiccast::byId(init('id'));
		if (!is_object($mcast)) {
			ajax::success();
		}
		ajax::success($mcast->emptyQueue());
	}

	if (init('action') == 'playPlaylist') {
        $mcast = musiccast::byId(init('id'));
		if (!is_object($mcast)) {
			ajax::success();
		}
		$cmd = $mcast->getCmd(null, 'play_playlist');
		$cmd->execCmd(array('title' => init('playlist')));
		ajax::success();
	}

	if (init('action') == 'playFavorite') {
        $mcast = musiccast::byId(init('id'));
		if (!is_object($mcast)) {
			ajax::success();
		}
		$cmd = $mcast->getCmd(null, 'play_favorite');
		$cmd->execCmd(array('title' => init('favorite')));
		ajax::success();
	}

	if (init('action') == 'addSpeaker') {
        $mcast = musiccast::byId(init('id'));
		if (!is_object($mcast)) {
			ajax::success();
		}
		$cmd = $mcast->getCmd(null, 'add_speaker');
		$cmd->execCmd(array('title' => init('speaker')));
		ajax::success();
	}

	if (init('action') == 'removeSpeaker') {
        $mcast = musiccast::byId(init('id'));
		if (!is_object($mcast)) {
			ajax::success();
		}
		$cmd = $mcast->getCmd(null, 'remove_speaker');
		$cmd->execCmd(array('title' => init('speaker')));
		ajax::success();
	}

	if (init('action') == 'getMusicCast') {
		if (init('object_id') == '') {
			$object = object::byId($_SESSION['user']->getOptions('defaultDashboardObject'));
		} else {
			$object = object::byId(init('object_id'));
		}
		if (!is_object($object)) {
			$object = object::rootObject();
		}
		$return = array();
		$return['eqLogics'] = array();
		if (init('object_id') == '') {
			foreach (object::all() as $object) {
				foreach ($object->getEqLogic(true, false, 'musiccast') as $mcast) {
					$return['eqLogics'][] = $mcast->toHtml(init('version'));
				}
			}
		} else {
			foreach ($object->getEqLogic(true, false, 'musiccast') as $mcast) {
				$return['eqLogics'][] = $mcast->toHtml(init('version'));
			}
			foreach (object::buildTree($object) as $child) {
                $mcasts = $child->getEqLogic(true, false, 'musiccast');
				if (count($mcasts) > 0) {
					foreach ($mcasts as $mcast) {
						$return['eqLogics'][] = $mcast->toHtml(init('version'));
					}
				}
			}
		}
		ajax::success($return);
	}

	if (init('action') == 'updateMusicCast') {
		musiccast::updateMusicCast();
		ajax::success();
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());
}

