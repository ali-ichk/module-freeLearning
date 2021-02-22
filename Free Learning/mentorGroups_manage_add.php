<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Domain\User\UserFieldGateway;
use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Forms\DatabaseFormFactory;

if (isActionAccessible($guid, $connection2, '/modules/Free Learning/mentorGroups_manage_add.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
    // Proceed!
    $page->breadcrumbs
        ->add(__m('Manage Mentor Groups'), 'mentorGroups_manage.php')
        ->add(__m('Add Mentor Group'));

    if (!empty($_GET['editID'])) {
        $page->return->setEditLink($_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Free Learning/mentorGroups_manage_edit.php&freeLearningMentorGroupID='.$_GET['editID']);
    }

    // Get a list of potential mentors
    $mentors = $container->get(UserGateway::class)->selectUserNamesByStatus('Full', 'Staff')->fetchAll();
    $mentors = Format::nameListArray($mentors, 'Staff', true, true);

    // Get a list of potential students (can include any user)
    $students = $container->get(UserGateway::class)->selectUserNamesByStatus('Full')->fetchAll();
    $students = array_reduce($students, function ($group, $person) {
        $group[$person['gibbonPersonID']] = Format::name($person['title'] ?? '', $person['preferredName'], $person['surname'], 'Student', true, true).' ('.$person['roleCategory'].', '.$person['username'].')';
        return $group;
    }, []);

    // Get the available custom fields for automatic assignment
    $fields = $container->get(UserFieldGateway::class)->selectBy(['active' => 'Y'], ['gibbonPersonFieldID', 'name'])->fetchKeyPair();
    
    $form = Form::create('mentorship', $gibbon->session->get('absoluteURL').'/modules/'.$gibbon->session->get('module').'/mentorGroups_manage_addProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    
    $form->addHiddenValue('address', $gibbon->session->get('address'));

    $row = $form->addRow();
        $row->addLabel('name', __m('Group Name'));
        $row->addTextField('name')->maxLength(90)->required();

    $col = $form->addRow()->addColumn();
        $col->addLabel('mentors', __('Mentors'));
        $select = $col->addMultiSelect('mentors');
        $select->source()->fromArray($mentors);

    $assignments = ['Manual' => __m('Manual'), 'Automatic' => __m('Automatic')];
    $row = $form->addRow();
        $row->addLabel('assignment', __m('Group Assignment'))->description(__m('Determines how students are added to this group.'));
        $row->addSelect('assignment')->fromArray($assignments)->required()->placeholder();

    $form->toggleVisibilityByClass('automatic')->onSelect('assignment')->when('Automatic');
    $row = $form->addRow()->addClass('automatic');
        $row->addLabel('gibbonPersonFieldID', __('Custom Field'));
        $row->addSelect('gibbonPersonFieldID')->fromArray($fields)->required()->placeholder();

    $row = $form->addRow()->addClass('automatic');
        $row->addLabel('fieldValue', __('Custom Field Value'));
        $row->addTextField('fieldValue')->maxLength(90)->required();

    $form->toggleVisibilityByClass('manual')->onSelect('assignment')->when('Manual');
    $col = $form->addRow()->addClass('manual')->addColumn();
        $col->addLabel('students', __('Students'));
        $select = $col->addMultiSelect('students');
        $select->source()->fromArray($students);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}