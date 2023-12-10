<?php

namespace Drupal\tsa_portal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Database\Database;


class MarkAttendanceForm extends FormBase {

    protected $entityTypeManager;

    public function getFormId() {
        return 'mark_attendance_form';
    }

    public function buildForm(array $form, FormStateInterface $form_state) {

        $form['#method'] = 'post';

        $form['class_select'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Class'),
        '#options' => $this->getClassOptions(),
        '#ajax' => [
            'callback' => '::updateStudentTable',
            'wrapper' => 'student-table',
        ],
        ];

        $date_time = date('H');
        if (date('H') < 12) {
            $date_time = 49;
        }
        else {
            $date_time = 50;
        }

        $form['period_select'] = [
        '#type' => 'select',
        '#title' => $this->t('Select Period'),
        '#options' => $this->getAttendanceOptions(),
        '#default_value' => $date_time,
        ];

        $form['student_table'] = [
        '#type' => 'tableselect',
        '#header' => [
            'student_id' => $this->t('Student ID'),
            'student_name' =>$this->t('Student Name'),
            'student_level' =>$this->t('Student Level'),
            // $this->t('Present'),
        ],
        '#options' => [],
        '#empty' => $this->t('No students found'),
        '#attributes' => [
            'id' => 'student-table',
        ],
        '#prefix' => '<div id="student-table">',
        '#suffix' => '</div>',
        ];

        $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Mark Attendance'),
        ];

        $form['student_table'] = $this->updateStudentTable($form, $form_state);


