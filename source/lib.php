<?php

/**
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the  specific functions, needed to implement all the module
 * logic, should go to locallib.php.
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Example constant, you probably want to remove this :-)
 */
define('TESTMODULE_ULTIMATE_ANSWER', 42);

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function testmodule_supports($feature) {

    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the testmodule into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $testmodule Submitted data from the form in mod_form.php
 * @param mod_testmodule_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted testmodule record
 */
function testmodule_add_instance(stdClass $testmodule, mod_testmodule_mod_form $mform = null) {
    global $DB;

    $testmodule->timecreated = time();

    // You may have to add extra stuff in here.

    $testmodule->id = $DB->insert_record('testmodule', $testmodule);

    testmodule_grade_item_update($testmodule);

    return $testmodule->id;
}

/**
 * Updates an instance of the testmodule in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $testmodule An object from the form in mod_form.php
 * @param mod_testmodule_mod_form $mform The form instance itself (if needed)
 * @return boolean Success/Fail
 */
function testmodule_update_instance(stdClass $testmodule, mod_testmodule_mod_form $mform = null) {
    global $DB;

    $testmodule->timemodified = time();
    $testmodule->id = $testmodule->instance;

    // You may have to add extra stuff in here.

    $result = $DB->update_record('testmodule', $testmodule);

    testmodule_grade_item_update($testmodule);

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every testmodule event in the site is checked, else
 * only testmodule events belonging to the course specified are checked.
 * This is only required if the module is generating calendar events.
 *
 * @param int $courseid Course ID
 * @return bool
 */
function testmodule_refresh_events($courseid = 0) {
    global $DB;

    if ($courseid == 0) {
        if (!$testmodules = $DB->get_records('testmodule')) {
            return true;
        }
    } else {
        if (!$testmodules = $DB->get_records('testmodule', array('course' => $courseid))) {
            return true;
        }
    }

    foreach ($testmodules as $testmodule) {
        // Create a function such as the one below to deal with updating calendar events.
        // testmodule_update_events($testmodule);
    }

    return true;
}

/**
 * Removes an instance of the testmodule from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function testmodule_delete_instance($id) {
    global $DB;

    if (!$testmodule = $DB->get_record('testmodule', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records here.

    $DB->delete_records('testmodule', array('id' => $testmodule->id));

    testmodule_grade_item_delete($testmodule);

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param cm_info|stdClass $mod The course module info object or record
 * @param stdClass $testmodule The testmodule instance record
 * @return stdClass|null
 */
function testmodule_user_outline($course, $user, $mod, $testmodule) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * It is supposed to echo directly without returning a value.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $testmodule the module instance record
 */
function testmodule_user_complete($course, $user, $mod, $testmodule) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in testmodule activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart Print activity since this timestamp
 * @return boolean True if anything was printed, otherwise false
 */
function testmodule_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link testmodule_print_recent_mod_activity()}.
 *
 * Returns void, it adds items into $activities and increases $index.
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 */
function testmodule_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid = 0, $groupid = 0) {
}

/**
 * Prints single activity item prepared by {@link testmodule_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 */
function testmodule_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * Note that this has been deprecated in favour of scheduled task API.
 *
 * @return boolean
 */
function testmodule_cron() {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function testmodule_get_extra_capabilities() {
    return array();
}

/* Gradebook API */

/**
 * Is a given scale used by the instance of testmodule?
 *
 * This function returns if a scale is being used by one testmodule
 * if it has support for grading and scales.
 *
 * @param int $testmoduleid ID of an instance of this module
 * @param int $scaleid ID of the scale
 * @return bool true if the scale is used by the given testmodule instance
 */
function testmodule_scale_used($testmoduleid, $scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('testmodule', array('id' => $testmoduleid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of testmodule.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale
 * @return boolean true if the scale is used by any testmodule instance
 */
function testmodule_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid and $DB->record_exists('testmodule', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given testmodule instance
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $testmodule instance object with extra cmidnumber and modname property
 * @param bool $reset reset grades in the gradebook
 * @return void
 */
function testmodule_grade_item_update(stdClass $testmodule, $reset = false) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($testmodule->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_VALUE;

    if ($testmodule->grade > 0) {
        $item['gradetype'] = GRADE_TYPE_VALUE;
        $item['grademax'] = $testmodule->grade;
        $item['grademin'] = 0;
    } else if ($testmodule->grade < 0) {
        $item['gradetype'] = GRADE_TYPE_SCALE;
        $item['scaleid'] = -$testmodule->grade;
    } else {
        $item['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($reset) {
        $item['reset'] = true;
    }

    grade_update('mod/testmodule', $testmodule->course, 'mod', 'testmodule',
        $testmodule->id, 0, null, $item);
}

/**
 * Delete grade item for given testmodule instance
 *
 * @param stdClass $testmodule instance object
 * @return grade_item
 */
function testmodule_grade_item_delete($testmodule) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/testmodule', $testmodule->course, 'mod', 'testmodule',
        $testmodule->id, 0, null, array('deleted' => 1));
}

/**
 * Update testmodule grades in the gradebook
 *
 * Needed by {@link grade_update_mod_grades()}.
 *
 * @param stdClass $testmodule instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 */
function testmodule_update_grades(stdClass $testmodule, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir . '/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();

    grade_update('mod/testmodule', $testmodule->course, 'mod', 'testmodule', $testmodule->id, 0, $grades);
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function testmodule_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for testmodule file areas
 *
 * @package mod_testmodule
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function testmodule_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the testmodule file areas
 *
 * @package mod_testmodule
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the testmodule's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function testmodule_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/* Navigation API */

/**
 * Extends the global navigation tree by adding testmodule nodes if there is a relevant content
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation tree node of the testmodule module instance
 * @param stdClass $course current course record
 * @param stdClass $module current testmodule instance record
 * @param cm_info $cm course module information
 */
function testmodule_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, cm_info $cm) {
    // TODO Delete this function and its docblock, or implement it.
}

/**
 * Extends the settings navigation with the testmodule settings
 *
 * This function is called when the context for the page is a testmodule module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav complete settings navigation tree
 * @param navigation_node $testmodulenode testmodule administration node
 */
function testmodule_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $testmodulenode = null) {
    // TODO Delete this function and its docblock, or implement it.
}
