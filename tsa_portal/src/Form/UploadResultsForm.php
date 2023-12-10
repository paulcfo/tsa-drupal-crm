<?php

namespace Drupal\tsa_portal\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Database;



class UploadResultsForm extends FormBase
{

  /**
   * {@inheritdoc}
   */
  public function getFormId()
  {
    return 'upload_results_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    // Return the current year and use that to determine the semester i.e. first, second or third term.
    $current_year = date('Y');
    $current_month = date('m');
    $current_semester = '';

    if ($current_month >= 1 && $current_month <= 4) {
      $current_semester = 'First Term';
    } elseif ($current_month >= 5 && $current_month <= 8) {
      $current_semester = 'Second Term';
    } elseif ($current_month >= 9 && $current_month <= 12) {
      $current_semester = 'Third Term';
    }

    $form['#method'] = 'post';

    $form['#prefix'] = '<div class="page-wrapper-ur">';
    $form['#suffix'] = '</div>';


    // ------- Sidebar Area ---------
    $form['sidebar'] = [
      '#prefix' => '<div class="sidebar-ur" style="width:30%; display: inline-block; vertical-align: top;"><h3></h3>',
      '#suffix' => '</div>',
    ];

    $form['sidebar']['semester_dropdown'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Semester'),
      '#options' => $this->getSemesterOptions(),
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['radios-title-ur'],
      ],
    ];

