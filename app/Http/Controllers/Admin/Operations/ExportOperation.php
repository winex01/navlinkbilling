<?php

namespace App\Http\Controllers\Admin\Operations;

use App\Exports\BillingExport;
use Illuminate\Support\Facades\Route;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

trait ExportOperation
{
    /**
     * Define which routes are needed for this operation.
     *
     * @param string $segment    Name of the current entity (singular). Used as first URL segment.
     * @param string $routeName  Prefix of the route name.
     * @param string $controller Name of the current CrudController.
     */
    protected function setupExportRoutes($segment, $routeName, $controller)
    {
        Route::get($segment.'/export', [
            'as'        => $routeName.'.export',
            'uses'      => $controller.'@export',
            'operation' => 'export',
        ]);
    }

    /**
     * Add the default settings, buttons, etc that this operation needs.
     */
    protected function setupExportDefaults()
    {
        CRUD::allowAccess('export');

        CRUD::operation('export', function () {
            CRUD::loadDefaultOperationSettingsFromConfig();
        });
    }

    /**
     * Show the view for performing the operation.
     *
     */
    public function export()
    {
        CRUD::hasAccessOrFail('export');

        // validate first
        if ($this->crud->hasAccess('filters')) {
            if (!$this->myFiltersValidation()) {
                return redirect()->back();
            }
        }

        return $this->exportClass();
    }

    // override this in controller to reuse
    protected function exportClass()
    {
        // default
        return (new BillingExport)->download('billings-'.carbonNow().'.xlsx');
    }
}