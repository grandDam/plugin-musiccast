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

/* * ***************************Includes********************************* */

use MusicCast\Speaker;

require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../vendor/autoload.php';


class musiccast extends eqLogic
{
    /*     * *************************Attributs****************************** */

    private static $_musiccast = null;
    private static $_eqLogics = null;
    public static $_widgetPossibility = array(
        'custom' => true,
        'custom::layout' => false,
        'parameters' => array(
            'sub-background-color' => array(
                'name' => 'Couleur de la barre de contrôle',
                'type' => 'color',
                'default' => 'rgba(var(--cat-multimedia-color), var(--opacity))',
                'allow_transparent' => true,
                'allow_displayType' => true,
            ),
            'sub-icon-color' => array(
                'name' => 'Couleur des icônes de la barre de contrôle',
                'type' => 'color',
                'default' => 'var(--eqTitle-color)',
                'allow_transparent' => true,
                'allow_displayType' => true,
            ),
        ),
    );

    public static $_device_list = array(
        'WX-010' => array('WX-010', 'WX-010'),
        'WX-030' => array('WX-030', 'WX-030'),
        'RESTIO' => array('RESTIO', 'RESTIO'),
        'NX-N500' => array('NX-N500', 'NX-N500'),
        'SOUNDBAR' => array('SOUNDBAR', 'Sound Bar'),
        'AMPLIFIER' => array('AMPLIFIER', 'Home Cinema Amplifier'),
        'GATEWAY' => array('GATEWAY', 'Gateway'),
        'ELEMENT' => array('ELEMENT', 'HI-FI Element'),
        'SYSTEM' => array('SYSTEM', 'HI-FI System'),
        'OTHER' => array('OTHER', 'Autres')
    );

    /*     * ***********************Methode static*************************** */

    public static function restore()
    {
        try {
            musiccast::syncNetwork();
        } catch (Exception $e) {

        }
    }

    public static function dependancy_info()
    {
        $return = array();
        $return['log'] = 'musiccast_update';
        $return['state'] = 'ok';
        $return['progress_file'] = jeedom::getTmpFolder('musiccast') . '/dependance';
        return $return;
    }

    public static function dependancy_install()
    {
        log::remove(__CLASS__ . '_update');
        return array('script' => dirname(__FILE__) . '/../../resources/install_#stype#.sh ' . jeedom::getTmpFolder('musiccast') . '/dependance', 'log' => log::getPathToLog(__CLASS__ . '_update'));
    }

    public static function deamon_info()
    {
        $return = array();
        $return['log'] = '';
        $return['state'] = 'nok';
        $cron = cron::byClassAndFunction('musiccast', 'pull');
        if (is_object($cron) && $cron->running()) {
            $return['state'] = 'ok';
        }
        $return['launchable'] = 'ok';
        return $return;
    }

    public static function deamon_start()
    {
        self::deamon_stop();
        $deamon_info = self::deamon_info();
        if ($deamon_info['launchable'] != 'ok') {
            throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
        }
        $cron = cron::byClassAndFunction('musiccast', 'pull');
        if (!is_object($cron)) {
            throw new Exception(__('Tache cron introuvable', __FILE__));
        }
        $cron->run();
    }

    public static function deamon_stop()
    {
        $cron = cron::byClassAndFunction('musiccast', 'pull');
        if (!is_object($cron)) {
            throw new Exception(__('Tache cron introuvable', __FILE__));
        }
        $cron->halt();
    }

    public static function deamon_changeAutoMode($_mode)
    {
        $cron = cron::byClassAndFunction('musiccast', 'pull');
        if (!is_object($cron)) {
            throw new Exception(__('Tache cron introuvable', __FILE__));
        }
        $cron->setEnable($_mode);
        $cron->save();
    }


    public static function getMusicCast($_emptyCache = false)
    {
        if (self::$_musiccast === null) {
            self::$_musiccast = new MusicCast\Network(null, log::getLogger('musiccast'));
        }
        return self::$_musiccast;
    }

    public static function cronDaily()
    {
        self::deamon_start();
    }

    public static function syncNetwork()
    {
        $speakers = musiccast::getSpeakers();
        foreach ($speakers as $speaker) {
            $eqLogic = musiccast::byLogicalId($speaker->getIp(), 'musiccast');
            if (!is_object($eqLogic)) {
                $eqLogic = new self();
                $eqLogic->setLogicalId($speaker->getIp());
                $eqLogic->setName($speaker->getUuid() . '-' . $speaker->getName());
                $object = object::byName($speaker->getUuid());
                if (is_object($object)) {
                    $eqLogic->setObject_id($object->getId());
                    $eqLogic->setName($speaker->getUuid() . '-' . $speaker->getName());
                }
                $model = $speaker->getModel();
                if (strpos($model, 'WX-030') !== false) {
                    $eqLogic->setConfiguration('model', 'WX-030');
                } elseif (strpos($model, 'WX-010') !== false) {
                    $eqLogic->setConfiguration('model', 'WX-010');
                } elseif (strpos($model, 'ISX') !== false) {
                    $eqLogic->setConfiguration('model', 'RESTIO');
                } elseif (strpos($model, 'YSP') !== false
                    || strpos($model, 'YAS') !== false
                    || strpos($model, 'SRT') !== false) {
                    $eqLogic->setConfiguration('model', 'SOUNDBAR');
                } elseif (strpos($model, 'CX-A') !== false
                    || strpos($model, 'RX-A') !== false
                    || strpos($model, 'RX-V') !== false
                    || strpos($model, 'RX-S') !== false) {
                    $eqLogic->setConfiguration('model', 'AMPLIFIER');
                } elseif (strpos($model, 'WXA-') !== false
                    || strpos($model, 'WXC-') !== false
                    || strpos($model, 'WXAD-') !== false
                    || strpos($model, 'WXC-') !== false) {
                    $eqLogic->setConfiguration('model', 'GATEWAY');
                } elseif (strpos($model, 'R-N') !== false
                    || strpos($model, 'CD-N') !== false
                    || strpos($model, 'CRX-N') !== false) {
                    $eqLogic->setConfiguration('model', 'ELEMENT');
                } elseif (strpos($model, 'MCR-N') !== false
                    || strpos($model, 'CD-N') !== false
                    || strpos($model, 'CRX-N') !== false) {
                    $eqLogic->setConfiguration('model', 'SYSTEM');
                } elseif (strpos($model, 'NX-N500') !== false) {
                    $eqLogic->setConfiguration('model', 'NX-N500');
                } else {
                    $eqLogic->setConfiguration('model', 'OTHER');
                }
                $eqLogic->setEqType_name('musiccast');
                $eqLogic->setIsVisible(1);
                $eqLogic->setIsEnable(1);
            }
            $speakers_array = array();
            foreach ($speakers as $otherSpeaker) {
                if ($otherSpeaker->getUuid() !== $speaker->getUuid())
                    $speakers_array[$otherSpeaker->getIp()] = $otherSpeaker->getName();
            }
            $eqLogic->setConfiguration('speakers', json_encode($speakers_array));
            $eqLogic->save();
        }
        self::deamon_start();
    }