    $form['sidebar']['result_type_dropdown'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select Result Type'),
      '#options' => $this->getResultTypeOptions(),
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['radios-title-ur'],
      ],
    ];

    $form['sidebar']['class_dropdown'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Class'),
      '#options' => $this->getClassOptions(),
      '#required' => TRUE,
      '#ajax' => [
        'callback' => '::updateStudentList',
        'wrapper' => 'student-list-wrapper',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Updating student list...'),
        ],
      ],
    ];

    $form['sidebar']['student_list_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'student-list-wrapper', 'width' => '350px'],
    ];

    // List of students in the class
    $form['sidebar']['student_list_wrapper']['student_list'] = [
      '#type' => 'select',
      '#options' => [],
      '#attributes' => [
        'multiple' => TRUE,
        'style' => 'height: 200px;',
        'id' => 'student-select-list',
      ],
    ];

    // ------- End of Sidebar Area ---------



    // ------- Content Area ---------

    $course = $this->getCourses();

    $form['content'] = [
      '#prefix' => '<div class="content-ur" style="width:70%; display: inline-block; vertical-align: top;">',
      '#suffix' => '</div>',
    ];

    // Add a row for student details and input fields for each course
    $form['content']['#markup'] = '<h6>Enter Results for ' . $current_semester . ' ' . $current_year . '</h6><table class="table-responsive courses-table"><thead><tr><th>Student Name / ID</th>';

    // Add course headers dynamically
    foreach ($course as $course_item) {
      $form['content']['#markup'] .= '<th>' . $this->t($course_item['title']) . '</th>';
    }
    $form['content']['#markup'] .= '</tr></thead><tbody>';

    // Student name
    $form['content']['student_name'] = [
      '#type' => 'markup',
      '#markup' => '<tr><td><div class="student-name" id="student-name" type="text"></div></td>',
    ];

    // Input fields for grades for each course
    foreach ($course as $course_item) {
      // Input textfield for numeric grades

      $numeric_grade_key = 'numeric_grade_' . $course_item['id'];
      $form['content'][$numeric_grade_key] = [
        '#type' => 'number',
        '#attributes' => ['type' => 'number'],
        '#prefix' => '<td>',
        '#suffix' => '',
        '#min' => '0',
        '#max' => '100',
        //'#required' => TRUE,
      ];

      // Input select dropdown for letter grades
      $letter_grade_key = 'letter_grade_' . $course_item['id'];
      $form['content'][$letter_grade_key] = [
        '#type' => 'select',
        '#options' => ['' => '', 31 => 'A', 32 => 'B', 51 => 'C', 52 => 'D', 53 => 'F'], // grade tids
        '#suffix' => '</td>',
        //'#required' => TRUE,
      ];
    }

    // Close the student row
    $form['content']['end_row'] = [
      '#type' => 'markup',
      '#markup' => '</tr>',
    ];

    // Close the table
    $form['content']['end_table'] = [
      '#type' => 'markup',
      '#markup' => '</tbody></table>',
    ];

    // Development and Skills table
    $form['content']['dev_skills'] = [
      '#type' => 'markup',
      '#markup' => '<div class="dev-skills"><h6>Development and Skills</h6>
            <table class="table-responsive courses-table">
                <thead>
                    <tr>
                        <th>Responsibility</th>
                        <th>Organization</th>
                        <th>Independent Work</th>
                        <th>Collaboration</th>
                        <th>Initiative</th>
                    </tr>
                </thead>'
    ];

    $resp_options = [
      ''  => '- Select -',
      'Excellent' => 'Excellent',
      'Good' => 'Good',
      'Satisfactory' => 'Satisfactory',
      'Needs Improvement' => 'Needs Improvement',
    ];


    $form['content']['resp'] = [
      '#type' => 'select',
      '#options' => $resp_options,
      '#prefix' => '<tbody><tr><td>',
      '#suffix' => '</td>',

    ];

    $form['content']['org'] = [
      '#type' => 'select',
      '#options' => $resp_options,
      '#prefix' => '<td>',
      '#suffix' => '</td>',

    ];

    $form['content']['ind'] = [
      '#type' => 'select',
      '#options' => $resp_options,
      '#prefix' => '<td>',
      '#suffix' => '</td>',

    ];

    $form['content']['coll'] = [
      '#type' => 'select',
      '#options' => $resp_options,
      '#prefix' => '<td>',
      '#suffix' => '</td>',

    ];

    $form['content']['init'] = [
      '#type' => 'select',
      '#options' => $resp_options,
      '#prefix' => '<td>',
      '#suffix' => '</td></tr></tbody></table></div>',

    ];


    // Textfield for teachers comments
    $form['content']['teachers_comments'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Teacher\'s Comments'),
      '#format' => 'plain_text',
      '#allowed_formats' => ['plain_text'],
      '#ckeditor' => [
        'height' => 500,
      ]
    ];

    // Submit button
    $form['content']['submit_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add Records'),
      '#attributes' => ['class' => ['submit-button-ur']],
      '#prefix' => '<div class="submit-button-container" style="text-align: right;">',
      '#suffix' => '</div>',
    ];

    // Update student list
    $form['sidebar']['student_list_wrapper'] = $this->updateStudentList($form, $form_state);

    return $form;
  }


  /**
   * {@inheritdoc}
   * submitForm() handles the submission of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {

    // Get the values from the form
    $selected_student = $form_state->getValue('student_list');
    $semester = $form_state->getValue('semester_dropdown');
    $resultType = $form_state->getValue('result_type_dropdown');
    $selectedClassLevel = $form_state->getValue('class_dropdown');
    $teachers_comments = $form_state->getValue('teachers_comments')['value'];
    $resp = $form_state->getValue('resp');
    $org = $form_state->getValue('org');
    $ind = $form_state->getValue('ind');
    $coll = $form_state->getValue('coll');
    $init = $form_state->getValue('init');

    // Get the name of the selected student
    $selected_student_node = \Drupal\node\Entity\Node::load($selected_student);
    if ($selected_student_node) {
        $student_name = $selected_student_node->getTitle();
    }

    $course = $this->getCourses(); // Get the list of courses
    foreach ($course as $course_item) {
      // Retrieve the numeric grade for each course
      $numeric_grade_key = ['numeric_grade_' . $course_item['id']];
      $numeric_grade = $form_state->getValue($numeric_grade_key);

      $letter_grade_key = ['letter_grade_' . $course_item['id']];
      $letter_grade = $form_state->getValue($letter_grade_key);

      // create paragraph objects for course grade
      $course_grade_paragraph = \Drupal\paragraphs\Entity\Paragraph::create([
        'type' => 'course_grade',
        'field_course' => $course_item['id'],
        'field_grade' => $numeric_grade, // % grade out of 100.
        'field_grade_letter' => $letter_grade,
      ]);
      $course_grade_paragraph->save();
      $course_grade_paragraphs[] = $course_grade_paragraph;
    }

    // create paragraph objects for development and skills
    $dev_paragraph = \Drupal\paragraphs\Entity\Paragraph::create([
      'type' => 'development_skills',
      'field_responsibility' => $resp,
      'field_organization' => $org,
      'field_independent_work' => $ind,
      'field_collaboration' => $coll,
      'field_initiative' => $init,
    ]);
    $dev_paragraph->save();

    // create a new node object -> student_grades
    $node = \Drupal::entityTypeManager()->getStorage('node')->create([
      // creating the static fields
      'type' => 'student_grades',
      'title' => 'Grades' . ' - ' . $student_name,
      'field_academic_term_semester_' => $semester,
      'field_result_type' => $resultType,
      'field_level' => $selectedClassLevel,
      'field_student_name' => $selected_student,
      'body' => $teachers_comments,
      'field_marked_by' => \Drupal::currentUser()->id(),
      'field_date_entered' => date('Y-m-d'),
      'field_development_skills' => [
        [
         'target_id' => $dev_paragraph->id(),
         'target_revision_id' => $dev_paragraph->getRevisionId()
        ],
      ]
    ]);

    // adding the paragraphs to the node
    foreach ($course_grade_paragraphs as $course_grade_paragraph) {
      $node->field_course_grade->appendItem([
        'target_id' => $course_grade_paragraph->id(),
        'target_revision_id' => $course_grade_paragraph->getRevisionId(),
      ]);
    }
    $saved = $node->save();

    if ($saved === FALSE) {
        \Drupal::messenger()->addMessage('Results could not been entered for ' . $student_name . '.');
    } else {
      \Drupal::messenger()->addMessage('Results have been entered for ' . $student_name . '.');
    }

  }


  protected function getSemesterOptions()
  {
    // Return an array of semester options, i.e. first term, second term or third term.
    $query = database::getConnection()->select('taxonomy_term_field_data', 'tid');
    $query->fields('tid', ['tid', 'name']);
    $query->condition('status', '1');
    $query->condition('vid', 'term_semester_');

    $results = $query->execute()->fetchAll();

    $options = array();
    foreach ($results as $result) {
      $options[$result->tid] = $result->name;
    }
    return $options;
  }

  protected function getClassOptions()
  {
    // Return an array of class or grade options, i.e. basic 1 or pre-k.
    $query = database::getConnection()->select('taxonomy_term_field_data', 'tid');
    $query->fields('tid', ['tid', 'name']);
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

  protected function getResultTypeOptions()
  {
    // Return an array of result type options, i.e. test, exam or assignment.
    $query = database::getConnection()->select('taxonomy_term_field_data', 'tid');
    $query->fields('tid', ['tid', 'name']);
    $query->condition('status', '1');
    $query->condition('vid', 'result_type');

    $results = $query->execute()->fetchAll();

    $options = array();
    foreach ($results as $result) {
      $options[$result->tid] = $result->name;
    }
    return $options;
  }

  protected function getClassName($tid)
  {

    // Return the name of the class based on the tid
    if (empty($tid)) {
      return '';
    }
    $term = \Drupal\taxonomy\Entity\Term::load($tid);


    return $term ? $term->getName() : '';
  }

  protected function getCourses()
  {

    $query = database::getConnection()->select('node_field_data', 'nfd');
    $query->fields('nfd', ['nid', 'title']);
    $query->condition('nfd.type', 'course');
    $query->condition('nfd.status', '1');
    $query->orderBy('nfd.title', 'ASC');
    $results = $query->execute()->fetchAll();

    $courses = array();
    foreach ($results as $result) {
      $courses[] = [
        'id' => $result->nid,
        'title' => $result->title,
      ];
    }

    return $courses;
  }


  public function updateStudentList(array $form, FormStateInterface $form_state)
  {
    // Update the student table based on the selected class.
    $selectedClass = $form_state->getValue('class_dropdown');
    $semester = $form_state->getValue('semester_dropdown');
    $resultType = $form_state->getValue('result_type_dropdown');

    // @todo we need to check if results exist for the student
    // in the selected semester and result type before adding them to the list.
    // If results exist, we should not add them to the list.

    $query = database::getConnection()->select('node_field_data', 'nfd');
    $query->fields('nfd', ['nid', 'title']);
    $query->addField('nfsi', 'field_student_id_value', 'student_id');
    $query->condition('nfd.type', 'student');
    $query->leftJoin('node__field_student_id', 'nfsi', 'nfsi.entity_id = nfd.nid');
    $query->innerJoin('node__field_current_grade', 'nfcg', 'nfcg.entity_id = nfd.nid');
    $query->leftJoin('taxonomy_term_field_data', 'ttfd', 'ttfd.tid = nfcg.field_current_grade_target_id');
    $query->addField('ttfd', 'name', 'student_level');
    $query->condition('nfcg.field_current_grade_target_id', $selectedClass);
    $query->condition('nfd.status', '1');
    $query->orderBy('nfsi.field_student_id_value', 'ASC');
    $results = $query->execute()->fetchAll();

    $students = [];
    foreach ($results as $result) {
      $students[$result->nid] = '(' . $result->student_id . ')' . ' ' . $result->title;
    }

    $form['sidebar']['student_list_wrapper']['student_list']['#options'] = $students ? $students : ['none' => 'No students in this class'];

    $form['sidebar']['student_list_wrapper']['student_list']['#title'] = 'Students Currently in ' . $this->getClassName($selectedClass);

    return $form['sidebar']['student_list_wrapper'];
  }


}
