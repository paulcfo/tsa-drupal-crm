<?php

namespace Drupal\tsa_portal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;



/**
 * Class PortalController.
 * @package Drupal\tsa_portal\Controller
 */
class PortalController extends ControllerBase {

  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  public function portal() {
    $userId = \Drupal::currentUser()->id();
    $user = \Drupal\user\Entity\User::load($userId);
    $userName = $user->get('name')->value;
    $userEmail = $user->get('mail')->value;

    // \Drupal::logger('tsa_portal')->info('<pre>' . print_r($userId, TRUE) . '</pre>');
    // \Drupal::logger('tsa_portal')->info('<pre>' . print_r($userName, TRUE) . '</pre>');
    // \Drupal::logger('tsa_portal')->info('<pre>' . print_r($userEmail, TRUE) . '</pre>');

    return [
      '#theme' => 'portal_template',
      '#user_name' => $userName,
      '#user_email' => $userEmail,
      '#attached' => [
        'library' => [
          'tsa_portal/tsa_portal_libraries',
        ],
      ],
    ];
  }

 /**
  * {@inheritdoc}
  * @todo let's get the results for a class in an array
  * @param mixed $student_id
  * @param mixed $semester
  * @param mixed $result_type
  * @param mixed $selected_class
  * @return array
  */

  public function fetchResults($student_id, $semester, $result_type, $selected_class){

    $query = \Drupal::database()->select('node_field_data', 'nfd');

    $query->fields('nfd', ['created', 'nid', 'vid', 'title']);

    // Join a bunch of required tables
    $query->leftJoin('node__field_academic_term_semester_', 'semester', 'nfd.nid = semester.entity_id AND semester.deleted = 0');
    //$query->isNotNull('semester.field_academic_term_semester__target_id');

    $query->leftJoin('node__field_date_entered', 'date_entered', 'nfd.nid = date_entered.entity_id AND date_entered.deleted = 0');
    //$query->isNotNull('date_entered.field_date_entered_value');

    $query->leftJoin('node__field_level', 'level', 'nfd.nid = level.entity_id AND level.deleted = 0');
    //$query->isNotNull('level.field_level_target_id');

    $query->leftJoin('node__body', 'body', 'nfd.nid = body.entity_id AND body.deleted = 0');
    $query->fields('body', ['body_value']);

    $query->leftJoin('node__field_marked_by', 'marked_by', 'nfd.nid = marked_by.entity_id AND marked_by.deleted = 0');
    //$query->isNotNull('marked_by.field_marked_by_target_id');

    $query->leftJoin('node__field_result_type', 'result_type', 'nfd.nid = result_type.entity_id AND result_type.deleted = 0');
    //$query->isNotNull('result_type.field_result_type_target_id');

    $query->leftJoin('node__field_student_name', 'student_name', 'nfd.nid = student_name.entity_id AND student_name.deleted = 0');
    $query->condition('student_name.field_student_name_target_id', $student_id);

    // Joining the node's course grade with the paragraph's data.
    $query->leftJoin('node__field_course_grade', 'node_course_grade', 'nfd.nid = node_course_grade.entity_id AND node_course_grade.deleted = 0');
    $query->leftJoin('paragraphs_item_field_data', 'pifd', 'node_course_grade.field_course_grade_target_id = pifd.id');

    // Join with the paragraph's fields
    $query->leftJoin('paragraph__field_grade', 'field_grade', 'pifd.id = field_grade.entity_id');
    $query->fields('field_grade', ['field_grade_value']);

    $query->leftJoin('paragraph__field_grade_letter', 'field_grade_letter', 'pifd.id = field_grade_letter.entity_id');
    $query->fields('field_grade_letter', ['field_grade_letter_target_id']);

    $query->leftJoin('paragraph__field_course', 'field_course', 'pifd.id = field_course.entity_id');
    $query->fields('field_course', ['field_course_target_id']);

    // If you want to fetch the name (or other fields) from the taxonomy term associated with field_grade_letter
    $query->leftJoin('taxonomy_term_field_data', 'ttfd', 'field_grade_letter.field_grade_letter_target_id = ttfd.tid');
    $query->fields('ttfd', ['name']);

    // Conditions
    $query->condition('nfd.status', 1);
    $query->condition('nfd.type', 'student_grades');
    $query->condition('semester.field_academic_term_semester__target_id', $semester);
    $query->condition('result_type.field_result_type_target_id', $result_type);
    $query->condition('level.field_level_target_id', $student_id);

    $query->orderBy('nfd.created', 'DESC');

    $results = $query->execute()->fetchAll();

    return $results;
    \Drupal::logger('tsa_portal')->info('<pre>' . print_r($results, TRUE) . '</pre>');
  }



}