    public static function cron5()
    {
        log::add('musiccast', 'debug', 'Refreshing playlist and favorites');
        self::$_eqLogics = self::byType('musiccast');
        foreach (self::$_eqLogics as $eqLogic) {
            if ($eqLogic->getIsEnable() == 0) {
                continue;
            }
            if ($eqLogic->getLogicalId() == '') {
                continue;
            }
            $speaker = $eqLogic->getSpeaker();
            if ($speaker != null) {
                self::getPlayLists($speaker->getIp());
                self::getFavorites($speaker->getIp());
            }
        }
    }

    public static function pull($_eqLogic_id = null)
    {
        self::$_eqLogics = self::byType('musiccast');
        $_groups = array();
        foreach (self::$_eqLogics as $eqLogic) {
            if ($_eqLogic_id != null && $_eqLogic_id != $eqLogic->getId()) {
                continue;
            }
            if ($eqLogic->getIsEnable() == 0) {
                continue;
            }
            if ($eqLogic->getLogicalId() == '') {
                continue;
            }
            try {
                $changed = false;
                $controller = $eqLogic->getController();
                if ($controller != null) {
                    //log::add('musiccast', 'debug', 'Pulling controller ' . $controller->getName());
                    $group_id = $controller->getGroup();
                    $state = self::convertState($controller->getStateName());
                    if ($state == __('Transition', __FILE__)) {
                        continue;
                    }
                    $state_details = $controller->getStateDetails();
                    $group = $controller->getGroupName();
                    $shuffle = ($controller->getShuffle() == '') ? 0 : $controller->getShuffle();
                    $repeat = ($controller->getRepeat() == '') ? 0 : $controller->getRepeat();
                    $mute = ($controller->isMuted() == '') ? 0 : $controller->isMuted();
                    $power = ($controller->isPowerOn() == '') ? 0 : $controller->isPowerOn();

                    $is_coordinator = ($controller->isCoordinator() == '') ? 0 : $controller->isCoordinator();

                    $track = null;
                    $title = '';
                    $album = '';
                    $artist = '';

                    if ($controller->isStreaming()) {
                        if (file_exists(dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg')) {
                            unlink(dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg');
                        }
                        $state = '';
                    } else {
                        $track = $state_details->track;
                        if ($track != null) {
                            $title = $track->getTitle();
                            $album = $track->getAlbum();
                            $artist = $track->getArtist();
                            if ($track->getAlbumArt() != '') {
                                if ($eqLogic->checkAndUpdateCmd('track_image', $track->getAlbumArt())) {
                                    file_put_contents(dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg', file_get_contents($track->getAlbumArt()));
                                    //$eqLogic->checkAndUpdateCmd('dominantColor', getDominantColor(dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg'));
                                    $changed = true;
                                }
                            } else if (file_exists(dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg')) {
                                unlink(dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg');
                            }
                        }
                    }


                    $input = $controller->getInput();

                    if ($title == '') {
                        $title = __('Aucun', __FILE__);
                    }
                    if ($state == '') {
                        $state = __('Aucun', __FILE__);
                    }
                    if ($album == '') {
                        $album = __('Aucun', __FILE__);
                    }
                    if ($artist == '') {
                        $artist = __('Aucun', __FILE__);
                    }
                    if ($state == '') {
                        $state = __('Aucun', __FILE__);
                    }
                    if ($input == '') {
                        $input = __('Aucun', __FILE__);
                    }

                    if ($controller->getGroup() != Speaker::NO_GROUP) {
                        $_groups[$group_id] = array();
                        $_groups[$group_id]['group'] = $group;
                        $_groups[$group_id]['state'] = $state;
                        $_groups[$group_id]['title'] = $title;
                        $_groups[$group_id]['album'] = $album;
                        $_groups[$group_id]['artist'] = $artist;
                        $_groups[$group_id]['album_art'] = ($track == null ? '' : $track->getAlbumArt());
                    }


                    $changed |= $eqLogic->checkAndUpdateCmd('group', $group);
                    $changed |= $eqLogic->checkAndUpdateCmd('state', $state);
                    $changed |= $eqLogic->checkAndUpdateCmd('volume', $controller->getVolume());
                    $changed |= $eqLogic->checkAndUpdateCmd('shuffle_state', $shuffle);
                    $changed |= $eqLogic->checkAndUpdateCmd('mute_state', $mute);
                    $changed |= $eqLogic->checkAndUpdateCmd('repeat_state', $repeat);
                    $changed |= $eqLogic->checkAndUpdateCmd('track_title', $title);
                    $changed |= $eqLogic->checkAndUpdateCmd('track_album', $album);
                    $changed |= $eqLogic->checkAndUpdateCmd('track_artist', $artist);
                    $changed |= $eqLogic->checkAndUpdateCmd('input', $input);
                    $changed |= $eqLogic->checkAndUpdateCmd('power_state', $power);
                    $changed |= $eqLogic->checkAndUpdateCmd('coordinator_state', $is_coordinator);

                    if ($changed) {
                        log::add('musiccast', 'debug', "Refreshing widget");
                        $eqLogic->refreshWidget();
                    }
                    if ($eqLogic->getConfiguration('amusiccastNumberFailed', 0) > 0) {
                        foreach (message::byPluginLogicalId('musiccast', 'musiccastLost' . $eqLogic->getId()) as $message) {
                            $message->remove();
                        }
                        $eqLogic->setConfiguration('amusiccastNumberFailed', 0);
                        $eqLogic->save();
                    }
                }
            } catch (Exception $e) {
                if ($_eqLogic_id != null) {
                    log::add('musiccast', 'error', $e->getMessage());
                } else {
                    if ($eqLogic->getConfiguration('amusiccastNumberFailed', 0) == 150) {
                        log::add('musiccast', 'error', __('Erreur sur ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $e->getMessage(), 'musiccastLost' . $eqLogic->getId());
                    } else {
                        $eqLogic->setConfiguration('amusiccastNumberFailed', $eqLogic->getConfiguration('amusiccastNumberFailed', 0) + 1);
                        $eqLogic->save();
                    }
                }
            }
        }
        foreach (self::$_eqLogics as $eqLogic) {
            if ($_eqLogic_id != null && $_eqLogic_id != $eqLogic->getId()) {
                continue;
            }
            if ($eqLogic->getIsEnable() == 0) {
                continue;
            }
            if ($eqLogic->getLogicalId() == '') {
                continue;
            }
            try {
                $changed = false;
                $speaker = $eqLogic->getSpeaker();
                if ($speaker != null && !$speaker->isCoordinator()) {
                    $group_id = $speaker->getGroup();
                    $group = $_groups[$group_id]['group'];
                    $power = ($speaker->isPowerOn() == '') ? 0 : $speaker->isPowerOn();
                    $is_coordinator = false;
                    $state = $_groups[$group_id]['state'];
                    $title = $_groups[$group_id]['title'];
                    $album = $_groups[$group_id]['album'];
                    $artist = $_groups[$group_id]['artist'];
                    $input = $speaker->getInput();
                    $album_art = $_groups[$group_id]['album_art'];

                    if ($album_art != '') {
                        if ($eqLogic->checkAndUpdateCmd('track_image', $album_art)) {
                            file_put_contents(dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg', file_get_contents($album_art));
                            //$eqLogic->checkAndUpdateCmd('dominantColor', getDominantColor(dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg'));
                            $changed = true;
                        }
                    } else if (file_exists(dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg')) {
                        unlink(dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg');
                    }


                    $changed |= $eqLogic->checkAndUpdateCmd('group', $group);
                    $changed |= $eqLogic->checkAndUpdateCmd('state', $state);
                    $changed |= $eqLogic->checkAndUpdateCmd('volume', $speaker->getVolume());
                    $changed |= $eqLogic->checkAndUpdateCmd('track_title', $title);
                    $changed |= $eqLogic->checkAndUpdateCmd('track_album', $album);
                    $changed |= $eqLogic->checkAndUpdateCmd('track_artist', $artist);
                    $changed |= $eqLogic->checkAndUpdateCmd('input', $input);
                    $changed |= $eqLogic->checkAndUpdateCmd('power_state', $power);
                    $changed |= $eqLogic->checkAndUpdateCmd('coordinator_state', $is_coordinator);
                    if ($changed) {
                        log::add('musiccast', 'debug', "refreshWidget");
                        $eqLogic->refreshWidget();
                    }
                    if ($eqLogic->getConfiguration('amusiccastNumberFailed', 0) > 0) {
                        foreach (message::byPluginLogicalId('musiccast', 'musiccastLost' . $eqLogic->getId()) as $message) {
                            $message->remove();
                        }
                        $eqLogic->setConfiguration('amusiccastNumberFailed', 0);
                        $eqLogic->save();
                    }
                }
            } catch (Exception $e) {
                if ($_eqLogic_id != null) {
                    log::add('musiccast', 'error', $e->getMessage());
                } else {
                    if ($eqLogic->getConfiguration('amusiccastNumberFailed', 0) == 150) {
                        log::add('musiccast', 'error', __('Erreur sur ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $e->getMessage(), 'musiccastLost' . $eqLogic->getId());
                    } else {
                        $eqLogic->setConfiguration('amusiccastNumberFailed', $eqLogic->getConfiguration('amusiccastNumberFailed', 0) + 1);
                        $eqLogic->save();
                    }
                }
            }
        }

    }

    public
    static function convertState($_state)
    {
        switch ($_state) {
            case 'play':
                return __('Lecture', __FILE__);
            case 'pause':
                return __('Pause', __FILE__);
            case 'stop':
                return __('Arrêté', __FILE__);
            case 'TRANSITIONING':
                return __('Transition', __FILE__);
        }
        return $_state;
    }

    public
    static function getSpeakers()
    {
        return musiccast::getMusicCast()->getSpeakers();
    }

    public
    static function getSpeakerByIp($_ip)
    {
        $musicCast = self::getMusicCast();
        $speaker = $musicCast->getSpeakerByIp($_ip);
        return $speaker;
    }

    public
    static function getControllerByIp($_ip)
    {
        $musicCast = self::getMusicCast();
        return $musicCast->getControllerByIp($_ip);
    }


    public
    static function getPlayLists($ip)
    {
        $musicCast = self::getMusicCast();
        $speaker = $musicCast->getSpeakerByIp($ip);
        if ($speaker != null) {
            $mcast = musiccast::byLogicalId($ip, 'musiccast');
            $playlists = $speaker->getPlaylists();
            $array = array();
            foreach ($playlists as $playlist) {
                $array[$playlist->getName()] = $playlist->getName();
            }
            $cmd = $mcast->getCmd('action', 'play_playlist');
            if (is_object($cmd)) {
                $cmd->setDisplay('title_possibility_list', json_encode(array_values($array)));
                $list_values = '';
                foreach ($array as $item) {
                    $list_values = $list_values . $item . '|' . $item . ';';
                }
                $cmd->setConfiguration('listValue', rtrim($list_values, ';'));
                $cmd->save();
            }

            return $playlists;
        }
        return array();
    }

    public
    static function getInputs($ip)
    {
        $musicCast = self::getMusicCast();
        $speaker = $musicCast->getSpeakerByIp($ip);
        if ($speaker != null) {
            $mcast = musiccast::byLogicalId($ip, 'musiccast');
            $inputList = $speaker->getInputList();
            $cmd = $mcast->getCmd('action', 'change_input');
            if (is_object($cmd)) {
                $list_values = '';
                foreach ($inputList as $input) {
                    $list_values = $list_values . $input . '|' . $input . ';';
                }
                $cmd->setConfiguration('listValue', rtrim($list_values, ';'));
                $cmd->save();
            }

            return $inputList;
        }
        return array();
    }

    public
    static function getFavorites($ip)
    {
        $musicCast = self::getMusicCast();
        $speaker = $musicCast->getSpeakerByIp($ip);
        if ($speaker != null) {
            $mcast = musiccast::byLogicalId($ip, 'musiccast');
            $favorites = $speaker->getFavorites();
            $array = array();
            foreach ($favorites as $favorite) {
                $array[$favorite->getName()] = $favorite->getName();
            }
            $cmd = $mcast->getCmd('action', 'play_favorite');
            if (is_object($cmd)) {
                $list_values = '';
                foreach ($array as $item) {
                    $list_values = $list_values . $item . '|' . $item . ';';
                }
                $cmd->setConfiguration('listValue', rtrim($list_values, ';'));
                $cmd->setDisplay('title_possibility_list', json_encode($array));
                $cmd->save();
            }

            return $favorites;
        }

        return array();
    }

    /*     * *********************Méthodes d'instance************************* */


    /**
     * @return \MusicCast\Speaker|null
     */
    public
    function getSpeaker()
    {
        return self::getSpeakerByIp($this->getLogicalId());

    }

    /**
     * @return \MusicCast\Controller|null
     */
    public
    function getController()
    {
        return self::getControllerByIp($this->getLogicalId());
    }

    public
    function preSave()
    {
        $this->setCategory('multimedia', 1);
    }

    public
    function postSave()
    {
        $state = $this->getCmd(null, 'state');
        if (!is_object($state)) {
            $state = new MusicCastCmd();
            $state->setLogicalId('state');
            $state->setName(__('Status', __FILE__));
        }
        $state->setType('info');
        $state->setSubType('string');
        $state->setConfiguration('repeatEventManagement', 'never');
        $state->setEqLogic_id($this->getId());
        $state->save();

        $play = $this->getCmd(null, 'play');
        if (!is_object($play)) {
            $play = new MusicCastCmd();
            $play->setLogicalId('play');
            $play->setName(__('Play', __FILE__));
        }
        $play->setType('action');
        $play->setSubType('other');
        $play->setGeneric_type('MEDIA_RESUME');
        $play->setEqLogic_id($this->getId());
        $play->save();

        $stop = $this->getCmd(null, 'stop');
        if (!is_object($stop)) {
            $stop = new MusicCastCmd();
            $stop->setLogicalId('stop');
            $stop->setName(__('Stop', __FILE__));
        }
        $stop->setType('action');
        $stop->setSubType('other');
        $stop->setGeneric_type('MEDIA_STOP');
        $stop->setEqLogic_id($this->getId());
        $stop->save();

        $pause = $this->getCmd(null, 'pause');
        if (!is_object($pause)) {
            $pause = new MusicCastCmd();
            $pause->setLogicalId('pause');
            $pause->setName(__('Pause', __FILE__));
        }
        $pause->setType('action');
        $pause->setSubType('other');
        $pause->setGeneric_type('MEDIA_PAUSE');
        $pause->setEqLogic_id($this->getId());
        $pause->save();

        $next = $this->getCmd(null, 'next');
        if (!is_object($next)) {
            $next = new MusicCastCmd();
            $next->setLogicalId('next');
            $next->setName(__('Suivant', __FILE__));
        }
        $next->setType('action');
        $next->setSubType('other');
        $next->setGeneric_type('MEDIA_NEXT');
        $next->setEqLogic_id($this->getId());
        $next->save();

        $previous = $this->getCmd(null, 'previous');
        if (!is_object($previous)) {
            $previous = new MusicCastCmd();
            $previous->setLogicalId('previous');
            $previous->setName(__('Précédent', __FILE__));
        }
        $previous->setType('action');
        $previous->setSubType('other');
        $previous->setGeneric_type('MEDIA_PREVIOUS');
        $previous->setEqLogic_id($this->getId());
        $previous->save();

        $mute = $this->getCmd(null, 'mute');
        if (!is_object($mute)) {
            $mute = new MusicCastCmd();
            $mute->setLogicalId('mute');
            $mute->setName(__('Muet', __FILE__));
        }
        $mute->setType('action');
        $mute->setSubType('other');
        $mute->setEqLogic_id($this->getId());
        $mute->save();

        $unmute = $this->getCmd(null, 'unmute');
        if (!is_object($unmute)) {
            $unmute = new MusicCastCmd();
            $unmute->setLogicalId('unmute');
            $unmute->setName(__('Non muet', __FILE__));
        }
        $unmute->setType('action');
        $unmute->setSubType('other');
        $unmute->setEqLogic_id($this->getId());
        $unmute->save();

        $mute_state = $this->getCmd(null, 'mute_state');
        if (!is_object($mute_state)) {
            $mute_state = new MusicCastCmd();
            $mute_state->setLogicalId('mute_state');
            $mute_state->setName(__('Muet status', __FILE__));
        }
        $mute_state->setType('info');
        $mute_state->setSubType('binary');
        $mute_state->setConfiguration('repeatEventManagement', 'never');
        $mute_state->setEqLogic_id($this->getId());
        $mute_state->save();

        $repeat = $this->getCmd(null, 'repeat');
        if (!is_object($repeat)) {
            $repeat = new MusicCastCmd();
            $repeat->setLogicalId('repeat');
            $repeat->setName(__('Répéter', __FILE__));
        }
        $repeat->setType('action');
        $repeat->setSubType('other');
        $repeat->setEqLogic_id($this->getId());
        $repeat->save();

        $repeat_state = $this->getCmd(null, 'repeat_state');
        if (!is_object($repeat_state)) {
            $repeat_state = new MusicCastCmd();
            $repeat_state->setLogicalId('repeat_state');
            $repeat_state->setName(__('Répéter status', __FILE__));
        }
        $repeat_state->setType('info');
        $repeat_state->setSubType('binary');
        $repeat_state->setConfiguration('repeatEventManagement', 'never');
        $repeat_state->setEqLogic_id($this->getId());
        $repeat_state->save();

        $shuffle = $this->getCmd(null, 'shuffle');
        if (!is_object($shuffle)) {
            $shuffle = new MusicCastCmd();
            $shuffle->setLogicalId('shuffle');
            $shuffle->setName(__('Aléatoire', __FILE__));
        }
        $shuffle->setType('action');
        $shuffle->setSubType('other');
        $shuffle->setEqLogic_id($this->getId());
        $shuffle->save();

        $shuffle_state = $this->getCmd(null, 'shuffle_state');
        if (!is_object($shuffle_state)) {
            $shuffle_state = new MusicCastCmd();
            $shuffle_state->setLogicalId('shuffle_state');
            $shuffle_state->setName(__('Aléatoire status', __FILE__));
        }
        $shuffle_state->setType('info');
        $shuffle_state->setSubType('binary');
        $shuffle_state->setConfiguration('repeatEventManagement', 'never');
        $shuffle_state->setEqLogic_id($this->getId());
        $shuffle_state->save();

        $volume = $this->getCmd(null, 'volume');
        if (!is_object($volume)) {
            $volume = new MusicCastCmd();
            $volume->setLogicalId('volume');
            $volume->setName(__('Volume status', __FILE__));
        }
        $volume->setUnite('%');
        $volume->setType('info');
        $volume->setSubType('numeric');
        $volume->setGeneric_type('VOLUME');
        $volume->setConfiguration('repeatEventManagement', 'never');
        $volume->setEqLogic_id($this->getId());
        $volume->save();

        $setVolume = $this->getCmd(null, 'setVolume');
        if (!is_object($setVolume)) {
            $setVolume = new MusicCastCmd();
            $setVolume->setLogicalId('setVolume');
            $setVolume->setName(__('Volume', __FILE__));
        }
        $setVolume->setType('action');
        $setVolume->setSubType('slider');
        $setVolume->setGeneric_type('SET_VOLUME');
        $setVolume->setValue($volume->getId());
        $setVolume->setEqLogic_id($this->getId());
        $setVolume->save();

        $group = $this->getCmd(null, 'group');
        if (!is_object($group)) {
            $group = new MusicCastCmd();
            $group->setLogicalId('group');
            $group->setName(__('Groupe', __FILE__));
        }
        $group->setType('info');
        $group->setSubType('string');
        $group->setConfiguration('repeatEventManagement', 'never');
        $group->setEqLogic_id($this->getId());
        $group->save();


        $track_title = $this->getCmd(null, 'track_title');
        if (!is_object($track_title)) {
            $track_title = new MusicCastCmd();
            $track_title->setLogicalId('track_title');
            $track_title->setName(__('Piste', __FILE__));
        }
        $track_title->setType('info');
        $track_title->setSubType('string');
        $track_title->setConfiguration('repeatEventManagement', 'never');
        $track_title->setEqLogic_id($this->getId());
        $track_title->save();

        $track_artist = $this->getCmd(null, 'track_artist');
        if (!is_object($track_artist)) {
            $track_artist = new MusicCastCmd();
            $track_artist->setLogicalId('track_artist');
            $track_artist->setName(__('Artiste', __FILE__));
        }
        $track_artist->setType('info');
        $track_artist->setSubType('string');
        $track_artist->setConfiguration('repeatEventManagement', 'never');
        $track_artist->setEqLogic_id($this->getId());
        $track_artist->save();

        $track_album = $this->getCmd(null, 'track_album');
        if (!is_object($track_album)) {
            $track_album = new MusicCastCmd();
            $track_album->setLogicalId('track_album');
            $track_album->setName(__('Album', __FILE__));
        }
        $track_album->setType('info');
        $track_album->setSubType('string');
        $track_album->setConfiguration('repeatEventManagement', 'never');
        $track_album->setEqLogic_id($this->getId());
        $track_album->save();

        $track_position = $this->getCmd(null, 'track_image');
        if (!is_object($track_position)) {
            $track_position = new MusicCastCmd();
            $track_position->setLogicalId('track_image');
            $track_position->setName(__('Image', __FILE__));
        }
        $track_position->setType('info');
        $track_position->setSubType('string');
        $track_position->setConfiguration('repeatEventManagement', 'never');
        $track_position->setEqLogic_id($this->getId());
        $track_position->save();

        $play_playlist = $this->getCmd(null, 'play_playlist');
        if (!is_object($play_playlist)) {
            $play_playlist = new MusicCastCmd();
            $play_playlist->setLogicalId('play_playlist');
            $play_playlist->setName(__('Jouer playlist', __FILE__));
        }
        $play_playlist->setType('action');
        $play_playlist->setSubType('select');
        $play_playlist->setDisplay('title_placeholder', __('Titre de la playlist', __FILE__));
        $play_playlist->setEqLogic_id($this->getId());
        $play_playlist->save();

        $play_favorite = $this->getCmd(null, 'play_favorite');
        if (!is_object($play_favorite)) {
            $play_favorite = new MusicCastCmd();
            $play_favorite->setLogicalId('play_favorite');
            $play_favorite->setName(__('Jouer un favoris', __FILE__));
        }
        $play_favorite->setType('action');
        $play_favorite->setSubType('select');
        $play_favorite->setDisplay('title_placeholder', __('Titre du favoris', __FILE__));
        $play_favorite->setEqLogic_id($this->getId());
        $play_favorite->save();

        $add_speaker = $this->getCmd(null, 'add_speaker');
        if (!is_object($add_speaker)) {
            $add_speaker = new MusicCastCmd();
            $add_speaker->setLogicalId('add_speaker');
            $add_speaker->setName(__('Ajout un haut parleur', __FILE__));
        }
        $add_speaker->setType('action');
        $add_speaker->setSubType('message');
        $add_speaker->setDisplay('message_disable', 1);
        $add_speaker->setDisplay('title_placeholder', __('Nom du haut parleur', __FILE__));
        $add_speaker->setEqLogic_id($this->getId());
        $add_speaker->save();

        $remove_speaker = $this->getCmd(null, 'remove_speaker');
        if (!is_object($remove_speaker)) {
            $remove_speaker = new MusicCastCmd();
            $remove_speaker->setLogicalId('remove_speaker');
            $remove_speaker->setName(__('Supprimer un haut parleur', __FILE__));
        }
        $remove_speaker->setType('action');
        $remove_speaker->setSubType('message');
        $remove_speaker->setDisplay('message_disable', 1);
        $remove_speaker->setDisplay('title_placeholder', __('Nom du haut parleur', __FILE__));
        $remove_speaker->setEqLogic_id($this->getId());
        $remove_speaker->save();

        $remove_allspeaker = $this->getCmd(null, 'remove_allspeaker');
        if (!is_object($remove_allspeaker)) {
            $remove_allspeaker = new MusicCastCmd();
            $remove_allspeaker->setLogicalId('remove_allspeaker');
            $remove_allspeaker->setName(__('Supprimer les hauts parleurs', __FILE__));
        }
        $remove_allspeaker->setType('action');
        $remove_allspeaker->setSubType('other');
        $remove_allspeaker->setEqLogic_id($this->getId());
        $remove_allspeaker->save();

        $power_on = $this->getCmd(null, 'power_on');
        if (!is_object($power_on)) {
            $power_on = new MusicCastCmd();
            $power_on->setLogicalId('power_on');
            $power_on->setName(__('PowerOn', __FILE__));
        }
        $power_on->setType('action');
        $power_on->setSubType('other');
        $power_on->setGeneric_type('ENERGY_ON');
        $power_on->setEqLogic_id($this->getId());
        $power_on->save();

        $standby = $this->getCmd(null, 'standby');
        if (!is_object($standby)) {
            $standby = new MusicCastCmd();
            $standby->setLogicalId('standby');
            $standby->setName(__('Standby', __FILE__));
        }
        $standby->setType('action');
        $standby->setSubType('other');
        $standby->setGeneric_type('ENERGY_OFF');
        $standby->setEqLogic_id($this->getId());
        $standby->save();

        $power = $this->getCmd(null, 'power_state');
        if (!is_object($power)) {
            $power = new MusicCastCmd();
            $power->setLogicalId('power_state');
            $power->setName(__('On', __FILE__));
        }
        $power->setType('info');
        $power->setSubType('binary');
        $power->setGeneric_type('ENERGY_STATE');
        $power->setEqLogic_id($this->getId());
        $power->save();

        $coordinator = $this->getCmd(null, 'coordinator_state');
        if (!is_object($coordinator)) {
            $coordinator = new MusicCastCmd();
            $coordinator->setLogicalId('coordinator_state');
            $coordinator->setName(__('Coordinateur', __FILE__));
        }
        $coordinator->setType('info');
        $coordinator->setSubType('binary');
        $coordinator->setEqLogic_id($this->getId());
        $coordinator->save();

        $change_input = $this->getCmd(null, 'change_input');
        if (!is_object($change_input)) {
            $change_input = new MusicCastCmd();
            $change_input->setLogicalId('change_input');
            $change_input->setName(__('Change input', __FILE__));
        }
        $change_input->setType('action');
        $change_input->setSubType('select');
        $change_input->setEqLogic_id($this->getId());
        $change_input->save();

        $input = $this->getCmd(null, 'input');
        if (!is_object($input)) {
            $input = new MusicCastCmd();
            $input->setLogicalId('input');
            $input->setName(__('Input', __FILE__));
        }
        $input->setType('info');
        $input->setSubType('string');
        $input->setConfiguration('repeatEventManagement', 'never');
        $input->setEqLogic_id($this->getId());
        $input->save();

        //si équipement désactivé ne pas mettre jour ces infos
        //car si l’équipement n'est pas joignable cela prend beaucoup de temps pour rien
        if ($this->getIsEnable() != 0) {
            try {
                self::getFavorites($this->getLogicalId());
                self::getPlayLists($this->getLogicalId());
                self::getInputs($this->getLogicalId());
            } catch (Exception $e) {

            }
        }

        //relance daemon pour prise en compte changement
        self::deamon_start();

    }

    public
    function toHtml($_version = 'dashboard')
    {
        $replace = $this->preToHtml($_version, array('#background-color#' => '#4a89dc'));
        if (!is_array($replace)) {
            return $replace;
        }
        $version = jeedom::versionAlias($_version);
        $replace['#text_color#'] = $this->getConfiguration('text_color');
        $replace['#version#'] = $_version;
        $cmd_state = $this->getCmd(null, 'state');
        if (is_object($cmd_state)) {
            $replace['#state#'] = $cmd_state->execCmd();
            if ($replace['#state#'] == __('Lecture', __FILE__)) {
                $replace['#state_nb#'] = 1;
            } else {
                $replace['#state_nb#'] = 0;
            }
        }

        foreach ($this->getCmd('action') as $cmd) {
            $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
            if ($_version != 'mobile' && $_version != 'mview' && $cmd->getLogicalId() == 'play_playlist') {
                $replace['#playlist#'] = str_replace(array("'", '+'), array("\'", '\+'), $cmd->getDisplay('title_possibility_list'));
            }
            if ($_version != 'mobile' && $_version != 'mview' && $cmd->getLogicalId() == 'play_favorite') {
                $replace['#favorite#'] = str_replace(array("'", '+'), array("\'", '\+'), $cmd->getDisplay('title_possibility_list'));
            }
        }

        $cmd_power_state = $this->getCmd(null, 'power_state');
        $cmd_standby = $this->getCmd(null, 'standby');
        if (is_object($cmd_power_state) && is_object($cmd_standby)) {
            $power = $cmd_power_state->execCmd();
            if ($power) {
                $replace['#power_on_id#'] = $cmd_standby->getId();
            }
        }

        foreach ($this->getCmd('info') as $cmd) {
            $replace['#' . $cmd->getLogicalId() . '_id#'] = $cmd->getId();
            $replace['#' . $cmd->getLogicalId() . '#'] = $cmd->execCmd();
        }


        if (is_object($cmd_state)) {
            $replace['#state#'] = $cmd_state->execCmd();
            if ($replace['#state#'] == __('Aucun', __FILE__)) {
                if ($this->getConfiguration('strControl', 0) == 0)
                    $replace['#state#'] = '';
                else
                    $replace['#state#'] = ' - ' . __('Inconnu', __FILE__);
            } else {
                $replace['#state#'] = ' - ' . $replace['#state#'];
            }
        }

        if ($replace['#mute_state#'] == 1) {
            $replace['#mute_id#'] = $replace['#unmute_id#'];
        }

        $cmd_track_artist = $this->getCmd(null, 'track_artist');
        if (is_object($cmd_track_artist)) {
            $subtitle = $cmd_track_artist->execCmd();
            if ($subtitle != __('Aucun', __FILE__))
                $replace['#subtitle#'] = $cmd_track_artist->execCmd();
        }

        $cmd_track_album = $this->getCmd(null, 'track_album');
        if (is_object($cmd_track_album)) {
            $subtitle = $cmd_track_album->execCmd();
            if ($subtitle != __('Aucun', __FILE__))
                $replace['#subtitle#'] .= ' - ' . $cmd_track_album->execCmd();
        }
        $replace['#subtitle#'] = trim(trim(trim($replace['#subtitle#']), ' - '));

        $cmd_track_title = $this->getCmd(null, 'track_title');
        if (is_object($cmd_track_title)) {
            $subtitle = $cmd_track_title->execCmd();
            if ($subtitle != __('Aucun', __FILE__))
                $replace['#title#'] = $cmd_track_title->execCmd();
        }
        $replace['#title#'] = trim(trim(trim($replace['#title#']), '-'));

        if (strlen($replace['#title#']) > 34) {
            $replace['#title#'] = '<marquee behavior="scroll" direction="left" scrollamount="4">' . $replace['#title#'] . '</marquee>';
        }

        $cmd_group = $this->getCmd(null, 'group');
        if (is_object($cmd_group)) {
            $subtitle = $cmd_group->execCmd();
            if ($subtitle != __('Aucun', __FILE__))
                $replace['#group#'] = $cmd_group->execCmd();
        }
        $replace['#group#'] = trim(trim(trim($replace['#group#']), '-'));

        $cmd_track_input = $this->getCmd(null, 'input');
        if (is_object($cmd_track_input)) {
            $subtitle = $cmd_track_input->execCmd();
            if ($subtitle != __('Aucun', __FILE__))
                $replace['#input#'] = $cmd_track_input->execCmd();
        }


        if ($_version != 'mobile' && $_version != 'mview') {
            $replace['#speakers#'] = str_replace(array("'", '+'), array("\'", '\+'), $this->getConfiguration('speakers'));
        }

        $cmd_track_image = $this->getCmd(null, 'track_image');
        if (is_object($cmd_track_image)) {
            $img = dirname(__FILE__) . '/../../../../plugins/musiccast/musiccast_' . $this->getId() . '.jpg';
            if (file_exists($img) && filesize($img) > 500) {
                $replace['#thumbnail#'] = 'plugins/musiccast/musiccast_' . $this->getId() . '.jpg?' . md5($cmd_track_image->execCmd());
            } else {
                $replace['#thumbnail#'] = 'plugins/musiccast/plugin_info/musiccast_alt_icon.png';
            }
        }

        return $this->postToHtml($_version, template_replace($replace, getTemplate('core', $version, 'eqLogic', 'musiccast')));
    }


    public
    function getImage()
    {
        return 'plugins/musiccast/core/img/' . $this->getConfiguration('model') . '.jpg';
    }

    /*     * **********************Getteur Setteur*************************** */
}

class MusicCastCmd extends cmd
{
    /*     * *************************Attributs****************************** */

    public static $_widgetPossibility = array('custom' => false);

    /*     * ***********************Methode static*************************** */

    /*     * *********************Methode d'instance************************* */
    /*
        public function imperihomeGenerate($ISSStructure)
        {
            $eqLogic = $this->getEqLogic();
            $object = $eqLogic->getObject();
            $type = 'DevPlayer';
            $info_device = array(
                'id' => $this->getId(),
                'name' => $eqLogic->getName(),
                'room' => (is_object($object)) ? $object->getId() : 99999,
                'type' => $type,
                'params' => array(),
            );
            $info_device['params'] = $ISSStructure[$info_device['type']]['params'];
            $info_device['params'][0]['value'] = '#' . $eqLogic->getCmd('info', 'state')->getId() . '#';
            $info_device['params'][1]['value'] = '#' . $eqLogic->getCmd('info', 'volume')->getId() . '#';
            $info_device['params'][2]['value'] = '#' . $eqLogic->getCmd('info', 'mute_state')->getId() . '#';
            $info_device['params'][3]['value'] = '';
            $info_device['params'][4]['value'] = '';
            $info_device['params'][5]['value'] = '#' . $eqLogic->getCmd('info', 'track_title')->getId() . '#';
            $info_device['params'][6]['value'] = '#' . $eqLogic->getCmd('info', 'track_album')->getId() . '#';
            $info_device['params'][7]['value'] = '#' . $eqLogic->getCmd('info', 'track_artist')->getId() . '#';
            $info_device['params'][8]['value'] = network::getNetworkAccess('external') . '/plugins/musiccast/musiccast_' . $eqLogic->getId() . '.jpg';
            return $info_device;
        }


            public function imperihomeAction($_action, $_value)
            {
                $eqLogic = $this->getEqLogic();
                switch ($_action) {
                    case 'setvolume':
                        $eqLogic->getCmd('action', 'setVolume')->execCmd(array('slider' => $_value));
                        break;
                    case 'play':
                        $eqLogic->getCmd('action', 'play')->execCmd();
                        break;
                    case 'pause':
                        $eqLogic->getCmd('action', 'pause')->execCmd();
                        break;
                    case 'next':
                        $eqLogic->getCmd('action', 'next')->execCmd();
                        break;
                    case 'previous':
                        $eqLogic->getCmd('action', 'previous')->execCmd();
                        break;
                    case 'stop':
                        $eqLogic->getCmd('action', 'stop')->execCmd();
                        break;
                    case 'power_on':
                        $eqLogic->getCmd('action', 'power_on')->execCmd();
                        break;
                    case 'mute':
                        if ($eqLogic->getCmd('info', 'mute_state')->execCmd() == 1) {
                            $eqLogic->getCmd('action', 'unmute')->execCmd();
                        } else {
                            $eqLogic->getCmd('action', 'mute')->execCmd();
                        }
                        break;
                }
                return;
            }

            public function imperihomeCmd()
            {
                return ($this->getLogicalId() == 'state');
            }
        */
    public function execute($_options = array())
    {
        if ($this->getType() == 'info') {
            return;
        }
        $eqLogic = $this->getEqLogic();
        $musicCast = musiccast::getMusicCast();
        $controller = $eqLogic->getController();
        if ($controller != null) {
            if (!is_object($controller)) {
                throw new Exception(__('Impossible de récuperer le musiccast : ', __FILE__) . $eqLogic->getHumanName());
            }
            log::add("musiccast", "debug", "Action on controller " . $controller->getName());
            log::add("musiccast", "debug", "Action is " . $this->getLogicalId() . " with parameter :" . print_r($_options, true));
            if ($this->getLogicalId() == 'play') {
                $state = $eqLogic->getCmd(null, 'state');
                $track_title = $eqLogic->getCmd(null, 'track_title');
                if (is_object($state) && is_object($track_title) && $track_title->execCmd() == '' && $state->execCmd() == __('Lecture', __FILE__)) {
                    return $controller->unmute();
                }
                try {
                    $controller->play();
                } catch (Exception $e) {

                }
            } elseif ($this->getLogicalId() == 'stop') {
                $state = $eqLogic->getCmd(null, 'state');
                $track_title = $eqLogic->getCmd(null, 'track_title');
                if ($track_title->execCmd() == __('Aucun', __FILE__) && $state->execCmd() == __('Arrêté', __FILE__)) {
                    return;
                }
                if ($track_title->execCmd() == __('Aucun', __FILE__) && $state->execCmd() == __('Lecture', __FILE__)) {
                    return $controller->mute();
                }
                try {
                    $controller->stop();
                } catch (Exception $e) {

                }
            } elseif ($this->getLogicalId() == 'pause') {
                $state = $eqLogic->getCmd(null, 'state');
                $track_title = $eqLogic->getCmd(null, 'track_title');
                if ($track_title->execCmd() == __('Aucun', __FILE__) && $state->execCmd() == __('Arrêté', __FILE__)) {
                    return;
                }
                if ($track_title->execCmd() == __('Aucun', __FILE__) && $state->execCmd() == __('Lecture', __FILE__)) {
                    return $controller->mute();
                }
                try {
                    $controller->pause();
                } catch (Exception $e) {

                }
            } elseif ($this->getLogicalId() == 'previous') {
                $controller->previous();
            } elseif ($this->getLogicalId() == 'next') {
                $controller->next();
            } elseif ($this->getLogicalId() == 'mute') {
                $controller->mute();
            } elseif ($this->getLogicalId() == 'unmute') {
                $controller->unmute();
            } elseif ($this->getLogicalId() == 'repeat') {
                $controller->toggleRepeat();
            } elseif ($this->getLogicalId() == 'shuffle') {
                $controller->toggleShuffle();
            } elseif ($this->getLogicalId() == 'setVolume') {
                if ($_options['slider'] < 0) {
                    $_options['slider'] = 0;
                } else if ($_options['slider'] > 100) {
                    $_options['slider'] = 100;
                }
                $controller->setVolume($_options['slider']);
            } elseif ($this->getLogicalId() == 'play_playlist') {
                $playlist = $controller->getPlaylistByName($_options['title']);
                if ($playlist == null) {
                    $playlist = $controller->getPlaylistByName($_options['select']);
                }
                if ($playlist == null) {
                    throw new Exception(__('Playlist non trouvée : ', __FILE__));
                }
                $playlist->play();
            } elseif ($this->getLogicalId() == 'play_favorite') {
                $favorite = $controller->getFavoriteByName($_options['title']);
                if ($favorite == null) {
                    $favorite = $controller->getFavoriteByName($_options['select']);
                }
                if ($favorite == null) {
                    throw new Exception(__('Favori non trouvée', __FILE__));
                }
                $favorite->play();
            } elseif ($this->getLogicalId() == 'add_speaker') {
                $speaker = $musicCast->getSpeakerByName($_options['title']);
                $controller->addSpeaker($speaker);
            } elseif ($this->getLogicalId() == 'remove_speaker') {
                $speaker = $musicCast->getSpeakerByName($_options['title']);
                $controller->removeSpeaker($speaker);
            } elseif ($this->getLogicalId() == 'remove_allspeaker') {
                $controller->removeAllSpeakers();
            } elseif ($this->getLogicalId() == 'power_on') {
                $controller->powerOn();
            } elseif ($this->getLogicalId() == 'standby') {
                $controller->standBy();
            } elseif ($this->getLogicalId() == 'change_input') {
                $controller->setInput($_options['select']);
            } else {
                musiccast::pull($eqLogic->getId());
            }
        } else {
            $speaker = $eqLogic->getSpeaker();
            if ($speaker != null) {
                if ($this->getLogicalId() == 'power_on') {
                    $speaker->powerOn();
                } elseif ($this->getLogicalId() == 'standby') {
                    $speaker->standBy();
                } elseif ($this->getLogicalId() == 'setVolume') {
                    if ($_options['slider'] < 0) {
                        $_options['slider'] = 0;
                    } else if ($_options['slider'] > 100) {
                        $_options['slider'] = 100;
                    }
                    $controller->setVolume($_options['slider']);
                } else {
                    musiccast::pull($eqLogic->getId());
                }
            }
        }
    }


    /*     * **********************Getteur Setteur*************************** */
}
