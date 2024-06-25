{{-- This file is used for menu items by any Backpack v6 theme --}}

<li class="nav-item"><a class="nav-link" href="{{ backpack_url('dashboard') }}"><i class="la la-home nav-icon"></i> {{ trans('backpack::base.dashboard') }}</a></li>


<x-backpack::menu-item title="Accounts" icon="las la-list" :link="backpack_url('account')" />
<x-backpack::menu-item title="Customers" icon="las la-user-tie" :link="backpack_url('customer')" />
<x-backpack::menu-item title="Customer Credits" icon="las la-credit-card" :link="backpack_url('customer-credit')" />
<x-backpack::menu-item title="Planned Applications" icon="las la-business-time" :link="backpack_url('planned-application')" />


<x-backpack::menu-separator title="APP Settings" />
<x-backpack::menu-item title="Account Statuses" icon="las la-rss" :link="backpack_url('account-status')" />
<x-backpack::menu-item title="Locations" icon="las la-map-marked" :link="backpack_url('location')" />
<x-backpack::menu-item title="Subscriptions" icon="lab la-youtube" :link="backpack_url('subscription')" />
<x-backpack::menu-item title="One-Time Charges" icon="las la-money-bill-alt" :link="backpack_url('otc')" />
<x-backpack::menu-item title="Contract Periods" icon="las la-file-contract" :link="backpack_url('contract-period')" />
<x-backpack::menu-item title="Planned App. Types" icon="las la-briefcase" :link="backpack_url('planned-application-type')" />


<x-backpack::menu-dropdown title="Admin Only" icon="la la-puzzle-piece">
    <x-backpack::menu-dropdown-header title="Authentication" />
    <x-backpack::menu-dropdown-item title="Users" icon="la la-user" :link="backpack_url('user')" />
    <x-backpack::menu-dropdown-item title="Roles" icon="la la-group" :link="backpack_url('role')" />
    <x-backpack::menu-dropdown-item title="Permissions" icon="la la-key" :link="backpack_url('permission')" />
    <x-backpack::menu-dropdown-header title="Tools" />
    <x-backpack::menu-dropdown-item title='Backups' icon='la la-hdd-o' :link="backpack_url('backup')" />
    <x-backpack::menu-dropdown-item :title="trans('backpack::crud.file_manager')" icon="la la-files-o" :link="backpack_url('elfinder')" />
    <x-backpack::menu-dropdown-item title='Logs' icon='la la-terminal' :link="backpack_url('log')" />
    <x-backpack::menu-dropdown-item title="Activity Logs" icon="la la-stream" :link="backpack_url('activity-log')" />
</x-backpack::menu-dropdown>









