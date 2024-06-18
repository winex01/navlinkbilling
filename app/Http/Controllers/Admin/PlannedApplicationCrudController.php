<?php

namespace App\Http\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PlannedApplicationCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PlannedApplicationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\PlannedApplication::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/planned-application');
        CRUD::setEntityNameStrings('planned application', 'planned applications');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::setFromDb(); // set columns from db columns.

        $this->crud->removeColumns($this->removeFK());

        $this->crud->column([
            'name' => 'plannedApplicationType',
            'limit' => 100
        ])->before('mbps');

        $this->crud->column([
            'name' => 'location',
        ])->before('mbps');
    }

    protected function setupShowOperation()
    {
        $this->setupListOperation();
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {   
        CRUD::setValidation([
            'plannedApplicationType' => 'required|integer|min:1',
            'location' => 'required|integer|min:1',
            'mbps' => 'required|integer|min:1',
            'price' => ['required', 'numeric', 'regex:/^\d+(\.\d+)?$/'],
        ]);
        CRUD::setFromDb(); // set fields from db columns.
        
        $this->crud->removeField('planned_application_type_id');
        $this->crud->removeField('location_id');

        $this->crud->removeFields($this->removeFK());

        $this->crud->field('plannedApplicationType')->label('Planned Application Type')->before('mbps');
        $this->crud->field('location')->before('mbps');
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    private function removeFK()
    {
        return [
            'planned_application_type_id',
            'location_id',
        ];
    }
}