        return $form;
    }


    protected function getClassOptions() {
        // Return an array of class or grade options, i.e. basic 1 or pre-k.
        $query = database::getConnection()->select('taxonomy_term_field_data', 'tid');
        $query->fields('tid', ['tid','name']);
        $query->condition('status', '1');
        $query->condition('vid', 'level');
        
        $results = $query->execute()->fetchAll();

        $options = array();
        $options[''] = $this->t('- Select -');
        foreach ($results as $result) {
            $options[$result->tid] = $result->name;
        }
        return $options;
    }

    protected function getAttendanceOptions() {
        // Return an array of attendance options, i.e. morning or afternoon.
        $query = database::getConnection()->select('taxonomy_term_field_data', 'tid');
        $query->fields('tid', ['tid','name']);
        $query->condition('status', '1');
        $query->condition('vid', 'attendance_period');

        $results = $query->execute()->fetchAll();

        $options = array();
        foreach ($results as $result) {
            $options[$result->tid] = $result->name;
        }
        return $options;
    }
    

    public function updateStudentTable(array $form, FormStateInterface $form_state) {
        // Update the student table based on the selected class.

        $selected_class = $form_state->getValue('class_select');

        $query = database::getConnection()->select('node_field_data', 'nfd');
        $query->fields('nfd', ['nid', 'title']);
        $query->addField('nfsi', 'field_student_id_value', 'student_id');
        $query->condition('nfd.type', 'student');
        $query->leftJoin('node__field_student_id', 'nfsi', 'nfsi.entity_id = nfd.nid');
        $query->innerJoin('node__field_current_grade', 'nfcg', 'nfcg.entity_id = nfd.nid');
        $query->leftJoin('taxonomy_term_field_data', 'ttfd', 'ttfd.tid = nfcg.field_current_grade_target_id');
        $query->addField('ttfd', 'name', 'student_level');
        $query->condition('nfcg.field_current_grade_target_id', $selected_class);
        $query->condition('nfd.status', '1');
        $query->orderBy('nfsi.field_student_id_value', 'ASC');
        $results = $query->execute()->fetchAll();

        //\Drupal::logger('results')->notice('<pre><code>' . print_r($results, TRUE) . '</code></pre>');

        $data = [];
        foreach ($results as $result) {

            $data[$result->nid] = [
                'student_id' => ['#plain_text' => $result->student_id],
                'student_name' => ['#plain_text' => $result->title],
                'student_level' => ['#plain_text' => $result->student_level],
            ];
        }


        $form['student_table']['#options'] = $data;

        return $form['student_table'];
    }

    
    public function submitForm(array &$form, FormStateInterface $form_state) 
    {

        // Consider how to mark students as absent??
        
        // Submit the form.
        $selected_class = $form_state->getValue('class_select');
        $selected_period = $form_state->getValue('period_select');
        $selected_students = $form_state->getValue('student_table');
        

        //$selected_students = array_filter($selected_students);  // Do not filter the array, we also need the empty values.
        $date = date('Y-m-d');

        
        $user = \Drupal::currentUser();

        // \Drupal::logger('selected_students')->notice('<pre><code>' . print_r($selected_students, TRUE) . '</code></pre>');
        // \Drupal::logger('selected_class')->notice('<pre><code>' . print_r($selected_class, TRUE) . '</code></pre>');
        // \Drupal::logger('selected_period')->notice('<pre><code>' . print_r($selected_period, TRUE) . '</code></pre>');
        // \Drupal::logger('date')->notice('<pre><code>' . print_r($date, TRUE) . '</code></pre>');
        // \Drupal::logger('time')->notice('<pre><code>' . print_r($time, TRUE) . '</code></pre>');
        // \Drupal::logger('user')->notice('<pre><code>' . print_r($user, TRUE) . '</code></pre>');

            foreach ($selected_students as $nid => $status) {
                // Get the student data from the database.
                $query = \Drupal::database()->select('node_field_data', 'nfd');
                $query->fields('nfd', ['nid', 'title']);
                $query->addField('nfsi', 'field_student_id_value', 'student_id');
                $query->addField('nfcg', 'field_current_grade_target_id', 'student_level_tid');
                $query->condition('nfd.status', '1');
                $query->condition('nfd.type', 'student');
                $query->leftJoin('node__field_student_id', 'nfsi', 'nfsi.entity_id = nfd.nid');
                $query->leftJoin('node__field_current_grade', 'nfcg', 'nfcg.entity_id = nfd.nid');
                $query->condition('nfd.nid', $nid);
                $result = $query->execute()->fetchObject();

                if (isset($result->nid)) {
                    $student_id = $result->student_id;
                    $student_name = $result->title;
                    $student_level = $result->student_level_tid;
                }

                $query = database::getConnection()->select('node_field_data', 'nfd');
                $query->fields('nfd', ['nid']);
                $query->addField('nfsi', 'field_student_id_value', 'student_id');
                $query->addField('nftap', 'field_attendance_period_target_id', 'attendance_period');
                $query->addField('nfsn', 'field_student_name_target_id', 'student_nid');  
                $query->condition('nfd.type', 'attendance');
                $query->leftJoin('node__field_student_id', 'nfsi', 'nfsi.entity_id = nfd.nid');
                $query->leftJoin('node__field_attendance_period', 'nftap', 'nftap.entity_id = nfd.nid');
                $query->leftJoin('node__field_student_name', 'nfsn', 'nfsn.entity_id = nfd.nid');  
                $query->condition('nfsi.field_student_id_value', $student_id);
                $query->condition('nfd.status', '1');
                $query->condition('nfd.created', strtotime($date), '>=');
                $query->condition('nfd.created', strtotime($date) + 86400, '<');
                $query->condition('nfd.uid', $user->id());
                $query->condition('nftap.field_attendance_period_target_id', $selected_period);
                $results = $query->execute()->fetchAll();

                //\Drupal::logger('results')->notice('<pre><code>' . print_r($results, TRUE) . '</code></pre>');

                if ($status){
                // if checkbox is checked (student is present)
                    if (empty($results)) {
                        // create a new attendance record for the student if one does not exist.
                        $attendance = Node::create([
                            'type' => 'attendance',
                            'title' => $student_name . ' - Attendance Record for ' . $date,
                            'field_student_id' => $student_id,
                            'field_level' => $student_level,
                            'body' => "Student is present.",
                            'field_attendance_period' => $selected_period,
                            'field_attendance_date' => $date,
                            'field_attendance_present' => TRUE,
                            'field_marked_by' => $user->id(),
                            'field_student_name' => ['target_id' => $nid],
                        ]);
                        $attendance->save();
                        
                        //\Drupal::messenger()->addMessage('Attendance has been marked successfully.');
                    }
                    else {
                        // Update the existing attendance record to mark the student as present
                        $attendance = Node::load($results[0]->nid);
                        $attendance->set('field_attendance_present', TRUE);
                        $attendance->save();
                        // \Drupal::messenger()->addMessage('Attendance has been marked successfully.');
                    }
                } 
                else {
                    // if checkbox is not checked (student is absent)
                    if (empty($results)) {
                        // create a new attendance record to mark the student absent.
                        $attendance = Node::create([
                            'type' => 'attendance',
                            'title' => $student_name . ' - Attendance Record for ' . $date,
                            'field_student_id' => $student_id,
                            'field_level' => $student_level,
                            'body' => "Student is absent.",
                            'field_attendance_period' => $selected_period,
                            'field_attendance_date' => $date,
                            'field_attendance_present' => FALSE,
                            'field_marked_by' => $user->id(),
                            'field_student_name' => ['target_id' => $nid],
                        ]);
                        $attendance->save();

                        //\Drupal::messenger()->addMessage('Attendance has been marked successfully.');
                    }
                    else {
                        // Update the existing attendance record to mark the student as absent
                        $attendance = Node::load($results[0]->nid);
                        $attendance->set('field_attendance_present', FALSE);
                        $attendance->save();
                        //\Drupal::messenger()->addMessage('Attendance has been marked successfully.');
                    }

                }
                \Drupal::messenger()->addMessage('Attendance has been marked successfully for ' . $student_name . '.');
            }

    }

}