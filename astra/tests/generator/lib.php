<?php
defined('MOODLE_INTERNAL') || die();

class mod_astra_generator extends testing_module_generator {

    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        $defaultsettings = array(
            'name' => 'Round',
            'introformat' => FORMAT_HTML,
            'ordernum' => 1,
            'status' => mod_astra_exercise_round::STATUS_READY,
            'grade' => 0,
            'pointstopass' => 0,
            'openingtime' => time(),
            'closingtime' => time() + 3600 * 24 * 7,
            'latesbmsallowed' => 0,
            'latesbmsdl' => 0,
            'latesbmspenalty' => 0.5,
        );

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }
}
