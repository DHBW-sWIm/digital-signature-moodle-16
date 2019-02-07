<?php

/**
 * Defines backup_digitalsignature_activity_task class
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/digitalsignature/backup/moodle2/backup_digitalsignature_stepslib.php');

/**
 * Provides the steps to perform one complete backup of the digitalsignature instance
 *
 * @package   mod_digitalsignature
 * @category  backup
 * @copyright 2016 Your Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_digitalsignature_activity_task extends backup_activity_task {

    /**
     * No specific settings for this activity
     */
    protected function define_my_settings() {
    }

    /**
     * Defines a backup step to store the instance data in the digitalsignature.xml file
     */
    protected function define_my_steps() {
        $this->add_step(new backup_digitalsignature_activity_structure_step('digitalsignature_structure', 'digitalsignature.xml'));
    }

    /**
     * Encodes URLs to the index.php and view.php scripts
     *
     * @param string $content some HTML text that eventually contains URLs to the activity instance scripts
     * @return string the content with the URLs encoded
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of digitalsignatures.
        $search = '/(' . $base . '\/mod\/digitalsignature\/index.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@digitalsignatureINDEX*$2@$', $content);

        // Link to digitalsignature view by moduleid.
        $search = '/(' . $base . '\/mod\/digitalsignature\/view.php\?id\=)([0-9]+)/';
        $content = preg_replace($search, '$@digitalsignatureVIEWBYID*$2@$', $content);

        return $content;
    }
}
