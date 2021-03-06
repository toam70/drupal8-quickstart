<?php

/**
 * @file
 * Definition of Drupal\system\Tests\Form\ElementsTableSelectTest.
 */

namespace Drupal\system\Tests\Form;

use Drupal\simpletest\WebTestBase;

/**
 * Test the tableselect form element for expected behavior.
 */
class ElementsTableSelectTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('form_test');

  public static function getInfo() {
    return array(
      'name' => 'Tableselect form element type test',
      'description' => 'Test the tableselect element for expected behavior',
      'group' => 'Form API',
    );
  }

  /**
   * Test the display of checkboxes when #multiple is TRUE.
   */
  function testMultipleTrue() {

    $this->drupalGet('form_test/tableselect/multiple-true');

    $this->assertNoText(t('Empty text.'), 'Empty text should not be displayed.');

    // Test for the presence of the Select all rows tableheader.
    $this->assertFieldByXPath('//th[@class="select-all"]', NULL, 'Presence of the "Select all" checkbox.');

    $rows = array('row1', 'row2', 'row3');
    foreach ($rows as $row) {
      $this->assertFieldByXPath('//input[@type="checkbox"]', $row, format_string('Checkbox for value @row.', array('@row' => $row)));
    }
  }

  /**
   * Test the display of radios when #multiple is FALSE.
   */
  function testMultipleFalse() {
    $this->drupalGet('form_test/tableselect/multiple-false');

    $this->assertNoText(t('Empty text.'), 'Empty text should not be displayed.');

    // Test for the absence of the Select all rows tableheader.
    $this->assertNoFieldByXPath('//th[@class="select-all"]', '', 'Absence of the "Select all" checkbox.');

    $rows = array('row1', 'row2', 'row3');
    foreach ($rows as $row) {
      $this->assertFieldByXPath('//input[@type="radio"]', $row, format_string('Radio button for value @row.', array('@row' => $row)));
    }
  }

  /**
   * Tests the display when #colspan is set.
   */
  function testTableselectColSpan() {
    $this->drupalGet('form_test/tableselect/colspan');

    $this->assertText(t('Three'), 'Presence of the third column');
    $this->assertNoText(t('Four'), 'Absence of a fourth column');

    // There should be three labeled column headers and 1 for the input.
    $table_head = $this->xpath('//thead');
    $this->assertEqual(count($table_head[0]->tr->th), 4, 'There are four column headers');

    $table_body = $this->xpath('//tbody');
    // The first two body rows should each have 5 table cells: One for the
    // radio, one cell in the first column, one cell in the the second column,
    // and two cells in the third column which has colspan 2.
    for ( $i = 0; $i <= 1; $i++) {
      $this->assertEqual(count($table_body[0]->tr[$i]->td), 5, format_string('There are five cells in row @row.', array('@row' => $i)));
    }
    // The third row should have 3 cells, one for the radio, one spanning the
    // first and second column, and a third in column 3 (which has colspan 3).
    $this->assertEqual(count($table_body[0]->tr[2]->td), 3, 'There are three cells in row 3.');
  }

  /**
   * Test the display of the #empty text when #options is an empty array.
   */
  function testEmptyText() {
    $this->drupalGet('form_test/tableselect/empty-text');
    $this->assertText(t('Empty text.'), 'Empty text should be displayed.');
  }

  /**
   * Test the submission of single and multiple values when #multiple is TRUE.
   */
  function testMultipleTrueSubmit() {

    // Test a submission with one checkbox checked.
    $edit = array();
    $edit['tableselect[row1]'] = TRUE;
    $this->drupalPostForm('form_test/tableselect/multiple-true', $edit, 'Submit');

    $this->assertText(t('Submitted: row1 = row1'), 'Checked checkbox row1');
    $this->assertText(t('Submitted: row2 = 0'), 'Unchecked checkbox row2.');
    $this->assertText(t('Submitted: row3 = 0'), 'Unchecked checkbox row3.');

    // Test a submission with multiple checkboxes checked.
    $edit['tableselect[row1]'] = TRUE;
    $edit['tableselect[row3]'] = TRUE;
    $this->drupalPostForm('form_test/tableselect/multiple-true', $edit, 'Submit');

    $this->assertText(t('Submitted: row1 = row1'), 'Checked checkbox row1.');
    $this->assertText(t('Submitted: row2 = 0'), 'Unchecked checkbox row2.');
    $this->assertText(t('Submitted: row3 = row3'), 'Checked checkbox row3.');

  }

  /**
   * Test submission of values when #multiple is FALSE.
   */
  function testMultipleFalseSubmit() {
    $edit['tableselect'] = 'row1';
    $this->drupalPostForm('form_test/tableselect/multiple-false', $edit, 'Submit');
    $this->assertText(t('Submitted: row1'), 'Selected radio button');
  }

  /**
   * Test the #js_select property.
   */
  function testAdvancedSelect() {
    // When #multiple = TRUE a Select all checkbox should be displayed by default.
    $this->drupalGet('form_test/tableselect/advanced-select/multiple-true-default');
    $this->assertFieldByXPath('//th[@class="select-all"]', NULL, 'Display a "Select all" checkbox by default when #multiple is TRUE.');

    // When #js_select is set to FALSE, a "Select all" checkbox should not be displayed.
    $this->drupalGet('form_test/tableselect/advanced-select/multiple-true-no-advanced-select');
    $this->assertNoFieldByXPath('//th[@class="select-all"]', NULL, 'Do not display a "Select all" checkbox when #js_select is FALSE.');

    // A "Select all" checkbox never makes sense when #multiple = FALSE, regardless of the value of #js_select.
    $this->drupalGet('form_test/tableselect/advanced-select/multiple-false-default');
    $this->assertNoFieldByXPath('//th[@class="select-all"]', NULL, 'Do not display a "Select all" checkbox when #multiple is FALSE.');

    $this->drupalGet('form_test/tableselect/advanced-select/multiple-false-advanced-select');
    $this->assertNoFieldByXPath('//th[@class="select-all"]', NULL, 'Do not display a "Select all" checkbox when #multiple is FALSE, even when #js_select is TRUE.');
  }


  /**
   * Test the whether the option checker gives an error on invalid tableselect values for checkboxes.
   */
  function testMultipleTrueOptionchecker() {

    list($header, $options) = _form_test_tableselect_get_data();

    $form['tableselect'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
    );

    // Test with a valid value.
    list($processed_form, $form_state, $errors) = $this->formSubmitHelper($form, array('tableselect' => array('row1' => 'row1')));
    $this->assertFalse(isset($errors['tableselect']), 'Option checker allows valid values for checkboxes.');

    // Test with an invalid value.
    list($processed_form, $form_state, $errors) = $this->formSubmitHelper($form, array('tableselect' => array('non_existing_value' => 'non_existing_value')));
    $this->assertTrue(isset($errors['tableselect']), 'Option checker disallows invalid values for checkboxes.');

  }


  /**
   * Test the whether the option checker gives an error on invalid tableselect values for radios.
   */
  function testMultipleFalseOptionchecker() {

    list($header, $options) = _form_test_tableselect_get_data();

    $form['tableselect'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#multiple' => FALSE,
    );

    // Test with a valid value.
    list($processed_form, $form_state, $errors) = $this->formSubmitHelper($form, array('tableselect' => 'row1'));
    $this->assertFalse(isset($errors['tableselect']), 'Option checker allows valid values for radio buttons.');

    // Test with an invalid value.
    list($processed_form, $form_state, $errors) = $this->formSubmitHelper($form, array('tableselect' => 'non_existing_value'));
    $this->assertTrue(isset($errors['tableselect']), 'Option checker disallows invalid values for radio buttons.');
  }


  /**
   * Helper function for the option check test to submit a form while collecting errors.
   *
   * @param $form_element
   *   A form element to test.
   * @param $edit
   *   An array containing post data.
   *
   * @return
   *   An array containing the processed form, the form_state and any errors.
   */
  private function formSubmitHelper($form, $edit) {
    $form_id = $this->randomName();
    $form_state = form_state_defaults();

    $form['op'] = array('#type' => 'submit', '#value' => t('Submit'));
    // The form token CSRF protection should not interfere with this test, so we
    // bypass it by setting the token to FALSE.
    $form['#token'] = FALSE;

    $form_state['input'] = $edit;
    $form_state['input']['form_id'] = $form_id;

    drupal_prepare_form($form_id, $form, $form_state);

    drupal_process_form($form_id, $form, $form_state);

    $errors = form_get_errors($form_state);

    // Clear errors and messages.
    drupal_get_messages();
    $form_state['errors'] = array();

    // Return the processed form together with form_state and errors
    // to allow the caller lowlevel access to the form.
    return array($form, $form_state, $errors);
  }
}
